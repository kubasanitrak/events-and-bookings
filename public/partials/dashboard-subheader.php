<?php
/**
 * @var string $display_name
 * @var string $subheader_back Panel id/hash to return to.
 */

if (!defined('ABSPATH')) {
    exit;
}

$subheader_back = isset($subheader_back) ? $subheader_back : 'overview';
?>
<header class="eab-dashboard__subheader">
    <button type="button" class="eab-dashboard__back" data-eab-dashboard-go="<?php echo esc_attr($subheader_back); ?>" aria-label="<?php esc_attr_e('Zpět', 'events-and-bookings'); ?>">
        <span aria-hidden="true"></span>
    </button>
    <div class="eab-dashboard__subheader-profile">
        <div class="eab-dashboard__avatar eab-dashboard__avatar--small" aria-hidden="true">
            <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
                <circle cx="12" cy="8" r="4" fill="none" stroke="currentColor" stroke-width="1.5"></circle>
                <path d="M4 20c1.5-4 6-6 8-6s6.5 2 8 6" fill="none" stroke="currentColor" stroke-width="1.5"></path>
            </svg>
        </div>
        <p class="eab-dashboard__name eab-dashboard__name--small" data-eab-dashboard-name><?php echo esc_html($display_name); ?></p>
    </div>
</header>
