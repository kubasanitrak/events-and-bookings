<?php
/**
 * @var object $order
 */

if (!defined('ABSPATH')) {
    exit;
}

$vs = preg_replace('/\D/', '', $order->order_number);
?>
<div class="eab-bank-transfer">
    <h2><?php esc_html_e('Platební instrukce', 'events-and-bookings'); ?></h2>
    <p><strong><?php esc_html_e('Číslo objednávky:', 'events-and-bookings'); ?></strong> <?php echo esc_html($order->order_number); ?></p>
    <p><strong><?php esc_html_e('Částka:', 'events-and-bookings'); ?></strong> <?php echo esc_html(EAB_Payments::format_price($order->total)); ?></p>

    <table class="eab-bank-transfer__table">
        <?php if ($account_name) : ?>
            <tr><th><?php esc_html_e('Příjemce', 'events-and-bookings'); ?></th><td><?php echo esc_html($account_name); ?></td></tr>
        <?php endif; ?>
        <?php if ($account_number && $bank_code) : ?>
            <tr><th><?php esc_html_e('Účet', 'events-and-bookings'); ?></th><td><?php echo esc_html($account_number . '/' . $bank_code); ?></td></tr>
        <?php endif; ?>
        <?php if ($iban) : ?>
            <tr><th>IBAN</th><td><?php echo esc_html($iban); ?></td></tr>
        <?php endif; ?>
        <tr><th><?php esc_html_e('Variabilní symbol', 'events-and-bookings'); ?></th><td><?php echo esc_html($vs); ?></td></tr>
    </table>

    <?php
    $qr = new EAB_QR_Generator();
    echo $qr->render_qr_html($order->total, $order->order_number, 220);
    ?>

    <?php if (!empty($order->expires_at)) : ?>
        <p class="eab-bank-transfer__expires">
            <strong><?php esc_html_e('Uhraďte do:', 'events-and-bookings'); ?></strong>
            <?php echo esc_html(date_i18n('j. n. Y H:i', strtotime($order->expires_at))); ?>
        </p>
    <?php endif; ?>

    <p class="eab-bank-transfer__note"><?php esc_html_e('Po připsání platby bude rezervace potvrzena administrátorem.', 'events-and-bookings'); ?></p>
</div>
