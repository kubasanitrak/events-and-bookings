<?php
/**
 * @var array  $profile
 * @var bool   $invoice_enabled
 * @var array  $invoice_data
 * @var string $password_url
 * @var string $user_email
 * @var string $display_name
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<section class="eab-dashboard__panel" data-panel="settings" aria-hidden="true">
    <?php
    $subheader_back = 'overview';
    include EAB_PLUGIN_DIR . 'public/partials/dashboard-subheader.php';
    ?>

    <h2 class="eab-dashboard__title"><?php esc_html_e('nastavení účtu', 'events-and-bookings'); ?></h2>

    <div class="eab-dashboard__notice eab-dashboard__notice--hidden" data-eab-settings-notice role="status" aria-live="polite"></div>

    <form class="eab-dashboard__settings-form" data-eab-dashboard-settings novalidate>
        <fieldset class="eab-dashboard__fieldset">
            <legend class="eab-dashboard__section-label caps"><?php esc_html_e('Osobní údaje', 'events-and-bookings'); ?></legend>
            <div class="eab-dashboard__rule" aria-hidden="true"></div>

            <div class="eab-dashboard__form-row eab-dashboard__form-row--half">
                <div class="eab-dashboard__form-col">
                    <label for="eab_dash_first_name"><?php esc_html_e('Jméno', 'events-and-bookings'); ?></label>
                    <input type="text" id="eab_dash_first_name" name="first_name" value="<?php echo esc_attr($profile['first_name']); ?>" autocomplete="given-name" required>
                </div>
                <div class="eab-dashboard__form-col">
                    <label for="eab_dash_last_name"><?php esc_html_e('Příjmení', 'events-and-bookings'); ?></label>
                    <input type="text" id="eab_dash_last_name" name="last_name" value="<?php echo esc_attr($profile['last_name']); ?>" autocomplete="family-name" required>
                </div>
            </div>

            <div class="eab-dashboard__form-row">
                <label for="eab_dash_email"><?php esc_html_e('E-mail', 'events-and-bookings'); ?></label>
                <input type="email" id="eab_dash_email" value="<?php echo esc_attr($user_email); ?>" readonly disabled>
            </div>

            <div class="eab-dashboard__form-row eab-dashboard__form-row--half">
                <div class="eab-dashboard__form-col">
                    <label for="eab_dash_dob"><?php esc_html_e('Datum narození', 'events-and-bookings'); ?></label>
                    <input type="text" id="eab_dash_dob" name="dob" value="<?php echo esc_attr($profile['dob']); ?>" inputmode="numeric" autocomplete="bday">
                </div>
                <div class="eab-dashboard__form-col">
                    <label for="eab_dash_phone"><?php esc_html_e('Telefon', 'events-and-bookings'); ?></label>
                    <input type="tel" id="eab_dash_phone" name="phone" value="<?php echo esc_attr($profile['phone']); ?>" autocomplete="tel">
                </div>
            </div>
        </fieldset>

        <fieldset class="eab-dashboard__fieldset">
            <legend class="eab-dashboard__section-label caps"><?php esc_html_e('Heslo', 'events-and-bookings'); ?></legend>
            <div class="eab-dashboard__rule" aria-hidden="true"></div>
            <p class="eab-dashboard__hint">
                <a class="textlink textlink-underline" href="<?php echo esc_url($password_url); ?>">
                    <?php esc_html_e('Změnit heslo', 'events-and-bookings'); ?>
                </a>
            </p>
        </fieldset>

        <?php if ($invoice_enabled) : ?>
            <fieldset class="eab-dashboard__fieldset">
                <legend class="eab-dashboard__section-label caps"><?php esc_html_e('Fakturační údaje', 'events-and-bookings'); ?></legend>
                <div class="eab-dashboard__rule" aria-hidden="true"></div>

                <div class="eab-dashboard__form-row">
                    <label for="eab_dash_company_name"><?php esc_html_e('Název firmy', 'events-and-bookings'); ?></label>
                    <input type="text" id="eab_dash_company_name" name="invoice_company_name" value="<?php echo esc_attr($invoice_data['company_name'] ?? ''); ?>">
                </div>

                <div class="eab-dashboard__form-row eab-dashboard__form-row--half">
                    <div class="eab-dashboard__form-col">
                        <label for="eab_dash_street"><?php esc_html_e('Ulice', 'events-and-bookings'); ?></label>
                        <input type="text" id="eab_dash_street" name="invoice_street" value="<?php echo esc_attr($invoice_data['street'] ?? ''); ?>">
                    </div>
                    <div class="eab-dashboard__form-col eab-dashboard__form-col--narrow">
                        <label for="eab_dash_street_number"><?php esc_html_e('Číslo', 'events-and-bookings'); ?></label>
                        <input type="text" id="eab_dash_street_number" name="invoice_street_number" value="<?php echo esc_attr($invoice_data['street_number'] ?? ''); ?>">
                    </div>
                </div>

                <div class="eab-dashboard__form-row eab-dashboard__form-row--half">
                    <div class="eab-dashboard__form-col eab-dashboard__form-col--narrow">
                        <label for="eab_dash_zip"><?php esc_html_e('PSČ', 'events-and-bookings'); ?></label>
                        <input type="text" id="eab_dash_zip" name="invoice_zip" value="<?php echo esc_attr($invoice_data['zip'] ?? ''); ?>">
                    </div>
                    <div class="eab-dashboard__form-col">
                        <label for="eab_dash_city"><?php esc_html_e('Město', 'events-and-bookings'); ?></label>
                        <input type="text" id="eab_dash_city" name="invoice_city" value="<?php echo esc_attr($invoice_data['city'] ?? ''); ?>">
                    </div>
                </div>

                <div class="eab-dashboard__form-row eab-dashboard__form-row--half">
                    <div class="eab-dashboard__form-col">
                        <label for="eab_dash_ic"><?php esc_html_e('IČ', 'events-and-bookings'); ?></label>
                        <input type="text" id="eab_dash_ic" name="invoice_ic" value="<?php echo esc_attr($invoice_data['ic'] ?? ''); ?>">
                    </div>
                    <div class="eab-dashboard__form-col">
                        <label for="eab_dash_dic"><?php esc_html_e('DIČ', 'events-and-bookings'); ?></label>
                        <input type="text" id="eab_dash_dic" name="invoice_dic" value="<?php echo esc_attr($invoice_data['dic'] ?? ''); ?>">
                    </div>
                </div>
            </fieldset>
        <?php endif; ?>

        <div class="eab-dashboard__form-submit">
            <button type="submit" class="btn btn-outline btn-oval caps hover-bgr">
                <?php esc_html_e('Uložit nastavení', 'events-and-bookings'); ?>
            </button>
        </div>
    </form>
</section>
