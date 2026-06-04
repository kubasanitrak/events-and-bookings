<?php
/**
 * Payment helpers and order completion (GoPay in Phase 6).
 */

if (!defined('ABSPATH')) {
    exit;
}

class EAB_Payments {

    public static function format_price($amount) {
        $symbol   = get_option('eab_currency_symbol', 'Kč');
        $position = get_option('eab_currency_position', 'after');
        $formatted = number_format_i18n((float) $amount, 0);

        if ($position === 'before') {
            return $symbol . ' ' . $formatted;
        }
        return $formatted . ' ' . $symbol;
    }

    public static function complete_payment($order_id, $transaction_id = null) {
        $order = EAB_Checkout::get_order($order_id);
        if (!$order || $order->status === 'paid') {
            return false;
        }

        EAB_Checkout::update_order_status($order_id, 'paid', $transaction_id);
        EAB_Emails::send_payment_confirmed_email($order_id);

        do_action('eab_payment_completed', $order_id, $order);

        return true;
    }

    public static function log($type, $message, $data = array()) {
        global $wpdb;

        if (!EAB_DB::table_exists('eab_logs')) {
            return;
        }

        $wpdb->insert(
            $wpdb->prefix . 'eab_logs',
            array(
                'type'       => $type,
                'message'    => $message,
                'data'       => wp_json_encode($data),
                'created_at' => current_time('mysql'),
            ),
            array('%s', '%s', '%s', '%s')
        );
    }
}
