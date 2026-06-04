<?php
/**
 * Bank transfer instructions (QR in Phase 5).
 */

if (!defined('ABSPATH')) {
    exit;
}

class EAB_Bank_Transfer {

    public static function process_order($order_id) {
        EAB_Checkout::update_order_status($order_id, 'awaiting_payment');
        EAB_Payments::log('bank_transfer', 'Order awaiting payment', array('order_id' => $order_id));
        return true;
    }

    public static function render_transfer_info($order_id) {
        $order = EAB_Checkout::get_order($order_id);

        if (!$order || (int) $order->user_id !== get_current_user_id()) {
            return '<p>' . esc_html__('Objednávka nenalezena.', 'events-and-bookings') . '</p>';
        }

        $account_name   = get_option('eab_bank_account_name', '');
        $account_number = get_option('eab_bank_account_number', '');
        $bank_code      = get_option('eab_bank_code', '');
        $iban           = get_option('eab_bank_iban', '');

        ob_start();
        include EAB_PLUGIN_DIR . 'public/partials/bank-transfer-info.php';
        return ob_get_clean();
    }
}
