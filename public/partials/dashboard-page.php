<?php
/**
 * @var array $orders
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="eab-dashboard">
    <h2><?php esc_html_e('Moje rezervace', 'events-and-bookings'); ?></h2>

    <?php if (empty($orders)) : ?>
        <p><?php esc_html_e('Zatím nemáte žádné objednávky.', 'events-and-bookings'); ?></p>
    <?php else : ?>
        <table class="eab-dashboard__orders">
            <thead>
                <tr>
                    <th><?php esc_html_e('Číslo', 'events-and-bookings'); ?></th>
                    <th><?php esc_html_e('Datum', 'events-and-bookings'); ?></th>
                    <th><?php esc_html_e('Stav', 'events-and-bookings'); ?></th>
                    <th><?php esc_html_e('Částka', 'events-and-bookings'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order) : ?>
                    <tr>
                        <td><?php echo esc_html($order->order_number); ?></td>
                        <td><?php echo esc_html(date_i18n('j. n. Y H:i', strtotime($order->created_at))); ?></td>
                        <td><?php echo esc_html(EAB_Dashboard::status_label($order->status)); ?></td>
                        <td><?php echo esc_html(EAB_Payments::format_price($order->total)); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
