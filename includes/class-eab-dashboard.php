<?php
/**
 * Member dashboard (bookings overview).
 */

if (!defined('ABSPATH')) {
    exit;
}

class EAB_Dashboard {

    public function __construct() {
        add_shortcode('eab_dashboard', array($this, 'render'));
    }

    public function render() {
        if (!is_user_logged_in()) {
            return '<p>' . esc_html__('Přihlaste se pro zobrazení účtu.', 'events-and-bookings') . '</p>';
        }

        $orders = $this->get_user_orders(get_current_user_id());

        ob_start();
        include EAB_PLUGIN_DIR . 'public/partials/dashboard-page.php';
        return ob_get_clean();
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
}
