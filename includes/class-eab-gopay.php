<?php
/**
 * GoPay REST API v3 (card payments).
 */

if (!defined('ABSPATH')) {
    exit;
}

class EAB_GoPay {

    const NOTIFY_QUERY = 'eab_gopay_notify';

    public function __construct() {
        add_action('init', array($this, 'handle_notification'), 4);
        add_action('template_redirect', array($this, 'handle_return'), 5);
    }

    public static function is_enabled() {
        return (bool) get_option('eab_gopay_enabled', 0)
            && get_option('eab_gopay_client_id', '')
            && get_option('eab_gopay_client_secret', '')
            && get_option('eab_gopay_goid', '');
    }

    public static function gateway_url() {
        return get_option('eab_gopay_test_mode', 1)
            ? 'https://gw.sandbox.gopay.com/api'
            : 'https://gate.gopay.com/api';
    }

    /**
     * @return array{redirect:string}|array{error:string}
     */
    public function create_payment($order_id) {
        if (!self::is_enabled()) {
            return array('error' => __('GoPay není nakonfigurováno.', 'events-and-bookings'));
        }

        $order = EAB_Checkout::get_order($order_id);
        if (!$order) {
            return array('error' => __('Objednávka nenalezena.', 'events-and-bookings'));
        }

        $user = get_userdata($order->user_id);
        if (!$user) {
            return array('error' => __('Uživatel nenalezen.', 'events-and-bookings'));
        }

        $amount = (int) round((float) $order->total * 100);
        if ($amount < 1) {
            return array('error' => __('Neplatná částka.', 'events-and-bookings'));
        }

        $items = array();
        foreach ($order->items as $item) {
            $items[] = array(
                'name'        => $item->post_title,
                'amount'      => (int) round((float) $item->line_total * 100),
                'count'       => max(1, (int) $item->qty),
                'vat_rate'    => (int) get_option('eab_fakturoid_vat_rate', 21),
                'type'        => 'ITEM',
            );
        }

        $payload = array(
            'payer' => array(
                'contact' => array(
                    'email' => $user->user_email,
                    'first_name' => get_user_meta($user->ID, EAB_Auth::META_FIRST_NAME, true) ?: $user->first_name,
                    'last_name'  => get_user_meta($user->ID, EAB_Auth::META_LAST_NAME, true) ?: $user->last_name,
                ),
            ),
            'amount'   => $amount,
            'currency' => $order->currency ?: 'CZK',
            'order_number' => $order->order_number,
            'order_description' => sprintf(__('Rezervace %s', 'events-and-bookings'), $order->order_number),
            'items'    => $items,
            'target'   => array(
                'type' => 'ACCOUNT',
                'goid' => (int) get_option('eab_gopay_goid'),
            ),
            'callback' => array(
                'return_url'       => self::get_return_url($order_id),
                'notification_url' => self::get_notification_url(),
            ),
            'lang' => 'CS',
        );

        $response = $this->api_post('/payments/payment', $payload);

        if (is_wp_error($response)) {
            return array('error' => $response->get_error_message());
        }

        if (empty($response['id']) || empty($response['gw_url'])) {
            EAB_Payments::log('gopay_create_failed', 'GoPay create payment failed', array(
                'order_id' => $order_id,
                'response' => $response,
            ));
            return array('error' => __('GoPay platbu se nepodařilo vytvořit.', 'events-and-bookings'));
        }

        EAB_Checkout::update_order_status($order_id, 'processing', (string) $response['id']);

        return array('redirect' => $response['gw_url']);
    }

    /**
     * HTTP GET notification ?eab_gopay_notify=1&id=PAYMENT_ID
     */
    public function handle_notification() {
        if (empty($_GET[self::NOTIFY_QUERY]) || empty($_GET['id'])) {
            return;
        }

        $payment_id = sanitize_text_field(wp_unslash($_GET['id']));
        $this->sync_payment_status($payment_id);

        status_header(200);
        echo 'OK';
        exit;
    }

