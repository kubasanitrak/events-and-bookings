<?php
/**
 * GoPay / payment failure page.
 *
 * @var object|null $order
 */

if (!defined('ABSPATH')) {
    exit;
}

$checkout = EAB_Auth::get_page_url('checkout');
?>
<div class="eab-payment-result eab-payment-result--failed">
    <h2><?php esc_html_e('Platba se nezdařila', 'events-and-bookings'); ?></h2>

    <?php if ($order) : ?>
        <p>
            <?php
            printf(
                esc_html__('Objednávka %s nebyla uhrazena. Můžete zkusit platbu znovu z pokladny.', 'events-and-bookings'),
                esc_html($order->order_number)
            );
            ?>
        </p>
    <?php else : ?>
        <p><?php esc_html_e('Platba nebyla dokončena.', 'events-and-bookings'); ?></p>
    <?php endif; ?>

    <?php if ($checkout) : ?>
        <p><a class="eab-btn" href="<?php echo esc_url($checkout); ?>"><?php esc_html_e('Zpět do pokladny', 'events-and-bookings'); ?></a></p>
    <?php endif; ?>
</div>
