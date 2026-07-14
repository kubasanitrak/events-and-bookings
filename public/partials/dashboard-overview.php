<?php
/**
 * @var array  $profile
 * @var string $display_name
 * @var string $logout_url
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<section class="eab-dashboard__panel is-active" data-panel="overview" aria-hidden="false">
    <header class="eab-dashboard__profile">
        <div class="eab-dashboard__avatar" aria-hidden="true">
            <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
                <circle cx="12" cy="8" r="4" fill="none" stroke="currentColor" stroke-width="1.5"></circle>
                <path d="M4 20c1.5-4 6-6 8-6s6.5 2 8 6" fill="none" stroke="currentColor" stroke-width="1.5"></path>
            </svg>
        </div>
        <p class="eab-dashboard__name" data-eab-dashboard-name><?php echo esc_html($display_name); ?></p>
    </header>

    <nav class="eab-dashboard__nav" aria-label="<?php esc_attr_e('Účet', 'events-and-bookings'); ?>">
        <button type="button" class="eab-dashboard__nav-item" data-eab-dashboard-go="bookings">
            <span><?php esc_html_e('moje rezervace', 'events-and-bookings'); ?></span>
            <span class="eab-dashboard__nav-icon" aria-hidden="true"></span>
        </button>
        <button type="button" class="eab-dashboard__nav-item" data-eab-dashboard-go="settings">
            <span><?php esc_html_e('nastavení účtu', 'events-and-bookings'); ?></span>
            <span class="eab-dashboard__nav-icon" aria-hidden="true"></span>
        </button>
    </nav>

    <footer class="eab-dashboard__footer">
        <a class="btn btn-outline btn-oval caps hover-bgr" href="<?php echo esc_url($logout_url); ?>">
            <?php esc_html_e('Odhlásit se', 'events-and-bookings'); ?>
        </a>
    </footer>
</section>
