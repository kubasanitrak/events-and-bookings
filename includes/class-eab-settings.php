<?php
/**
 * Plugin settings and feature flags.
 */

if (!defined('ABSPATH')) {
    exit;
}

class EAB_Settings {

    /** Attendee names on event detail: only logged-in members (and admins). */
    const ATTENDEE_LIST_VISIBILITY = 'logged_in';

    const OPT_BASKET_MULTIPLE_EVENTS = 'eab_basket_allow_multiple_events';
    const OPT_CHECKOUT_INVOICE_ENABLED = 'eab_checkout_invoice_enabled';

    public function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
    }

    public static function defaults() {
        return array(
            self::OPT_BASKET_MULTIPLE_EVENTS   => 0,
            self::OPT_CHECKOUT_INVOICE_ENABLED => 1,
        );
    }

    public static function ensure_defaults() {
        foreach (self::defaults() as $key => $value) {
            if (get_option($key) === false) {
                update_option($key, $value);
            }
        }
    }

    /**
     * Whether the basket may contain more than one event/training at once.
     * Default false (one event per basket); enable later via option or filter.
     */
    public static function basket_allows_multiple_events() {
        $allowed = (bool) get_option(self::OPT_BASKET_MULTIPLE_EVENTS, 0);
        return (bool) apply_filters('eab_basket_allow_multiple_events', $allowed);
    }

    /**
     * Minimum spots (attendees) per basket line — always at least one.
     */
    public static function basket_min_spots_per_item() {
        $min = 1;
        return max(1, (int) apply_filters('eab_basket_min_spots_per_item', $min));
    }

    public static function checkout_invoice_enabled() {
        return (bool) apply_filters(
            'eab_checkout_invoice_enabled',
            (bool) get_option(self::OPT_CHECKOUT_INVOICE_ENABLED, 1)
        );
    }

    public function register_settings() {
        register_setting('eab_settings', self::OPT_BASKET_MULTIPLE_EVENTS, array(
            'type'              => 'boolean',
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
            'default'           => 0,
        ));
        register_setting('eab_settings', self::OPT_CHECKOUT_INVOICE_ENABLED, array(
            'type'              => 'boolean',
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
            'default'           => 1,
        ));
    }

    public function sanitize_checkbox($value) {
        return !empty($value) ? 1 : 0;
    }
}
