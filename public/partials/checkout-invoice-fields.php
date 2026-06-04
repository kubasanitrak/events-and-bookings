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
<div class="eab-checkout-section eab-invoice-section">
    <h3><?php esc_html_e('Faktura na firmu (volitelné)', 'events-and-bookings'); ?></h3>

    <label class="eab-checkbox-label">
        <input type="checkbox" name="want_invoice" id="eab_want_invoice" value="1" <?php checked(!empty($has_saved_invoice)); ?>>
        <span><?php esc_html_e('Chci fakturu na firmu', 'events-and-bookings'); ?></span>
    </label>

    <div class="eab-invoice-fields" <?php echo empty($has_saved_invoice) ? 'style="display:none;"' : ''; ?>>
        <div class="eab-form-row">
            <label for="eab_invoice_company_name"><?php esc_html_e('Název firmy', 'events-and-bookings'); ?> <span class="required">*</span></label>
            <input type="text" name="invoice_company_name" id="eab_invoice_company_name"
                   value="<?php echo esc_attr($saved_invoice['company_name'] ?? ''); ?>">
        </div>
        <div class="eab-form-row eab-form-row-half">
            <div class="eab-form-col">
                <label for="eab_invoice_street"><?php esc_html_e('Ulice', 'events-and-bookings'); ?> <span class="required">*</span></label>
                <input type="text" name="invoice_street" id="eab_invoice_street"
                       value="<?php echo esc_attr($saved_invoice['street'] ?? ''); ?>">
            </div>
            <div class="eab-form-col eab-form-col-small">
                <label for="eab_invoice_street_number"><?php esc_html_e('Číslo', 'events-and-bookings'); ?></label>
                <input type="text" name="invoice_street_number" id="eab_invoice_street_number"
                       value="<?php echo esc_attr($saved_invoice['street_number'] ?? ''); ?>">
            </div>
        </div>
        <div class="eab-form-row eab-form-row-half">
            <div class="eab-form-col eab-form-col-small">
                <label for="eab_invoice_zip"><?php esc_html_e('PSČ', 'events-and-bookings'); ?> <span class="required">*</span></label>
                <input type="text" name="invoice_zip" id="eab_invoice_zip"
                       value="<?php echo esc_attr($saved_invoice['zip'] ?? ''); ?>">
            </div>
            <div class="eab-form-col">
                <label for="eab_invoice_city"><?php esc_html_e('Město', 'events-and-bookings'); ?> <span class="required">*</span></label>
                <input type="text" name="invoice_city" id="eab_invoice_city"
                       value="<?php echo esc_attr($saved_invoice['city'] ?? ''); ?>">
            </div>
        </div>
        <div class="eab-form-row eab-form-row-half">
            <div class="eab-form-col">
                <label for="eab_invoice_ic"><?php esc_html_e('IČ', 'events-and-bookings'); ?> <span class="required">*</span></label>
                <input type="text" name="invoice_ic" id="eab_invoice_ic"
                       value="<?php echo esc_attr($saved_invoice['ic'] ?? ''); ?>">
            </div>
            <div class="eab-form-col">
                <label for="eab_invoice_dic"><?php esc_html_e('DIČ', 'events-and-bookings'); ?></label>
                <input type="text" name="invoice_dic" id="eab_invoice_dic"
                       value="<?php echo esc_attr($saved_invoice['dic'] ?? ''); ?>">
            </div>
        </div>
        <label class="eab-checkbox-label">
            <input type="checkbox" name="save_invoice_to_profile" id="eab_save_invoice_to_profile" value="1">
            <span><?php esc_html_e('Uložit do profilu', 'events-and-bookings'); ?></span>
        </label>
    </div>
</div>
