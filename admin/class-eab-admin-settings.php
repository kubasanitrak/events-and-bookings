<?php
/**
 * Plugin settings screen.
 */

if (!defined('ABSPATH')) {
    exit;
}

class EAB_Admin_Settings {

    public function __construct() {
        add_action('admin_menu', array($this, 'register_menu'), 12);
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function register_settings() {
        $options = array(
            EAB_Settings::OPT_BASKET_MULTIPLE_EVENTS,
            EAB_Settings::OPT_CHECKOUT_INVOICE_ENABLED,
            'eab_bank_transfer_enabled',
            'eab_gopay_enabled',
            'eab_order_expiry_hours',
            'eab_order_expiry_notification',
            'eab_basket_cleanup_hours',
            'eab_bank_account_name',
            'eab_bank_account_number',
            'eab_bank_code',
            'eab_bank_iban',
            'eab_bank_bic',
            'eab_terms_page',
            'eab_admin_notification_enabled',
            'eab_admin_notification_email',
            'eab_email_sender_name',
            'eab_email_sender_email',
        );

        foreach ($options as $opt) {
            register_setting('eab_settings', $opt);
        }
    }

    public function register_menu() {
        add_submenu_page(
            EAB_Admin::MENU_SLUG,
            __('Nastavení', 'events-and-bookings'),
            __('Nastavení', 'events-and-bookings'),
            'manage_options',
            'eab-settings',
            array($this, 'render_page')
        );
    }

    public function render_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $pages = get_pages(array('post_status' => 'publish'));
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Nastavení – Akce a rezervace', 'events-and-bookings'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('eab_settings'); ?>

                <h2><?php esc_html_e('Obecné', 'events-and-bookings'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e('Stránka obchodních podmínek', 'events-and-bookings'); ?></th>
                        <td>
                            <select name="eab_terms_page">
                                <option value="0"><?php esc_html_e('—', 'events-and-bookings'); ?></option>
                                <?php foreach ($pages as $page) : ?>
                                    <option value="<?php echo (int) $page->ID; ?>" <?php selected(get_option('eab_terms_page'), $page->ID); ?>>
                                        <?php echo esc_html($page->post_title); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Košík', 'events-and-bookings'); ?></th>
                        <td>
                            <input type="hidden" name="<?php echo esc_attr(EAB_Settings::OPT_BASKET_MULTIPLE_EVENTS); ?>" value="0">
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr(EAB_Settings::OPT_BASKET_MULTIPLE_EVENTS); ?>" value="1"
                                    <?php checked(get_option(EAB_Settings::OPT_BASKET_MULTIPLE_EVENTS, 0)); ?>>
                                <?php esc_html_e('Více akcí / tréninků v jednom košíku', 'events-and-bookings'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Faktura na firmu', 'events-and-bookings'); ?></th>
                        <td>
                            <input type="hidden" name="<?php echo esc_attr(EAB_Settings::OPT_CHECKOUT_INVOICE_ENABLED); ?>" value="0">
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr(EAB_Settings::OPT_CHECKOUT_INVOICE_ENABLED); ?>" value="1"
                                    <?php checked(get_option(EAB_Settings::OPT_CHECKOUT_INVOICE_ENABLED, 1)); ?>>
                                <?php esc_html_e('Volitelná faktura v pokladně', 'events-and-bookings'); ?>
                            </label>
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e('Platby', 'events-and-bookings'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e('Bankovní převod', 'events-and-bookings'); ?></th>
                        <td>
                            <input type="hidden" name="eab_bank_transfer_enabled" value="0">
                            <label><input type="checkbox" name="eab_bank_transfer_enabled" value="1" <?php checked(get_option('eab_bank_transfer_enabled', 1)); ?>>
                                <?php esc_html_e('Povolit', 'events-and-bookings'); ?></label>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="eab_bank_account_name"><?php esc_html_e('Název účtu', 'events-and-bookings'); ?></label></th>
                        <td><input type="text" class="regular-text" id="eab_bank_account_name" name="eab_bank_account_name" value="<?php echo esc_attr(get_option('eab_bank_account_name', '')); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="eab_bank_account_number"><?php esc_html_e('Číslo účtu', 'events-and-bookings'); ?></label></th>
                        <td><input type="text" class="regular-text" id="eab_bank_account_number" name="eab_bank_account_number" value="<?php echo esc_attr(get_option('eab_bank_account_number', '')); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="eab_bank_code"><?php esc_html_e('Kód banky', 'events-and-bookings'); ?></label></th>
                        <td><input type="text" class="small-text" id="eab_bank_code" name="eab_bank_code" value="<?php echo esc_attr(get_option('eab_bank_code', '')); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="eab_bank_iban">IBAN</label></th>
                        <td><input type="text" class="regular-text" id="eab_bank_iban" name="eab_bank_iban" value="<?php echo esc_attr(get_option('eab_bank_iban', '')); ?>"></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Lhůta platby (hodin)', 'events-and-bookings'); ?></th>
                        <td><input type="number" min="1" class="small-text" name="eab_order_expiry_hours" value="<?php echo esc_attr(get_option('eab_order_expiry_hours', 24)); ?>"></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Připomínka platby', 'events-and-bookings'); ?></th>
                        <td>
                            <input type="hidden" name="eab_order_expiry_notification" value="0">
                            <label><input type="checkbox" name="eab_order_expiry_notification" value="1" <?php checked(get_option('eab_order_expiry_notification', 1)); ?>>
                                <?php esc_html_e('E-mail 2 h před vypršením', 'events-and-bookings'); ?></label>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('GoPay', 'events-and-bookings'); ?></th>
                        <td>
                            <input type="hidden" name="eab_gopay_enabled" value="0">
                            <label><input type="checkbox" name="eab_gopay_enabled" value="1" <?php checked(get_option('eab_gopay_enabled', 0)); ?> disabled>
                                <?php esc_html_e('Fáze 6', 'events-and-bookings'); ?></label>
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e('E-maily', 'events-and-bookings'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e('Odesílatel', 'events-and-bookings'); ?></th>
                        <td>
                            <input type="text" name="eab_email_sender_name" value="<?php echo esc_attr(get_option('eab_email_sender_name', get_bloginfo('name'))); ?>" class="regular-text">
                            <input type="email" name="eab_email_sender_email" value="<?php echo esc_attr(get_option('eab_email_sender_email', get_option('admin_email'))); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Notifikace admin', 'events-and-bookings'); ?></th>
                        <td>
                            <input type="hidden" name="eab_admin_notification_enabled" value="0">
                            <label><input type="checkbox" name="eab_admin_notification_enabled" value="1" <?php checked(get_option('eab_admin_notification_enabled', 1)); ?>>
                                <?php esc_html_e('Nová objednávka', 'events-and-bookings'); ?></label><br>
                            <input type="email" name="eab_admin_notification_email" value="<?php echo esc_attr(get_option('eab_admin_notification_email', get_option('admin_email'))); ?>" class="regular-text">
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
