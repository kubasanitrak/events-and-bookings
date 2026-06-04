<?php
/**
 * Plugin deactivation.
 */

if (!defined('ABSPATH')) {
    exit;
}

class EAB_Deactivator {

    public static function deactivate() {
        require_once EAB_PLUGIN_DIR . 'includes/class-eab-cron.php';
        EAB_Cron::unschedule();
        flush_rewrite_rules();
    }
}
