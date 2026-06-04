<?php
/**
 * Front-end shortcodes.
 */

if (!defined('ABSPATH')) {
    exit;
}

class EAB_Shortcodes {

    private static $assets_needed = false;

    public function __construct() {
        add_shortcode('eab_events_grid', array($this, 'events_grid'));
        add_shortcode('eab_events_list', array($this, 'events_list'));
        add_shortcode('eab_event_detail', array($this, 'event_detail'));
        add_shortcode('eab_book_button', array($this, 'book_button'));
        add_filter('eab_enqueue_public_assets', array($this, 'force_enqueue_assets'));
    }

    public function force_enqueue_assets($load) {
        return $load || self::$assets_needed;
    }

    private function flag_assets() {
        self::$assets_needed = true;
    }

    /**
     * [eab_events_grid type="event|training" ids="1,2,3" limit="6" title="..." layout="grid|section"]
     */
    public function events_grid($atts) {
        $this->flag_assets();
        $atts = shortcode_atts(array(
            'type'            => '',
            'ids'             => '',
            'limit'           => 6,
            'title'           => '',
            'layout'          => 'grid',
            'only_open'       => 'false',
            'audience'        => '',
            'schedule'        => '',
            'kind'            => '',
            'region'          => '',
            'use_url_filters' => 'false',
            'show_filters'    => 'false',
        ), $atts, 'eab_events_grid');

        $url_type = EAB_Query::get_url_type_override();
        if ($url_type) {
            $atts['type'] = $url_type;
        }

        $query = new WP_Query(EAB_Query::build_query_args($atts));

        ob_start();
        $context = array(
            'query'         => $query,
            'title'         => $atts['title'],
            'layout'        => $atts['layout'],
            'show_filters'  => filter_var($atts['show_filters'], FILTER_VALIDATE_BOOLEAN),
            'filter_action' => '',
            'preset_atts'   => $atts,
        );
        $this->load_partial('events-grid', $context);
        wp_reset_postdata();
        return ob_get_clean();
    }

    /**
     * [eab_events_list type="event" limit="24" show_filters="true" filter_action="/akce/"]
     */
    public function events_list($atts) {
        $this->flag_assets();
        $atts = shortcode_atts(array(
            'type'            => 'event',
            'limit'           => 24,
            'title'           => '',
            'only_open'       => 'false',
            'audience'        => '',
            'schedule'        => '',
            'kind'            => '',
            'region'          => '',
            'use_url_filters' => 'true',
            'show_filters'    => 'true',
            'filter_action'   => '',
        ), $atts, 'eab_events_list');

        $query = new WP_Query(EAB_Query::build_query_args($atts));

        ob_start();
        $context = array(
            'query'         => $query,
            'title'         => $atts['title'],
            'layout'        => 'list',
            'show_filters'  => filter_var($atts['show_filters'], FILTER_VALIDATE_BOOLEAN),
            'filter_action' => $atts['filter_action'] ? esc_url($atts['filter_action']) : '',
            'preset_atts'   => $atts,
        );
        $this->load_partial('events-list', $context);
        wp_reset_postdata();
        return ob_get_clean();
    }

    /**
     * [eab_event_detail id="" show_gallery="true" show_program="true"]
     */
    public function event_detail($atts) {
        $this->flag_assets();
        $atts = shortcode_atts(array(
            'id'              => 0,
            'show_gallery'    => 'true',
            'show_program'    => 'true',
            'show_attendees'  => 'true',
            'show_services'   => 'true',
        ), $atts, 'eab_event_detail');

        $post_id = (int) $atts['id'];
        if (!$post_id && is_singular(EAB_Post_Types::get_bookable_post_types())) {
            $post_id = get_the_ID();
        }

        if (!$post_id || !EAB_Post_Types::is_bookable_post_type(get_post_type($post_id))) {
            return '<p class="eab-notice">' . esc_html__('Akce nebo trénink nenalezen.', 'events-and-bookings') . '</p>';
        }

        ob_start();
        $this->load_partial('event-detail', array(
            'post_id'        => $post_id,
            'show_gallery'   => filter_var($atts['show_gallery'], FILTER_VALIDATE_BOOLEAN),
            'show_program'   => filter_var($atts['show_program'], FILTER_VALIDATE_BOOLEAN),
            'show_attendees' => filter_var($atts['show_attendees'], FILTER_VALIDATE_BOOLEAN),
            'show_services'  => filter_var($atts['show_services'], FILTER_VALIDATE_BOOLEAN),
        ));
        return ob_get_clean();
    }

    /**
     * [eab_book_button id="" class=""]
     */
    public function book_button($atts) {
        $this->flag_assets();
        $atts = shortcode_atts(array(
            'id'    => 0,
            'class' => '',
        ), $atts, 'eab_book_button');

        $post_id = (int) $atts['id'];
        if (!$post_id && is_singular(EAB_Post_Types::get_bookable_post_types())) {
            $post_id = get_the_ID();
        }

        if (!$post_id) {
            return '';
        }

        ob_start();
        $this->load_partial('book-button', array(
            'post_id' => $post_id,
            'class'   => $atts['class'],
        ));
        return ob_get_clean();
    }

    /**
     * @param string $name
     * @param array  $context
     */
    private function load_partial($name, $context) {
        $file = EAB_PLUGIN_DIR . 'public/partials/' . $name . '.php';
        if (!is_readable($file)) {
            return;
        }
        extract($context, EXTR_SKIP);
        include $file;
    }
}
