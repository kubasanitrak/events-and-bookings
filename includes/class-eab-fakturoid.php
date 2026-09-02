<?php
/**
 * Fakturoid API v3 — proforma at checkout, webhook invoice_paid.
 */

if (!defined('ABSPATH')) {
    exit;
}

class EAB_Fakturoid {

    const SUBDIR = 'events-and-bookings/invoices';
    const WEBHOOK_QUERY = 'eab_fakturoid_webhook';

    public function __construct() {
        add_action('init', array($this, 'maybe_handle_webhook'), 4);
    }

    public static function is_enabled() {
        return (bool) get_option('eab_fakturoid_enabled', 0)
            && get_option('eab_fakturoid_slug', '')
            && get_option('eab_fakturoid_client_id', '')
            && get_option('eab_fakturoid_client_secret', '');
    }

    public static function api_base() {
        $slug = self::get_account_slug();
        return 'https://app.fakturoid.cz/api/v3/accounts/' . $slug;
    }

    public static function get_account_slug() {
        $slug = (string) get_option('eab_fakturoid_slug', '');
        // Account slug from URL: app.fakturoid.cz/{slug}/…
        $slug = trim($slug, "/ \t\n\r\0\x0B");
        return preg_replace('/[^a-zA-Z0-9\-_]/', '', $slug);
    }

    public static function get_user_agent() {
        $ua = (string) get_option('eab_fakturoid_user_agent', 'Events and Bookings (kubasanitrak)');
        return $ua !== '' ? $ua : 'Events and Bookings (kubasanitrak)';
    }

    /**
     * Line VAT rate for new documents. Non-VAT Fakturoid accounts require 0.
     */
    public static function get_document_vat_rate() {
        $account = self::get_account_details();
        if (is_array($account) && isset($account['vat_mode']) && $account['vat_mode'] === 'non_vat_payer') {
            return 0;
        }

        return max(0, (int) get_option('eab_fakturoid_vat_rate', 0));
    }

    /**
     * Cached account detail (vat_mode, default rates, …).
     *
     * @return array|WP_Error|null
     */
    public static function get_account_details() {
        if (!self::is_enabled()) {
            return null;
        }

        $slug = self::get_account_slug();
        $cache_key = 'eab_fakturoid_account_' . md5($slug);
        $cached = get_transient($cache_key);
        if (is_array($cached)) {
            return $cached;
        }

        $account = self::api_request('GET', '/account.json');
        if (is_wp_error($account) || !is_array($account)) {
            return $account;
        }

        set_transient($cache_key, $account, HOUR_IN_SECONDS);
        return $account;
    }

    public static function get_webhook_url() {
        return add_query_arg(self::WEBHOOK_QUERY, '1', home_url('/'));
    }

    /**
     * Variable symbol for QR / bank details: Fakturoid first, else order digits.
     */
    public static function get_variable_symbol($order) {
        if (!empty($order->fakturoid_variable_symbol)) {
            return substr(preg_replace('/\D/', '', (string) $order->fakturoid_variable_symbol), 0, 10);
        }
        return substr(preg_replace('/\D/', '', (string) $order->order_number), 0, 10);
    }

