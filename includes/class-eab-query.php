<?php
/**
 * Build WP_Query for listings and shortcodes.
 */

if (!defined('ABSPATH')) {
    exit;
}

class EAB_Query {

    const GET_TYPE         = 'eab_type';
    const GET_AUDIENCE     = 'eab_publikum';
    const GET_SCHEDULE     = 'eab_rozvrzeni';
    const GET_KIND         = 'eab_druh';
    const GET_REGION       = 'eab_region';
    const GET_AGE_GROUP    = 'eab_vek';
    const GET_SKILL_LEVEL  = 'eab_uroven';
    const GET_GENDER       = 'eab_pohlavi';

    /**
     * @param array $atts Shortcode attributes.
     * @return array WP_Query args.
     */
    public static function build_query_args($atts) {
        $url_type = self::get_url_type_override();
        if ($url_type && empty($atts['type'])) {
            $atts['type'] = $url_type;
        }

        $atts = wp_parse_args($atts, array(
            'type'        => '',
            'ids'         => '',
            'limit'       => 12,
            'orderby'     => 'date',
            'order'       => 'DESC',
            'only_open'   => '',
            'audience'    => '',
            'schedule'    => '',
            'kind'        => '',
            'region'      => '',
            'age_group'   => '',
            'skill_level' => '',
            'gender'      => '',
            'skip_url_filters' => array(),
            'use_url_filters' => 'true',
        ));

        $post_types = self::resolve_post_types($atts['type']);
        $paged = (int) get_query_var('paged');
        if ($paged < 1) {
            $paged = (int) get_query_var('page');
        }
        $paged = max(1, $paged);

        $args = array(
            'post_type'      => $post_types,
            'post_status'    => 'publish',
            'posts_per_page' => max(1, (int) $atts['limit']),
            'paged'          => $paged,
            'orderby'        => sanitize_key($atts['orderby']),
            'order'          => strtoupper($atts['order']) === 'ASC' ? 'ASC' : 'DESC',
            'no_found_rows'  => false,
        );

        if (!empty($atts['ids'])) {
            $ids = array_filter(array_map('intval', explode(',', $atts['ids'])));
            if ($ids) {
                $args['post__in'] = $ids;
                $args['orderby']  = 'post__in';
            }
        }

        $use_url = filter_var($atts['use_url_filters'], FILTER_VALIDATE_BOOLEAN);
        $skip_url = array();
        if (!empty($atts['skip_url_filters']) && is_array($atts['skip_url_filters'])) {
            $skip_url = array_map('sanitize_key', $atts['skip_url_filters']);
        }
        $tax_query = self::build_tax_query($atts, $use_url, $skip_url);
        if (!empty($tax_query)) {
            $args['tax_query'] = array_merge(array('relation' => 'AND'), $tax_query);
        }

        if (filter_var($atts['only_open'], FILTER_VALIDATE_BOOLEAN)) {
            $args = self::append_booking_open_meta($args);
        }

        return apply_filters('eab_query_args', $args, $atts);
    }

    /**
     * @param string $type event|training|both|empty
     * @return string[]
     */
    public static function resolve_post_types($type) {
        $type = sanitize_key($type);
        if ($type === 'event') {
            return array(EAB_Post_Types::POST_TYPE_EVENT);
        }
        if ($type === 'training') {
            return array(EAB_Post_Types::POST_TYPE_TRAINING);
        }
        return EAB_Post_Types::get_bookable_post_types();
    }

