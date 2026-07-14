<?php
/**
 * Member dashboard (bookings overview + account settings).
 */

if (!defined('ABSPATH')) {
    exit;
}

class EAB_Dashboard {

    const QUERY_DOWNLOAD_INVOICE = 'eab_download_invoice';

    public function __construct() {
        add_shortcode('eab_dashboard', array($this, 'render'));
        add_action('init', array($this, 'handle_download_requests'));
        add_action('wp_ajax_eab_dashboard_save_profile', array($this, 'ajax_save_profile'));
        add_action('wp_ajax_eab_dashboard_cancel_order', array($this, 'ajax_cancel_order'));
        add_filter('eab_enqueue_public_assets', array($this, 'enqueue_assets_flag'));
    }

    public function enqueue_assets_flag($load) {
        return $load || $this->current_page_has_dashboard_shortcode();
    }

    private function current_page_has_dashboard_shortcode() {
        $post = get_post();
        if (!$post || empty($post->post_content)) {
            return false;
        }
        return has_shortcode($post->post_content, 'eab_dashboard');
    }

    public function render() {
        if (!is_user_logged_in()) {
            return '<p>' . esc_html__('Přihlaste se pro zobrazení účtu.', 'events-and-bookings') . '</p>';
        }

        $user_id = get_current_user_id();
        $user    = wp_get_current_user();
        $profile = self::get_user_profile($user_id);
        $groups  = self::get_user_booking_groups($user_id);

        $dashboard_url = EAB_Auth::get_page_url('dashboard');
        if (!$dashboard_url) {
            $dashboard_url = get_permalink();
        }

        $context = array(
            'profile'          => $profile,
            'groups'           => $groups,
            'invoice_enabled'  => EAB_Settings::checkout_invoice_enabled(),
            'invoice_data'     => EAB_Invoice::get_user_invoice_data($user_id),
            'logout_url'       => wp_logout_url(home_url('/')),
            'password_url'     => wp_lostpassword_url($dashboard_url),
            'user_email'       => $user->user_email,
            'dashboard_url'    => $dashboard_url,
        );

        ob_start();
        include EAB_PLUGIN_DIR . 'public/partials/dashboard-page.php';
        return ob_get_clean();
    }

    public static function get_user_profile($user_id) {
        $first = get_user_meta($user_id, EAB_Auth::META_FIRST_NAME, true);
        $last  = get_user_meta($user_id, EAB_Auth::META_LAST_NAME, true);

        return array(
            'first_name' => $first,
            'last_name'  => $last,
            'full_name'  => trim($first . ' ' . $last),
            'dob'        => get_user_meta($user_id, EAB_Auth::META_DOB, true),
            'phone'      => get_user_meta($user_id, EAB_Auth::META_PHONE, true),
        );
    }

    /**
     * @return array{trainings:array,events:array}
     */
    public static function get_user_booking_groups($user_id) {
        $items = self::get_user_booking_items($user_id);

        $groups = array(
            'trainings' => array(),
            'events'    => array(),
        );

        foreach ($items as $item) {
            if ($item['object_type'] === EAB_Post_Types::POST_TYPE_TRAINING) {
                $groups['trainings'][] = $item;
            } else {
                $groups['events'][] = $item;
            }
        }

        return $groups;
    }

