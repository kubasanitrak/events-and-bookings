<?php
/**
 * Set password after e-mail verification.
 *
 * @var int    $uid
 * @var string $key
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<form class="eab-auth-form eab-auth-form--set-password" method="post" action="">
    <?php wp_nonce_field(EAB_Auth::ACTION_SET_PASSWORD); ?>
    <input type="hidden" name="eab_auth_action" value="<?php echo esc_attr(EAB_Auth::ACTION_SET_PASSWORD); ?>">
    <input type="hidden" name="eab_user_id" value="<?php echo esc_attr($uid); ?>">
    <input type="hidden" name="eab_password_key" value="<?php echo esc_attr($key); ?>">

    <div class="eab-auth-form__row">
        <label for="eab_password"><?php esc_html_e('Heslo', 'events-and-bookings'); ?> <span class="required">*</span></label>
        <input type="password" name="eab_password" id="eab_password" required minlength="8" autocomplete="new-password">
    </div>

    <div class="eab-auth-form__row">
        <label for="eab_password_confirm"><?php esc_html_e('Heslo znovu', 'events-and-bookings'); ?> <span class="required">*</span></label>
        <input type="password" name="eab_password_confirm" id="eab_password_confirm" required minlength="8" autocomplete="new-password">
    </div>

    <p class="eab-auth-form__hint"><?php esc_html_e('Minimálně 8 znaků.', 'events-and-bookings'); ?></p>

    <div class="eab-auth-form__actions">
        <button type="submit" class="eab-btn"><?php esc_html_e('Uložit heslo', 'events-and-bookings'); ?></button>
    </div>
</form>
