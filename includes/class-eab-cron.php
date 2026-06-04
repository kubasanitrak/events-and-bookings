<?php
/**
 * Scheduled maintenance: expiring orders, basket cleanup.
 */

if (!defined('ABSPATH')) {
    exit;
}

class EAB_Cron {

    public function __construct() {
        add_action('eab_hourly_cron', array($this, 'hourly_tasks'));
        add_action('eab_daily_cron', array($this, 'daily_tasks'));
    }

    public static function schedule() {
        if (!wp_next_scheduled('eab_hourly_cron')) {
            wp_schedule_event(time(), 'hourly', 'eab_hourly_cron');
        }
        if (!wp_next_scheduled('eab_daily_cron')) {
            wp_schedule_event(time(), 'daily', 'eab_daily_cron');
        }
    }

    public static function unschedule() {
        wp_clear_scheduled_hook('eab_hourly_cron');
        wp_clear_scheduled_hook('eab_daily_cron');
    }

    public function hourly_tasks() {
        $this->send_expiry_notifications();
        $this->expire_old_orders();
    }

    public function daily_tasks() {
        $this->cleanup_old_baskets();
        $this->cleanup_old_logs();
    }

    private function send_expiry_notifications() {
        if (!get_option('eab_order_expiry_notification', 1)) {
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'eab_orders';

        if (!EAB_DB::table_exists('eab_orders')) {
            return;
        }

        $orders = $wpdb->get_results(
            "SELECT * FROM $table
             WHERE status = 'awaiting_payment'
             AND expires_at IS NOT NULL
             AND expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 2 HOUR)"
        );

        foreach ($orders as $order) {
            if ($this->expiry_notification_sent($order->id)) {
                continue;
            }
            EAB_Emails::send_expiry_notification((int) $order->id);
            EAB_Payments::log('expiry_notification_' . $order->id, 'Expiry reminder sent', array('order_id' => $order->id));
        }
    }

    private function expiry_notification_sent($order_id) {
        global $wpdb;
        if (!EAB_DB::table_exists('eab_logs')) {
            return false;
        }
        $type = 'expiry_notification_' . (int) $order_id;
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}eab_logs WHERE type = %s LIMIT 1",
            $type
        ));
    }

    private function expire_old_orders() {
        global $wpdb;
        $table = $wpdb->prefix . 'eab_orders';

        if (!EAB_DB::table_exists('eab_orders')) {
            return;
        }

        $ids = $wpdb->get_col(
            "SELECT id FROM $table
             WHERE status IN ('pending', 'awaiting_payment')
             AND expires_at IS NOT NULL
             AND expires_at < NOW()"
        );

        foreach ($ids as $order_id) {
            EAB_Checkout::update_order_status((int) $order_id, 'expired');
            EAB_Payments::log('order_expired', 'Order expired', array('order_id' => (int) $order_id));
        }
    }

    private function cleanup_old_baskets() {
        global $wpdb;
        if (!EAB_DB::table_exists('eab_basket')) {
            return;
        }
        $hours = (int) get_option('eab_basket_cleanup_hours', 72);
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->prefix}eab_basket WHERE added_at < DATE_SUB(NOW(), INTERVAL %d HOUR)",
            $hours
        ));
    }

    private function cleanup_old_logs() {
        global $wpdb;
        if (!EAB_DB::table_exists('eab_logs')) {
            return;
        }
        $wpdb->query(
            "DELETE FROM {$wpdb->prefix}eab_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)"
        );
    }
}
