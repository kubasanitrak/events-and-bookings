<?php
/**
 * Admin orders list and actions.
 */

if (!defined('ABSPATH')) {
    exit;
}

class EAB_Admin_Orders {

    public function __construct() {
        add_action('admin_menu', array($this, 'register_menu'), 11);
        add_action('admin_init', array($this, 'handle_actions'));
    }

    public static function actionable_statuses() {
        return array('pending', 'awaiting_payment', 'processing');
    }

    public function register_menu() {
        add_submenu_page(
            EAB_Admin::MENU_SLUG,
            __('Objednávky', 'events-and-bookings'),
            __('Objednávky', 'events-and-bookings'),
            'manage_options',
            'eab-orders',
            array($this, 'render_page')
        );
    }

    public function handle_actions() {
        if (!isset($_GET['eab_action'], $_GET['order_id'], $_GET['_wpnonce'])) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'eab_admin_order')) {
            return;
        }

        $order_id = (int) $_GET['order_id'];
        $order    = EAB_Checkout::get_order($order_id);

        if (!$order) {
            return;
        }

        $action = sanitize_key(wp_unslash($_GET['eab_action']));
        $redirect = admin_url('admin.php?page=eab-orders');

        switch ($action) {
            case 'confirm_payment':
                if (in_array($order->status, self::actionable_statuses(), true)) {
                    EAB_Payments::complete_payment($order_id, 'manual_' . get_current_user_id());
                    $redirect = add_query_arg('eab_msg', 'payment_confirmed', $redirect);
                }
                break;

            case 'cancel_order':
                if (in_array($order->status, self::actionable_statuses(), true)) {
                    EAB_Checkout::cancel_order($order_id);
                    $redirect = add_query_arg('eab_msg', 'order_cancelled', $redirect);
                }
                break;
        }

        wp_safe_redirect($redirect);
        exit;
    }

    public function render_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        global $wpdb;

        if (!EAB_DB::table_exists('eab_orders')) {
            echo '<div class="wrap"><p>' . esc_html__('Tabulky objednávek nejsou vytvořené. Načtěte libovolnou stránku webu pro migraci databáze.', 'events-and-bookings') . '</p></div>';
            return;
        }

        $status_filter = isset($_GET['status']) ? sanitize_key(wp_unslash($_GET['status'])) : '';
        $paged         = max(1, (int) ($_GET['paged'] ?? 1));
        $per_page      = 20;
        $offset        = ($paged - 1) * $per_page;

        $table = $wpdb->prefix . 'eab_orders';
        $where = '1=1';
        $params = array();

        if ($status_filter !== '') {
            $where .= ' AND o.status = %s';
            $params[] = $status_filter;
        }

        $sql_count = "SELECT COUNT(*) FROM $table o WHERE $where";
        $total = $params
            ? (int) $wpdb->get_var($wpdb->prepare($sql_count, $params))
            : (int) $wpdb->get_var($sql_count);

        $sql = "SELECT o.*, u.display_name, u.user_email
                FROM $table o
                LEFT JOIN {$wpdb->users} u ON o.user_id = u.ID
                WHERE $where
                ORDER BY o.created_at DESC
                LIMIT %d OFFSET %d";

        $query_params = array_merge($params, array($per_page, $offset));
        $orders = $wpdb->get_results($wpdb->prepare($sql, $query_params));

        $total_pages = max(1, (int) ceil($total / $per_page));
        $actionable_statuses = self::actionable_statuses();

        include EAB_PLUGIN_DIR . 'admin/partials/orders-page.php';
    }
}