    /**
     * Flatten orders into one dashboard row per order line item.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function get_user_booking_items($user_id) {
        $orders = self::get_user_orders($user_id);
        $items  = array();

        foreach ($orders as $order) {
            if (in_array($order->status, array('cancelled', 'expired', 'failed'), true)) {
                continue;
            }

            $full = EAB_Checkout::get_order((int) $order->id);
            if (!$full || empty($full->items)) {
                continue;
            }

            foreach ($full->items as $line) {
                $built = self::build_booking_item($full, $line);
                if ($built) {
                    $items[] = $built;
                }
            }
        }

        return $items;
    }

    /**
     * @param object $order  Order with items loaded.
     * @param object $line   Order item row.
     * @return array<string,mixed>|null
     */
    public static function build_booking_item($order, $line) {
        $object_id   = (int) $line->object_id;
        $line_meta   = is_array($line->line_meta) ? $line->line_meta : array();
        $object_type = $line->object_type ?: EAB_Event::get_post_type($object_id);
        $spots       = max(1, (int) ($line_meta['spots'] ?? 1));
        $services    = isset($line_meta['services']) && is_array($line_meta['services']) ? $line_meta['services'] : array();

        return array(
            'order_id'       => (int) $order->id,
            'item_id'        => (int) $line->id,
            'order_number'   => $order->order_number,
            'status'         => $order->status,
            'status_label'   => self::status_label_detail($order->status),
            'object_id'      => $object_id,
            'object_type'    => $object_type,
            'title'          => $line->post_title ?: get_the_title($object_id),
            'date_line'      => EAB_Event::get_detail_date_line($object_id),
            'location_line'  => EAB_Event::get_detail_location_line($object_id),
            'spots'          => $spots,
            'services'       => EAB_Event::get_service_labels($object_id, $services),
            'has_invoice'    => !empty($order->fakturoid_pdf),
            'invoice_url'    => self::get_invoice_download_url((int) $order->id),
            'cancellation'   => self::get_cancellation_state($order, $object_type, $object_id),
            'hash'           => self::booking_hash((int) $order->id, (int) $line->id),
        );
    }

    public static function booking_hash($order_id, $item_id) {
        return 'booking/' . (int) $order_id . '/' . (int) $item_id;
    }

    /**
     * @return array{action:string,message:string}
     */
    public static function get_cancellation_state($order, $object_type, $object_id) {
        $terminal = array('cancelled', 'expired', 'failed');
        if (in_array($order->status, $terminal, true)) {
            return array(
                'action'  => 'none',
                'message' => '',
            );
        }

        $start_ts = EAB_Event::get_start_timestamp($object_id);
        $hours    = EAB_Settings::cancel_hours_before_start($object_type);

        if ($start_ts && $hours > 0) {
            $seconds_until = $start_ts - time();
            if ($seconds_until <= ($hours * HOUR_IN_SECONDS)) {
                return array(
                    'action'  => 'reschedule',
                    'message' => sprintf(
                        /* translators: %d: hours before start */
                        __('Lhůta pro zrušení (%d h před začátkem) již uplynula. Kontaktujte nás ohledně přesunu rezervace.', 'events-and-bookings'),
                        $hours
                    ),
                );
            }
        }

        return array(
            'action'  => 'cancel',
            'message' => '',
        );
    }

