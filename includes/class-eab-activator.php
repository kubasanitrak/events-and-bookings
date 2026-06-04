<?php
/**
 * Plugin activation.
 */

if (!defined('ABSPATH')) {
    exit;
}

class EAB_Activator {

    public static function activate() {
        require_once EAB_PLUGIN_DIR . 'includes/class-eab-settings.php';
        require_once EAB_PLUGIN_DIR . 'includes/class-eab-post-types.php';
        require_once EAB_PLUGIN_DIR . 'includes/class-eab-roles.php';

        $post_types = new EAB_Post_Types();
        $post_types->register_post_types();
        $post_types->register_taxonomies();
        EAB_Post_Types::seed_default_terms();

        EAB_Roles::register_role();
        EAB_Settings::ensure_defaults();
        self::set_default_options();
        self::create_pages();

        flush_rewrite_rules();

        update_option('eab_plugin_activated', true);
        update_option('eab_db_version', EAB_VERSION);
    }

    public static function set_default_options() {
        $defaults = array(
            'eab_currency_code'       => 'CZK',
            'eab_currency_symbol'     => 'Kč',
            'eab_currency_position'   => 'after',
            'eab_terms_page'          => 0,
            'eab_gdpr_page'           => 0,
            'eab_login_page'          => 0,
            'eab_register_page'       => 0,
            'eab_email_sender_name'   => get_bloginfo('name'),
            'eab_email_sender_email'  => get_option('admin_email'),
        );

        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                update_option($key, $value);
            }
        }
    }

    /**
     * Create front-end pages with auth shortcodes (Czech slugs).
     */
    public static function create_pages() {
        $pages = array(
            'register' => array(
                'title'   => __('Registrace', 'events-and-bookings'),
                'slug'    => 'registrace',
                'content' => '[eab_register]',
            ),
            'login' => array(
                'title'   => __('Přihlášení', 'events-and-bookings'),
                'slug'    => 'prihlaseni',
                'content' => '[eab_login]',
            ),
            'set_password' => array(
                'title'   => __('Nastavení hesla', 'events-and-bookings'),
                'slug'    => 'nastaveni-hesla',
                'content' => '[eab_set_password]',
            ),
            'dashboard' => array(
                'title'   => __('Můj účet', 'events-and-bookings'),
                'slug'    => 'muj-ucet',
                'content' => '<!-- eab_dashboard phase 4 -->',
            ),
        );

        $page_ids = get_option('eab_page_ids', array());
        if (!is_array($page_ids)) {
            $page_ids = array();
        }

        foreach ($pages as $key => $page) {
            $existing = get_page_by_path($page['slug']);
            if (!$existing) {
                $page_id = wp_insert_post(array(
                    'post_title'   => $page['title'],
                    'post_name'    => $page['slug'],
                    'post_content' => $page['content'],
                    'post_status'  => 'publish',
                    'post_type'    => 'page',
                    'post_author'  => 1,
                ));
                $page_ids[$key] = $page_id;
            } else {
                $page_ids[$key] = $existing->ID;
            }
        }

        update_option('eab_page_ids', $page_ids);

        if (!empty($page_ids['login'])) {
            update_option('eab_login_page', (int) $page_ids['login']);
        }
        if (!empty($page_ids['register'])) {
            update_option('eab_register_page', (int) $page_ids['register']);
        }
    }

    /**
     * Ensure auth pages exist (upgrade without re-activation).
     */
    public static function maybe_create_pages() {
        $ids = get_option('eab_page_ids', array());
        if (!is_array($ids) || empty($ids['login']) || !get_post($ids['login'])) {
            self::create_pages();
        }
    }
}
