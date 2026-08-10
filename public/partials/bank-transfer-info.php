<?php
/**
 * @var object $order
 * @var string $account_name
 * @var string $account_number
 * @var string $bank_code
 * @var string $iban
 * @var string $vs
 * @var string $account_full
 * @var string $copy_payload
 * @var string $qr_message
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="eab-bank-transfer">
    <h2><?php esc_html_e('Platební instrukce', 'events-and-bookings'); ?></h2>
    <p class="eab-bank-transfer__hold">
        <?php esc_html_e('Místo držíme. Jakmile platbu přijmeme, pošleme potvrzení.', 'events-and-bookings'); ?>
    </p>
    <p><strong><?php esc_html_e('Číslo objednávky:', 'events-and-bookings'); ?></strong> <?php echo esc_html($order->order_number); ?></p>
    <p><strong><?php esc_html_e('Částka:', 'events-and-bookings'); ?></strong> <?php echo esc_html(EAB_Payments::format_price($order->total)); ?></p>

    <div class="eab-bank-transfer__layout">
        <div class="eab-bank-transfer__details">
            <table class="eab-bank-transfer__table">
                <?php if ($account_name) : ?>
                    <tr><th><?php esc_html_e('Příjemce', 'events-and-bookings'); ?></th><td><?php echo esc_html($account_name); ?></td></tr>
                <?php endif; ?>
                <?php if ($account_full) : ?>
                    <tr><th><?php esc_html_e('Účet', 'events-and-bookings'); ?></th><td><?php echo esc_html($account_full); ?></td></tr>
                <?php endif; ?>
                <?php if ($iban) : ?>
                    <tr><th>IBAN</th><td><?php echo esc_html($iban); ?></td></tr>
                <?php endif; ?>
                <tr><th><?php esc_html_e('Variabilní symbol', 'events-and-bookings'); ?></th><td><?php echo esc_html($vs); ?></td></tr>
            </table>

            <p class="eab-bank-transfer__actions">
                <button
                    type="button"
                    class="btn btn-outline btn-oval caps hover-bgr eab-copy-payment"
                    data-copy="<?php echo esc_attr($copy_payload); ?>"
                    data-copied="<?php echo esc_attr__('Zkopírováno', 'events-and-bookings'); ?>"
                >
                    <?php esc_html_e('Zkopírovat platební údaje', 'events-and-bookings'); ?>
                </button>
            </p>
        </div>

        <div class="eab-bank-transfer__qr">
            <p class="eab-bank-transfer__qr-label">
                <?php
                printf(
                    /* translators: %s: formatted amount */
                    esc_html__('Zaplatit %s', 'events-and-bookings'),
                    esc_html(EAB_Payments::format_price($order->total))
                );
                ?>
            </p>
            <?php
            $qr = new EAB_QR_Generator();
            echo $qr->render_qr_html($order->total, $vs, 220, $qr_message); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            ?>
        </div>
    </div>

    <?php if (!empty($order->expires_at)) : ?>
        <p class="eab-bank-transfer__expires">
            <strong><?php esc_html_e('Uhraďte do:', 'events-and-bookings'); ?></strong>
            <?php echo esc_html(date_i18n('j. n. Y H:i', strtotime($order->expires_at))); ?>
        </p>
    <?php endif; ?>

    <p class="eab-bank-transfer__note">
        <?php esc_html_e('Po přijetí platby automaticky potvrdíme rezervaci a pošleme e-mail. Do té doby je místo rezervované.', 'events-and-bookings'); ?>
    </p>
</div>
