<?php
/**
 * Plugin deactivation.
 */

if (!defined('ABSPATH')) {
    exit;
}

class EAB_Deactivator {

    public static function deactivate() {
        flush_rewrite_rules();
    }
}
