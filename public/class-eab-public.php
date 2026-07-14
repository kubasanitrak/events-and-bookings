<?php
/**
 * Public-facing assets.
 */

if (!defined('ABSPATH')) {
    exit;
}

class EAB_Public {

    private static $enqueued = false;

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'maybe_enqueue_assets'), 20);
        add_action('wp_footer', array($this, 'maybe_enqueue_assets_late'), 1);
    }

    public function maybe_enqueue_assets() {
        if ($this->should_load_assets()) {
            $this->enqueue_assets();
        }
    }

    /**
     * Shortcodes render after wp_enqueue_scripts — load assets in footer if needed.
     */
    public function maybe_enqueue_assets_late() {
        if (apply_filters('eab_enqueue_public_assets', false)) {
            $this->enqueue_assets();
        }
    }

    private function should_load_assets() {
        if (apply_filters('eab_enqueue_public_assets', false)) {
            return true;
        }
        if (is_singular(EAB_Post_Types::get_bookable_post_types())) {
            return true;
        }
        if (is_post_type_archive(EAB_Post_Types::get_bookable_post_types())) {
            return true;
        }
        if (is_singular('page') && $this->current_page_has_shortcode()) {
            return true;
        }
        return false;
    }

    private function current_page_has_shortcode() {
        $post = get_post();
        if (!$post || empty($post->post_content)) {
            return false;
        }
        $tags = array(
            'eab_events_grid',
            'eab_events_list',
            'eab_trainings_list',
            'eab_event_detail',
            'eab_book_button',
            'eab_register',
            'eab_login',
            'eab_set_password',
            'eab_checkout',
            'eab_dashboard',
            'eab_basket_count',
        );
        foreach ($tags as $tag) {
            if (has_shortcode($post->post_content, $tag)) {
                return true;
            }
        }
        return false;
    }

    private function enqueue_assets() {
        if (self::$enqueued) {
            return;
        }
        self::$enqueued = true;

        wp_enqueue_style(
            'eab-public',
            EAB_PLUGIN_URL . 'public/css/public.css',
            array(),
            EAB_VERSION
        );

        wp_enqueue_script(
            'eab-public',
            EAB_PLUGIN_URL . 'public/js/public.js',
            array('jquery'),
            EAB_VERSION,
            true
        );

        wp_localize_script('eab-public', 'eab_public', array(
            'ajax_url'      => admin_url('admin-ajax.php'),
            'nonce'         => wp_create_nonce('eab_public'),
            'is_logged_in'  => is_user_logged_in(),
            'checkout_url'  => EAB_Auth::get_page_url('checkout'),
            'i18n'          => array(
                'confirm_cancel'  => __('Opravdu chcete zrušit tuto rezervaci?', 'events-and-bookings'),
                'reschedule_soon' => __('Funkce přesunu rezervace bude brzy k dispozici. Kontaktujte nás prosím.', 'events-and-bookings'),
            ),
        ));
    }
}
