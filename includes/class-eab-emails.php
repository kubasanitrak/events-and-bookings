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

    public static function send_order_placed_email($order_id) {
        $order = EAB_Checkout::get_order($order_id);
        if (!$order) {
            return false;
        }
        $user = get_userdata($order->user_id);
        if (!$user) {
            return false;
        }

        $blog = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
        $subject = sprintf(__('[%s] Potvrzení objednávky %s', 'events-and-bookings'), $blog, $order->order_number);

        $lines = array();
        foreach ($order->items as $item) {
            $lines[] = sprintf('- %s: %s', $item->post_title, EAB_Payments::format_price($item->line_total));
        }

        $payment_url = '';
        if ($order->payment_method === 'bank_transfer') {
            $checkout = EAB_Auth::get_page_url('checkout');
            if ($checkout) {
                $payment_url = add_query_arg(array(
                    'order'  => $order_id,
                    'method' => 'bank_transfer',
                ), $checkout);
            }
        }

        $expires = '';
        if (!empty($order->expires_at)) {
            $expires = sprintf(
                "\n" . __('Platbu prosím uhraďte do: %s', 'events-and-bookings') . "\n",
                date_i18n('j. n. Y H:i', strtotime($order->expires_at))
            );
        }

        $body = sprintf(
            __("Dobrý den %s,\n\nvaše objednávka %s byla přijata.\n\n%s\n\nCelkem: %s%s\n\n%s\n", 'events-and-bookings'),
            $user->display_name,
            $order->order_number,
            implode("\n", $lines),
            EAB_Payments::format_price($order->total),
            $expires,
            $payment_url
                ? sprintf(__('Platební instrukce a QR kód: %s', 'events-and-bookings'), $payment_url)
                : __('Platební instrukce najdete na webu v sekci Můj účet.', 'events-and-bookings')
        );

        self::mail($user->user_email, $subject, $body);
        self::notify_admin_new_order($order_id);

        return true;
    }

    public static function send_expiry_notification($order_id) {
        $order = EAB_Checkout::get_order($order_id);
        if (!$order || $order->status !== 'awaiting_payment') {
            return false;
        }

        $user = get_userdata($order->user_id);
        if (!$user) {
            return false;
        }

        $blog = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
        $subject = sprintf(__('[%s] Platba za objednávku %s brzy vyprší', 'events-and-bookings'), $blog, $order->order_number);

        $checkout = EAB_Auth::get_page_url('checkout');
        $payment_url = $checkout ? add_query_arg(array('order' => $order_id, 'method' => 'bank_transfer'), $checkout) : '';

        $body = sprintf(
            __("Dobrý den %s,\n\npřipomínáme platbu objednávky %s (částka %s).\nLhůta platby vyprší: %s\n\n%s\n", 'events-and-bookings'),
            $user->display_name,
            $order->order_number,
            EAB_Payments::format_price($order->total),
            !empty($order->expires_at) ? date_i18n('j. n. Y H:i', strtotime($order->expires_at)) : '—',
            $payment_url ? __('Instrukce k platbě: ', 'events-and-bookings') . $payment_url : ''
        );

        return self::mail($user->user_email, $subject, $body);
    }

    private static function notify_admin_new_order($order_id) {
        $email = get_option('eab_admin_notification_email', get_option('admin_email'));
        if (!$email || !get_option('eab_admin_notification_enabled', 1)) {
            return false;
        }

        $order = EAB_Checkout::get_order($order_id);
        if (!$order) {
            return false;
        }

        $subject = sprintf(__('[%s] Nová objednávka %s', 'events-and-bookings'), get_bloginfo('name'), $order->order_number);
        $body = sprintf(
            __("Nová objednávka %s — %s\n\nSpráva: %s\n", 'events-and-bookings'),
            $order->order_number,
            EAB_Payments::format_price($order->total),
            admin_url('admin.php?page=eab-orders')
        );

        return self::mail($email, $subject, $body);
    }

    public static function send_payment_confirmed_email($order_id) {
        $order = EAB_Checkout::get_order($order_id);
        if (!$order) {
            return false;
        }
        $user = get_userdata($order->user_id);
        if (!$user) {
            return false;
        }

        $blog = wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES);
        $subject = sprintf(__('[%s] Platba přijata – %s', 'events-and-bookings'), $blog, $order->order_number);
        $body = sprintf(
            __("Dobrý den %s,\n\nplatba za objednávku %s byla přijata. Rezervace je potvrzena.\n", 'events-and-bookings'),
            $user->display_name,
            $order->order_number
        );

        return self::mail($user->user_email, $subject, $body);
    }

    private static function mail($to, $subject, $body) {
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        $from_name  = get_option('eab_email_sender_name', get_bloginfo('name'));
        $from_email = get_option('eab_email_sender_email', get_option('admin_email'));
        if ($from_email) {
            $headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';
        }
        return wp_mail($to, $subject, $body, $headers);
    }
}