    public function get_user_orders($user_id) {
        global $wpdb;

        if (!EAB_DB::table_exists('eab_orders')) {
            return array();
        }

        $table = $wpdb->prefix . 'eab_orders';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d ORDER BY created_at DESC LIMIT 50",
            $user_id
        ));
    }

    public static function get_invoice_download_url($order_id) {
        return add_query_arg(
            array(
                self::QUERY_DOWNLOAD_INVOICE => 1,
                'order_id'                   => (int) $order_id,
                'nonce'                      => wp_create_nonce('eab_invoice_' . (int) $order_id),
            ),
            EAB_Auth::get_page_url('dashboard') ?: home_url('/')
        );
    }

    public function handle_download_requests() {
        if (empty($_GET[self::QUERY_DOWNLOAD_INVOICE]) || empty($_GET['order_id'])) {
            return;
        }

        if (!is_user_logged_in()) {
            wp_die(esc_html__('Přihlaste se.', 'events-and-bookings'), 403);
        }

        $order_id = (int) $_GET['order_id'];
        $nonce    = isset($_GET['nonce']) ? sanitize_text_field(wp_unslash($_GET['nonce'])) : '';

        if (!wp_verify_nonce($nonce, 'eab_invoice_' . $order_id)) {
            wp_die(esc_html__('Neplatný požadavek.', 'events-and-bookings'), 403);
        }

        global $wpdb;
        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}eab_orders WHERE id = %d AND user_id = %d",
            $order_id,
            get_current_user_id()
        ));

        if (!$order || empty($order->fakturoid_pdf)) {
            wp_die(esc_html__('Faktura není k dispozici.', 'events-and-bookings'), 404);
        }

        $path = EAB_Fakturoid::absolute_path($order->fakturoid_pdf);
        if (!is_readable($path)) {
            wp_die(esc_html__('Soubor faktury nebyl nalezen.', 'events-and-bookings'), 404);
        }

        $filename = sanitize_file_name($order->order_number . '.pdf');
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    public function ajax_save_profile() {
        check_ajax_referer('eab_public', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Přihlaste se.', 'events-and-bookings')));
        }

        $user_id = get_current_user_id();

        $first = isset($_POST['first_name']) ? sanitize_text_field(wp_unslash($_POST['first_name'])) : '';
        $last  = isset($_POST['last_name']) ? sanitize_text_field(wp_unslash($_POST['last_name'])) : '';
        $dob   = isset($_POST['dob']) ? sanitize_text_field(wp_unslash($_POST['dob'])) : '';
        $phone = isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : '';

        if ($first === '' || $last === '') {
            wp_send_json_error(array('message' => __('Vyplňte jméno a příjmení.', 'events-and-bookings')));
        }

        update_user_meta($user_id, EAB_Auth::META_FIRST_NAME, $first);
        update_user_meta($user_id, EAB_Auth::META_LAST_NAME, $last);
        update_user_meta($user_id, EAB_Auth::META_DOB, $dob);
        update_user_meta($user_id, EAB_Auth::META_PHONE, $phone);

        if (EAB_Settings::checkout_invoice_enabled()) {
            $invoice = array();
            foreach (EAB_Invoice::field_map() as $form_key => $suffix) {
                $post_key = 'invoice_' . $form_key;
                if (isset($_POST[$post_key])) {
                    $invoice[$form_key] = sanitize_text_field(wp_unslash($_POST[$post_key]));
                }
            }
            EAB_Invoice::save_to_user_profile($user_id, $invoice);
        }

        wp_send_json_success(array(
            'message'   => __('Nastavení bylo uloženo.', 'events-and-bookings'),
            'full_name' => trim($first . ' ' . $last),
        ));
    }

    public function ajax_cancel_order() {
        check_ajax_referer('eab_public', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Přihlaste se.', 'events-and-bookings')));
        }

        $order_id = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
        $item_id  = isset($_POST['item_id']) ? (int) $_POST['item_id'] : 0;

        if (!$order_id) {
            wp_send_json_error(array('message' => __('Neplatná rezervace.', 'events-and-bookings')));
        }

        global $wpdb;
        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}eab_orders WHERE id = %d AND user_id = %d",
            $order_id,
            get_current_user_id()
        ));

        if (!$order) {
            wp_send_json_error(array('message' => __('Rezervace nebyla nalezena.', 'events-and-bookings')));
        }

        if (in_array($order->status, array('cancelled', 'expired', 'failed'), true)) {
            wp_send_json_error(array('message' => __('Tuto rezervaci již nelze zrušit.', 'events-and-bookings')));
        }

        $full = EAB_Checkout::get_order($order_id);
        $line = null;
        foreach ($full->items as $candidate) {
            if ((int) $candidate->id === $item_id) {
                $line = $candidate;
                break;
            }
        }

        if (!$line && !empty($full->items)) {
            $line = $full->items[0];
        }

        if ($line) {
            $state = self::get_cancellation_state($order, $line->object_type, (int) $line->object_id);
            if ($state['action'] === 'reschedule') {
                wp_send_json_error(array('message' => $state['message']));
            }
        }

        EAB_Checkout::cancel_order($order_id);

        wp_send_json_success(array(
            'message' => __('Rezervace byla zrušena.', 'events-and-bookings'),
        ));
    }

    public static function status_label($status) {
        $map = array(
            'pending'          => __('Čeká', 'events-and-bookings'),
            'awaiting_payment' => __('Čeká na platbu', 'events-and-bookings'),
            'paid'             => __('Zaplaceno', 'events-and-bookings'),
            'cancelled'        => __('Zrušeno', 'events-and-bookings'),
            'expired'          => __('Vypršelo', 'events-and-bookings'),
            'failed'           => __('Neúspěšné', 'events-and-bookings'),
        );
        return $map[$status] ?? $status;
    }

    public static function status_label_detail($status) {
        $map = array(
            'pending'          => __('Čekající', 'events-and-bookings'),
            'awaiting_payment' => __('Čeká na platbu', 'events-and-bookings'),
            'paid'             => __('Zaplacená', 'events-and-bookings'),
            'cancelled'        => __('Zrušená', 'events-and-bookings'),
            'expired'          => __('Vypršelá', 'events-and-bookings'),
            'failed'           => __('Neúspěšná', 'events-and-bookings'),
        );
        return $map[$status] ?? self::status_label($status);
    }
}
