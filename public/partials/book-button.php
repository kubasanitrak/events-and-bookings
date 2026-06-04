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
$checkout    = EAB_Auth::get_page_url('checkout');
$in_basket   = is_user_logged_in() && (new EAB_Basket())->is_in_basket($post_id);
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
    <?php elseif ($in_basket && $checkout) : ?>
        <a class="eab-btn" href="<?php echo esc_url($checkout); ?>"><?php esc_html_e('Pokračovat v rezervaci', 'events-and-bookings'); ?></a>
    <?php else : ?>
        <div class="eab-book-cta__add">
            <label for="eab-spots-<?php echo esc_attr($post_id); ?>"><?php esc_html_e('Počet míst', 'events-and-bookings'); ?></label>
            <input type="number" id="eab-spots-<?php echo esc_attr($post_id); ?>" class="eab-book-spots" min="1" max="20" value="1">
            <button type="button" class="eab-btn eab-btn--book" data-eab-book="<?php echo esc_attr($post_id); ?>">
                <?php esc_html_e('Rezervovat místo', 'events-and-bookings'); ?>
            </button>
        </div>
    <?php endif; ?>
</div>
