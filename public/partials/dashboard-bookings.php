<?php
/**
 * @var array  $groups
 * @var string $display_name
 * @var bool   $has_bookings
 */

if (!defined('ABSPATH')) {
    exit;
}

$sections = array(
    'trainings' => array(
        'label' => __('Tréninky', 'events-and-bookings'),
        'items' => $groups['trainings'],
    ),
    'events' => array(
        'label' => __('Akce', 'events-and-bookings'),
        'items' => $groups['events'],
    ),
);
?>
<section class="eab-dashboard__panel" data-panel="bookings" aria-hidden="true">
    <?php
    $subheader_back = 'overview';
    include EAB_PLUGIN_DIR . 'public/partials/dashboard-subheader.php';
    ?>

    <h2 class="eab-dashboard__title"><?php esc_html_e('moje rezervace', 'events-and-bookings'); ?></h2>

    <?php if (!$has_bookings) : ?>
        <p class="eab-dashboard__empty"><?php esc_html_e('Zatím nemáte žádné rezervace.', 'events-and-bookings'); ?></p>
    <?php else : ?>
        <?php foreach ($sections as $section) : ?>
            <?php if (empty($section['items'])) {
                continue;
            } ?>
            <div class="eab-dashboard__section">
                <h3 class="eab-dashboard__section-label caps"><?php echo esc_html($section['label']); ?></h3>
                <div class="eab-dashboard__rule" aria-hidden="true"></div>
                <div class="eab-dashboard__booking-list">
                    <?php foreach ($section['items'] as $booking) : ?>
                        <button
                            type="button"
                            class="eab-dashboard__booking-row"
                            data-eab-dashboard-go="<?php echo esc_attr($booking['hash']); ?>"
                        >
                            <span class="eab-dashboard__booking-row-title"><?php echo esc_html($booking['title']); ?></span>
                            <span class="eab-dashboard__nav-icon" aria-hidden="true"></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
