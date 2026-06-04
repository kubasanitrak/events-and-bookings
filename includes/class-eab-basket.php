<?php
/**
 * Basket rules (storage table added in checkout phase).
 *
 * - Items are added only from event/training detail.
 * - Each line requires at least one spot (attendee).
 * - By default only one event/training per basket; multi-event is opt-in for later.
 */

if (!defined('ABSPATH')) {
    exit;
}

class EAB_Basket {

    public function __construct() {
        // AJAX and table implementation in a later phase.
    }

    /**
     * When adding from detail, replace existing lines if multi-event is disabled.
     */
    public static function should_replace_basket_on_add() {
        return !EAB_Settings::basket_allows_multiple_events();
    }

    /**
     * Validate spot count for a basket line.
     */
    public static function validate_spot_count($spots) {
        $spots = (int) $spots;
        $min   = EAB_Settings::basket_min_spots_per_item();

        if ($spots < $min) {
            return new WP_Error(
                'eab_invalid_spots',
                sprintf(
                    /* translators: %d: minimum spots */
                    __('Minimální počet míst na položku je %d.', 'events-and-bookings'),
                    $min
                )
            );
        }

        return $spots;
    }

    /**
     * Whether user may add this post to basket (published, bookable type, booking open).
     */
    public static function can_add_post($post_id, $user_id = 0) {
        $post = get_post($post_id);
        if (!$post || $post->post_status !== 'publish') {
            return false;
        }

        if (!EAB_Post_Types::is_bookable_post_type($post->post_type)) {
            return false;
        }

        if ($user_id && !get_userdata($user_id)) {
            return false;
        }

        if (function_exists('get_field')) {
            $open = get_field('booking_open', $post_id);
            if ($open === false || $open === 0 || $open === '0') {
                return false;
            }
        }

        return true;
    }
}
