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
    <h2><?php esc_html_e('Rezervace', 'events-and-bookings'); ?></h2>

    <form id="eab-checkout-form" class="eab-checkout-form">
        <div class="eab-checkout-lines">
            <?php foreach ($items as $item) :
                $post_id = (int) $item->object_id;
                $meta    = $item->line_meta;
                $spots   = (int) ($meta['spots'] ?? 1);
                $services_defs = EAB_Pricing::get_optional_services($post_id);
                $field_defs    = EAB_Pricing::get_attendee_field_defs($post_id);
                $spot_type     = $meta['spot_type'] ?? EAB_Capacity::SPOT_REGULAR;
                ?>
                <div class="eab-checkout-line" data-post-id="<?php echo esc_attr($post_id); ?>">
                    <div class="eab-checkout-line--row eab-checkout-line__header">
                        <h3>
                            <a href="<?php echo esc_url($item->permalink); ?>"><?php echo esc_html($item->post_title); ?></a>
                        </h3>
                        <?php if ($item->schedule) : ?>
                            <p class="eab-checkout-line__schedule"><?php echo esc_html($item->schedule); ?></p>
                        <?php endif; ?>
                        <?php if ($spot_type === EAB_Capacity::SPOT_ALTERNATE) : ?>
                            <p class="eab-checkout-line__waitlist"><?php esc_html_e('Náhradník / čekací lista', 'events-and-bookings'); ?></p>
                        <?php endif; ?>
                        <button type="button" class="eab-remove-line" data-post-id="<?php echo esc_attr($post_id); ?>">&times;</button>
                    </div>

                    <div class="eab-checkout-line--row eab-checkout-line__spots">
                        <label><?php esc_html_e('Počet míst', 'events-and-bookings'); ?></label>
                        <input type="number" class="eab-spots-input" min="1" max="20" value="<?php echo esc_attr($spots); ?>" data-post-id="<?php echo esc_attr($post_id); ?>">
                    </div>

                    <?php if (!empty($services_defs)) : ?>
                        <div class="eab-checkout-line--row">
                            <fieldset class="eab-checkout-services">
                                <legend><?php esc_html_e('Volitelné služby', 'events-and-bookings'); ?></legend>
                                <?php foreach ($services_defs as $svc) :
                                    $slug = $svc['slug'] ?? '';
                                    if ($slug === '') {
                                        continue;
                                    }
                                    $checked = in_array($slug, $meta['services'] ?? array(), true);
                                    ?>
                                    <label class="eab-checkbox">
                                        <input type="checkbox" class="eab-service-cb" value="<?php echo esc_attr($slug); ?>" data-post-id="<?php echo esc_attr($post_id); ?>" <?php checked($checked); ?>>
                                        <span class="eab-chcekbox--label"><?php echo esc_html($svc['label'] ?? $slug); ?></span>
                                        <?php if (!empty($svc['price_addon'])) : ?>
                                            <span class="eab-service-price">+<?php echo esc_html(EAB_Payments::format_price($svc['price_addon'])); ?></span>
                                        <?php endif; ?>
                                    </label>
                                <?php endforeach; ?>
                            </fieldset>
                        </div>
                    <?php endif; ?>

                    <div class="eab-checkout-line--row eab-checkout-attendees" data-post-id="<?php echo esc_attr($post_id); ?>"
                         data-field-defs="<?php echo esc_attr(wp_json_encode($field_defs)); ?>">
                        <h4><?php esc_html_e('Účastníci', 'events-and-bookings'); ?></h4>
                        <div class="eab-attendees-list"></div>
                    </div>

                    <div class="eab-checkout-line--row">
                        <p class="eab-checkout-line__total">
                            <?php esc_html_e('Cena:', 'events-and-bookings'); ?>
                            <strong class="eab-line-total" data-post-id="<?php echo esc_attr($post_id); ?>">
                                <?php echo esc_html(EAB_Payments::format_price($item->line_total)); ?>
                            </strong>
                        </p>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>
        <div class="eab-checkout-lines">
            <p class="eab-checkout-grand-total">
                <?php esc_html_e('Cena Celkem:', 'events-and-bookings'); ?>
                <strong id="eab-checkout-total"><?php echo esc_html(EAB_Payments::format_price($total)); ?></strong>
            </p>
        </div>

        <?php
        $has_saved_invoice = !empty($has_saved_invoice);
        include EAB_PLUGIN_DIR . 'public/partials/checkout-invoice-fields.php';
        ?>

        <div class="eab-checkout-lines">
            <fieldset class="eab-checkout-payment">
                <legend><?php esc_html_e('Platba', 'events-and-bookings'); ?></legend>
                <?php if ($bank_enabled) : ?>

                    <label class="eab-radio">
                        <input type="radio" name="payment_method" value="bank_transfer" checked>
                        <span><?php esc_html_e('Bankovní převod', 'events-and-bookings'); ?></span>
                    </label>
                <?php endif; ?>
                <?php if ($gopay_enabled) : ?>

                    <label class="eab-radio">
                        <input type="radio" name="payment_method" value="gopay" <?php checked(!$bank_enabled); ?>>
                        <span><?php esc_html_e('Karta (GoPay)', 'events-and-bookings'); ?></span>
                    </label>
                <?php endif; ?>
            </fieldset>
        </div>

        <?php if ($terms_page) : ?>
            <div class="eab-checkout-lines">
                <div class="eab-checkout-line--row">
                    <label class="eab-checkbox">
                        <input type="checkbox" name="agree_terms" value="1" required>
                        <span class="eab-chcekbox--label">
                            <?php
                            printf(
                                wp_kses_post(__('Souhlasím s <a class="link textlink" href="%s" target="_blank" rel="noopener">obchodními podmínkami</a>', 'events-and-bookings')),
                                esc_url(get_permalink($terms_page))
                            );
                            ?>
                        </span>
                    </label>
                </div>
            </div>
        <?php endif; ?>

        <div class="eab-checkout-lines">
            <div class="eab-checkout-line--row">
                <button type="submit" class="eab-btn eab-btn--large" id="eab-checkout-submit">
                    <?php esc_html_e('Potvrdit rezervaci', 'events-and-bookings'); ?>
                </button>
            </div>
        </div>
        
    </form>
</div>
