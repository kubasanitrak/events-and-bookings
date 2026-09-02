<?php
/**
 * @var array  $orders
 * @var string $status_filter
 * @var int    $paged
 * @var int    $total_pages
 * @var array  $actionable_statuses
 */

if (!defined('ABSPATH')) {
    exit;
}

$statuses = array(
    ''                 => __('Vše', 'events-and-bookings'),
    'pending'          => __('Čeká', 'events-and-bookings'),
    'awaiting_payment' => __('Čeká na platbu', 'events-and-bookings'),
    'paid'             => __('Zaplaceno', 'events-and-bookings'),
    'cancelled'        => __('Zrušeno', 'events-and-bookings'),
    'expired'          => __('Vypršelo', 'events-and-bookings'),
    'failed'           => __('Neúspěšné', 'events-and-bookings'),
);

$last_fr_error = get_option('eab_fakturoid_last_order_error', null);
?>
<div class="wrap eab-admin-wrap">
    <h1><?php esc_html_e('Objednávky', 'events-and-bookings'); ?></h1>

    <?php if (!empty($_GET['eab_msg'])) : ?>
        <?php if ($_GET['eab_msg'] === 'payment_confirmed') : ?>
            <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Platba byla potvrzena.', 'events-and-bookings'); ?></p></div>
        <?php elseif ($_GET['eab_msg'] === 'order_cancelled') : ?>
            <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Objednávka byla zrušena.', 'events-and-bookings'); ?></p></div>
        <?php elseif ($_GET['eab_msg'] === 'proforma_created') : ?>
            <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Proforma ve Fakturoidu byla vytvořena. VS na platební stránce se načte z proformy.', 'events-and-bookings'); ?></p></div>
        <?php elseif ($_GET['eab_msg'] === 'proforma_failed') : ?>
            <div class="notice notice-error is-dismissible"><p>
                <?php
                $err = isset($_GET['eab_error']) ? sanitize_text_field(rawurldecode(wp_unslash($_GET['eab_error']))) : '';
                echo esc_html($err !== '' ? $err : __('Vytvoření proformy selhalo.', 'events-and-bookings'));
                ?>
            </p></div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if (is_array($last_fr_error) && !empty($last_fr_error['message'])) : ?>
        <div class="notice notice-warning">
            <p>
                <?php
                printf(
                    /* translators: 1: order id, 2: error message */
                    esc_html__('Poslední chyba Fakturoid proformy (objednávka #%1$s): %2$s', 'events-and-bookings'),
                    (int) ($last_fr_error['order_id'] ?? 0),
                    esc_html((string) $last_fr_error['message'])
                );
                ?>
            </p>
        </div>
    <?php endif; ?>

    <form method="get" class="eab-orders-filter" style="margin:1em 0;">
        <input type="hidden" name="page" value="eab-orders">
        <select name="status" onchange="this.form.submit()">
            <?php foreach ($statuses as $value => $label) : ?>
                <option value="<?php echo esc_attr($value); ?>" <?php selected($status_filter, $value); ?>><?php echo esc_html($label); ?></option>
            <?php endforeach; ?>
        </select>
    </form>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php esc_html_e('Objednávka', 'events-and-bookings'); ?></th>
                <th><?php esc_html_e('Zákazník', 'events-and-bookings'); ?></th>
                <th><?php esc_html_e('Platba', 'events-and-bookings'); ?></th>
                <th><?php esc_html_e('Fakturoid', 'events-and-bookings'); ?></th>
                <th><?php esc_html_e('Částka', 'events-and-bookings'); ?></th>
                <th><?php esc_html_e('Stav', 'events-and-bookings'); ?></th>
                <th><?php esc_html_e('Datum', 'events-and-bookings'); ?></th>
                <th><?php esc_html_e('Akce', 'events-and-bookings'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($orders)) : ?>
                <tr><td colspan="8"><?php esc_html_e('Žádné objednávky.', 'events-and-bookings'); ?></td></tr>
            <?php else : ?>
                <?php foreach ($orders as $order) : ?>
                    <tr>
                        <td><strong><?php echo esc_html($order->order_number); ?></strong></td>
                        <td>
                            <?php echo esc_html($order->display_name ?: '—'); ?><br>
                            <small><?php echo esc_html($order->user_email); ?></small>
                        </td>
                        <td>
                            <?php
                            echo $order->payment_method === 'gopay'
                                ? esc_html__('Karta', 'events-and-bookings')
                                : esc_html__('Převod', 'events-and-bookings');
                            ?>
                        </td>
                        <td>
                            <?php if (!empty($order->fakturoid_invoice_id)) : ?>
                                <strong><?php echo esc_html($order->fakturoid_invoice_number ?: ('#' . $order->fakturoid_invoice_id)); ?></strong><br>
                                <small>VS <?php echo esc_html($order->fakturoid_variable_symbol ?: '—'); ?></small>
                            <?php else : ?>
                                <span class="eab-status eab-status--failed"><?php esc_html_e('Bez proformy', 'events-and-bookings'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html(EAB_Payments::format_price($order->total)); ?></td>
                        <td>
                            <span class="eab-status eab-status--<?php echo esc_attr($order->status); ?>">
                                <?php echo esc_html(EAB_Dashboard::status_label($order->status)); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html(date_i18n('j. n. Y H:i', strtotime($order->created_at))); ?></td>
                        <td>
                            <?php if (in_array($order->status, $actionable_statuses, true)) : ?>
                                <?php if (empty($order->fakturoid_invoice_id) && EAB_Fakturoid::is_enabled()) : ?>
                                    <a class="button button-small"
                                       href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=eab-orders&eab_action=create_proforma&order_id=' . $order->id), 'eab_admin_order')); ?>">
                                        <?php esc_html_e('Vytvořit proformu', 'events-and-bookings'); ?>
                                    </a>
                                <?php endif; ?>
                                <a class="button button-primary button-small"
                                   href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=eab-orders&eab_action=confirm_payment&order_id=' . $order->id), 'eab_admin_order')); ?>"
                                   onclick="return confirm('<?php echo esc_js(__('Potvrdit přijetí platby?', 'events-and-bookings')); ?>')">
                                    <?php esc_html_e('Potvrdit platbu', 'events-and-bookings'); ?>
                                </a>
                                <a class="button button-small"
                                   href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=eab-orders&eab_action=cancel_order&order_id=' . $order->id), 'eab_admin_order')); ?>"
                                   onclick="return confirm('<?php echo esc_js(__('Zrušit objednávku?', 'events-and-bookings')); ?>')">
                                    <?php esc_html_e('Zrušit', 'events-and-bookings'); ?>
                                </a>
                            <?php else : ?>
                                —
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ($total_pages > 1) : ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <?php
                echo wp_kses_post(paginate_links(array(
                    'base'      => add_query_arg('paged', '%#%'),
                    'format'    => '',
                    'current'   => $paged,
                    'total'     => $total_pages,
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                )));
                ?>
            </div>
        </div>
    <?php endif; ?>
</div>
