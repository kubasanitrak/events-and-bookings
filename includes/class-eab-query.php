<?php
/**
 * Build WP_Query for listings and shortcodes.
 */

if (!defined('ABSPATH')) {
    exit;
}

class EAB_Query {

    const GET_TYPE     = 'eab_type';
    const GET_AUDIENCE = 'eab_publikum';
    const GET_SCHEDULE = 'eab_rozvrzeni';
    const GET_KIND     = 'eab_druh';
    const GET_REGION   = 'eab_region';

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
        $tax_query = self::build_tax_query($atts, $use_url);
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

    private static function build_tax_query($atts, $use_url) {
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
        );

        if ($use_url) {
            $url_type = isset($_GET[self::GET_TYPE]) ? sanitize_key(wp_unslash($_GET[self::GET_TYPE])) : '';
            if ($url_type === 'event' || $url_type === 'training') {
                // Handled at post_type level in shortcode render when merging.
            }
        }

        $tax_query = array();
        foreach ($map as $row) {
            $slug = sanitize_title($row['slug']);
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
            'type'     => self::get_url_type_override(),
            'audience' => self::get_param(self::GET_AUDIENCE, ''),
            'schedule' => self::get_param(self::GET_SCHEDULE, ''),
            'kind'     => self::get_param(self::GET_KIND, ''),
            'region'   => self::get_param(self::GET_REGION, ''),
        );
    }
}
