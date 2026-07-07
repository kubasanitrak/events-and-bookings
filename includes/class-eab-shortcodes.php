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
        add_shortcode('eab_trainings_list', array($this, 'trainings_list'));
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
            'filter_action' => $this->resolve_filter_action($atts['filter_action']),
            'preset_atts'   => $atts,
        );
        $this->load_partial('events-list', $context);
        wp_reset_postdata();
        return ob_get_clean();
    }

    /**
     * [eab_trainings_list limit="48" filter_action="/treninky/" kids_title="..." adults_title="..."]
     */
    public function trainings_list($atts) {
        $this->flag_assets();
        $atts = shortcode_atts(array(
            'limit'           => 48,
            'kids_title'      => __('školní rok 25/26', 'events-and-bookings'),
            'adults_title'    => __('zimní sezóna 26', 'events-and-bookings'),
            'only_open'       => 'false',
            'use_url_filters' => 'true',
            'filter_action'   => '',
        ), $atts, 'eab_trainings_list');

        $base = array(
            'type'            => 'training',
            'limit'           => $atts['limit'],
            'only_open'       => $atts['only_open'],
            'use_url_filters' => $atts['use_url_filters'],
        );

        $kids_query = new WP_Query(EAB_Query::build_query_args(array_merge($base, array(
            'audience'         => 'deti',
            'skill_level'      => '',
            'gender'           => '',
            'skip_url_filters' => array('audience', 'skill_level', 'gender', 'schedule', 'kind', 'region'),
        ))));

        $adults_query = new WP_Query(EAB_Query::build_query_args(array_merge($base, array(
            'audience'         => 'dospeli',
            'age_group'        => '',
            'skip_url_filters' => array('audience', 'age_group', 'schedule', 'kind', 'region'),
        ))));

        ob_start();
        $context = array(
            'kids_query'    => $kids_query,
            'adults_query'  => $adults_query,
            'kids_title'    => $atts['kids_title'],
            'adults_title'  => $atts['adults_title'],
            'filter_action' => $this->resolve_filter_action($atts['filter_action']),
        );
        $this->load_partial('trainings-list', $context);
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
     * Resolve the listing filter base URL.
     *
     * Empty → current page permalink. Root-relative path (e.g. "/akce/") →
     * absolute URL respecting subdirectory installs. Absolute → left as-is.
     *
     * @param string $filter_action
     * @return string
     */
    private function resolve_filter_action($filter_action) {
        $filter_action = trim((string) $filter_action);

        if ($filter_action === '') {
            $permalink = get_permalink();
            return $permalink ? $permalink : '';
        }

        if (preg_match('#^https?://#i', $filter_action)) {
            return esc_url($filter_action);
        }

        if (strpos($filter_action, '/') === 0) {
            return esc_url(home_url($filter_action));
        }

        return esc_url($filter_action);
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
