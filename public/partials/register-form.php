<?php
/**
 * Member registration form (Kamigos auth layout).
 */

if (!defined('ABSPATH')) {
    exit;
}

$terms_url = EAB_Auth::get_terms_url();
$login_url = EAB_Event::get_login_url();
?>
<form class="auth-form" method="post" action="" novalidate>
    <?php wp_nonce_field(EAB_Auth::ACTION_REGISTER); ?>
    <input type="hidden" name="eab_auth_action" value="<?php echo esc_attr(EAB_Auth::ACTION_REGISTER); ?>">

    <h1 class="auth-form__title"><?php esc_html_e('registrace', 'events-and-bookings'); ?></h1>

    <div class="auth-form__row auth-form__row--half">
        <div class="auth-form__col auth-form__field">
            <label for="first_name"><?php esc_html_e('Jméno', 'events-and-bookings'); ?></label>
            <input type="text" id="first_name" name="first_name" placeholder="Anna" autocomplete="given-name" required>
        </div>
        <div class="auth-form__col auth-form__field">
            <label for="last_name"><?php esc_html_e('Příjmení', 'events-and-bookings'); ?></label>
            <input type="text" id="last_name" name="last_name" placeholder="Beachová" autocomplete="family-name" required>
        </div>
    </div>

    <div class="auth-form__row auth-form__field">
        <label for="birth_date"><?php esc_html_e('Datum narození', 'events-and-bookings'); ?></label>
        <input type="text" id="birth_date" name="birth_date" placeholder="dd.mm.rrrr" inputmode="numeric" autocomplete="bday" required>
    </div>
    
    <div class="auth-form__row auth-form__row--wrap-on-land">
        <div class="auth-form__col auth-form__field">
            <label for="email"><?php esc_html_e('E-mail', 'events-and-bookings'); ?></label>
            <input type="email" id="email" name="email" placeholder="beachova@email.com" autocomplete="email" required>
        </div>

        <div class="auth-form__col auth-form__field">
            <label for="phone"><?php esc_html_e('Telefon', 'events-and-bookings'); ?></label>
            <input type="tel" id="phone" name="phone" placeholder="+420 123 456 789" autocomplete="tel" required>
        </div>
    </div>

    <div class="auth-form__checkboxes">
        <label class="auth-checkbox">
            <input type="checkbox" name="agreement" value="1" required>
            <span>
                <?php
                printf(
                    wp_kses(
                        /* translators: %s: terms and conditions URL */
                        __('Souhlasím s <a class="textlink textlink-underline" href="%s">podmínkami</a> a ochranou osobních&nbsp;údajů.', 'events-and-bookings'),
                        array('a' => array('class' => array(), 'href' => array()))
                    ),
                    esc_url($terms_url)
                );
                ?>
            </span>
        </label>

        <label class="auth-checkbox">
            <input type="checkbox" name="newsletter" value="1">
            <span><?php esc_html_e('Chci dostávat info o nových akcích.', 'events-and-bookings'); ?></span>
        </label>
    </div>

    <div class="auth-form__submit">
        <button type="submit" class="btn btn-outline btn-oval caps hover-bgr">
            <?php esc_html_e('Registrovat se', 'events-and-bookings'); ?>
        </button>
    </div>

    <div class="auth-form__footer">
        <p><?php esc_html_e('Už máte účet?', 'events-and-bookings'); ?></p>
        <a class="textlink textlink-underline" href="<?php echo esc_url($login_url); ?>">
            <?php esc_html_e('Přihlaste se', 'events-and-bookings'); ?>
        </a>
    </div>
</form>
