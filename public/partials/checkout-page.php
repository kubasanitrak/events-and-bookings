<?php
/**
 * Checkout with basket lines, attendees, payment.
 *
 * @var array  $items
 * @var float  $total
 * @var array  $saved_invoice
 * @var bool   $has_saved_invoice
 * @var int    $terms_page
 * @var bool   $bank_enabled
 * @var bool   $gopay_enabled
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="eab-checkout" id="eab-checkout">
    <h1 class="eab-checkout__title"><?php esc_html_e('rezervace', 'events-and-bookings'); ?></h1>

    <form id="eab-checkout-form" class="eab-checkout-form auth-form">
        <div class="eab-checkout-lines">
            <?php foreach ($items as $item) :
                $post_id = (int) $item->object_id;
                $meta    = $item->line_meta;
                $spots   = (int) ($meta['spots'] ?? 1);
                $services_defs = EAB_Pricing::get_optional_services($post_id);
                $field_defs    = EAB_Pricing::get_attendee_field_defs($post_id);
                $spot_type     = $meta['spot_type'] ?? EAB_Capacity::SPOT_REGULAR;
                $spots_id      = 'eab-checkout-spots-' . $post_id;
                ?>
                <div class="eab-checkout-line" data-post-id="<?php echo esc_attr($post_id); ?>">
                    <fieldset class="eab-checkout__section">
                        <legend class="eab-checkout__section-label caps"><?php esc_html_e('Akce / Trénink', 'events-and-bookings'); ?></legend>
                        <div class="eab-checkout__rule" aria-hidden="true"></div>

                        <div class="eab-checkout-line__header">
                            <h2 class="eab-checkout-line__title">
                                <a href="<?php echo esc_url($item->permalink); ?>"><?php echo esc_html($item->post_title); ?></a>
                            </h2>
                            <?php if ($item->schedule) : ?>
                                <p class="eab-checkout-line__schedule"><?php echo esc_html($item->schedule); ?></p>
                            <?php endif; ?>
                            <button type="button" class="eab-remove-line" data-post-id="<?php echo esc_attr($post_id); ?>" aria-label="<?php esc_attr_e('Odebrat z rezervace', 'events-and-bookings'); ?>">&times;</button>
                        </div>

                        <?php if ($spot_type === EAB_Capacity::SPOT_ALTERNATE) : ?>
                            <p class="eab-checkout-line__waitlist"><?php esc_html_e('Náhradník / čekací lista', 'events-and-bookings'); ?></p>
                        <?php endif; ?>

                        <div class="eab-checkout-line__spots">
                            <label class="eab-checkout-line__spots-label caps" for="<?php echo esc_attr($spots_id); ?>"><?php esc_html_e('Počet míst', 'events-and-bookings'); ?></label>
                            <div class="eab-quantity">
                                <input type="number" id="<?php echo esc_attr($spots_id); ?>" class="eab-spots-input" min="1" max="20" value="<?php echo esc_attr($spots); ?>" data-post-id="<?php echo esc_attr($post_id); ?>" inputmode="numeric">
                                <div class="eab-quantity-nav">
                                    <button type="button" class="eab-quantity-button eab-quantity-up" aria-label="<?php esc_attr_e('Přidat místo', 'events-and-bookings'); ?>"></button>
                                    <button type="button" class="eab-quantity-button eab-quantity-down" aria-label="<?php esc_attr_e('Odebrat místo', 'events-and-bookings'); ?>"></button>
                                </div>
                            </div>
                        </div>
                    </fieldset>

                    <?php if (!empty($services_defs)) : ?>
                        <fieldset class="eab-checkout__section eab-checkout-services">
                            <legend class="eab-checkout__section-label caps"><?php esc_html_e('Volitelné služby', 'events-and-bookings'); ?></legend>
                            <div class="eab-checkout__rule" aria-hidden="true"></div>
                            <?php
                            $selected_service_keys = array_fill_keys(
                                EAB_Pricing::selected_service_keys($meta['services'] ?? array()),
                                true
                            );
                            $service_availability = EAB_Capacity::get_service_availability($post_id, $spots);
                            foreach ($services_defs as $svc) :
                                if (!is_array($svc)) {
                                    continue;
                                }
                                $slug = EAB_Pricing::service_key($svc);
                                if ($slug === '') {
                                    continue;
                                }
                                $avail    = $service_availability[$slug] ?? array();
                                $limit    = (int) ($avail['limit'] ?? 0);
                                $remaining = isset($avail['remaining']) ? (int) $avail['remaining'] : -1;
                                $sold_out = !empty($avail['sold_out']);
                                $checked  = isset($selected_service_keys[$slug]) && !$sold_out;
                                $label_class = $sold_out ? 'auth-checkbox is-sold-out' : 'auth-checkbox';
                                ?>
                                <label class="<?php echo esc_attr($label_class); ?>">
                                    <input type="checkbox" class="eab-service-cb" value="<?php echo esc_attr($slug); ?>" data-post-id="<?php echo esc_attr($post_id); ?>" data-price-addon="<?php echo esc_attr((float) ($svc['price_addon'] ?? 0)); ?>" data-capacity="<?php echo esc_attr($limit); ?>" data-remaining="<?php echo esc_attr($remaining); ?>" <?php checked($checked); ?> <?php disabled($sold_out); ?>>
                                    <span><?php echo esc_html($svc['label'] ?? $slug); ?></span>
                                    <?php if (!empty($svc['price_addon'])) : ?>
                                        <span class="eab-service-price">+<?php echo esc_html(EAB_Payments::format_price($svc['price_addon'])); ?></span>
                                    <?php endif; ?>
                                    <span class="eab-service-sold-out"<?php echo $sold_out ? '' : ' hidden'; ?>><?php esc_html_e('Naplněná kapacita', 'events-and-bookings'); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </fieldset>
                    <?php endif; ?>

                    <fieldset class="eab-checkout__section eab-checkout-attendees" data-post-id="<?php echo esc_attr($post_id); ?>"
                         data-field-defs="<?php echo esc_attr(wp_json_encode($field_defs)); ?>">
                        <legend class="eab-checkout__section-label caps"><?php esc_html_e('Účastníci', 'events-and-bookings'); ?></legend>
                        <div class="eab-checkout__rule" aria-hidden="true"></div>
                        <div class="eab-attendees-list"></div>
                    </fieldset>
                </div>
            <?php endforeach; ?>
        </div>

        <fieldset class="eab-checkout__section eab-checkout__total">
            <legend class="eab-checkout__section-label caps"><?php esc_html_e('Cena celkem', 'events-and-bookings'); ?></legend>
            <div class="eab-checkout__rule" aria-hidden="true"></div>
            <p class="eab-checkout-grand-total">
                <strong id="eab-checkout-total"><?php echo esc_html(EAB_Payments::format_price($total)); ?></strong>
            </p>
        </fieldset>

        <?php
        $has_saved_invoice = !empty($has_saved_invoice);
        include EAB_PLUGIN_DIR . 'public/partials/checkout-invoice-fields.php';
        ?>

        <?php if ($bank_enabled || $gopay_enabled) : ?>
            <fieldset class="eab-checkout__section eab-checkout-payment">
                <legend class="eab-checkout__section-label caps"><?php esc_html_e('Platba', 'events-and-bookings'); ?></legend>
                <div class="eab-checkout__rule" aria-hidden="true"></div>
                <?php if ($bank_enabled) : ?>
                    <label class="eab-radio auth-checkbox">
                        <input type="radio" name="payment_method" value="bank_transfer" checked>
                        <span><?php esc_html_e('Bankovní převod', 'events-and-bookings'); ?></span>
                    </label>
                <?php endif; ?>
                <?php if ($gopay_enabled) : ?>
                    <label class="eab-radio auth-checkbox">
                        <input type="radio" name="payment_method" value="gopay" <?php checked(!$bank_enabled); ?>>
                        <span><?php esc_html_e('Karta (GoPay)', 'events-and-bookings'); ?></span>
                    </label>
                <?php endif; ?>
            </fieldset>
        <?php endif; ?>

        <div class="eab-checkout__section eab-checkout__consents auth-form__checkboxes">
            <?php if ($terms_page) : ?>
                <label class="auth-checkbox">
                    <input type="checkbox" name="agree_terms" value="1" required>
                    <span>
                        <?php
                        printf(
                            wp_kses(
                                /* translators: %s: terms and conditions URL */
                                __('Souhlasím s <a class="textlink textlink-underline" href="%s" target="_blank" rel="noopener">obchodními podmínkami</a>', 'events-and-bookings'),
                                array(
                                    'a' => array(
                                        'class'  => array(),
                                        'href'   => array(),
                                        'target' => array(),
                                        'rel'    => array(),
                                    ),
                                )
                            ),
                            esc_url(get_permalink($terms_page))
                        );
                        ?>
                    </span>
                </label>
            <?php endif; ?>
        </div>

        <div class="auth-form__submit eab-checkout__submit">
            <button type="submit" class="btn btn-outline btn-oval caps hover-bgr" id="eab-checkout-submit">
                <?php esc_html_e('Potvrdit rezervaci', 'events-and-bookings'); ?>
            </button>
        </div>
    </form>
</div>
