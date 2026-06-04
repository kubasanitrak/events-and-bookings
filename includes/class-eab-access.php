<?php
/**
 * Front-end access rules (attendee lists, etc.).
 */

if (!defined('ABSPATH')) {
    exit;
}

class EAB_Access {

    /**
     * Whether the current user may see the attendee / alternate list on event detail.
     */
    public static function can_view_attendee_list($post_id = 0) {
        if (!is_user_logged_in()) {
            return false;
        }

        if (current_user_can('edit_posts')) {
            return true;
        }

        if ($post_id && function_exists('get_field')) {
            $show = get_field('show_attendee_list', $post_id);
            if ($show === false || $show === 0 || $show === '0') {
                return false;
            }
        }

        return EAB_Settings::ATTENDEE_LIST_VISIBILITY === 'logged_in';
    }
}
