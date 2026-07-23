<?php
/**
 * Checkout and orders.
 */

if (!defined('ABSPATH')) {
    exit;
}

class EAB_Checkout {

    public function __construct() {
        add_shortcode('eab_checkout', array($this, 'render_checkout'));
        add_action('wp_ajax_eab_process_checkout', array($this, 'ajax_process_checkout'));
        add_action('wp_ajax_eab_save_basket_from_checkout', array($this, 'ajax_save_basket_from_checkout'));
    }

    public function render_checkout() {
        if (!is_user_logged_in()) {
            return '<p class="eab-notice">' . esc_html__('Pro dokončení rezervace se přihlaste.', 'events-and-bookings') . ' <a href="' . esc_url(EAB_Event::get_login_url(get_permalink())) . '">' . esc_html__('Přihlásit', 'events-and-bookings') . '</a></p>';
        }

        if (isset($_GET['order']) && isset($_GET['method']) && $_GET['method'] === 'bank_transfer') {
            return EAB_Bank_Transfer::render_transfer_info((int) $_GET['order']);
        }

        $basket = new EAB_Basket();
        $items  = $basket->get_items();

        if (empty($items)) {
            ob_start();
            ?>
            <div class="eab-checkout-empty">
                <p><?php esc_html_e('Košík je prázdný.', 'events-and-bookings'); ?></p>
            </div>
            <?php
            return ob_get_clean();
        }

        $total = $basket->get_total();
        $user_id = get_current_user_id();
        $saved_invoice = EAB_Invoice::get_user_invoice_data($user_id);
        $has_saved_invoice = EAB_Invoice::user_has_saved_invoice($user_id);
        $terms_page = (int) get_option('eab_terms_page', 0);
        $bank_enabled  = (bool) get_option('eab_bank_transfer_enabled', 1);
        $gopay_enabled = EAB_GoPay::is_enabled();

        ob_start();
        include EAB_PLUGIN_DIR . 'public/partials/checkout-page.php';
        return ob_get_clean();
    }

    public function ajax_save_basket_from_checkout() {
        check_ajax_referer('eab_public', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Přihlaste se prosím.', 'events-and-bookings')));
        }

        $lines = isset($_POST['lines']) ? json_decode(wp_unslash($_POST['lines']), true) : array();
        if (!is_array($lines)) {
            wp_send_json_error(array('message' => __('Neplatná data.', 'events-and-bookings')));
        }

        $basket = new EAB_Basket();
        $line_totals = array();

        foreach ($lines as $line) {
            $post_id = isset($line['post_id']) ? (int) $line['post_id'] : 0;
            $meta    = isset($line['line_meta']) && is_array($line['line_meta']) ? $line['line_meta'] : array();
            if (!$post_id) {
                continue;
            }

            // Live checkout updates only need spots/capacity; attendees are validated on submit.
            $validation = self::validate_line_meta($post_id, $meta, false);
            if (is_wp_error($validation)) {
                wp_send_json_error(array('message' => $validation->get_error_message()));
            }

            if ($basket->is_in_basket($post_id)) {
                $updated = $basket->update_line($post_id, $meta);
                if (is_wp_error($updated)) {
                    wp_send_json_error(array('message' => $updated->get_error_message()));
                }
            }

            $pricing = EAB_Pricing::calculate_line($post_id, $meta);
            $line_totals[] = array(
                'post_id'              => $post_id,
                'line_total'           => $pricing['line_total'],
                'line_total_formatted' => EAB_Payments::format_price($pricing['line_total']),
            );
        }

        $total = $basket->get_total();

        wp_send_json_success(array(
            'total'           => $total,
            'total_formatted' => EAB_Payments::format_price($total),
            'lines'           => $line_totals,
        ));
    }

