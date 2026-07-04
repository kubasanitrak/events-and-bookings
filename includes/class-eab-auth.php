<?php
/**
 * Member registration, e-mail verification, login, password setup.
 */

if (!defined('ABSPATH')) {
    exit;
}

class EAB_Auth {

    const META_FIRST_NAME           = 'eab_first_name';
    const META_LAST_NAME            = 'eab_last_name';
    const META_DOB                  = 'eab_dob';
    const META_PHONE                = 'eab_phone';
    const META_GDPR_AT              = 'eab_gdpr_at';
    const META_NEWSLETTER           = 'eab_newsletter';
    const META_PENDING_VERIFICATION = 'eab_pending_verification';
    const META_EMAIL_VERIFIED       = 'eab_email_verified';
    const META_VERIFY_TOKEN_HASH    = 'eab_verify_token_hash';
    const META_PASSWORD_KEY         = 'eab_password_key';
    const META_PASSWORD_KEY_EXPIRES = 'eab_password_key_expires';

    const ACTION_REGISTER     = 'eab_register';
    const ACTION_LOGIN        = 'eab_login';
    const ACTION_SET_PASSWORD = 'eab_set_password';

    const QUERY_VERIFY = 'eab_verify';
    const QUERY_UID    = 'eab_uid';
    const QUERY_TOKEN  = 'eab_token';

    const QUERY_SET_PASSWORD = 'eab_set_password';
    const QUERY_SET_UID      = 'eab_set_uid';
    const QUERY_SET_KEY      = 'eab_set_key';

    public function __construct() {
        add_action('init', array($this, 'handle_verification_link'), 5);
        add_action('init', array($this, 'handle_form_posts'), 6);
        add_action('template_redirect', array($this, 'redirect_logged_in_from_register'));
        add_action('login_init', array($this, 'redirect_wp_register'));
        add_filter('register_url', array($this, 'filter_register_url'));
        add_filter('authenticate', array($this, 'block_unverified_login'), 30, 3);
        add_shortcode('eab_register', array($this, 'shortcode_register'));
        add_shortcode('eab_login', array($this, 'shortcode_login'));
        add_shortcode('eab_set_password', array($this, 'shortcode_set_password'));
        add_filter('eab_enqueue_public_assets', array($this, 'enqueue_assets_flag'));
    }

    public function redirect_wp_register() {
        if (isset($_GET['action']) && 'register' === $_GET['action']) {
            $url = self::get_page_url('register');
            if ($url) {
                wp_safe_redirect($url);
                exit;
            }
        }
    }

    public function filter_register_url($url) {
        $custom = self::get_page_url('register');
        return $custom ?: $url;
    }

    public function redirect_logged_in_from_register() {
        if (!is_user_logged_in()) {
            return;
        }

        $page_ids = get_option('eab_page_ids', array());
        $register_id = is_array($page_ids) ? (int) ($page_ids['register'] ?? 0) : 0;
        if (!$register_id || !is_page($register_id)) {
            return;
        }

        $dashboard = self::get_page_url('dashboard');
        wp_safe_redirect($dashboard ?: home_url('/'));
        exit;
    }

    public function enqueue_assets_flag($load) {
        return $load || $this->current_page_has_auth_shortcode();
    }

    private function current_page_has_auth_shortcode() {
        $post = get_post();
        if (!$post || empty($post->post_content)) {
            return false;
        }
        foreach (array('eab_register', 'eab_login', 'eab_set_password') as $tag) {
            if (has_shortcode($post->post_content, $tag)) {
                return true;
            }
        }
        return false;
    }

    public static function get_page_url($key) {
        $ids = get_option('eab_page_ids', array());
        if (!empty($ids[$key])) {
            $url = get_permalink((int) $ids[$key]);
            if ($url) {
                return $url;
            }
        }
        return '';
    }

    public static function get_terms_url() {
        $terms_page = (int) get_option('eab_terms_page', 0);
        if ($terms_page) {
            $url = get_permalink($terms_page);
            if ($url) {
                return $url;
            }
        }

        $terms_page = get_page_by_path('podminky');
        if ($terms_page) {
            return get_permalink($terms_page);
        }

        $privacy = get_privacy_policy_url();
        return $privacy ?: home_url('/');
    }

