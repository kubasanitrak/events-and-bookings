<?php
/**
 * Optional company invoice data at checkout (DL-compatible shape).
 */

if (!defined('ABSPATH')) {
    exit;
}

class EAB_Invoice {

    const META_PREFIX = 'eab_invoice_';

    /**
     * Field map: form/request key => user meta suffix.
     */
    public static function field_map() {
        return array(
            'company_name'  => 'company_name',
            'street'        => 'street',
            'street_number' => 'street_number',
            'city'          => 'city',
            'zip'           => 'zip',
            'ic'            => 'ic',
            'dic'           => 'dic',
        );
    }

    public static function meta_key($suffix) {
        return self::META_PREFIX . $suffix;
    }

    public static function get_user_invoice_data($user_id) {
        $data = array();
        foreach (self::field_map() as $form_key => $suffix) {
            $data[$form_key] = get_user_meta($user_id, self::meta_key($suffix), true);
        }
        return $data;
    }

    public static function user_has_saved_invoice($user_id) {
        $data = self::get_user_invoice_data($user_id);
        return !empty($data['company_name']) || !empty($data['ic']);
    }

    /**
     * Parse invoice payload from checkout POST/JSON. All fields optional unless want_invoice.
     *
     * @param array $source Raw input (e.g. $_POST).
     * @param bool  $want_invoice User requested an invoice.
     * @return array|WP_Error
     */
    public static function parse_checkout_input(array $source, $want_invoice = false) {
        if (!$want_invoice) {
            return null;
        }

        if (!EAB_Settings::checkout_invoice_enabled()) {
            return new WP_Error('eab_invoice_disabled', __('Fakturační údaje nejsou k dispozici.', 'events-and-bookings'));
        }

        $data = array();
        foreach (self::field_map() as $form_key => $suffix) {
            $post_key = 'invoice_' . $form_key;
            $data[$form_key] = isset($source[$post_key])
                ? sanitize_text_field(wp_unslash($source[$post_key]))
                : '';
        }

        $required = array('company_name', 'street', 'city', 'zip', 'ic');
        foreach ($required as $key) {
            if (empty($data[$key])) {
                return new WP_Error(
                    'eab_invoice_required',
                    __('Vyplňte prosím povinné fakturační údaje (včetně IČ).', 'events-and-bookings')
                );
            }
        }

        return $data;
    }

    /**
     * Persist invoice fields to user profile when requested at checkout.
     */
    public static function save_to_user_profile($user_id, array $data) {
        foreach (self::field_map() as $form_key => $suffix) {
            if (isset($data[$form_key])) {
                update_user_meta($user_id, self::meta_key($suffix), $data[$form_key]);
            }
        }
    }

    /**
     * Store on order row (JSON) — used when orders table exists.
     */
    public static function encode_for_order(array $data) {
        return wp_json_encode($data);
    }
}
