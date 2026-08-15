<?php
/**
 * Line pricing for basket / checkout.
 */

if (!defined('ABSPATH')) {
    exit;
}

class EAB_Pricing {

    /**
     * @param int   $post_id
     * @param array $line_meta spots, services[]
     * @return array{unit:float,line_total:float,breakdown:array}
     */
    public static function calculate_line($post_id, array $line_meta) {
        $spots = max(1, (int) ($line_meta['spots'] ?? 1));
        $base  = self::get_base_price($post_id);
        $total = $base * $spots;
        $breakdown = array(
            array('label' => __('Základní cena', 'events-and-bookings'), 'amount' => $base * $spots),
        );

        $rules = function_exists('get_field') ? get_field('price_rules', $post_id) : array();
        if (is_array($rules)) {
            foreach ($rules as $rule) {
                $adjust = self::apply_rule($rule, $spots, $base);
                if ($adjust != 0) {
                    $total += $adjust;
                    $breakdown[] = array(
                        'label'  => self::rule_label($rule),
                        'amount' => $adjust,
                    );
                }
            }
        }

        $services = isset($line_meta['services']) && is_array($line_meta['services']) ? $line_meta['services'] : array();
        $service_total = self::services_addon_total($post_id, $services, $spots);
        if ($service_total > 0) {
            $total += $service_total;
            $breakdown[] = array(
                'label'  => __('Volitelné služby', 'events-and-bookings'),
                'amount' => $service_total,
            );
        }

        $total = max(0, round($total, 2));

        return array(
            'unit'       => $spots > 0 ? round($total / $spots, 2) : $total,
            'line_total' => $total,
            'breakdown'  => $breakdown,
        );
    }

    public static function get_base_price($post_id) {
        if (function_exists('get_field')) {
            $price = get_field('price_per_person', $post_id);
            if ($price !== '' && $price !== null) {
                return (float) $price;
            }
        }
        return 0.0;
    }

    private static function apply_rule($rule, $spots, $base) {
        $type = isset($rule['rule_type']) ? $rule['rule_type'] : '';
        $now  = current_time('timestamp');

        if ($type === 'early_bird' && !empty($rule['valid_until'])) {
            $until = strtotime($rule['valid_until']);
            if ($until && $now > $until) {
                return 0;
            }
        }

        if ($type === 'second_person' && $spots < 2) {
            return 0;
        }

        if (!empty($rule['amount'])) {
            return (float) $rule['amount'] * ($type === 'second_person' ? 1 : 1);
        }

        if (!empty($rule['percent'])) {
            $pct = (float) $rule['percent'];
            if ($type === 'second_person') {
                return -($base * ($pct / 100));
            }
            return -($base * $spots * ($pct / 100));
        }

        return 0;
    }

    private static function rule_label($rule) {
        $type = isset($rule['rule_type']) ? $rule['rule_type'] : 'custom';
        $map  = array(
            'second_person' => __('Sleva za 2. osobu', 'events-and-bookings'),
            'early_bird'    => __('Early bird', 'events-and-bookings'),
            'custom'        => __('Sleva', 'events-and-bookings'),
        );
        return $map[$type] ?? $map['custom'];
    }

    private static function services_addon_total($post_id, array $selected_slugs, $spots) {
        if (empty($selected_slugs)) {
            return 0.0;
        }
        $rows = self::get_optional_services($post_id);
        if (empty($rows)) {
            return 0.0;
        }
        $selected_keys = array();
        foreach ($selected_slugs as $value) {
            $key = self::service_key_from_value($value);
            if ($key !== '') {
                $selected_keys[$key] = true;
            }
        }
        if (empty($selected_keys)) {
            return 0.0;
        }
        $total = 0.0;
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $key = self::service_key($row);
            if ($key !== '' && isset($selected_keys[$key])) {
                $total += (float) ($row['price_addon'] ?? 0) * $spots;
            }
        }
        return $total;
    }

    /**
     * Stable key for an optional-service row (slug, or label if slug is empty).
     */
    public static function service_key(array $row) {
        $slug  = trim((string) ($row['slug'] ?? ''));
        $label = trim((string) ($row['label'] ?? ''));
        return self::service_key_from_value($slug !== '' ? $slug : $label);
    }

    /**
     * Normalize a service identifier so NBSP / spaces / accents still match.
     * Theme typography often turns " v " into " v&nbsp;", which broke the first extra.
     */
    public static function service_key_from_value($value) {
        $value = (string) $value;
        $value = preg_replace('/[\x{00A0}\x{202F}\x{2007}\x{2060}]/u', ' ', $value);
        $value = trim(preg_replace('/\s+/u', ' ', $value));
        if ($value === '') {
            return '';
        }
        $key = sanitize_title($value);
        return $key !== '' ? $key : $value;
    }

    public static function selected_service_keys(array $selected_slugs) {
        $keys = array();
        foreach ($selected_slugs as $value) {
            $key = self::service_key_from_value($value);
            if ($key !== '') {
                $keys[] = $key;
            }
        }
        return $keys;
    }

    public static function get_optional_services($post_id) {
        if (!function_exists('get_field')) {
            return array();
        }
        $rows = get_field('optional_services', $post_id);
        return is_array($rows) ? $rows : array();
    }

    public static function get_attendee_field_defs($post_id) {
        $defaults = array(
            array('field_key' => 'first_name', 'label' => __('Jméno', 'events-and-bookings'), 'field_type' => 'text', 'required' => 1),
            array('field_key' => 'last_name', 'label' => __('Příjmení', 'events-and-bookings'), 'field_type' => 'text', 'required' => 1),
        );
        if (!function_exists('get_field')) {
            return $defaults;
        }
        $custom = get_field('booking_form_fields', $post_id);
        if (!is_array($custom) || empty($custom)) {
            return $defaults;
        }
        return array_merge($defaults, $custom);
    }
}
