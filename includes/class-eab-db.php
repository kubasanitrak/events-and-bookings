<?php
/**
 * Database schema and migrations.
 */

if (!defined('ABSPATH')) {
    exit;
}

class EAB_DB {

    public static function maybe_upgrade() {
        $installed = get_option('eab_db_version', '');
        if (version_compare($installed, EAB_VERSION, '>=')) {
            return;
        }
        self::create_tables();
        update_option('eab_db_version', EAB_VERSION);
    }

    public static function create_tables() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset = $wpdb->get_charset_collate();

        $basket = $wpdb->prefix . 'eab_basket';
        dbDelta("CREATE TABLE $basket (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            object_id bigint(20) UNSIGNED NOT NULL,
            object_type varchar(32) NOT NULL,
            line_meta longtext DEFAULT NULL,
            added_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_object (user_id, object_id, object_type),
            KEY user_id (user_id)
        ) $charset;");

        $orders = $wpdb->prefix . 'eab_orders';
        dbDelta("CREATE TABLE $orders (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            order_number varchar(32) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            payment_method varchar(50) NOT NULL,
            total decimal(10,2) NOT NULL DEFAULT 0.00,
            discount decimal(10,2) NOT NULL DEFAULT 0.00,
            currency varchar(3) NOT NULL DEFAULT 'CZK',
            transaction_id varchar(100) DEFAULT NULL,
            invoice_data longtext DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            expires_at datetime DEFAULT NULL,
            paid_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY order_number (order_number),
            KEY status (status)
        ) $charset;");

        $order_items = $wpdb->prefix . 'eab_order_items';
        dbDelta("CREATE TABLE $order_items (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id bigint(20) UNSIGNED NOT NULL,
            object_id bigint(20) UNSIGNED NOT NULL,
            object_type varchar(32) NOT NULL,
            qty int(11) NOT NULL DEFAULT 1,
            unit_price decimal(10,2) NOT NULL DEFAULT 0.00,
            line_total decimal(10,2) NOT NULL DEFAULT 0.00,
            line_meta longtext DEFAULT NULL,
            PRIMARY KEY (id),
            KEY order_id (order_id),
            KEY object (object_id, object_type)
        ) $charset;");

        $spots = $wpdb->prefix . 'eab_booking_spots';
        dbDelta("CREATE TABLE $spots (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id bigint(20) UNSIGNED NOT NULL,
            order_item_id bigint(20) UNSIGNED NOT NULL,
            object_id bigint(20) UNSIGNED NOT NULL,
            object_type varchar(32) NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            spot_type varchar(16) NOT NULL DEFAULT 'regular',
            status varchar(16) NOT NULL DEFAULT 'held',
            attendee_index int(11) NOT NULL DEFAULT 0,
            attendee_data longtext DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY object_status (object_id, object_type, status),
            KEY order_id (order_id),
            KEY user_id (user_id)
        ) $charset;");

        $logs = $wpdb->prefix . 'eab_logs';
        dbDelta("CREATE TABLE $logs (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            type varchar(50) NOT NULL,
            message text NOT NULL,
            data longtext,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY type (type)
        ) $charset;");
    }

    public static function table_exists($table_suffix) {
        global $wpdb;
        $table = $wpdb->prefix . $table_suffix;
        return $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table;
    }
}
