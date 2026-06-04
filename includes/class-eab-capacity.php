<?php
/**
 * Capacity and waitlist (regular / alternate).
 */

if (!defined('ABSPATH')) {
    exit;
}

class EAB_Capacity {

    const SPOT_REGULAR   = 'regular';
    const SPOT_ALTERNATE = 'alternate';

    const STATUS_HELD      = 'held';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_CANCELLED = 'cancelled';

    public static function get_limits($post_id) {
        $regular = 0;
        $alternate = 0;
        if (function_exists('get_field')) {
            $regular   = (int) get_field('capacity_regular', $post_id);
            $alternate = (int) get_field('capacity_alternate', $post_id);
        }
        return array(
            'regular'   => $regular,
            'alternate' => $alternate,
        );
    }

    /**
     * Count active spots for an event (held + confirmed, non-expired holds).
     */
    public static function count_spots($post_id, $spot_type = self::SPOT_REGULAR) {
        global $wpdb;

        if (!EAB_DB::table_exists('eab_booking_spots')) {
            return 0;
        }

        $table = $wpdb->prefix . 'eab_booking_spots';
        $orders = $wpdb->prefix . 'eab_orders';
        $post_type = get_post_type($post_id);

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table s
             INNER JOIN $orders o ON s.order_id = o.id
             WHERE s.object_id = %d AND s.object_type = %s AND s.spot_type = %s
             AND s.status IN ('held', 'confirmed')
             AND o.status NOT IN ('cancelled', 'expired', 'failed')
             AND (s.status = 'confirmed' OR o.expires_at IS NULL OR o.expires_at > %s)",
            $post_id,
            $post_type,
            $spot_type,
            current_time('mysql')
        ));
    }

    /**
     * @return array{spot_type:string, available:int}|WP_Error
     */
    public static function resolve_spot_type($post_id, $spots_needed) {
        $limits = self::get_limits($post_id);
        $regular_used = self::count_spots($post_id, self::SPOT_REGULAR);

        if ($limits['regular'] === 0 || ($regular_used + $spots_needed) <= $limits['regular']) {
            return array(
                'spot_type' => self::SPOT_REGULAR,
                'available' => $limits['regular'] === 0 ? PHP_INT_MAX : max(0, $limits['regular'] - $regular_used),
            );
        }

        if ($limits['alternate'] > 0) {
            $alt_used = self::count_spots($post_id, self::SPOT_ALTERNATE);
            if (($alt_used + $spots_needed) <= $limits['alternate']) {
                return array(
                    'spot_type' => self::SPOT_ALTERNATE,
                    'available' => max(0, $limits['alternate'] - $alt_used),
                );
            }
        }

        return new WP_Error('eab_full', __('Kapacita je naplněna.', 'events-and-bookings'));
    }

    public static function can_reserve($post_id, $spots) {
        $spots = (int) $spots;
        if ($spots < 1) {
            return new WP_Error('eab_invalid_spots', __('Neplatný počet míst.', 'events-and-bookings'));
        }
        return self::resolve_spot_type($post_id, $spots);
    }

    /**
     * @param int   $order_id
     * @param int   $order_item_id
     * @param array $line_meta From basket/checkout.
     */
    public static function create_holds_from_line($order_id, $order_item_id, $object_id, $object_type, $user_id, array $line_meta, $spot_type) {
        global $wpdb;

        $table = $wpdb->prefix . 'eab_booking_spots';
        $spots = isset($line_meta['spots']) ? (int) $line_meta['spots'] : 1;
        $attendees = isset($line_meta['attendees']) && is_array($line_meta['attendees']) ? $line_meta['attendees'] : array();

        for ($i = 0; $i < $spots; $i++) {
            $attendee_data = isset($attendees[$i]) ? $attendees[$i] : array();
            $wpdb->insert(
                $table,
                array(
                    'order_id'       => $order_id,
                    'order_item_id'  => $order_item_id,
                    'object_id'      => $object_id,
                    'object_type'    => $object_type,
                    'user_id'        => $user_id,
                    'spot_type'      => $spot_type,
                    'status'         => self::STATUS_HELD,
                    'attendee_index' => $i,
                    'attendee_data'  => wp_json_encode($attendee_data),
                    'created_at'     => current_time('mysql'),
                ),
                array('%d', '%d', '%d', '%s', '%d', '%s', '%s', '%d', '%s', '%s')
            );
        }
    }

    public static function confirm_order_spots($order_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'eab_booking_spots';
        $wpdb->update(
            $table,
            array('status' => self::STATUS_CONFIRMED),
            array('order_id' => $order_id, 'status' => self::STATUS_HELD),
            array('%s'),
            array('%d', '%s')
        );
    }

    public static function release_order_spots($order_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'eab_booking_spots';
        $wpdb->update(
            $table,
            array('status' => self::STATUS_CANCELLED),
            array('order_id' => $order_id),
            array('%s'),
            array('%d')
        );
    }

    /**
     * Attendee display names for detail (confirmed only).
     *
     * @return string[]
     */
    public static function get_public_attendee_names($post_id) {
        global $wpdb;

        if (!EAB_DB::table_exists('eab_booking_spots')) {
            return array();
        }

        $table = $wpdb->prefix . 'eab_booking_spots';
        $rows = $wpdb->get_col($wpdb->prepare(
            "SELECT attendee_data FROM $table
             WHERE object_id = %d AND object_type = %s AND status = %s",
            $post_id,
            get_post_type($post_id),
            self::STATUS_CONFIRMED
        ));

        $names = array();
        foreach ($rows as $json) {
            $data = json_decode($json, true);
            if (!is_array($data)) {
                continue;
            }
            $name = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));
            if ($name === '' && !empty($data['name'])) {
                $name = $data['name'];
            }
            if ($name !== '') {
                $names[] = $name;
            }
        }
        return $names;
    }
}
