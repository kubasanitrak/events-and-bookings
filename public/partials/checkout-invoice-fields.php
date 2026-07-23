<?php
/**
 * Optional company invoice fields (checkout / profile).
 *
 * @var array $saved_invoice Keys from EAB_Invoice::field_map().
 * @var bool  $has_saved_invoice
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!EAB_Settings::checkout_invoice_enabled()) {
    return;
}
?>
<fieldset class="eab-checkout__section eab-invoice-section">
    <legend class="eab-checkout__section-label caps"><?php esc_html_e('Faktura (volitelné)', 'events-and-bookings'); ?></legend>
    <div class="eab-checkout__rule" aria-hidden="true"></div>

    <label class="auth-checkbox">
        <input type="checkbox" name="want_invoice" id="eab_want_invoice" value="1" <?php checked(!empty($has_saved_invoice)); ?>>
        <span><?php esc_html_e('Chci fakturu na firmu', 'events-and-bookings'); ?></span>
    </label>

    <div class="eab-invoice-fields" <?php echo empty($has_saved_invoice) ? 'hidden' : ''; ?>>
        <div class="auth-form__row auth-form__field">
            <label for="eab_invoice_company_name"><?php esc_html_e('Název', 'events-and-bookings'); ?> <span class="required">*</span></label>
            <input type="text" name="invoice_company_name" id="eab_invoice_company_name"
                   value="<?php echo esc_attr($saved_invoice['company_name'] ?? ''); ?>" autocomplete="organization">
        </div>
        <div class="auth-form__row auth-form__row--half">
            <div class="auth-form__col auth-form__field">
                <label for="eab_invoice_street"><?php esc_html_e('Ulice', 'events-and-bookings'); ?> <span class="required">*</span></label>
                <input type="text" name="invoice_street" id="eab_invoice_street"
                       value="<?php echo esc_attr($saved_invoice['street'] ?? ''); ?>" autocomplete="address-line1">
            </div>
            <div class="auth-form__col auth-form__field">
                <label for="eab_invoice_street_number"><?php esc_html_e('Číslo', 'events-and-bookings'); ?> <span class="required">*</span></label>
                <input type="text" name="invoice_street_number" id="eab_invoice_street_number"
                       value="<?php echo esc_attr($saved_invoice['street_number'] ?? ''); ?>" autocomplete="address-line2">
            </div>
        </div>
        <div class="auth-form__row auth-form__row--half">
            <div class="auth-form__col auth-form__field">
                <label for="eab_invoice_zip"><?php esc_html_e('PSČ', 'events-and-bookings'); ?> <span class="required">*</span></label>
                <input type="text" name="invoice_zip" id="eab_invoice_zip"
                       value="<?php echo esc_attr($saved_invoice['zip'] ?? ''); ?>" autocomplete="postal-code">
            </div>
            <div class="auth-form__col auth-form__field">
                <label for="eab_invoice_city"><?php esc_html_e('Město', 'events-and-bookings'); ?> <span class="required">*</span></label>
                <input type="text" name="invoice_city" id="eab_invoice_city"
                       value="<?php echo esc_attr($saved_invoice['city'] ?? ''); ?>" autocomplete="address-level2">
            </div>
        </div>
        <div class="auth-form__row auth-form__row--half">
            <div class="auth-form__col auth-form__field">
                <label for="eab_invoice_ic"><?php esc_html_e('IČ', 'events-and-bookings'); ?> <span class="required">*</span></label>
                <input type="text" name="invoice_ic" id="eab_invoice_ic"
                       value="<?php echo esc_attr($saved_invoice['ic'] ?? ''); ?>">
            </div>
            <div class="auth-form__col auth-form__field">
                <label for="eab_invoice_dic"><?php esc_html_e('DIČ', 'events-and-bookings'); ?></label>
                <input type="text" name="invoice_dic" id="eab_invoice_dic"
                       value="<?php echo esc_attr($saved_invoice['dic'] ?? ''); ?>">
            </div>
        </div>
    </div>
</fieldset>
