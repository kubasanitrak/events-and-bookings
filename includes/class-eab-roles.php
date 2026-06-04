<?php
/**
 * Member role and capabilities.
 */

if (!defined('ABSPATH')) {
    exit;
}

class EAB_Roles {

    const MEMBER_ROLE = 'eab_member';

    public function __construct() {
        add_action('init', array($this, 'ensure_role'), 1);
    }

    public static function register_role() {
        if (get_role(self::MEMBER_ROLE)) {
            return;
        }

        add_role(
            self::MEMBER_ROLE,
            __('Člen (rezervace)', 'events-and-bookings'),
            array(
                'read' => true,
            )
        );
    }

    public function ensure_role() {
        self::register_role();
    }

    /**
     * Assign member role on registration (used later by auth).
     */
    public static function assign_member_role($user_id) {
        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }
        $user->set_role(self::MEMBER_ROLE);
    }
}
