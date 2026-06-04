<?php
/**
 * Member registration form.
 */

if (!defined('ABSPATH')) {
    exit;
}

$gdpr_page = (int) get_option('eab_gdpr_page', 0);
$gdpr_link = $gdpr_page ? get_permalink($gdpr_page) : '';
$login_url = EAB_Auth::get_page_url('login') ?: EAB_Event::get_login_url();
?>
<form class="eab-auth-form eab-auth-form--register" method="post" action="">
    <?php wp_nonce_field(EAB_Auth::ACTION_REGISTER); ?>
    <input type="hidden" name="eab_auth_action" value="<?php echo esc_attr(EAB_Auth::ACTION_REGISTER); ?>">

    <div class="eab-auth-form__row eab-auth-form__row--half">
        <div class="eab-auth-form__col">
            <label for="eab_first_name"><?php esc_html_e('Jméno', 'events-and-bookings'); ?> <span class="required">*</span></label>
            <input type="text" name="eab_first_name" id="eab_first_name" required autocomplete="given-name">
        </div>
        <div class="eab-auth-form__col">
            <label for="eab_last_name"><?php esc_html_e('Příjmení', 'events-and-bookings'); ?> <span class="required">*</span></label>
            <input type="text" name="eab_last_name" id="eab_last_name" required autocomplete="family-name">
        </div>
    </div>

    <div class="eab-auth-form__row">
        <label for="eab_dob"><?php esc_html_e('Datum narození', 'events-and-bookings'); ?> <span class="required">*</span></label>
        <input type="date" name="eab_dob" id="eab_dob" required>
    </div>

    <div class="eab-auth-form__row">
        <label for="eab_email"><?php esc_html_e('E-mail', 'events-and-bookings'); ?> <span class="required">*</span></label>
        <input type="email" name="eab_email" id="eab_email" required autocomplete="email">
    </div>

    <div class="eab-auth-form__row">
        <label for="eab_username"><?php esc_html_e('Uživatelské jméno', 'events-and-bookings'); ?> <span class="required">*</span></label>
        <input type="text" name="eab_username" id="eab_username" required autocomplete="username">
    </div>

    <div class="eab-auth-form__row">
        <label for="eab_phone"><?php esc_html_e('Telefon', 'events-and-bookings'); ?> <span class="required">*</span></label>
        <input type="tel" name="eab_phone" id="eab_phone" required autocomplete="tel">
    </div>

    <div class="eab-auth-form__row eab-auth-form__checkboxes">
        <label class="eab-checkbox">
            <input type="checkbox" name="eab_gdpr" value="1" required>
            <span>
                <?php esc_html_e('Souhlasím se zpracováním osobních údajů', 'events-and-bookings'); ?>
                <?php if ($gdpr_link) : ?>
                    (<a href="<?php echo esc_url($gdpr_link); ?>" target="_blank" rel="noopener"><?php esc_html_e('více informací', 'events-and-bookings'); ?></a>)
                <?php endif; ?>
                <span class="required">*</span>
            </span>
        </label>
        <label class="eab-checkbox">
            <input type="checkbox" name="eab_newsletter" value="1">
            <span><?php esc_html_e('Chci dostávat novinky e-mailem', 'events-and-bookings'); ?></span>
        </label>
    </div>

    <p class="eab-auth-form__hint"><?php esc_html_e('Heslo si nastavíte po ověření e-mailu.', 'events-and-bookings'); ?></p>

    <div class="eab-auth-form__actions">
        <button type="submit" class="eab-btn"><?php esc_html_e('Registrovat', 'events-and-bookings'); ?></button>
    </div>

    <p class="eab-auth-form__footer">
        <?php esc_html_e('Již máte účet?', 'events-and-bookings'); ?>
        <a href="<?php echo esc_url($login_url); ?>"><?php esc_html_e('Přihlásit se', 'events-and-bookings'); ?></a>
    </p>
</form>
