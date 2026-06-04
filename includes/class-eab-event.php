<?php
/**
 * Event / training display helpers.
 */

if (!defined('ABSPATH')) {
    exit;
}

class EAB_Event {

    /**
     * @param int $post_id
     * @return string eab_event|eab_training
     */
    public static function get_post_type($post_id) {
        return get_post_type($post_id);
    }

    public static function get_type_label($post_id) {
        $type = self::get_post_type($post_id);
        if ($type === EAB_Post_Types::POST_TYPE_TRAINING) {
            return __('Trénink', 'events-and-bookings');
        }
        return __('Akce', 'events-and-bookings');
    }

    public static function is_booking_open($post_id) {
        if (!EAB_Basket::can_add_post($post_id)) {
            return false;
        }
        if (function_exists('get_field')) {
            $open = get_field('booking_open', $post_id);
            return !($open === false || $open === 0 || $open === '0');
        }
        return true;
    }

    public static function get_tile_image_id($post_id) {
        $thumb = get_post_thumbnail_id($post_id);
        if ($thumb) {
            return (int) $thumb;
        }
        if (function_exists('get_field')) {
            $place = get_field('place_photo', $post_id);
            if (is_array($place) && !empty($place['ID'])) {
                return (int) $place['ID'];
            }
            if (is_numeric($place)) {
                return (int) $place;
            }
        }
        return 0;
    }

    /**
     * Human-readable schedule line for cards and detail header.
     */
    public static function get_schedule_summary($post_id) {
        if (!function_exists('get_field')) {
            return '';
        }

        $mode = get_field('schedule_mode', $post_id);
        if (!$mode) {
            return '';
        }

        switch ($mode) {
            case 'season':
                $start = self::format_date(get_field('season_start_date', $post_id));
                $end   = self::format_date(get_field('season_end_date', $post_id));
                $t1    = get_field('season_start_time', $post_id);
                $t2    = get_field('season_end_time', $post_id);
                $line  = sprintf('%s – %s', $start, $end);
                if ($t1 || $t2) {
                    $line .= ' · ' . trim($t1 . ' – ' . $t2, ' –');
                }
                return $line;

            case 'whole_day':
                $date = self::format_date(get_field('whole_day_date', $post_id));
                $from = get_field('whole_day_time_from', $post_id);
                $to   = get_field('whole_day_time_to', $post_id);
                if ($from && $to) {
                    return sprintf('%s · %s–%s', $date, $from, $to);
                }
                return $date;

            case 'one_off':
            default:
                $date = self::format_date(get_field('one_off_date', $post_id));
                $time = get_field('one_off_time', $post_id);
                return $time ? $date . ' · ' . $time : $date;
        }
    }

    public static function format_date($value) {
        if (empty($value)) {
            return '';
        }
        $ts = strtotime($value);
        return $ts ? date_i18n('j. n. Y', $ts) : $value;
    }

    public static function get_price_label($post_id) {
        if (!function_exists('get_field')) {
            return '';
        }
        $price = get_field('price_per_person', $post_id);
        if ($price === '' || $price === null) {
            return '';
        }
        $symbol = get_option('eab_currency_symbol', 'Kč');
        $position = get_option('eab_currency_position', 'after');
        $formatted = number_format_i18n((float) $price, 0);
        if ($position === 'before') {
            return $symbol . ' ' . $formatted;
        }
        return $formatted . ' ' . $symbol;
    }

    /**
     * @return WP_Term[]
     */
    public static function get_filter_terms($post_id) {
        $terms = array();
        $taxonomies = array(
            EAB_Post_Types::TAX_AUDIENCE,
            EAB_Post_Types::TAX_SCHEDULE_TYPE,
            EAB_Post_Types::TAX_EVENT_KIND,
            EAB_Post_Types::TAX_REGION,
        );
        foreach ($taxonomies as $tax) {
            $post_terms = get_the_terms($post_id, $tax);
            if (is_array($post_terms)) {
                $terms = array_merge($terms, $post_terms);
            }
        }
        return $terms;
    }

    public static function render_tags($post_id, $args = array()) {
        $terms = self::get_filter_terms($post_id);
        if (empty($terms)) {
            return '';
        }

        $args = wp_parse_args($args, array(
            'class' => 'eab-tags',
            'link'  => false,
        ));

        ob_start();
        echo '<ul class="' . esc_attr($args['class']) . '">';
        foreach ($terms as $term) {
            echo '<li>';
            if ($args['link']) {
                echo '<a href="' . esc_url(get_term_link($term)) . '">' . esc_html($term->name) . '</a>';
            } else {
                echo esc_html($term->name);
            }
            echo '</li>';
        }
        echo '</ul>';
        return ob_get_clean();
    }

    public static function get_login_url($redirect = '') {
        $url = class_exists('EAB_Auth') ? EAB_Auth::get_page_url('login') : '';
        if (!$url) {
            $page_id = (int) get_option('eab_login_page', 0);
            if ($page_id) {
                $url = get_permalink($page_id);
            }
        }
        if ($url) {
            return $redirect ? add_query_arg('redirect_to', urlencode($redirect), $url) : $url;
        }
        return wp_login_url($redirect);
    }

    public static function get_register_url() {
        $url = class_exists('EAB_Auth') ? EAB_Auth::get_page_url('register') : '';
        if (!$url) {
            $page_id = (int) get_option('eab_register_page', 0);
            if ($page_id) {
                $url = get_permalink($page_id);
            }
        }
        if ($url) {
            return $url;
        }
        if (get_option('users_can_register')) {
            return wp_registration_url();
        }
        return self::get_login_url();
    }
}