    /**
     * Generate a unique username from e-mail local part.
     */
    public static function generate_username($email) {
        $base = sanitize_user(current(explode('@', $email)), true);

        if ($base === '') {
            $base = 'user';
        }

        $username = $base;
        $suffix   = 1;

        while (username_exists($username)) {
            $username = $base . $suffix;
            ++$suffix;
        }

        return $username;
    }

    public static function is_email_verified($user_id) {
        return (bool) get_user_meta($user_id, self::META_EMAIL_VERIFIED, true);
    }

    public static function is_pending_verification($user_id) {
        return (bool) get_user_meta($user_id, self::META_PENDING_VERIFICATION, true);
    }

    /**
     * E-mail verification from link (?eab_verify=1&eab_uid=&eab_token=).
     */
    public function handle_verification_link() {
        if (empty($_GET[self::QUERY_VERIFY])) {
            return;
        }

        $uid   = isset($_GET[self::QUERY_UID]) ? (int) $_GET[self::QUERY_UID] : 0;
        $token = isset($_GET[self::QUERY_TOKEN]) ? sanitize_text_field(wp_unslash($_GET[self::QUERY_TOKEN])) : '';

        if (!$uid || $token === '') {
            return;
        }

        $result = self::verify_email($uid, $token);
        $redirect = self::get_page_url('set_password');

        if (!$redirect) {
            $redirect = home_url('/');
        }

        if (is_wp_error($result)) {
            $redirect = add_query_arg(array(
                'eab_auth_error' => rawurlencode($result->get_error_code()),
            ), self::get_page_url('login') ?: $redirect);
        } else {
            $redirect = add_query_arg(array(
                self::QUERY_SET_PASSWORD => '1',
                self::QUERY_SET_UID      => $uid,
                self::QUERY_SET_KEY      => rawurlencode($result['key']),
                'eab_auth_success'       => 'verified',
            ), $redirect);
        }

        wp_safe_redirect($redirect);
        exit;
    }

