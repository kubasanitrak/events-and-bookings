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
        add_filter('acf/load_field/key=field_eab_location', array($this, 'coerce_location_field'));
        add_filter('acf/load_value/key=field_eab_location', array($this, 'normalize_location_value'), 10, 3);
    }

    /**
     * Force a text field so the Google Map widget never loads (even before JSON sync).
     *
     * @param array $field
     * @return array
     */
    public function coerce_location_field($field) {
        $field['type']         = 'text';
        $field['label']        = __('Místo – odkaz / GPS', 'events-and-bookings');
        $field['instructions'] = __('Odkaz na mapu (Google Maps, Mapy.cz) nebo GPS souřadnice (např. 49.195, 16.608). Na webu se zobrazí jako odkaz, mapa se nevykresluje.', 'events-and-bookings');
        $field['placeholder']  = 'https://mapy.cz/… nebo 49.195, 16.608';
        unset($field['center_lat'], $field['center_lng'], $field['zoom'], $field['height']);
        return $field;
    }

    /**
     * Convert legacy Google Map arrays into a maps URL for the text field.
     *
     * @param mixed $value
     * @param int|string $post_id
     * @param array $field
     * @return mixed
     */
    public function normalize_location_value($value, $post_id, $field) {
        unset($post_id, $field);

        if (!is_array($value) || !class_exists('EAB_Event')) {
            return $value;
        }

        $normalized = EAB_Event::normalize_place_link($value, true);
        return $normalized !== '' ? $normalized : '';
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