    /**
     * Customer return from GoPay gateway.
     */
    public function handle_return() {
        $page_ids = get_option('eab_page_ids', array());
        $success_id = isset($page_ids['payment_success']) ? (int) $page_ids['payment_success'] : 0;

        if (!$success_id || !is_page($success_id)) {
            return;
        }

        $payment_id = isset($_GET['id']) ? sanitize_text_field(wp_unslash($_GET['id'])) : '';
        if ($payment_id === '') {
            return;
        }

        $status = $this->get_payment_status($payment_id);
        $state  = is_array($status) && isset($status['state']) ? $status['state'] : '';

        $this->sync_payment_status($payment_id);

        if (in_array($state, array('CANCELED', 'TIMEOUTED', 'REFUSED'), true)) {
            $order = EAB_Checkout::get_order_by_transaction($payment_id);
            if ($order) {
                wp_safe_redirect(self::get_failed_url($order->id));
                exit;
            }
        }
    }

    public function sync_payment_status($payment_id) {
        $status = $this->get_payment_status($payment_id);
        if (!$status) {
            return false;
        }

        $order = EAB_Checkout::get_order_by_transaction($payment_id);
        if (!$order) {
            return false;
        }

        $state = isset($status['state']) ? $status['state'] : '';

        if ($state === 'PAID' || $state === 'AUTHORIZED') {
            EAB_Payments::complete_payment($order->id, $payment_id);
            return true;
        }

        if (in_array($state, array('CANCELED', 'TIMEOUTED', 'REFUSED'), true)) {
            EAB_Payments::fail_payment($order->id, $state);
        }

        return true;
    }

    public function get_payment_status($payment_id) {
        $response = $this->api_get('/payments/payment/' . rawurlencode($payment_id));
        if (is_wp_error($response)) {
            return null;
        }
        return $response;
    }

    public static function get_notification_url() {
        return add_query_arg(self::NOTIFY_QUERY, '1', home_url('/'));
    }

    public static function get_return_url($order_id) {
        $page_ids = get_option('eab_page_ids', array());
        $url = !empty($page_ids['payment_success']) ? get_permalink($page_ids['payment_success']) : home_url('/');
        return add_query_arg('order', (int) $order_id, $url);
    }

    public static function get_failed_url($order_id) {
        $page_ids = get_option('eab_page_ids', array());
        $url = !empty($page_ids['payment_failed']) ? get_permalink($page_ids['payment_failed']) : home_url('/');
        return add_query_arg('order', (int) $order_id, $url);
    }

    /**
     * Admin connectivity probe: OAuth + resolved callback URLs.
     *
     * @return array<string,mixed>
     */
    public static function run_connectivity_test() {
        $info = self::get_connectivity_info();

        if (!get_option('eab_gopay_client_id', '') || !get_option('eab_gopay_client_secret', '')) {
            $info['oauth'] = array(
                'ok'      => false,
                'message' => __('Vyplňte Client ID a Client Secret.', 'events-and-bookings'),
            );
            return $info;
        }

        $info['oauth'] = self::probe_oauth();
        return $info;
    }

    /**
     * @return array<string,mixed>
     */
    public static function get_connectivity_info() {
        $page_ids = get_option('eab_page_ids', array());

        return array(
            'api_url'          => self::gateway_url(),
            'test_mode'        => (bool) get_option('eab_gopay_test_mode', 1),
            'site_url'         => home_url('/'),
            'notification_url' => self::get_notification_url(),
            'return_url_sample' => self::get_return_url(1),
            'failed_url_sample' => self::get_failed_url(1),
            'payment_success_page' => self::get_page_status('payment_success', $page_ids),
            'payment_failed_page'  => self::get_page_status('payment_failed', $page_ids),
            'checkout_ready'       => self::is_enabled(),
            'credentials' => array(
                'enabled'       => (bool) get_option('eab_gopay_enabled', 0),
                'goid'          => (string) get_option('eab_gopay_goid', ''),
                'has_client_id' => (bool) get_option('eab_gopay_client_id', ''),
                'has_secret'    => (bool) get_option('eab_gopay_client_secret', ''),
            ),
            'docs' => 'docs/gopay-sandbox-testing.md',
        );
    }

