<?php
/**
 * Optional invoice fields on user profile (saved for checkout prefill).
 */

if (!defined('ABSPATH')) {
    exit;
}

class EAB_User_Profile {

    public function __construct() {
        add_action('show_user_profile', array($this, 'render_fields'));
        add_action('edit_user_profile', array($this, 'render_fields'));
        add_action('personal_options_update', array($this, 'save_fields'));
        add_action('edit_user_profile_update', array($this, 'save_fields'));
    }

    public function render_fields($user) {
        if (!current_user_can('edit_user', $user->ID) || !EAB_Settings::checkout_invoice_enabled()) {
            return;
        }

        $data = EAB_Invoice::get_user_invoice_data($user->ID);
        ?>
        <h2><?php esc_html_e('Fakturační údaje (volitelné)', 'events-and-bookings'); ?></h2>
        <p class="description"><?php esc_html_e('Použijí se při objednávce, pokud požadujete fakturu na firmu.', 'events-and-bookings'); ?></p>
        <table class="form-table" role="presentation">
            <?php
            $rows = array(
                'company_name'  => __('Název firmy', 'events-and-bookings'),
                'street'        => __('Ulice', 'events-and-bookings'),
                'street_number' => __('Číslo popisné', 'events-and-bookings'),
                'city'          => __('Město', 'events-and-bookings'),
                'zip'           => __('PSČ', 'events-and-bookings'),
                'ic'            => __('IČ', 'events-and-bookings'),
                'dic'           => __('DIČ', 'events-and-bookings'),
            );
            foreach ($rows as $key => $label) :
                $meta = EAB_Invoice::meta_key($key);
                ?>
                <tr>
                    <th><label for="<?php echo esc_attr($meta); ?>"><?php echo esc_html($label); ?></label></th>
                    <td>
                        <input type="text" name="<?php echo esc_attr($meta); ?>" id="<?php echo esc_attr($meta); ?>"
                               value="<?php echo esc_attr($data[$key]); ?>" class="regular-text">
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php
    }

    public function save_fields($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return;
        }

        foreach (EAB_Invoice::field_map() as $form_key => $suffix) {
            $meta = EAB_Invoice::meta_key($suffix);
            if (isset($_POST[$meta])) {
                update_user_meta($user_id, $meta, sanitize_text_field(wp_unslash($_POST[$meta])));
            }
        }
    }
}
