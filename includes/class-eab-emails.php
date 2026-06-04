<?php
/**
 * Transactional e-mails.
 */

if (!defined('ABSPATH')) {
    exit;
}

class EAB_Emails {

    public static function send_verification_email($user_id, $token) {
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }

        $url = EAB_Auth::get_verification_url($user_id, $token);
        $blog = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);

        $subject = sprintf(
            /* translators: %s: site name */
            __('[%s] Ověření registrace', 'events-and-bookings'),
            $blog
        );

        $body = sprintf(
            __("Dobrý den %s,\n\npro dokončení registrace na %s klikněte na odkaz:\n\n%s\n\nPo ověření si nastavíte heslo.\n\nPokud jste se neregistrovali, tento e-mail ignorujte.", 'events-and-bookings'),
            $user->display_name,
            $blog,
            $url
        );

        $headers = array('Content-Type: text/plain; charset=UTF-8');
        $from_name  = get_option('eab_email_sender_name', $blog);
        $from_email = get_option('eab_email_sender_email', get_option('admin_email'));
        if ($from_email) {
            $headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';
        }

        return wp_mail($user->user_email, $subject, $body, $headers);
    }
}
