<?php
/**
 * ACF Pro integration (JSON field groups from plugin or theme).
 */

if (!defined('ABSPATH')) {
    exit;
}

class EAB_ACF {

    public function __construct() {
        add_filter('acf/settings/load_json', array($this, 'add_json_load_path'));
        add_filter('acf/settings/save_json', array($this, 'save_json_path'));
    }

    public function add_json_load_path($paths) {
        $plugin_json = EAB_PLUGIN_DIR . 'acf-json';
        if (is_dir($plugin_json)) {
            $paths[] = $plugin_json;
        }
        return $paths;
    }

    public function save_json_path($path) {
        $dir = EAB_PLUGIN_DIR . 'acf-json';
        if (self::should_save_json_to_plugin() && is_dir($dir)) {
            return $dir;
        }
        return $path;
    }

    /**
     * Save field group exports into the plugin (default on).
     */
    public static function should_save_json_to_plugin() {
        if (defined('EAB_ACF_SAVE_JSON')) {
            return (bool) EAB_ACF_SAVE_JSON;
        }
        return true;
    }

    /**
     * Whether ACF is available for field UI.
     */
    public static function is_active() {
        return function_exists('get_field');
    }
}