    private static function build_tax_query($atts, $use_url, $skip_url = array()) {
        $map = array(
            'audience' => array(
                'tax'  => EAB_Post_Types::TAX_AUDIENCE,
                'get'  => self::GET_AUDIENCE,
                'slug' => $use_url ? self::get_param(self::GET_AUDIENCE, $atts['audience']) : $atts['audience'],
            ),
            'schedule' => array(
                'tax'  => EAB_Post_Types::TAX_SCHEDULE_TYPE,
                'get'  => self::GET_SCHEDULE,
                'slug' => $use_url ? self::get_param(self::GET_SCHEDULE, $atts['schedule']) : $atts['schedule'],
            ),
            'kind' => array(
                'tax'  => EAB_Post_Types::TAX_EVENT_KIND,
                'get'  => self::GET_KIND,
                'slug' => $use_url ? self::get_param(self::GET_KIND, $atts['kind']) : $atts['kind'],
            ),
            'region' => array(
                'tax'  => EAB_Post_Types::TAX_REGION,
                'get'  => self::GET_REGION,
                'slug' => $use_url ? self::get_param(self::GET_REGION, $atts['region']) : $atts['region'],
            ),
            'age_group' => array(
                'tax'  => EAB_Post_Types::TAX_AGE_GROUP,
                'get'  => self::GET_AGE_GROUP,
                'slug' => $use_url ? self::get_param(self::GET_AGE_GROUP, $atts['age_group']) : $atts['age_group'],
            ),
            'skill_level' => array(
                'tax'  => EAB_Post_Types::TAX_SKILL_LEVEL,
                'get'  => self::GET_SKILL_LEVEL,
                'slug' => $use_url ? self::get_param(self::GET_SKILL_LEVEL, $atts['skill_level']) : $atts['skill_level'],
            ),
            'gender' => array(
                'tax'  => EAB_Post_Types::TAX_GENDER,
                'get'  => self::GET_GENDER,
                'slug' => $use_url ? self::get_param(self::GET_GENDER, $atts['gender']) : $atts['gender'],
            ),
        );

        if ($use_url) {
            $url_type = isset($_GET[self::GET_TYPE]) ? sanitize_key(wp_unslash($_GET[self::GET_TYPE])) : '';
            if ($url_type === 'event' || $url_type === 'training') {
                // Handled at post_type level in shortcode render when merging.
            }
        }

        $tax_query = array();
        foreach ($map as $key => $row) {
            $read_url = $use_url && !in_array($key, $skip_url, true);
            $slug = $read_url ? self::get_param($row['get'], $atts[$key]) : sanitize_title($atts[$key]);
            $slug = sanitize_title($slug);
            if ($slug === '') {
                continue;
            }
            $tax_query[] = array(
                'taxonomy' => $row['tax'],
                'field'    => 'slug',
                'terms'    => $slug,
            );
        }

        return $tax_query;
    }

    public static function get_url_type_override() {
        if (!isset($_GET[self::GET_TYPE])) {
            return '';
        }
        $type = sanitize_key(wp_unslash($_GET[self::GET_TYPE]));
        return in_array($type, array('event', 'training'), true) ? $type : '';
    }

    private static function get_param($key, $fallback) {
        if (isset($_GET[$key]) && $_GET[$key] !== '') {
            return sanitize_title(wp_unslash($_GET[$key]));
        }
        return sanitize_title($fallback);
    }

    private static function append_booking_open_meta($args) {
        if (!function_exists('get_field')) {
            return $args;
        }
        $args['meta_query'] = array(
            'relation' => 'OR',
            array(
                'key'     => 'booking_open',
                'compare' => 'NOT EXISTS',
            ),
            array(
                'key'     => 'booking_open',
                'value'   => '0',
                'compare' => '!=',
            ),
        );
        return $args;
    }

    /**
     * Terms for filter dropdowns.
     *
     * @return array<string, WP_Term[]>
     */
    public static function get_filter_term_groups() {
        return array(
            'audience' => get_terms(array(
                'taxonomy'   => EAB_Post_Types::TAX_AUDIENCE,
                'hide_empty' => false,
            )),
            'schedule' => get_terms(array(
                'taxonomy'   => EAB_Post_Types::TAX_SCHEDULE_TYPE,
                'hide_empty' => false,
            )),
            'kind' => get_terms(array(
                'taxonomy'   => EAB_Post_Types::TAX_EVENT_KIND,
                'hide_empty' => false,
            )),
            'region' => get_terms(array(
                'taxonomy'   => EAB_Post_Types::TAX_REGION,
                'hide_empty' => false,
            )),
        );
    }

    public static function get_active_filters() {
        return array(
            'type'        => self::get_url_type_override(),
            'audience'    => self::get_param(self::GET_AUDIENCE, ''),
            'schedule'    => self::get_param(self::GET_SCHEDULE, ''),
            'kind'        => self::get_param(self::GET_KIND, ''),
            'region'      => self::get_param(self::GET_REGION, ''),
            'age_group'   => self::get_param(self::GET_AGE_GROUP, ''),
            'skill_level' => self::get_param(self::GET_SKILL_LEVEL, ''),
            'gender'      => self::get_param(self::GET_GENDER, ''),
        );
    }

