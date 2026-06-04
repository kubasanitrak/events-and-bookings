<?php
/**
 * @var int    $post_id
 * @var string $class
 */

if (!defined('ABSPATH')) {
    exit;
}

$extra_class = !empty($class) ? ' ' . sanitize_html_class($class) : '';
$redirect    = get_permalink($post_id);
$open        = EAB_Event::is_booking_open($post_id);
?>
<div class="eab-book-cta<?php echo esc_attr($extra_class); ?>">
    <?php if (!is_user_logged_in()) : ?>
        <p class="eab-book-cta__hint"><?php esc_html_e('Pro rezervaci se přihlaste nebo zaregistrujte.', 'events-and-bookings'); ?></p>
        <div class="eab-book-cta__actions">
            <a class="eab-btn" href="<?php echo esc_url(EAB_Event::get_login_url($redirect)); ?>" rel="nofollow">
                <?php esc_html_e('Přihlásit', 'events-and-bookings'); ?>
            </a>
            <a class="eab-btn eab-btn--ghost" href="<?php echo esc_url(EAB_Event::get_register_url()); ?>">
                <?php esc_html_e('Registrovat', 'events-and-bookings'); ?>
            </a>
        </div>
    <?php elseif (!$open) : ?>
        <p class="eab-book-cta__hint"><?php esc_html_e('Rezervace není momentálně dostupná.', 'events-and-bookings'); ?></p>
    <?php else : ?>
        <button type="button"
                class="eab-btn eab-btn--book"
                data-eab-book="<?php echo esc_attr($post_id); ?>"
                disabled>
            <?php esc_html_e('Rezervovat místo', 'events-and-bookings'); ?>
        </button>
        <p class="eab-book-cta__hint eab-book-cta__hint--soon">
            <?php esc_html_e('Košík a platba budou dostupné v další verzi pluginu.', 'events-and-bookings'); ?>
        </p>
    <?php endif; ?>
</div>
