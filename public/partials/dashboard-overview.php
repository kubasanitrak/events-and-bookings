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
            <!-- <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
                <path d="M4 20c1.5-4 6-6 8-6s6.5 2 8 6" fill="none" stroke="currentColor" stroke-width="1.5"></path>
            </svg> -->
            <!-- <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 40 40">
                <circle cx="12" cy="8" r="4" fill="none" stroke="currentColor" stroke-width="1.5"></circle>
                <path fill="#000001" d="M27.093 15.385a7.092 7.092 0 1 0-14.185-.001 7.092 7.092 0 0 0 14.185 0m1.2 0a8.292 8.292 0 1 1-16.584 0 8.292 8.292 0 0 1 16.584 0M20.002 27.097a15.99 15.99 0 0 1 13.65 7.668l-.511.312-.513.312a14.787 14.787 0 0 0-19.867-5.198 14.8 14.8 0 0 0-5.385 5.198l-.512-.312-.513-.312a15.99 15.99 0 0 1 13.651-7.668"/>
            </svg> -->
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
