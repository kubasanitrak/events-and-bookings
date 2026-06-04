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
        require_once EAB_PLUGIN_DIR . 'includes/class-eab-db.php';
        require_once EAB_PLUGIN_DIR . 'includes/class-eab-capacity.php';
        require_once EAB_PLUGIN_DIR . 'includes/class-eab-pricing.php';
        require_once EAB_PLUGIN_DIR . 'includes/class-eab-emails.php';
        require_once EAB_PLUGIN_DIR . 'includes/class-eab-auth.php';
        require_once EAB_PLUGIN_DIR . 'includes/class-eab-payments.php';
        require_once EAB_PLUGIN_DIR . 'includes/class-eab-checkout.php';
        require_once EAB_PLUGIN_DIR . 'includes/class-eab-bank-transfer.php';
        require_once EAB_PLUGIN_DIR . 'includes/class-eab-dashboard.php';

        require_once EAB_PLUGIN_DIR . 'includes/class-eab-qr-generator.php';
        require_once EAB_PLUGIN_DIR . 'includes/class-eab-cron.php';

        if (is_admin()) {
            require_once EAB_PLUGIN_DIR . 'admin/class-eab-admin.php';
            require_once EAB_PLUGIN_DIR . 'admin/class-eab-admin-settings.php';
            require_once EAB_PLUGIN_DIR . 'admin/class-eab-admin-orders.php';
        }

        require_once EAB_PLUGIN_DIR . 'public/class-eab-public.php';
    }

    public function run() {
        EAB_Settings::ensure_defaults();
        EAB_DB::maybe_upgrade();
        EAB_Cron::schedule();
        add_action('init', array('EAB_Activator', 'maybe_create_pages'), 15);

        new EAB_Post_Types();
        new EAB_Roles();
        new EAB_Settings();
        new EAB_Basket();
        new EAB_User_Profile();
        new EAB_Shortcodes();
        new EAB_Auth();
        new EAB_Checkout();
        new EAB_Dashboard();
        new EAB_Cron();

        if (class_exists('ACF')) {
            new EAB_ACF();
        }

        if (is_admin()) {
            new EAB_Admin();
            new EAB_Admin_Settings();
            new EAB_Admin_Orders();
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        }

        new EAB_Public();
    }

    public function enqueue_admin_assets($hook) {
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        $allowed = array(EAB_Admin::MENU_SLUG, 'eab-orders', 'eab-settings');
        if (!in_array($page, $allowed, true) && strpos($hook, 'eab') === false) {
            return;
        }
        wp_enqueue_style('eab-admin', EAB_PLUGIN_URL . 'admin/css/admin.css', array(), EAB_VERSION);
    }
}