    public function ajax_process_checkout() {
        check_ajax_referer('eab_public', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Přihlaste se prosím.', 'events-and-bookings')));
        }

        $payment_method = isset($_POST['payment_method']) ? sanitize_key($_POST['payment_method']) : '';
        $agree_terms    = !empty($_POST['agree_terms']);
        $want_invoice   = !empty($_POST['want_invoice']);

        if (!in_array($payment_method, array('bank_transfer', 'gopay'), true)) {
            wp_send_json_error(array('message' => __('Neplatná platební metoda.', 'events-and-bookings')));
        }

        if ($payment_method === 'bank_transfer' && !get_option('eab_bank_transfer_enabled', 1)) {
            wp_send_json_error(array('message' => __('Platba převodem není dostupná.', 'events-and-bookings')));
        }

        if ($payment_method === 'gopay' && !EAB_GoPay::is_enabled()) {
            wp_send_json_error(array('message' => __('Platba kartou není dostupná.', 'events-and-bookings')));
        }

        $terms_page = (int) get_option('eab_terms_page', 0);
        if ($terms_page && !$agree_terms) {
            wp_send_json_error(array('message' => __('Souhlaste s obchodními podmínkami.', 'events-and-bookings')));
        }

        $invoice_data = EAB_Invoice::parse_checkout_input($_POST, $want_invoice);
        if (is_wp_error($invoice_data)) {
            wp_send_json_error(array('message' => $invoice_data->get_error_message()));
        }

        if ($want_invoice && !empty($_POST['save_invoice_to_profile'])) {
            EAB_Invoice::save_to_user_profile(get_current_user_id(), $invoice_data);
        }

        if (!empty($_POST['lines'])) {
            $lines = json_decode(wp_unslash($_POST['lines']), true);
            if (is_array($lines)) {
                $basket = new EAB_Basket();
                foreach ($lines as $line) {
                    $post_id = (int) ($line['post_id'] ?? 0);
                    $meta    = $line['line_meta'] ?? array();
                    if ($post_id && $basket->is_in_basket($post_id)) {
                        $v = self::validate_line_meta($post_id, $meta);
                        if (is_wp_error($v)) {
                            wp_send_json_error(array('message' => $v->get_error_message()));
                        }
                        $basket->update_line($post_id, $meta);
                    }
                }
            }
        }

        $order_id = self::create_order($payment_method, $invoice_data);

        if (!$order_id) {
            wp_send_json_error(array('message' => __('Objednávku se nepodařilo vytvořit.', 'events-and-bookings')));
        }

        if ($payment_method === 'bank_transfer') {
            EAB_Bank_Transfer::process_order($order_id);
            EAB_Emails::send_order_placed_email($order_id);

            $checkout_url = EAB_Auth::get_page_url('checkout');
            wp_send_json_success(array(
                'redirect' => add_query_arg(array(
                    'order'  => $order_id,
                    'method' => 'bank_transfer',
                ), $checkout_url ?: home_url('/')),
            ));
        }

        EAB_Emails::send_order_placed_email($order_id);

        $gopay  = new EAB_GoPay();
        $result = $gopay->create_payment($order_id);

        if (!empty($result['error'])) {
            self::cancel_order($order_id);
            wp_send_json_error(array('message' => $result['error']));
        }

        wp_send_json_success(array('redirect' => $result['redirect']));
    }

    /**
     * @return int|false
     */
    public static function create_order($payment_method, $invoice_data = null) {
        global $wpdb;

        $user_id = get_current_user_id();
        $basket  = new EAB_Basket();
        $items   = $basket->get_items();

        if (empty($items) || !EAB_DB::table_exists('eab_orders')) {
            return false;
        }

        $total    = $basket->get_total();
        $currency = get_option('eab_currency_code', 'CZK');
        $expiry_h = (int) get_option('eab_order_expiry_hours', 24);
        $order_number = self::generate_order_number();

        $orders_table = $wpdb->prefix . 'eab_orders';
        $insert = array(
            'user_id'        => $user_id,
            'order_number'   => $order_number,
            'status'         => 'pending',
            'payment_method' => $payment_method,
            'total'          => $total,
            'discount'       => 0,
            'currency'       => $currency,
            'created_at'     => current_time('mysql'),
            'expires_at'     => gmdate('Y-m-d H:i:s', time() + $expiry_h * HOUR_IN_SECONDS),
        );
        $format = array('%d', '%s', '%s', '%s', '%f', '%f', '%s', '%s', '%s');

        if ($invoice_data) {
            $insert['invoice_data'] = EAB_Invoice::encode_for_order($invoice_data);
            $format[] = '%s';
        }

        if ($wpdb->insert($orders_table, $insert, $format) === false) {
            return false;
        }

        $order_id = (int) $wpdb->insert_id;
        $items_table = $wpdb->prefix . 'eab_order_items';

        foreach ($items as $item) {
            $validation = self::validate_line_meta($item->object_id, $item->line_meta);
            if (is_wp_error($validation)) {
                self::cancel_order($order_id);
                return false;
            }

            $pricing = EAB_Pricing::calculate_line($item->object_id, $item->line_meta);
            $wpdb->insert(
                $items_table,
                array(
                    'order_id'     => $order_id,
                    'object_id'    => $item->object_id,
                    'object_type'  => $item->object_type,
                    'qty'          => (int) $item->line_meta['spots'],
                    'unit_price'   => $pricing['unit'],
                    'line_total'   => $pricing['line_total'],
                    'line_meta'    => wp_json_encode($item->line_meta),
                ),
                array('%d', '%d', '%s', '%d', '%f', '%f', '%s')
            );

            $order_item_id = (int) $wpdb->insert_id;
            EAB_Capacity::create_holds_from_line(
                $order_id,
                $order_item_id,
                $item->object_id,
                $item->object_type,
                $user_id,
                $item->line_meta,
                $item->line_meta['spot_type'] ?? EAB_Capacity::SPOT_REGULAR
            );
        }

        $wpdb->update(
            $orders_table,
            array('status' => 'awaiting_payment'),
            array('id' => $order_id),
            array('%s'),
            array('%d')
        );

        $basket->clear($user_id);

        return $order_id;
    }