    /**
     * Pill definitions for a listing context.
     *
     * @param string $context events|kids|adults
     * @return array<int, array{key:string,param:string,slug:string,label:string,active:bool}>
     */
    public static function get_filter_pills($context = 'events') {
        $active = self::get_active_filters();
        $configs = array(
            'events' => array(
                array('key' => 'audience', 'param' => self::GET_AUDIENCE, 'slug' => 'deti', 'label' => __('Děti', 'events-and-bookings')),
                array('key' => 'audience', 'param' => self::GET_AUDIENCE, 'slug' => 'dospeli', 'label' => __('Dospělí', 'events-and-bookings')),
                array('key' => 'schedule', 'param' => self::GET_SCHEDULE, 'slug' => 'tydenni', 'label' => __('Týdenní', 'events-and-bookings')),
                array('key' => 'schedule', 'param' => self::GET_SCHEDULE, 'slug' => 'vikend', 'label' => __('Víkendové', 'events-and-bookings')),
                array('key' => 'schedule', 'param' => self::GET_SCHEDULE, 'slug' => 'cely-den', 'label' => __('Jednodenní', 'events-and-bookings')),
                array('key' => 'region', 'param' => self::GET_REGION, 'slug' => 'zahranici', 'label' => __('Zahraniční', 'events-and-bookings')),
                array('key' => 'kind', 'param' => self::GET_KIND, 'slug' => 'turnaj', 'label' => __('Turnaj', 'events-and-bookings')),
                array('key' => 'kind', 'param' => self::GET_KIND, 'slug' => 'kemp', 'label' => __('Kemp', 'events-and-bookings')),
                array('key' => 'kind', 'param' => self::GET_KIND, 'slug' => 'tabor', 'label' => __('Tábor', 'events-and-bookings')),
            ),
            'kids' => array(
                array('key' => 'age_group', 'param' => self::GET_AGE_GROUP, 'slug' => '3-6-let', 'label' => __('3–6 let', 'events-and-bookings')),
                array('key' => 'age_group', 'param' => self::GET_AGE_GROUP, 'slug' => '6-9-let', 'label' => __('6–9 let', 'events-and-bookings')),
                array('key' => 'age_group', 'param' => self::GET_AGE_GROUP, 'slug' => '10-15-let', 'label' => __('10–15 let', 'events-and-bookings')),
            ),
            'adults' => array(
                array('key' => 'skill_level', 'param' => self::GET_SKILL_LEVEL, 'slug' => 'zacatecnici', 'label' => __('Začátečníci', 'events-and-bookings')),
                array('key' => 'skill_level', 'param' => self::GET_SKILL_LEVEL, 'slug' => 'stredne-pokrocili', 'label' => __('Středně pokročilí', 'events-and-bookings')),
                array('key' => 'skill_level', 'param' => self::GET_SKILL_LEVEL, 'slug' => 'pokrocili', 'label' => __('Pokročilí', 'events-and-bookings')),
                array('key' => 'gender', 'param' => self::GET_GENDER, 'slug' => 'zeny', 'label' => __('Ženy', 'events-and-bookings')),
                array('key' => 'gender', 'param' => self::GET_GENDER, 'slug' => 'muzi', 'label' => __('Muži', 'events-and-bookings')),
            ),
        );

        $pills = isset($configs[$context]) ? $configs[$context] : array();
        foreach ($pills as $index => $pill) {
            $pills[$index]['active'] = (($active[$pill['key']] ?? '') === $pill['slug']);
        }

        return $pills;
    }

    /**
     * @param string $base_url
     * @param string $param
     * @param string $slug
     * @param string $group_key
     */
    public static function get_filter_toggle_url($base_url, $param, $slug, $group_key) {
        $active = self::get_active_filters();
        if (($active[$group_key] ?? '') === $slug) {
            return remove_query_arg($param, $base_url);
        }

        return add_query_arg($param, $slug, $base_url);
    }

    /**
     * @param string   $base_url
     * @param string[] $params
     */
    public static function get_filter_reset_url($base_url, $params) {
        return remove_query_arg($params, $base_url);
    }

    /**
     * @return string[]
     */
    public static function get_all_filter_params() {
        return array(
            self::GET_TYPE,
            self::GET_AUDIENCE,
            self::GET_SCHEDULE,
            self::GET_KIND,
            self::GET_REGION,
            self::GET_AGE_GROUP,
            self::GET_SKILL_LEVEL,
            self::GET_GENDER,
        );
    }
}
