<?php
/**
 * @var int  $post_id
 * @var bool $show_gallery
 * @var bool $show_program
 * @var bool $show_attendees
 * @var bool $show_services
 */

if (!defined('ABSPATH')) {
    exit;
}

$post = get_post($post_id);
if (!$post) {
    return;
}

$schedule = EAB_Event::get_schedule_summary($post_id);
$price    = EAB_Event::get_price_label($post_id);
$img_id   = EAB_Event::get_tile_image_id($post_id);
?>
<article class="eab-detail" id="eab-detail-<?php echo esc_attr($post_id); ?>">
    <header class="eab-detail__header">
        <p class="eab-detail__type"><?php echo esc_html(EAB_Event::get_type_label($post_id)); ?></p>
        <h1 class="eab-detail__title"><?php echo esc_html(get_the_title($post_id)); ?></h1>
        <?php if ($schedule) : ?>
            <p class="eab-detail__schedule"><?php echo esc_html($schedule); ?></p>
        <?php endif; ?>
        <?php echo EAB_Event::render_tags($post_id, array('class' => 'eab-detail__tags', 'link' => true)); ?>
        <?php if ($price) : ?>
            <p class="eab-detail__price"><?php echo esc_html($price); ?></p>
        <?php endif; ?>
    </header>

    <?php if ($img_id) : ?>
        <figure class="eab-detail__hero">
            <?php echo wp_get_attachment_image($img_id, 'large', false, array('class' => 'eab-detail__hero-img')); ?>
        </figure>
    <?php endif; ?>

    <?php if (has_excerpt($post_id)) : ?>
        <p class="eab-detail__excerpt"><?php echo esc_html(get_the_excerpt($post_id)); ?></p>
    <?php endif; ?>

    <?php if (function_exists('get_field')) :
        $place_text = get_field('place_text', $post_id);
        $location   = get_field('location', $post_id);
        if ($place_text || $location) : ?>
            <section class="eab-detail__section">
                <h2><?php esc_html_e('Místo', 'events-and-bookings'); ?></h2>
                <?php if ($place_text) : ?>
                    <div class="eab-detail__prose"><?php echo wp_kses_post(wpautop($place_text)); ?></div>
                <?php endif; ?>
                <?php if (is_array($location) && !empty($location['lat']) && !empty($location['lng'])) : ?>
                    <p class="eab-detail__location">
                        <a href="<?php echo esc_url('https://www.google.com/maps/search/?api=1&query=' . rawurlencode($location['lat'] . ',' . $location['lng'])); ?>" target="_blank" rel="noopener noreferrer">
                            <?php esc_html_e('Zobrazit na mapě', 'events-and-bookings'); ?>
                        </a>
                    </p>
                <?php endif; ?>
            </section>
        <?php endif;
    endif; ?>

    <div class="eab-detail__content">
        <?php echo apply_filters('the_content', $post->post_content); ?>
    </div>

    <?php if ($show_services && function_exists('get_field')) :
        $services = get_field('optional_services', $post_id);
        if (!empty($services) && is_array($services)) : ?>
            <section class="eab-detail__section">
                <h2><?php esc_html_e('Volitelné služby', 'events-and-bookings'); ?></h2>
                <ul class="eab-detail__services">
                    <?php foreach ($services as $row) :
                        $label = isset($row['label']) ? $row['label'] : '';
                        if ($label === '') {
                            continue;
                        }
                        $addon = isset($row['price_addon']) && $row['price_addon'] !== '' ? (float) $row['price_addon'] : 0;
                        ?>
                        <li>
                            <?php echo esc_html($label); ?>
                            <?php if ($addon > 0) : ?>
                                <span class="eab-detail__service-price">+<?php echo esc_html(number_format_i18n($addon, 0)); ?> <?php echo esc_html(get_option('eab_currency_symbol', 'Kč')); ?></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif;
    endif; ?>

    <?php if ($show_program && function_exists('get_field')) :
        $synopsis = get_field('synopsis', $post_id);
        $program  = get_field('program', $post_id);
        if ($synopsis) : ?>
            <section class="eab-detail__section">
                <h2><?php esc_html_e('Synopse', 'events-and-bookings'); ?></h2>
                <div class="eab-detail__prose"><?php echo wp_kses_post(wpautop($synopsis)); ?></div>
            </section>
        <?php endif;
        if ($program) : ?>
            <section class="eab-detail__section">
                <h2><?php esc_html_e('Program', 'events-and-bookings'); ?></h2>
                <div class="eab-detail__prose"><?php echo wp_kses_post($program); ?></div>
            </section>
        <?php endif;
    endif; ?>

    <?php if ($show_gallery && function_exists('get_field')) :
        $gallery = get_field('gallery', $post_id);
        if (!empty($gallery) && is_array($gallery)) : ?>
            <section class="eab-detail__section eab-detail__gallery">
                <h2><?php esc_html_e('Galerie', 'events-and-bookings'); ?></h2>
                <div class="eab-gallery">
                    <?php foreach ($gallery as $image) :
                        $id = is_array($image) ? ($image['ID'] ?? 0) : (int) $image;
                        if ($id) {
                            echo wp_get_attachment_image($id, 'medium_large', false, array('class' => 'eab-gallery__img', 'loading' => 'lazy'));
                        }
                    endforeach; ?>
                </div>
            </section>
        <?php endif;
    endif; ?>

    <?php if (function_exists('get_field')) :
        $instructors = get_field('instructors', $post_id);
        if (!empty($instructors)) :
            $ids = is_array($instructors) ? $instructors : array($instructors);
            ?>
            <section class="eab-detail__section">
                <h2><?php esc_html_e('Instruktoři', 'events-and-bookings'); ?></h2>
                <ul class="eab-detail__instructors">
                    <?php foreach ($ids as $inst_id) :
                        $inst_id = (int) $inst_id;
                        if (!$inst_id) {
                            continue;
                        }
                        ?>
                        <li>
                            <a href="<?php echo esc_url(get_permalink($inst_id)); ?>">
                                <?php echo esc_html(get_the_title($inst_id)); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
        <?php endif;
    endif; ?>

    <?php if ($show_attendees && EAB_Access::can_view_attendee_list($post_id)) : ?>
        <section class="eab-detail__section eab-detail__attendees">
            <h2><?php esc_html_e('Účastníci', 'events-and-bookings'); ?></h2>
            <?php
            $attendees = apply_filters('eab_event_attendees', array(), $post_id);
            if (!empty($attendees)) :
                echo '<ul class="eab-attendees-list">';
                foreach ($attendees as $name) {
                    echo '<li>' . esc_html($name) . '</li>';
                }
                echo '</ul>';
            else :
                ?>
                <p class="eab-detail__muted"><?php esc_html_e('Zatím žádní potvrzení účastníci.', 'events-and-bookings'); ?></p>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <footer class="eab-detail__footer">
        <?php
        $post_id = $post_id;
        include EAB_PLUGIN_DIR . 'public/partials/book-button.php';
        ?>
    </footer>
</article>
