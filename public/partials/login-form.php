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


<!-- <div id="login">
  <h1 role="presentation" class="wp-login-logo"><a href="//localhost:3000/kamigos.cz/">Kamigos</a></h1>

  <form name="loginform" id="loginform" action="//localhost:3000/kamigos.cz/wp-login.php" method="post">
    <p>
      <label for="user_login">Uživatelské jméno nebo e-mail</label>
      <input type="text" name="log" id="user_login" class="input ltr" value="" size="20" autocapitalize="off" autocomplete="username" required="required">
    </p>

    <div class="user-pass-wrap">
      <label for="user_pass">Heslo</label>
      <div class="wp-pwd">
        <input type="password" name="pwd" id="user_pass" class="input password-input ltr" value="" size="20" autocomplete="current-password" spellcheck="false" required="required" data-kamigos-toggle="1">
        <button type="button" class="button button-secondary wp-hide-pw hide-if-no-js" data-toggle="0" aria-label="Zobrazit heslo">
          <span class="dashicons dashicons-visibility" aria-hidden="true"></span>
        </button>
      </div>
      <button type="button" class="kamigos-password-toggle" aria-label="Zobrazit heslo">Zobrazit heslo</button>
    </div>
    <p class="kamigos-login-forgot">
      <a href="//localhost:3000/kamigos.cz/wp-login.php?action=lostpassword">
        Zapomněli jste heslo? </a>
    </p>
    <p class="forgetmenot"><input name="rememberme" type="checkbox" id="rememberme" value="forever"> <label for="rememberme">Pamatovat si mě</label></p>
    <p class="submit">
      <input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="Přihlásit se">
      <input type="hidden" name="redirect_to" value="//localhost:3000/kamigos.cz/prihlaseni/">
      <input type="hidden" name="testcookie" value="1">
    </p>
  </form>

  <p id="nav">
    <a class="wp-login-lost-password" href="//localhost:3000/kamigos.cz/wp-login.php?action=lostpassword">Zapomněli jste heslo?</a>
  </p>
  
</div>

<div class="kamigos-login-footer">
  <p class="kamigos-login-footer__text">Nemáte ještě účet?</p>
  <a class="kamigos-login-footer__link" href="//localhost:3000/kamigos.cz/registrace/"> Zaregistrujte se </a>
</div> -->