    /**
     * Create proforma for an awaiting-payment order (idempotent).
     *
     * @return array|WP_Error
     */
    public static function create_proforma_for_order($order_id) {
        if (!self::is_enabled()) {
            return new WP_Error('fakturoid_disabled', 'Fakturoid disabled');
        }

        $order = EAB_Checkout::get_order($order_id);
        if (!$order) {
            return new WP_Error('invalid_order', 'Order missing');
        }

        if (!empty($order->fakturoid_invoice_id)) {
            return array(
                'invoice_id'       => $order->fakturoid_invoice_id,
                'number'           => $order->fakturoid_invoice_number,
                'variable_symbol'  => $order->fakturoid_variable_symbol,
                'pdf'              => $order->fakturoid_pdf,
            );
        }

        $subject_id = self::ensure_subject($order);
        if (is_wp_error($subject_id)) {
            EAB_Payments::log('fakturoid_subject_failed', $subject_id->get_error_message(), array('order_id' => $order_id));
            return $subject_id;
        }

        $vat_rate = self::get_document_vat_rate();

        $lines = array();
        foreach ($order->items as $item) {
            $lines[] = array(
                'name'       => $item->post_title,
                'quantity'   => max(1, (int) $item->qty),
                'unit_price' => (float) $item->unit_price,
                'vat_rate'   => $vat_rate,
            );
        }

        $payload = array(
            'subject_id'                 => $subject_id,
            'lines'                      => $lines,
            'document_type'              => 'proforma',
            // Keep a single document; do not auto-issue a second final invoice.
            'proforma_followup_document' => 'none',
            'custom_id'                  => $order->order_number,
            'order_number'               => $order->order_number,
            'note'                       => sprintf(__('Objednávka %s', 'events-and-bookings'), $order->order_number),
        );

        // Only set VAT mode when the account uses VAT (non-zero rate).
        if ($vat_rate > 0) {
            // Site prices are treated as amounts the customer pays (incl. VAT).
            $payload['vat_price_mode'] = 'from_total_with_vat';
        }

        $created = self::api_request('POST', '/invoices.json', $payload);
        if (is_wp_error($created)) {
            EAB_Payments::log('fakturoid_proforma_failed', $created->get_error_message(), array('order_id' => $order_id));
            return $created;
        }

        $invoice_id = isset($created['id']) ? (int) $created['id'] : 0;
        if (!$invoice_id) {
            return new WP_Error('fakturoid_create', __('Fakturoid nevrátil ID proformy.', 'events-and-bookings'));
        }

        $vs = isset($created['variable_symbol'])
            ? preg_replace('/\D/', '', (string) $created['variable_symbol'])
            : '';
        $number = isset($created['number']) ? (string) $created['number'] : '';
        $pdf_relative = self::download_invoice_pdf($invoice_id, $order->order_number);

        self::update_order_invoice_meta($order_id, array(
            'fakturoid_invoice_id'      => (string) $invoice_id,
            'fakturoid_invoice_number'  => $number,
            'fakturoid_variable_symbol' => $vs,
            'fakturoid_pdf'             => $pdf_relative,
        ));

        EAB_Payments::log('fakturoid_proforma_created', 'Proforma created', array(
            'order_id'         => $order_id,
            'invoice_id'       => $invoice_id,
            'variable_symbol'  => $vs,
        ));

        delete_option('eab_fakturoid_last_order_error');

        return array(
            'invoice_id'      => $invoice_id,
            'number'          => $number,
            'variable_symbol' => $vs,
            'pdf'             => $pdf_relative,
        );
    }

    /**
     * After local payment confirmation: refresh PDF for existing Fakturoid doc (no new document).
     *
     * @return array|WP_Error|true
     */
    public static function sync_paid_document($order_id) {
        if (!self::is_enabled()) {
            return true;
        }

        $order = EAB_Checkout::get_order($order_id);
        if (!$order || empty($order->fakturoid_invoice_id)) {
            return true;
        }

        $invoice_id = (int) $order->fakturoid_invoice_id;
        $remote = self::api_request('GET', '/invoices/' . $invoice_id . '.json');
        if (is_wp_error($remote)) {
            EAB_Payments::log('fakturoid_sync_failed', $remote->get_error_message(), array('order_id' => $order_id));
            return $remote;
        }

        $fields = array();
        if (!empty($remote['number'])) {
            $fields['fakturoid_invoice_number'] = (string) $remote['number'];
        }
        if (!empty($remote['variable_symbol'])) {
            $fields['fakturoid_variable_symbol'] = preg_replace('/\D/', '', (string) $remote['variable_symbol']);
        }

        $pdf_relative = self::download_invoice_pdf($invoice_id, $order->order_number);
        if ($pdf_relative !== '') {
            $fields['fakturoid_pdf'] = $pdf_relative;
        }

        if ($fields) {
            self::update_order_invoice_meta($order_id, $fields);
        }

        return array(
            'invoice_id' => $invoice_id,
            'pdf'        => $pdf_relative !== '' ? $pdf_relative : ($order->fakturoid_pdf ?? ''),
        );
    }

    /**
     * @deprecated Use create_proforma_for_order / sync_paid_document.
     */
    public static function create_invoice_for_order($order_id) {
        $order = EAB_Checkout::get_order($order_id);
        if ($order && !empty($order->fakturoid_invoice_id)) {
            return self::sync_paid_document($order_id);
        }
        if ($order && $order->status === 'paid') {
            // Legacy path: paid without prior proforma — still avoid inventing a second flow.
            return self::sync_paid_document($order_id);
        }
        return self::create_proforma_for_order($order_id);
    }

