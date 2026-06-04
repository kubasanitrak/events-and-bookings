<?php
/**
 * Member login form.
 *
 * @var string $redirect
 */

if (!defined('ABSPATH')) {
    exit;
}

$register_url = EAB_Auth::get_page_url('register') ?: EAB_Event::get_register_url();
?>
<form class="eab-auth-form eab-auth-form--login" method="post" action="">
    <?php wp_nonce_field(EAB_Auth::ACTION_LOGIN); ?>
    <input type="hidden" name="eab_auth_action" value="<?php echo esc_attr(EAB_Auth::ACTION_LOGIN); ?>">
    <?php if (!empty($redirect)) : ?>
        <input type="hidden" name="eab_redirect" value="<?php echo esc_attr($redirect); ?>">
    <?php endif; ?>

    <div class="eab-auth-form__row">
        <label for="eab_login"><?php esc_html_e('E-mail nebo uživatelské jméno', 'events-and-bookings'); ?></label>
        <input type="text" name="eab_login" id="eab_login" required autocomplete="username">
    </div>

    <div class="eab-auth-form__row">
        <label for="eab_password"><?php esc_html_e('Heslo', 'events-and-bookings'); ?></label>
        <input type="password" name="eab_password" id="eab_password" required autocomplete="current-password">
    </div>

    <div class="eab-auth-form__row">
        <label class="eab-checkbox">
            <input type="checkbox" name="eab_remember" value="1">
            <span><?php esc_html_e('Zapamatovat přihlášení', 'events-and-bookings'); ?></span>
        </label>
    </div>

    <div class="eab-auth-form__actions">
        <button type="submit" class="eab-btn"><?php esc_html_e('Přihlásit', 'events-and-bookings'); ?></button>
    </div>

    <p class="eab-auth-form__footer">
        <?php esc_html_e('Nemáte účet?', 'events-and-bookings'); ?>
        <a href="<?php echo esc_url($register_url); ?>"><?php esc_html_e('Registrovat', 'events-and-bookings'); ?></a>
    </p>
</form>
