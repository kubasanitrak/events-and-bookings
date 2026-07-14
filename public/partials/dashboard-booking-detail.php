<?php
/**
 * @var array $booking_context
 * @var string $display_name
 */

if (!defined('ABSPATH')) {
    exit;
}

$booking = $booking_context;
$cancel_action = $booking['cancellation']['action'];
?>
<section
    class="eab-dashboard__panel"
    data-panel="<?php echo esc_attr($booking['hash']); ?>"
    data-order-id="<?php echo esc_attr((string) $booking['order_id']); ?>"
    data-item-id="<?php echo esc_attr((string) $booking['item_id']); ?>"
    aria-hidden="true"
>
    <?php
    $subheader_back = 'bookings';
    include EAB_PLUGIN_DIR . 'public/partials/dashboard-subheader.php';
    ?>

    <div class="eab-dashboard__detail-meta">
        <?php if ($booking['date_line'] !== '') : ?>
            <p class="eab-dashboard__detail-date caps"><?php echo esc_html($booking['date_line']); ?></p>
        <?php endif; ?>
        <?php if ($booking['location_line'] !== '') : ?>
            <p class="eab-dashboard__detail-location caps"><?php echo esc_html($booking['location_line']); ?></p>
        <?php endif; ?>
    </div>

    <h2 class="eab-dashboard__detail-title"><?php echo esc_html($booking['title']); ?></h2>
    <div class="eab-dashboard__rule eab-dashboard__rule--spaced" aria-hidden="true"></div>

    <dl class="eab-dashboard__detail-list">
        <div class="eab-dashboard__detail-row">
            <dt class="caps"><?php esc_html_e('Stav rezervace', 'events-and-bookings'); ?></dt>
            <dd>
                <span class="eab-dashboard__badge" data-eab-booking-status><?php echo esc_html($booking['status_label']); ?></span>
            </dd>
        </div>
        <div class="eab-dashboard__detail-row">
            <dt class="caps"><?php esc_html_e('Počet míst', 'events-and-bookings'); ?></dt>
            <dd>
                <span class="eab-dashboard__count"><?php echo esc_html((string) $booking['spots']); ?></span>
            </dd>
        </div>
        <?php if (!empty($booking['services'])) : ?>
            <div class="eab-dashboard__detail-row eab-dashboard__detail-row--stack">
                <dt class="caps"><?php esc_html_e('Volitelné služby', 'events-and-bookings'); ?></dt>
                <dd>
                    <ul class="eab-dashboard__service-list">
                        <?php foreach ($booking['services'] as $service_label) : ?>
                            <li><span class="eab-dashboard__badge">+ <?php echo esc_html($service_label); ?></span></li>
                        <?php endforeach; ?>
                    </ul>
                </dd>
            </div>
        <?php endif; ?>
    </dl>

    <div class="eab-dashboard__detail-actions">
        <?php if ($booking['has_invoice']) : ?>
            <a class="btn btn-outline btn-oval caps hover-bgr" href="<?php echo esc_url($booking['invoice_url']); ?>">
                <?php esc_html_e('Stáhnout fakturu', 'events-and-bookings'); ?>
            </a>
        <?php endif; ?>

        <?php if ($cancel_action === 'cancel') : ?>
            <button
                type="button"
                class="btn btn-outline btn-oval caps hover-bgr"
                data-eab-dashboard-cancel
            >
                <?php esc_html_e('Zrušit rezervaci', 'events-and-bookings'); ?>
            </button>
        <?php elseif ($cancel_action === 'reschedule') : ?>
            <p class="eab-dashboard__notice"><?php echo esc_html($booking['cancellation']['message']); ?></p>
            <button
                type="button"
                class="btn btn-outline btn-oval caps hover-bgr"
                data-eab-dashboard-reschedule
            >
                <?php esc_html_e('Přesunout rezervaci', 'events-and-bookings'); ?>
            </button>
        <?php endif; ?>
    </div>
</section>
