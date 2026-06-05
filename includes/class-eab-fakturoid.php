<?php
/**
 * Fakturoid API v3 — invoice on paid order.
 */

if (!defined('ABSPATH')) {
    exit;
}

class EAB_Fakturoid {

    const SUBDIR = 'events-and-bookings/invoices';

    public static function is_enabled() {
        return (bool) get_option('eab_fakturoid_enabled', 0)
            && get_option('eab_fakturoid_slug', '')
            && get_option('eab_fakturoid_email', '')
            && get_option('eab_fakturoid_api_token', '');
    }

    public static function api_base() {
        $slug = sanitize_title(get_option('eab_fakturoid_slug', ''));
        return 'https://app.fakturoid.cz/api/v3/accounts/' . $slug;
    }

    /**
     * Create invoice for paid order (idempotent if already exists).
     *
     * @return array|WP_Error
     */
    public static function create_invoice_for_order($order_id) {
        if (!self::is_enabled()) {
            return new WP_Error('fakturoid_disabled', 'Fakturoid disabled');
        }

        $order = EAB_Checkout::get_order($order_id);
        if (!$order || $order->status !== 'paid') {
            return new WP_Error('invalid_order', 'Order not paid');
        }

        if (!empty($order->fakturoid_invoice_id)) {
            return array(
                'invoice_id' => $order->fakturoid_invoice_id,
                'pdf'        => $order->fakturoid_pdf,
            );
        }

        $subject_id = self::ensure_subject($order);
        if (is_wp_error($subject_id)) {
            return $subject_id;
        }

        $lines = array();
        foreach ($order->items as $item) {
            $lines[] = array(
                'name'        => $item->post_title,
                'quantity'    => max(1, (int) $item->qty),
                'unit_price'  => (float) $item->unit_price,
                'vat_rate'    => (int) get_option('eab_fakturoid_vat_rate', 21),
            );
        }

        $invoice_payload = array(
            'subject_id'    => $subject_id,
            'lines'         => $lines,
            'document_type' => 'invoice',
            'order_number'  => $order->order_number,
            'note'          => sprintf(__('Objednávka %s', 'events-and-bookings'), $order->order_number),
        );

        $created = self::api_request('POST', '/invoices.json', $invoice_payload);
        if (is_wp_error($created)) {
            EAB_Payments::log('fakturoid_create_failed', $created->get_error_message(), array('order_id' => $order_id));
            return $created;
        }

        $invoice_id = isset($created['id']) ? (int) $created['id'] : 0;
        if (!$invoice_id) {
            return new WP_Error('fakturoid_create', __('Fakturoid nevrátil ID faktury.', 'events-and-bookings'));
        }

        if ($order->payment_method === 'gopay') {
            self::record_gopay_payment($invoice_id, $order);
        }

        $pdf_relative = self::download_invoice_pdf($invoice_id, $order->order_number);

        self::update_order_invoice_meta($order_id, array(
            'fakturoid_invoice_id'     => (string) $invoice_id,
            'fakturoid_invoice_number' => isset($created['number']) ? $created['number'] : '',
            'fakturoid_pdf'            => $pdf_relative,
        ));

        return array(
            'invoice_id' => $invoice_id,
            'number'     => $created['number'] ?? '',
            'pdf'        => $pdf_relative,
        );
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
                'custom_id' => $custom_id,
                'name'      => $invoice['company_name'],
                'street'    => trim(($invoice['street'] ?? '') . ' ' . ($invoice['street_number'] ?? '')),
                'city'      => $invoice['city'] ?? '',
                'zip'       => $invoice['zip'] ?? '',
                'country'   => 'CZ',
                'registration_no' => preg_replace('/\D/', '', $invoice['ic'] ?? ''),
                'vat_no'    => $invoice['dic'] ?? '',
                'email'     => $user->user_email,
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

    private static function record_gopay_payment($invoice_id, $order) {
        self::api_request('POST', '/invoices/' . $invoice_id . '/payments.json', array(
            'paid_on'         => gmdate('Y-m-d'),
            'amount'          => (float) $order->total,
            'variable_symbol' => preg_replace('/\D/', '', $order->order_number),
            'account'         => 'GoPay',
        ));
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
        $wpdb->update(
            $wpdb->prefix . 'eab_orders',
            $fields,
            array('id' => $order_id),
            array('%s', '%s', '%s'),
            array('%d')
        );
    }

    /**
     * @return array|WP_Error|string raw body for PDF
     */
    private static function api_request($method, $path, $body = null, $raw = false) {
        $email = get_option('eab_fakturoid_email', '');
        $token = get_option('eab_fakturoid_api_token', '');
        $ua    = get_option('eab_fakturoid_user_agent', 'Events and Bookings (kubasanitrak)');

        $args = array(
            'method'  => $method,
            'timeout' => 45,
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($email . ':' . $token),
                'User-Agent'    => $ua,
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