    public function maybe_handle_webhook() {
        if (empty($_GET[self::WEBHOOK_QUERY])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            status_header(405);
            header('Allow: POST');
            echo 'Method Not Allowed';
            exit;
        }

        $expected = (string) get_option('eab_fakturoid_webhook_auth', '');
        if ($expected === '') {
            EAB_Payments::log('fakturoid_webhook', 'Rejected: webhook auth not configured');
            status_header(503);
            echo 'Webhook auth not configured';
            exit;
        }

        $auth = self::get_request_authorization();
        if (!hash_equals($expected, $auth)) {
            EAB_Payments::log('fakturoid_webhook', 'Rejected: bad Authorization header');
            status_header(401);
            echo 'Unauthorized';
            exit;
        }

        $idempotency = isset($_SERVER['HTTP_IDEMPOTENCY_KEY'])
            ? sanitize_text_field(wp_unslash($_SERVER['HTTP_IDEMPOTENCY_KEY']))
            : '';
        if ($idempotency !== '') {
            $cache_key = 'eab_fr_wh_' . md5($idempotency);
            if (get_transient($cache_key)) {
                status_header(200);
                echo 'OK';
                exit;
            }
        }

        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            status_header(400);
            echo 'Invalid JSON';
            exit;
        }

        $event = isset($payload['event_name']) ? (string) $payload['event_name'] : '';
        if ($event !== 'invoice_paid') {
            status_header(200);
            echo 'Ignored';
            exit;
        }

        $invoice = isset($payload['body']['invoice']) && is_array($payload['body']['invoice'])
            ? $payload['body']['invoice']
            : array();
        $payment = isset($payload['body']['payment']) && is_array($payload['body']['payment'])
            ? $payload['body']['payment']
            : array();

        $order = self::find_order_for_webhook($invoice, $payment);
        if (!$order) {
            EAB_Payments::log('fakturoid_webhook', 'No matching order', array(
                'invoice_id' => $invoice['id'] ?? null,
                'custom_id'  => $invoice['custom_id'] ?? null,
                'vs'         => $payment['variable_symbol'] ?? ($invoice['variable_symbol'] ?? null),
            ));
            // Acknowledge so Fakturoid does not keep retrying forever for unknown docs.
            status_header(200);
            echo 'No order';
            exit;
        }

        if ($order->status === 'paid') {
            if ($idempotency !== '') {
                set_transient('eab_fr_wh_' . md5($idempotency), 1, WEEK_IN_SECONDS);
            }
            status_header(200);
            echo 'Already paid';
            exit;
        }

        if (!in_array($order->status, array('pending', 'awaiting_payment', 'processing'), true)) {
            EAB_Payments::log('fakturoid_webhook', 'Order not payable', array(
                'order_id' => (int) $order->id,
                'status'   => $order->status,
            ));
            status_header(200);
            echo 'Ignored status';
            exit;
        }

        $transaction_id = !empty($payment['id'])
            ? 'fakturoid-payment-' . $payment['id']
            : 'fakturoid-' . ($invoice['id'] ?? $order->id);

        $ok = EAB_Payments::complete_payment((int) $order->id, $transaction_id);
        EAB_Payments::log('fakturoid_webhook', $ok ? 'Payment completed' : 'complete_payment returned false', array(
            'order_id'       => (int) $order->id,
            'transaction_id' => $transaction_id,
        ));

        if ($idempotency !== '') {
            set_transient('eab_fr_wh_' . md5($idempotency), 1, WEEK_IN_SECONDS);
        }