    public function handle_form_posts() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['eab_auth_action'])) {
            return;
        }

        $action = sanitize_key(wp_unslash($_POST['eab_auth_action']));

        switch ($action) {
            case self::ACTION_REGISTER:
                $this->process_register_post();
                break;
            case self::ACTION_LOGIN:
                $this->process_login_post();
                break;
            case self::ACTION_SET_PASSWORD:
                $this->process_set_password_post();
                break;
        }
    }

    private function process_register_post() {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), self::ACTION_REGISTER)) {
            $this->redirect_with_message('register', 'invalid_nonce');
        }

        $result = self::register_member($_POST);

        if (is_wp_error($result)) {
            $this->redirect_with_message('register', $result->get_error_code(), $result->get_error_message());
        }

        $this->redirect_with_success('register', 'verification_sent');
    }

    private function process_login_post() {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), self::ACTION_LOGIN)) {
            $this->redirect_with_message('login', 'invalid_nonce');
        }

        $login    = isset($_POST['eab_login']) ? sanitize_text_field(wp_unslash($_POST['eab_login'])) : '';
        $password = isset($_POST['eab_password']) ? (string) $_POST['eab_password'] : '';
        $remember = !empty($_POST['eab_remember']);

        $user = self::authenticate_member($login, $password);

        if (is_wp_error($user)) {
            $this->redirect_with_message('login', $user->get_error_code(), $user->get_error_message());
        }

        wp_set_auth_cookie($user->ID, $remember);
        wp_set_current_user($user->ID);

        $redirect = isset($_POST['eab_redirect']) ? esc_url_raw(wp_unslash($_POST['eab_redirect'])) : '';
        if (!$redirect || !wp_validate_redirect($redirect, false)) {
            $redirect = self::get_page_url('dashboard');
            if (!$redirect) {
                $redirect = home_url('/');
            }
        }

        wp_safe_redirect($redirect);
        exit;
    }

    private function process_set_password_post() {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), self::ACTION_SET_PASSWORD)) {
            $this->redirect_with_message('set_password', 'invalid_nonce');
        }

        $uid  = isset($_POST['eab_user_id']) ? (int) $_POST['eab_user_id'] : 0;
        $key  = isset($_POST['eab_password_key']) ? sanitize_text_field(wp_unslash($_POST['eab_password_key'])) : '';
        $pass = isset($_POST['eab_password']) ? (string) $_POST['eab_password'] : '';
        $pass2 = isset($_POST['eab_password_confirm']) ? (string) $_POST['eab_password_confirm'] : '';

        $result = self::set_member_password($uid, $key, $pass, $pass2);

        if (is_wp_error($result)) {
            $this->redirect_with_message('set_password', $result->get_error_code(), $result->get_error_message(), array(
                self::QUERY_SET_PASSWORD => '1',
                self::QUERY_SET_UID      => $uid,
                self::QUERY_SET_KEY      => rawurlencode($key),
            ));
        }

        wp_set_auth_cookie($uid, true);
        wp_set_current_user($uid);

        $redirect = self::get_page_url('dashboard');
        if (!$redirect) {
            $redirect = home_url('/');
        }
        wp_safe_redirect($redirect);
        exit;
    }

    private function redirect_with_success($page_key, $code) {
        $url = self::get_page_url($page_key) ?: home_url('/');
        wp_safe_redirect(add_query_arg('eab_auth_success', $code, $url));
        exit;
    }

    private function redirect_with_message($page_key, $code, $message = '', $extra_args = array()) {
        $url = self::get_page_url($page_key) ?: home_url('/');
        $args = array_merge(array(
            'eab_auth_error' => rawurlencode($code),
        ), $extra_args);
        if ($message) {
            $args['eab_auth_message'] = rawurlencode($message);
        }
        wp_safe_redirect(add_query_arg($args, $url));
        exit;
    }

    /**
     * @param array $data $_POST
     * @return int|WP_Error user ID
     */
    public static function register_member($data) {
        $first = isset($data['first_name']) ? sanitize_text_field(wp_unslash($data['first_name']))
            : (isset($data['eab_first_name']) ? sanitize_text_field(wp_unslash($data['eab_first_name'])) : '');
        $last = isset($data['last_name']) ? sanitize_text_field(wp_unslash($data['last_name']))
            : (isset($data['eab_last_name']) ? sanitize_text_field(wp_unslash($data['eab_last_name'])) : '');
        $dob = isset($data['birth_date']) ? sanitize_text_field(wp_unslash($data['birth_date']))
            : (isset($data['eab_dob']) ? sanitize_text_field(wp_unslash($data['eab_dob'])) : '');
        $email = isset($data['email']) ? sanitize_email(wp_unslash($data['email']))
            : (isset($data['eab_email']) ? sanitize_email(wp_unslash($data['eab_email'])) : '');
        $phone = isset($data['phone']) ? sanitize_text_field(wp_unslash($data['phone']))
            : (isset($data['eab_phone']) ? sanitize_text_field(wp_unslash($data['eab_phone'])) : '');
        $gdpr = !empty($data['agreement']) || !empty($data['eab_gdpr']);
        $newsletter = !empty($data['newsletter']) || !empty($data['eab_newsletter']);

        if ($first === '') {
            return new WP_Error('first_name', __('Vyplňte prosím jméno.', 'events-and-bookings'));
        }
        if ($last === '') {
            return new WP_Error('last_name', __('Vyplňte prosím příjmení.', 'events-and-bookings'));
        }
        if ($email === '' || !is_email($email)) {
            return new WP_Error('email', __('Zadejte platný e-mail.', 'events-and-bookings'));
        }
        if (email_exists($email)) {
            return new WP_Error('email_exists', __('Účet s tímto e-mailem již existuje.', 'events-and-bookings'));
        }
        if ($phone === '') {
            return new WP_Error('phone', __('Vyplňte prosím telefon.', 'events-and-bookings'));
        }
        if (!$gdpr) {
            return new WP_Error('agreement', __('Musíte souhlasit s podmínkami.', 'events-and-bookings'));
        }

        $login = isset($data['eab_username']) ? sanitize_user(wp_unslash($data['eab_username']), true) : '';
        if ($login === '') {
            $login = self::generate_username($email);
        } elseif (!validate_username($login)) {
            return new WP_Error('invalid_username', __('Neplatné uživatelské jméno.', 'events-and-bookings'));
        } elseif (username_exists($login)) {
            return new WP_Error('username_exists', __('Toto uživatelské jméno je obsazené.', 'events-and-bookings'));
        }

        $dob_normalized = self::normalize_dob($dob);
        if (is_wp_error($dob_normalized)) {
            return new WP_Error('birth_date', __('Zadejte platné datum narození (dd.mm.rrrr).', 'events-and-bookings'));
        }

        $temp_password = wp_generate_password(24, true, true);
        $user_id = wp_insert_user(array(
            'user_login'   => $login,
            'user_email'   => $email,
            'user_pass'    => $temp_password,
            'first_name'   => $first,
            'last_name'    => $last,
            'display_name' => trim($first . ' ' . $last),
            'role'         => EAB_Roles::MEMBER_ROLE,
        ));

        if (is_wp_error($user_id)) {
            return new WP_Error('registration_failed', __('Registraci se nepodařilo dokončit. Zkuste to prosím znovu.', 'events-and-bookings'));
        }

        update_user_meta($user_id, self::META_FIRST_NAME, $first);
        update_user_meta($user_id, self::META_LAST_NAME, $last);
        update_user_meta($user_id, self::META_DOB, $dob_normalized);
        update_user_meta($user_id, self::META_PHONE, $phone);
        update_user_meta($user_id, self::META_GDPR_AT, current_time('mysql'));
        update_user_meta($user_id, self::META_NEWSLETTER, $newsletter ? '1' : '0');
        update_user_meta($user_id, self::META_PENDING_VERIFICATION, '1');
        update_user_meta($user_id, self::META_EMAIL_VERIFIED, '0');

        $token = wp_generate_password(32, false, false);
        update_user_meta($user_id, self::META_VERIFY_TOKEN_HASH, wp_hash_password($token));

        EAB_Emails::send_verification_email($user_id, $token);

        if ($newsletter) {
            do_action('eab_newsletter_subscribed', $user_id, $email);
        }

        return $user_id;
    }

    /**
     * @return array{key:string}|WP_Error
     */
    public static function verify_email($user_id, $token) {
        $user = get_userdata($user_id);
        if (!$user) {
            return new WP_Error('invalid_user', __('Neplatný odkaz.', 'events-and-bookings'));
        }

        $hash = get_user_meta($user_id, self::META_VERIFY_TOKEN_HASH, true);
        if (!$hash || !wp_check_password($token, $hash)) {
            return new WP_Error('invalid_token', __('Ověřovací odkaz je neplatný nebo vypršel.', 'events-and-bookings'));
        }

        if (self::is_email_verified($user_id)) {
            return self::issue_password_key($user_id);
        }

        update_user_meta($user_id, self::META_EMAIL_VERIFIED, '1');
        delete_user_meta($user_id, self::META_PENDING_VERIFICATION);
        delete_user_meta($user_id, self::META_VERIFY_TOKEN_HASH);

        return self::issue_password_key($user_id);
    }

    /**
     * @return array{key:string}
     */
    private static function issue_password_key($user_id) {
        $key = wp_generate_password(32, false, false);
        update_user_meta($user_id, self::META_PASSWORD_KEY, wp_hash_password($key));
        update_user_meta($user_id, self::META_PASSWORD_KEY_EXPIRES, time() + DAY_IN_SECONDS);

        return array('key' => $key);
    }

    public static function set_member_password($user_id, $key, $password, $password_confirm) {
        $user = get_userdata($user_id);
        if (!$user) {
            return new WP_Error('invalid_user', __('Neplatný požadavek.', 'events-and-bookings'));
        }

        if (!self::is_email_verified($user_id)) {
            return new WP_Error('not_verified', __('Nejdříve ověřte e-mail.', 'events-and-bookings'));
        }

        if (!self::validate_password_key($user_id, $key)) {
            return new WP_Error('invalid_key', __('Odkaz pro nastavení hesla vypršel. Požádejte o nový ověřovací e-mail.', 'events-and-bookings'));
        }

        if (strlen($password) < 8) {
            return new WP_Error('weak_password', __('Heslo musí mít alespoň 8 znaků.', 'events-and-bookings'));
        }

        if ($password !== $password_confirm) {
            return new WP_Error('password_mismatch', __('Hesla se neshodují.', 'events-and-bookings'));
        }

        wp_set_password($password, $user_id);
        delete_user_meta($user_id, self::META_PASSWORD_KEY);
        delete_user_meta($user_id, self::META_PASSWORD_KEY_EXPIRES);

        return true;
    }

    private static function validate_password_key($user_id, $key) {
        $hash = get_user_meta($user_id, self::META_PASSWORD_KEY, true);
        $expires = (int) get_user_meta($user_id, self::META_PASSWORD_KEY_EXPIRES, true);

        if (!$hash || $expires < time()) {
            return false;
        }

        return wp_check_password($key, $hash);
    }

    /**
     * @return WP_User|WP_Error
     */
    public static function authenticate_member($login, $password) {
        if ($login === '' || $password === '') {
            return new WP_Error('empty_credentials', __('Zadejte přihlašovací údaje.', 'events-and-bookings'));
        }

        $user = wp_authenticate($login, $password);

        if (is_wp_error($user)) {
            return new WP_Error('invalid_login', __('Nesprávný e-mail / uživatelské jméno nebo heslo.', 'events-and-bookings'));
        }

        if (!self::is_email_verified($user->ID) && self::is_pending_verification($user->ID)) {
            return new WP_Error('not_verified', __('Účet není ověřen. Zkontrolujte e-mail s ověřovacím odkazem.', 'events-and-bookings'));
        }

        return $user;
    }

    public function block_unverified_login($user, $username, $password) {
        if ($user instanceof WP_User && self::is_pending_verification($user->ID) && !self::is_email_verified($user->ID)) {
            return new WP_Error(
                'eab_not_verified',
                __('Účet není ověřen. Zkontrolujte e-mail s ověřovacím odkazem.', 'events-and-bookings')
            );
        }
        return $user;
    }

    public static function normalize_dob($value) {
        $value = trim($value);
        if ($value === '') {
            return new WP_Error('invalid_dob', __('Zadejte datum narození.', 'events-and-bookings'));
        }

        $ts = strtotime($value);
        if (!$ts) {
            if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $value, $m)) {
                $ts = mktime(0, 0, 0, (int) $m[2], (int) $m[1], (int) $m[3]);
            }
        }

        if (!$ts) {
            return new WP_Error('invalid_dob', __('Neplatné datum narození.', 'events-and-bookings'));
        }

        return gmdate('Y-m-d', $ts);
    }

    public static function get_verification_url($user_id, $token) {
        return add_query_arg(array(
            self::QUERY_VERIFY => '1',
            self::QUERY_UID    => $user_id,
            self::QUERY_TOKEN  => rawurlencode($token),
        ), home_url('/'));
    }

    public function shortcode_register() {
        ob_start();
        echo '<div class="auth-page" data-theme="ochre">';
        echo '<section class="auth-section section full-width">';
        $this->render_messages();
        include EAB_PLUGIN_DIR . 'public/partials/register-form.php';
        echo '</section>';
        echo '</div>';
        return ob_get_clean();
    }

    public function shortcode_login() {
        if (is_user_logged_in()) {
            $redirect = isset($_GET['redirect_to']) ? esc_url_raw(wp_unslash($_GET['redirect_to'])) : '';
            if ($redirect && wp_validate_redirect($redirect, false)) {
                return '<p class="eab-auth-notice"><a href="' . esc_url($redirect) . '">' . esc_html__('Pokračovat', 'events-and-bookings') . '</a></p>';
            }
            return '<p class="eab-auth-notice">' . esc_html__('Jste již přihlášeni.', 'events-and-bookings') . '</p>';
        }

        ob_start();
        $this->render_messages();
        $redirect = isset($_GET['redirect_to']) ? esc_url_raw(wp_unslash($_GET['redirect_to'])) : '';
        include EAB_PLUGIN_DIR . 'public/partials/login-form.php';
        return ob_get_clean();
    }

    public function shortcode_set_password() {
        if (is_user_logged_in() && self::is_email_verified(get_current_user_id())) {
            return '<p class="eab-auth-notice">' . esc_html__('Heslo již máte nastavené. Můžete ho změnit v profilu WordPressu.', 'events-and-bookings') . '</p>';
        }

        $uid = isset($_GET[self::QUERY_SET_UID]) ? (int) $_GET[self::QUERY_SET_UID] : 0;
        $key = isset($_GET[self::QUERY_SET_KEY]) ? sanitize_text_field(wp_unslash($_GET[self::QUERY_SET_KEY])) : '';

        if (!$uid || $key === '' || !self::validate_password_key($uid, $key)) {
            return '<p class="eab-auth-notice eab-auth-notice--error">' .
                esc_html__('Odkaz pro nastavení hesla je neplatný nebo vypršel. Ověřte e-mail znovu nebo kontaktujte správce.', 'events-and-bookings') .
                '</p>';
        }

        ob_start();
        $this->render_messages();
        include EAB_PLUGIN_DIR . 'public/partials/set-password-form.php';
        return ob_get_clean();
    }

    private function render_messages() {
        $codes = array(
            'verification_sent' => __('Registrace proběhla. Na váš e-mail jsme odeslali ověřovací odkaz — po kliknutí si nastavíte heslo.', 'events-and-bookings'),
            'verified'          => __('E-mail byl ověřen. Nastavte si heslo níže.', 'events-and-bookings'),
            'password_set'      => __('Heslo bylo uloženo. Nyní se můžete přihlásit.', 'events-and-bookings'),
            'invalid_nonce'     => __('Platnost formuláře vypršela. Odešlete ho znovu.', 'events-and-bookings'),
            'first_name'        => __('Vyplňte prosím jméno.', 'events-and-bookings'),
            'last_name'         => __('Vyplňte prosím příjmení.', 'events-and-bookings'),
            'birth_date'        => __('Zadejte platné datum narození (dd.mm.rrrr).', 'events-and-bookings'),
            'email'             => __('Zadejte platný e-mail.', 'events-and-bookings'),
            'email_exists'      => __('Účet s tímto e-mailem již existuje.', 'events-and-bookings'),
            'phone'             => __('Vyplňte prosím telefon.', 'events-and-bookings'),
            'agreement'         => __('Musíte souhlasit s podmínkami.', 'events-and-bookings'),
            'registration_failed' => __('Registraci se nepodařilo dokončit. Zkuste to prosím znovu.', 'events-and-bookings'),
            'username_exists'   => __('Toto uživatelské jméno je obsazené.', 'events-and-bookings'),
            'gdpr_required'     => __('Musíte souhlasit se zpracováním osobních údajů.', 'events-and-bookings'),
            'missing_fields'    => __('Vyplňte prosím všechna povinná pole.', 'events-and-bookings'),
            'invalid_login'     => __('Nesprávný e-mail / uživatelské jméno nebo heslo.', 'events-and-bookings'),
            'not_verified'      => __('Účet není ověřen. Zkontrolujte e-mail.', 'events-and-bookings'),
            'password_mismatch' => __('Hesla se neshodují.', 'events-and-bookings'),
            'weak_password'     => __('Heslo musí mít alespoň 8 znaků.', 'events-and-bookings'),
        );

        if (!empty($_GET['eab_auth_success'])) {
            $code = sanitize_key(wp_unslash($_GET['eab_auth_success']));
            if (isset($codes[$code])) {
                echo '<div class="auth-notice auth-notice--success" role="status">';
                echo '<p>' . esc_html($codes[$code]) . '</p>';
                echo '</div>';
            }
        }

        if (!empty($_GET['eab_auth_error'])) {
            $code = sanitize_key(rawurldecode(wp_unslash($_GET['eab_auth_error'])));
            $msg  = !empty($_GET['eab_auth_message'])
                ? sanitize_text_field(rawurldecode(wp_unslash($_GET['eab_auth_message'])))
                : (isset($codes[$code]) ? $codes[$code] : __('Došlo k chybě.', 'events-and-bookings'));
            echo '<div class="auth-notice auth-notice--error" role="alert">';
            echo '<p>' . esc_html($msg) . '</p>';
            echo '</div>';
        }
    }
}