    /**
     * @param array<string,int> $page_ids
     * @return array<string,mixed>
     */
    private static function get_page_status($key, $page_ids) {
        $id   = isset($page_ids[$key]) ? (int) $page_ids[$key] : 0;
        $post = $id ? get_post($id) : null;

        return array(
            'id'   => $id,
            'slug' => $post ? $post->post_name : '',
            'url'  => $id ? get_permalink($id) : '',
            'ok'   => $post && $post->post_status === 'publish',
        );
    }

    /**
     * @return array{ok:bool,message:string,expires_in?:int}
     */
    private static function probe_oauth() {
        $client_id     = get_option('eab_gopay_client_id', '');
        $client_secret = get_option('eab_gopay_client_secret', '');

        $response = wp_remote_post(self::gateway_url() . '/oauth2/token', array(
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($client_id . ':' . $client_secret),
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ),
            'body' => array(
                'grant_type' => 'client_credentials',
                'scope'      => 'payment-all',
            ),
        ));

        if (is_wp_error($response)) {
            return array(
                'ok'      => false,
                'message' => $response->get_error_message(),
            );
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code >= 200 && $code < 300 && !empty($body['access_token'])) {
            return array(
                'ok'         => true,
                'message'    => __('OAuth2 token získán.', 'events-and-bookings'),
                'expires_in' => isset($body['expires_in']) ? (int) $body['expires_in'] : null,
            );
        }

        $message = isset($body['errors'][0]['message'])
            ? $body['errors'][0]['message']
            : sprintf(__('OAuth selhalo (HTTP %d).', 'events-and-bookings'), $code);

        return array(
            'ok'      => false,
            'message' => $message,
        );
    }

    private function get_access_token() {
        $cached = get_transient('eab_gopay_access_token');
        if ($cached) {
            return $cached;
        }

        $client_id     = get_option('eab_gopay_client_id', '');
        $client_secret = get_option('eab_gopay_client_secret', '');

        $response = wp_remote_post(self::gateway_url() . '/oauth2/token', array(
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($client_id . ':' . $client_secret),
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ),
            'body' => array(
                'grant_type' => 'client_credentials',
                'scope'      => 'payment-all',
            ),
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($body['access_token'])) {
            return new WP_Error('gopay_auth', __('GoPay autorizace selhala.', 'events-and-bookings'));
        }

        $ttl = max(60, (int) ($body['expires_in'] ?? 1800) - 60);
        set_transient('eab_gopay_access_token', $body['access_token'], $ttl);

        return $body['access_token'];
    }

    private function api_get($path) {
        $token = $this->get_access_token();
        if (is_wp_error($token)) {
            return $token;
        }

        $response = wp_remote_get(self::gateway_url() . $path, array(
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'Accept'        => 'application/json',
            ),
        ));

        return $this->parse_response($response);
    }

    private function api_post($path, array $payload) {
        $token = $this->get_access_token();
        if (is_wp_error($token)) {
            return $token;
        }

        $response = wp_remote_post(self::gateway_url() . $path, array(
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ),
            'body' => wp_json_encode($payload),
        ));

        return $this->parse_response($response);
    }

    private function parse_response($response) {
        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code < 200 || $code >= 300) {
            $message = isset($body['errors'][0]['message']) ? $body['errors'][0]['message'] : __('GoPay API chyba.', 'events-and-bookings');
            return new WP_Error('gopay_api', $message, array('code' => $code, 'body' => $body));
        }

        return is_array($body) ? $body : array();
    }
}
