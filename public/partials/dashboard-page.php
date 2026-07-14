<?php
/**
 * Member dashboard shell with hash-routed panels.
 *
 * @var array  $profile
 * @var array  $groups
 * @var bool   $invoice_enabled
 * @var array  $invoice_data
 * @var string $logout_url
 * @var string $password_url
 * @var string $user_email
 * @var string $dashboard_url
 */

if (!defined('ABSPATH')) {
    exit;
}

$display_name = $profile['full_name'] !== '' ? $profile['full_name'] : __('Uživatel', 'events-and-bookings');
$all_bookings = array_merge($groups['trainings'], $groups['events']);
$has_bookings = !empty($all_bookings);
?>
<div class="eab-dashboard" data-eab-dashboard>
    <?php include EAB_PLUGIN_DIR . 'public/partials/dashboard-overview.php'; ?>
    <?php include EAB_PLUGIN_DIR . 'public/partials/dashboard-bookings.php'; ?>

    <?php foreach ($all_bookings as $booking) : ?>
        <?php
        $booking_context = $booking;
        include EAB_PLUGIN_DIR . 'public/partials/dashboard-booking-detail.php';
        ?>
    <?php endforeach; ?>

    <?php include EAB_PLUGIN_DIR . 'public/partials/dashboard-settings.php'; ?>
</div>
