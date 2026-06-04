<?php
/**
 * @var int   $pending_orders
 * @var int   $paid_orders
 * @var float $revenue
 * @var array $recent_orders
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap eab-admin-wrap">
    <h1><?php esc_html_e('Přehled', 'events-and-bookings'); ?></h1>

    <div class="eab-admin-cards" style="display:flex;gap:1.5rem;flex-wrap:wrap;margin:1.5rem 0;">
        <div class="eab-admin-card" style="background:#fff;border:1px solid #c3c4c7;padding:1rem 1.5rem;border-radius:4px;min-width:10rem;">
            <strong><?php esc_html_e('Čeká na platbu', 'events-and-bookings'); ?></strong>
            <p style="font-size:1.75rem;margin:0.25rem 0 0;"><?php echo (int) $pending_orders; ?></p>
        </div>
        <div class="eab-admin-card" style="background:#fff;border:1px solid #c3c4c7;padding:1rem 1.5rem;border-radius:4px;min-width:10rem;">
            <strong><?php esc_html_e('Zaplacené', 'events-and-bookings'); ?></strong>
            <p style="font-size:1.75rem;margin:0.25rem 0 0;"><?php echo (int) $paid_orders; ?></p>
        </div>
        <div class="eab-admin-card" style="background:#fff;border:1px solid #c3c4c7;padding:1rem 1.5rem;border-radius:4px;min-width:10rem;">
            <strong><?php esc_html_e('Tržby (zaplaceno)', 'events-and-bookings'); ?></strong>
            <p style="font-size:1.75rem;margin:0.25rem 0 0;"><?php echo esc_html(EAB_Payments::format_price($revenue)); ?></p>
        </div>
    </div>

    <p>
        <a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=eab-orders')); ?>">
            <?php esc_html_e('Všechny objednávky', 'events-and-bookings'); ?>
        </a>
        <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=eab-settings')); ?>">
            <?php esc_html_e('Nastavení', 'events-and-bookings'); ?>
        </a>
    </p>

    <h2><?php esc_html_e('Poslední objednávky', 'events-and-bookings'); ?></h2>
    <?php if (empty($recent_orders)) : ?>
        <p><?php esc_html_e('Zatím žádné objednávky.', 'events-and-bookings'); ?></p>
    <?php else : ?>
        <table class="wp-list-table widefat striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Číslo', 'events-and-bookings'); ?></th>
                    <th><?php esc_html_e('Zákazník', 'events-and-bookings'); ?></th>
                    <th><?php esc_html_e('Stav', 'events-and-bookings'); ?></th>
                    <th><?php esc_html_e('Částka', 'events-and-bookings'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_orders as $order) : ?>
                    <tr>
                        <td><?php echo esc_html($order->order_number); ?></td>
                        <td><?php echo esc_html($order->display_name); ?></td>
                        <td><?php echo esc_html(EAB_Dashboard::status_label($order->status)); ?></td>
                        <td><?php echo esc_html(EAB_Payments::format_price($order->total)); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
