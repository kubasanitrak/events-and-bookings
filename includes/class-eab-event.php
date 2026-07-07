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

    /**
     * Compact schedule line for listing cards (matches design mockup).
     */
    public static function get_card_schedule_line($post_id) {
        if (!function_exists('get_field')) {
            return '';
        }

        $mode = get_field('schedule_mode', $post_id);
        if (!$mode) {
            return '';
        }

        switch ($mode) {
            case 'season':
                return self::format_card_date_range(
                    get_field('season_start_date', $post_id),
                    get_field('season_end_date', $post_id)
                );

            case 'whole_day':
                $date = self::format_card_date(get_field('whole_day_date', $post_id));
                $from = get_field('whole_day_time_from', $post_id);
                $to   = get_field('whole_day_time_to', $post_id);
                if ($from && $to) {
                    return sprintf('%s, %s–%s', $date, self::format_card_time($from), self::format_card_time($to));
                }
                return $date;

            case 'one_off':
            default:
                $date = self::format_card_date(get_field('one_off_date', $post_id));
                $time = get_field('one_off_time', $post_id);
                return $time ? $date . ', ' . self::format_card_time($time) : $date;
        }
    }

    private static function format_card_date($value) {
        $ts = !empty($value) ? strtotime($value) : false;
        if (!$ts) {
            return '';
        }

        return sprintf('%d/%d/%d', (int) date('j', $ts), (int) date('n', $ts), (int) date('Y', $ts));
    }

    private static function format_card_date_range($start, $end) {
        $ts1 = !empty($start) ? strtotime($start) : false;
        if (!$ts1) {
            return '';
        }

        $d1 = (int) date('j', $ts1);
        $m1 = (int) date('n', $ts1);
        $y1 = (int) date('Y', $ts1);

        $ts2 = !empty($end) ? strtotime($end) : false;
        if (!$ts2 || $start === $end) {
            return sprintf('%d/%d/%d', $d1, $m1, $y1);
        }

        $d2 = (int) date('j', $ts2);
        $m2 = (int) date('n', $ts2);
        $y2 = (int) date('Y', $ts2);

        if ($m1 === $m2 && $y1 === $y2) {
            return sprintf('%d—%d/%d/%d', $d1, $d2, $m1, $y1);
        }

        return sprintf('%d/%d/%d – %d/%d/%d', $d1, $m1, $y1, $d2, $m2, $y2);
    }

    private static function format_card_time($value) {
        if ($value === '' || $value === null) {
            return '';
        }

        return str_replace(':', '.', (string) $value);
    }

    /**
     * Location + time meta for training row cards.
     *
     * @return array{location:string,time:string}
     */
    public static function get_training_row_meta($post_id) {
        $meta = array(
            'location' => '',
            'time'     => '',
        );

        if (!function_exists('get_field')) {
            return $meta;
        }

        $place = trim((string) get_field('place_text', $post_id));
        if ($place !== '') {
            $meta['location'] = mb_strtoupper(wp_strip_all_tags($place));
        }

        $mode = get_field('schedule_mode', $post_id);
        if ($mode === 'season') {
            $t1 = get_field('season_start_time', $post_id);
            $t2 = get_field('season_end_time', $post_id);
            if ($t1 && $t2) {
                $meta['time'] = self::format_card_time($t1) . '–' . self::format_card_time($t2);
            } elseif ($t1) {
                $meta['time'] = self::format_card_time($t1);
            }
        } elseif ($mode === 'whole_day') {
            $from = get_field('whole_day_time_from', $post_id);
            $to   = get_field('whole_day_time_to', $post_id);
            if ($from && $to) {
                $meta['time'] = self::format_card_time($from) . '–' . self::format_card_time($to);
            }
        } elseif ($mode === 'one_off') {
            $time = get_field('one_off_time', $post_id);
            if ($time) {
                $meta['time'] = self::format_card_time($time);
            }
        }

        return $meta;
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
            EAB_Post_Types::TAX_AGE_GROUP,
            EAB_Post_Types::TAX_SKILL_LEVEL,
            EAB_Post_Types::TAX_GENDER,
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
