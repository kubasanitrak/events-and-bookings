<?php
/**
 * GoPay / payment success page.
 *
 * @var object|null $order
 */

if (!defined('ABSPATH')) {
    exit;
}

$dashboard = EAB_Auth::get_page_url('dashboard');
?>
<div class="eab-payment-result eab-payment-result--success">
    <?php if ($order && $order->status === 'paid') : ?>
        <h2><?php esc_html_e('Platba byla úspěšná', 'events-and-bookings'); ?></h2>
        <p>
            <?php
            printf(
                esc_html__('Objednávka %s je potvrzena. Děkujeme za rezervaci.', 'events-and-bookings'),
                esc_html($order->order_number)
            );
            ?>
        </p>
    <?php elseif ($order && in_array($order->status, array('processing', 'awaiting_payment'), true)) : ?>
        <h2><?php esc_html_e('Platba se zpracovává', 'events-and-bookings'); ?></h2>
        <p>
            <?php
            printf(
                esc_html__('Objednávka %s čeká na potvrzení platby. Obnovte stránku za chvíli.', 'events-and-bookings'),
                esc_html($order->order_number)
            );
            ?>
        </p>
    <?php else : ?>
        <h2><?php esc_html_e('Platba', 'events-and-bookings'); ?></h2>
        <p><?php esc_html_e('Informace o platbě nejsou k dispozici.', 'events-and-bookings'); ?></p>
    <?php endif; ?>

    <?php if ($dashboard) : ?>
        <p><a class="eab-btn" href="<?php echo esc_url($dashboard); ?>"><?php esc_html_e('Můj účet', 'events-and-bookings'); ?></a></p>
    <?php endif; ?>
</div>
