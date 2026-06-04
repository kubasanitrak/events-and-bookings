<?php
/**
 * Plugin loader.
 */

if (!defined('ABSPATH')) {
    exit;
}

class EAB_Loader {

    public function __construct() {
        $this->load_dependencies();
    }

    private function load_dependencies() {
        require_once EAB_PLUGIN_DIR . 'includes/class-eab-activator.php';
        require_once EAB_PLUGIN_DIR . 'includes/class-eab-settings.php';
        require_once EAB_PLUGIN_DIR . 'includes/class-eab-post-types.php';
        require_once EAB_PLUGIN_DIR . 'includes/class-eab-roles.php';
        require_once EAB_PLUGIN_DIR . 'includes/class-eab-acf.php';
        require_once EAB_PLUGIN_DIR . 'includes/class-eab-access.php';
        require_once EAB_PLUGIN_DIR . 'includes/class-eab-basket.php';
        require_once EAB_PLUGIN_DIR . 'includes/class-eab-invoice.php';
        require_once EAB_PLUGIN_DIR . 'includes/class-eab-user-profile.php';
        require_once EAB_PLUGIN_DIR . 'includes/class-eab-event.php';
        require_once EAB_PLUGIN_DIR . 'includes/class-eab-query.php';
        require_once EAB_PLUGIN_DIR . 'includes/class-eab-shortcodes.php';
        require_once EAB_PLUGIN_DIR . 'includes/class-eab-emails.php';
        require_once EAB_PLUGIN_DIR . 'includes/class-eab-auth.php';

        if (is_admin()) {
            require_once EAB_PLUGIN_DIR . 'admin/class-eab-admin.php';
            require_once EAB_PLUGIN_DIR . 'admin/class-eab-admin-settings.php';
        }

        require_once EAB_PLUGIN_DIR . 'public/class-eab-public.php';
    }

    public function run() {
        EAB_Settings::ensure_defaults();
        add_action('init', array('EAB_Activator', 'maybe_create_pages'), 15);

        new EAB_Post_Types();
        new EAB_Roles();
        new EAB_Settings();
        new EAB_Basket();
        new EAB_User_Profile();
        new EAB_Shortcodes();
        new EAB_Auth();

        if (class_exists('ACF')) {
            new EAB_ACF();
        }

        if (is_admin()) {
            new EAB_Admin();
            new EAB_Admin_Settings();
        }

        new EAB_Public();
    }
}
