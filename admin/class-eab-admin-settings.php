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
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Nastavení – Akce a rezervace', 'events-and-bookings'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('eab_settings');
                do_settings_sections('eab_settings');
                ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e('Košík', 'events-and-bookings'); ?></th>
                        <td>
                            <input type="hidden" name="<?php echo esc_attr(EAB_Settings::OPT_BASKET_MULTIPLE_EVENTS); ?>" value="0">
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr(EAB_Settings::OPT_BASKET_MULTIPLE_EVENTS); ?>" value="1"
                                    <?php checked(get_option(EAB_Settings::OPT_BASKET_MULTIPLE_EVENTS, 0)); ?>>
                                <?php esc_html_e('Povolit více akcí / tréninků v jednom košíku', 'events-and-bookings'); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e('Výchozí: vypnuto — při přidání z detailu se košík nahradí. Minimálně 1 místo na položku vždy platí.', 'events-and-bookings'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Fakturace', 'events-and-bookings'); ?></th>
                        <td>
                            <input type="hidden" name="<?php echo esc_attr(EAB_Settings::OPT_CHECKOUT_INVOICE_ENABLED); ?>" value="0">
                            <label>
                                <input type="checkbox" name="<?php echo esc_attr(EAB_Settings::OPT_CHECKOUT_INVOICE_ENABLED); ?>" value="1"
                                    <?php checked(get_option(EAB_Settings::OPT_CHECKOUT_INVOICE_ENABLED, 1)); ?>>
                                <?php esc_html_e('Volitelná faktura na firmu v pokladně (IČ, DIČ, adresa)', 'events-and-bookings'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Seznam účastníků', 'events-and-bookings'); ?></th>
                        <td>
                            <p><?php esc_html_e('Na detailu akce / tréninku vidí seznam pouze přihlášení uživatelé. Vypnutí per položka: pole ACF „Zobrazit seznam účastníků“.', 'events-and-bookings'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('ACF JSON', 'events-and-bookings'); ?></th>
                        <td>
                            <code><?php echo esc_html(EAB_PLUGIN_DIR . 'acf-json/'); ?></code>
                            <?php if (EAB_ACF::is_active()) : ?>
                                <p class="description">
                                    <?php esc_html_e('Po úpravě polí synchronizujte ve Vlastní pole (Sync), pokud ACF nabídne aktualizaci z JSON.', 'events-and-bookings'); ?>
                                    <a href="<?php echo esc_url(admin_url('edit.php?post_type=acf-field-group')); ?>"><?php esc_html_e('Vlastní pole', 'events-and-bookings'); ?></a>
                                </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
