<?php
/**
 * QR platby (Paylibo SPAYD) pro český bankovní převod.
 */

if (!defined('ABSPATH')) {
    exit;
}

class EAB_QR_Generator {

    private $api_url = 'https://api.paylibo.com/paylibo/generator/czech/image';

    public function generate_payment_qr($amount, $currency = 'CZK', $variable_symbol = '', $message = '', $size = 250) {
        $account_number = get_option('eab_bank_account_number', '');
        $bank_code      = get_option('eab_bank_code', '');

        if ($account_number === '' || $bank_code === '') {
            return null;
        }

        $vs = substr(preg_replace('/\D/', '', $variable_symbol), 0, 10);

        $params = array(
            'accountNumber' => $this->sanitize_account_number($account_number),
            'bankCode'      => $this->sanitize_bank_code($bank_code),
            'amount'        => number_format((float) $amount, 2, '.', ''),
            'currency'      => strtoupper($currency),
            'size'          => (int) $size,
        );

        if ($vs !== '') {
            $params['vs'] = $vs;
        }

        if ($message !== '') {
            $params['message'] = $this->sanitize_message($message);
        }

        return add_query_arg($params, $this->api_url);
    }

    public function generate_order_qr($order, $size = 250) {
        if (!$order) {
            return null;
        }

        $message = sprintf(__('Objednávka %s', 'events-and-bookings'), $order->order_number);

        return $this->generate_payment_qr(
            $order->total,
            $order->currency ?: 'CZK',
            $order->order_number,
            $message,
            $size
        );
    }

    public function render_qr_html($amount, $variable_symbol = '', $size = 250) {
        $url = $this->generate_payment_qr($amount, 'CZK', $variable_symbol, '', $size);

        if (!$url) {
            return '<p class="eab-qr-error">' . esc_html__('QR kód nelze vygenerovat. Použijte údaje výše.', 'events-and-bookings') . '</p>';
        }

        return sprintf(
            '<div class="eab-qr-code"><img src="%s" alt="%s" width="%d" height="%d" loading="lazy"></div>',
            esc_url($url),
            esc_attr__('QR platba', 'events-and-bookings'),
            (int) $size,
            (int) $size
        );
    }

    private function sanitize_account_number($account_number) {
        $account_number = preg_replace('/\s+/', '', $account_number);
        if (strpos($account_number, '-') !== false) {
            list($prefix, $base) = explode('-', $account_number, 2);
            $prefix = ltrim($prefix, '0');
            $base   = ltrim($base, '0');
            return $prefix !== '' ? $prefix . '-' . $base : $base;
        }
        return ltrim($account_number, '0');
    }

    private function sanitize_bank_code($bank_code) {
        return str_pad(preg_replace('/\D/', '', $bank_code), 4, '0', STR_PAD_LEFT);
    }

    private function sanitize_message($message) {
        $message = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $message);
        $message = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $message);
        return substr(trim($message), 0, 60);
    }
}