        status_header(200);
        echo 'OK';
        exit;
    }

    private static function get_request_authorization() {
        if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
            return trim((string) wp_unslash($_SERVER['HTTP_AUTHORIZATION']));
        }
        if (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            return trim((string) wp_unslash($_SERVER['REDIRECT_HTTP_AUTHORIZATION']));
        }
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (is_array($headers)) {
                foreach ($headers as $name => $value) {
                    if (strtolower((string) $name) === 'authorization') {
                        return trim((string) $value);
                    }
                }
            }
        }
        return '';
    }

    /**
     * @param array $invoice
     * @param array $payment
     * @return object|null
     */
    private static function find_order_for_webhook(array $invoice, array $payment) {
        global $wpdb;

        $table = $wpdb->prefix . 'eab_orders';

        if (!empty($invoice['id'])) {
            $order_id = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table WHERE fakturoid_invoice_id = %s LIMIT 1",
                (string) $invoice['id']
            ));
            if ($order_id) {
                return EAB_Checkout::get_order($order_id);
            }
        }

        if (!empty($invoice['custom_id'])) {
            $order_id = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table WHERE order_number = %s LIMIT 1",
                (string) $invoice['custom_id']
            ));
            if ($order_id) {
                return EAB_Checkout::get_order($order_id);
            }
        }

        $vs = '';
        if (!empty($payment['variable_symbol'])) {
            $vs = (string) $payment['variable_symbol'];
        } elseif (!empty($invoice['variable_symbol'])) {
            $vs = (string) $invoice['variable_symbol'];
        }
        $vs = preg_replace('/\D/', '', $vs);
        if ($vs !== '') {
            $order_id = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table WHERE fakturoid_variable_symbol = %s LIMIT 1",
                $vs
            ));
            if ($order_id) {
                return EAB_Checkout::get_order($order_id);
            }
        }

        return null;
    }

    private static function ensure_subject($order) {
        $user = get_userdata($order->user_id);
        if (!$user) {
            return new WP_Error('no_user', 'User missing');
        }

        $invoice = is_array($order->invoice_data) ? $order->invoice_data : array();
        $custom_id = !empty($invoice['ic'])
            ? 'ic_' . preg_replace('/\D/', '', $invoice['ic'])
            : 'user_' . $user->ID;

        $existing = self::api_request('GET', '/subjects.json?custom_id=' . rawurlencode($custom_id));
        if (!is_wp_error($existing) && !empty($existing[0]['id'])) {
            return (int) $existing[0]['id'];
        }

        if (!empty($invoice['company_name'])) {
            $payload = array(
                'custom_id'       => $custom_id,
                'name'            => $invoice['company_name'],
                'street'          => trim(($invoice['street'] ?? '') . ' ' . ($invoice['street_number'] ?? '')),
                'city'            => $invoice['city'] ?? '',
                'zip'             => $invoice['zip'] ?? '',
                'country'         => 'CZ',
                'registration_no' => preg_replace('/\D/', '', $invoice['ic'] ?? ''),
                'vat_no'          => $invoice['dic'] ?? '',
                'email'           => $user->user_email,
            );
        } else {
            $payload = array(
                'custom_id' => $custom_id,
                'name'      => $user->display_name,
                'email'     => $user->user_email,
            );
        }

        $created = self::api_request('POST', '/subjects.json', $payload);
        if (is_wp_error($created)) {
            return $created;
        }

        return isset($created['id']) ? (int) $created['id'] : new WP_Error('fakturoid_subject', 'Subject create failed');
    }

    private static function download_invoice_pdf($invoice_id, $order_number) {
        $response = self::api_request('GET', '/invoices/' . $invoice_id . '/download.pdf', null, true);
        if (is_wp_error($response) || empty($response['body'])) {
            return '';
        }

        self::ensure_storage_dir();
        $filename = sanitize_file_name($order_number . '-fakturoid.pdf');
        $relative = self::SUBDIR . '/' . $filename;
        $path     = self::absolute_path($relative);

        file_put_contents($path, $response['body']);

        return $relative;
    }

    public static function ensure_storage_dir() {
        $upload = wp_upload_dir();
        if (!empty($upload['error'])) {
            return false;
        }
        $dir = trailingslashit($upload['basedir']) . self::SUBDIR;
        wp_mkdir_p($dir);
        $htaccess = $dir . '/.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Deny from all\n");
        }
        return $dir;
    }

    public static function absolute_path($relative) {
        $upload = wp_upload_dir();
        return trailingslashit($upload['basedir']) . ltrim($relative, '/');
    }

    private static function update_order_invoice_meta($order_id, array $fields) {
        global $wpdb;
        if (!$fields) {
            return;
        }
        $formats = array_fill(0, count($fields), '%s');
        $wpdb->update(
            $wpdb->prefix . 'eab_orders',
            $fields,
            array('id' => $order_id),
            $formats,
            array('%d')
        );
    }

    /**
     * OAuth2 Client Credentials — cached access token.
     *
     * @return string|WP_Error
     */
    private static function get_access_token() {
        $client_id     = trim((string) get_option('eab_fakturoid_client_id', ''));
        $client_secret = trim((string) get_option('eab_fakturoid_client_secret', ''));

        if ($client_id === '' || $client_secret === '') {
            return new WP_Error('fakturoid_auth', __('Fakturoid Client ID / Secret chybí.', 'events-and-bookings'));
        }

        $cache_key = 'eab_fakturoid_token_' . md5($client_id);
        $cached = get_transient($cache_key);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        // Prefer form body (OAuth RFC); Fakturoid also accepts JSON.
        $response = wp_remote_post('https://app.fakturoid.cz/api/v3/oauth/token', array(
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($client_id . ':' . $client_secret),
                'User-Agent'    => self::get_user_agent(),
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ),
            'body' => array(
                'grant_type' => 'client_credentials',
            ),
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $raw  = wp_remote_retrieve_body($response);
        $body = json_decode($raw, true);

        if ($code < 200 || $code >= 300 || empty($body['access_token'])) {
            $error = is_array($body) && isset($body['error']) ? (string) $body['error'] : '';
            $message = isset($body['error_description'])
                ? (string) $body['error_description']
                : ($error !== '' ? $error : __('Fakturoid OAuth selhalo.', 'events-and-bookings'));

            if ($error === 'invalid_client') {
                $message = __('invalid_client — Client ID nebo Client Secret nesedí. Zkontrolujte zkopírované údaje (celý řetězec, bez mezer), uložte nastavení a zkuste znovu. Použijte údaje z Nastavení → Uživatelský účet → API přístupy.', 'events-and-bookings');
            }

            EAB_Payments::log('fakturoid_oauth_failed', $message, array(
                'http'  => $code,
                'error' => $error,
                'body'  => is_array($body) ? $body : substr((string) $raw, 0, 300),
            ));

            return new WP_Error('fakturoid_auth', $message, array('code' => $code));
        }

        $ttl = max(60, (int) ($body['expires_in'] ?? 7200) - 120);
        set_transient($cache_key, $body['access_token'], $ttl);

        return $body['access_token'];
    }

    /**
     * Quick connectivity check (OAuth + account read).
     *
     * @return array{ok:bool,message:string}
     */
    public static function run_connectivity_test() {
        if (!self::is_enabled()) {
            return array(
                'ok'      => false,
                'message' => __('Doplňte slug, Client ID a Client Secret a zapněte Fakturoid.', 'events-and-bookings'),
            );
        }

        $token = self::get_access_token();
        if (is_wp_error($token)) {
            return array(
                'ok'      => false,
                'message' => $token->get_error_message(),
            );
        }

        $account = self::api_request('GET', '/account.json');
        if (is_wp_error($account)) {
            return array(
                'ok'      => false,
                'message' => $account->get_error_message(),
            );
        }

        $name = isset($account['name']) ? (string) $account['name'] : self::get_account_slug();
        $vat_mode = isset($account['vat_mode']) ? (string) $account['vat_mode'] : '';
        $vat_note = '';
        if ($vat_mode === 'non_vat_payer') {
            $vat_note = ' ' . __('(neplátce DPH → sazba 0)', 'events-and-bookings');
        } elseif ($vat_mode !== '') {
            $vat_note = ' (' . $vat_mode . ')';
        }

        return array(
            'ok'      => true,
            'message' => sprintf(
                /* translators: 1: Fakturoid account name, 2: optional VAT note */
                __('Připojení OK — účet „%1$s“%2$s.', 'events-and-bookings'),
                $name,
                $vat_note
            ),
        );
    }

    /**
     * @return array|WP_Error
     */
    private static function api_request($method, $path, $body = null, $raw = false) {
        $token = self::get_access_token();
        if (is_wp_error($token)) {
            return $token;
        }

        $args = array(
            'method'  => $method,
            'timeout' => 45,
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'User-Agent'    => self::get_user_agent(),
                'Accept'        => $raw ? 'application/pdf' : 'application/json',
                'Content-Type'  => 'application/json',
            ),
        );

        if ($body !== null && !$raw) {
            $args['body'] = wp_json_encode($body);
        }

        $response = wp_remote_request(self::api_base() . $path, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $resp_body = wp_remote_retrieve_body($response);

        // Stale token — clear cache and retry once.
        if ($code === 401 && empty($GLOBALS['eab_fakturoid_token_retry'])) {
            $client_id = (string) get_option('eab_fakturoid_client_id', '');
            delete_transient('eab_fakturoid_token_' . md5($client_id));
            $GLOBALS['eab_fakturoid_token_retry'] = true;
            $result = self::api_request($method, $path, $body, $raw);
            unset($GLOBALS['eab_fakturoid_token_retry']);
            return $result;
        }

        if ($raw) {
            if ($code >= 200 && $code < 300) {
                return array('body' => $resp_body);
            }
            return new WP_Error('fakturoid_pdf', 'PDF download failed', array('code' => $code));
        }

        $json = json_decode($resp_body, true);

        if ($code >= 200 && $code < 300) {
            return $json;
        }

        $message = isset($json['errors']) ? wp_json_encode($json['errors']) : __('Fakturoid API chyba.', 'events-and-bookings');
        return new WP_Error('fakturoid_api', $message, array('code' => $code));
    }
}