    /**
     * @param int   $post_id
     * @param array $meta
     * @param bool  $require_attendees When false, only spots/capacity are checked (live checkout updates).
     * @return true|WP_Error
     */
    public static function validate_line_meta($post_id, array $meta, $require_attendees = true) {
        $spots = EAB_Basket::validate_spot_count($meta['spots'] ?? 1);
        if (is_wp_error($spots)) {
            return $spots;
        }

        $capacity = EAB_Capacity::can_reserve($post_id, $spots);
        if (is_wp_error($capacity)) {
            return $capacity;
        }

        if (!$require_attendees) {
            return true;
        }

        $attendees = isset($meta['attendees']) && is_array($meta['attendees']) ? $meta['attendees'] : array();
        if (count($attendees) < $spots) {
            return new WP_Error('eab_attendees', __('Vyplňte údaje všech účastníků.', 'events-and-bookings'));
        }

        $fields = EAB_Pricing::get_attendee_field_defs($post_id);
        for ($i = 0; $i < $spots; $i++) {
            $row = $attendees[$i] ?? array();
            foreach ($fields as $field) {
                if (empty($field['required'])) {
                    continue;
                }
                $key = $field['field_key'] ?? '';
                if ($key && empty($row[$key])) {
                    return new WP_Error('eab_attendees', __('Vyplňte povinná pole účastníků.', 'events-and-bookings'));
                }
            }
        }

        return true;
    }

    public static function cancel_order($order_id) {
        EAB_Capacity::release_order_spots($order_id);
        self::update_order_status($order_id, 'cancelled');
    }

    public static function generate_order_number() {
        return 'EAB-' . strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 8));
    }

    public static function get_order($order_id) {
        global $wpdb;

        $orders_table = $wpdb->prefix . 'eab_orders';
        $items_table  = $wpdb->prefix . 'eab_order_items';

        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $orders_table WHERE id = %d",
            $order_id
        ));

        if (!$order) {
            return null;
        }

        $order->items = $wpdb->get_results($wpdb->prepare(
            "SELECT oi.*, p.post_title
             FROM $items_table oi
             LEFT JOIN {$wpdb->posts} p ON oi.object_id = p.ID
             WHERE oi.order_id = %d",
            $order_id
        ));

        foreach ($order->items as &$item) {
            $item->line_meta = json_decode($item->line_meta, true);
        }

        if (!empty($order->invoice_data)) {
            $order->invoice_data = json_decode($order->invoice_data, true);
        }

        return $order;
    }

    public static function update_order_status($order_id, $status, $transaction_id = null) {
        global $wpdb;

        $data = array(
            'status'     => $status,
            'updated_at' => current_time('mysql'),
        );
        $format = array('%s', '%s');

        if ($transaction_id) {
            $data['transaction_id'] = $transaction_id;
            $format[] = '%s';
        }

        if ($status === 'paid') {
            $data['paid_at'] = current_time('mysql');
            $format[] = '%s';
            EAB_Capacity::confirm_order_spots($order_id);
        }

        if (in_array($status, array('cancelled', 'expired', 'failed'), true)) {
            EAB_Capacity::release_order_spots($order_id);
        }

        return $wpdb->update(
            $wpdb->prefix . 'eab_orders',
            $data,
            array('id' => $order_id),
            $format,
            array('%d')
        );
    }

    public static function get_order_by_transaction($transaction_id) {
        global $wpdb;

        if ($transaction_id === '') {
            return null;
        }

        $order_id = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}eab_orders WHERE transaction_id = %s LIMIT 1",
            $transaction_id
        ));

        return $order_id ? self::get_order($order_id) : null;
    }
}
