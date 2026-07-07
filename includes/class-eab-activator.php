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
        require_once EAB_PLUGIN_DIR . 'includes/class-eab-db.php';
        EAB_DB::create_tables();
        self::create_pages();

        require_once EAB_PLUGIN_DIR . 'includes/class-eab-cron.php';
        EAB_Cron::schedule();

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
            'eab_email_sender_email'    => get_option('admin_email'),
            'eab_bank_transfer_enabled' => 1,
            'eab_gopay_enabled'           => 0,
            'eab_gopay_test_mode'         => 1,
            'eab_gopay_goid'              => '',
            'eab_gopay_client_id'         => '',
            'eab_gopay_client_secret'     => '',
            'eab_fakturoid_enabled'       => 0,
            'eab_fakturoid_slug'          => '',
            'eab_fakturoid_email'         => '',
            'eab_fakturoid_api_token'     => '',
            'eab_fakturoid_user_agent'    => 'Events and Bookings (kubasanitrak)',
            'eab_fakturoid_vat_rate'      => 21,
            'eab_order_expiry_hours'      => 24,
            'eab_bank_account_name'       => '',
            'eab_bank_account_number'     => '',
            'eab_bank_code'               => '',
            'eab_bank_iban'                 => '',
            'eab_bank_bic'                  => '',
            'eab_order_expiry_notification' => 1,
            'eab_basket_cleanup_hours'        => 72,
            'eab_admin_notification_enabled'  => 1,
            'eab_admin_notification_email'  => get_option('admin_email'),
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
            'events' => array(
                'title'   => __('Akce', 'events-and-bookings'),
                'slug'    => 'akce',
                'content' => '[eab_events_list type="event" show_filters="true" filter_action="/akce/"]',
            ),
            'trainings' => array(
                'title'   => __('Tréninky', 'events-and-bookings'),
                'slug'    => 'treninky',
                'content' => '[eab_events_list type="training" show_filters="true" filter_action="/treninky/"]',
            ),
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
                'content' => '[eab_dashboard]',
            ),
            'checkout' => array(
                'title'   => __('Rezervace – pokladna', 'events-and-bookings'),
                'slug'    => 'pokladna',
                'content' => '[eab_checkout]',
            ),
            'payment_success' => array(
                'title'   => __('Platba úspěšná', 'events-and-bookings'),
                'slug'    => 'platba-uspesna',
                'content' => '[eab_payment_success]',
            ),
            'payment_failed' => array(
                'title'   => __('Platba neúspěšná', 'events-and-bookings'),
                'slug'    => 'platba-neuspesna',
                'content' => '[eab_payment_failed]',
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
        if (!is_array($ids) || empty($ids['login']) || !get_post($ids['login'])
            || empty($ids['checkout']) || !get_post($ids['checkout'])
            || empty($ids['payment_success']) || !get_post($ids['payment_success'])
            || empty($ids['payment_failed']) || !get_post($ids['payment_failed'])
            || empty($ids['events']) || !get_post($ids['events'])
            || empty($ids['trainings']) || !get_post($ids['trainings'])) {
            self::create_pages();
            // Archives were switched off in favour of these pages; flush stale
            // rewrite rules once so /akce/ and /treninky/ route to the pages.
            flush_rewrite_rules();
        }
    }
}
