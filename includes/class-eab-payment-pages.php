<?php
/**
 * Payment return pages (GoPay success / failure).
 */

if (!defined('ABSPATH')) {
    exit;
}

class EAB_Payment_Pages {

    public function __construct() {
        add_shortcode('eab_payment_success', array($this, 'render_success'));
        add_shortcode('eab_payment_failed', array($this, 'render_failed'));
    }

    public function render_success() {
        $order_id = isset($_GET['order']) ? (int) $_GET['order'] : 0;
        $order    = $order_id ? EAB_Checkout::get_order($order_id) : null;

        if ($order && (int) $order->user_id !== get_current_user_id() && !current_user_can('manage_options')) {
            $order = null;
        }

        ob_start();
        include EAB_PLUGIN_DIR . 'public/partials/payment-success.php';
        return ob_get_clean();
    }

    public function render_failed() {
        $order_id = isset($_GET['order']) ? (int) $_GET['order'] : 0;
        $order    = $order_id ? EAB_Checkout::get_order($order_id) : null;

        if ($order && (int) $order->user_id !== get_current_user_id() && !current_user_can('manage_options')) {
            $order = null;
        }

        ob_start();
        include EAB_PLUGIN_DIR . 'public/partials/payment-failed.php';
        return ob_get_clean();
    }
}
