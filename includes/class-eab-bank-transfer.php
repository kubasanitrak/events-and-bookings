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

        if (EAB_Fakturoid::is_enabled()) {
            $result = EAB_Fakturoid::create_proforma_for_order($order_id);
            if (is_wp_error($result)) {
                // Keep reservation + local bank QR; admin can still confirm manually.
                EAB_Payments::log('fakturoid_proforma_skipped', $result->get_error_message(), array(
                    'order_id' => $order_id,
                ));
                update_option('eab_fakturoid_last_order_error', array(
                    'order_id'   => (int) $order_id,
                    'message'    => $result->get_error_message(),
                    'created_at' => current_time('mysql'),
                ), false);
            }
        } else {
            EAB_Payments::log('fakturoid_skipped', 'Fakturoid disabled or incomplete credentials', array(
                'order_id' => $order_id,
            ));
        }

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
        $vs             = EAB_Fakturoid::get_variable_symbol($order);
        $account_full   = ($account_number && $bank_code) ? ($account_number . '/' . $bank_code) : '';
        $copy_payload   = self::build_copy_payload($order, $account_name, $account_full, $iban, $vs);
        $qr_message     = __('Místo držíme. Jakmile platbu přijmeme, pošleme potvrzení.', 'events-and-bookings');

        ob_start();
        include EAB_PLUGIN_DIR . 'public/partials/bank-transfer-info.php';
        return ob_get_clean();
    }

    private static function build_copy_payload($order, $account_name, $account_full, $iban, $vs) {
        $lines = array(
            sprintf(__('Částka: %s', 'events-and-bookings'), EAB_Payments::format_price($order->total)),
        );
        if ($account_name !== '') {
            $lines[] = sprintf(__('Příjemce: %s', 'events-and-bookings'), $account_name);
        }
        if ($account_full !== '') {
            $lines[] = sprintf(__('Účet: %s', 'events-and-bookings'), $account_full);
        }
        if ($iban !== '') {
            $lines[] = 'IBAN: ' . $iban;
        }
        if ($vs !== '') {
            $lines[] = sprintf(__('Variabilní symbol: %s', 'events-and-bookings'), $vs);
        }
        return implode("\n", $lines);
    }
}
