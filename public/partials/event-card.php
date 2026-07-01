<?php
/**
 * Single card in grid/list.
 *
 * @var int $post_id
 * @var string $layout grid|list
 */

if (!defined('ABSPATH')) {
    exit;
}

$post_id = isset($post_id) ? (int) $post_id : get_the_ID();
$layout  = isset($layout) ? $layout : 'grid';
$permalink = get_permalink($post_id);
$title = get_the_title($post_id);
$schedule = EAB_Event::get_schedule_summary($post_id);
$price = EAB_Event::get_price_label($post_id);
$img_id = EAB_Event::get_tile_image_id($post_id);
$type_label = EAB_Event::get_type_label($post_id);
?>
<div class="eab-card eab-card--<?php echo esc_attr($layout); ?>" data-post-id="<?php echo esc_attr($post_id); ?>">
        <div class="eab-card__header">
            <h3 class="eab-card__title"><?php echo esc_html($title); ?></h3>
            <?php if ($schedule) : ?>
                <time class="eab-card__date"><?php echo esc_html($schedule); ?></time>
            <?php endif; ?>
            <?php echo EAB_Event::render_tags($post_id, array('class' => 'eab-card__tags')); ?>
            <?php if ($price) : ?>
                <p class="eab-card__price"><?php echo esc_html($price); ?></p>
            <?php endif; ?>
            <span class="icon icon-circ icon-arrow"></span>
        </div>
        <div class="eab-card__media">
            <?php if ($img_id) : ?>
                <?php echo wp_get_attachment_image($img_id, 'medium_large', false, array('class' => 'eab-card__img', 'loading' => 'lazy')); ?>
            <?php else : ?>
                <span class="eab-card__img eab-card__img--placeholder" aria-hidden="true"></span>
            <?php endif; ?>
            <span class="eab-card__type"><?php echo esc_html($type_label); ?></span>
        </div>
    <a class="eab-card__link abs-link" href="<?php echo esc_url($permalink); ?>">
    </a>
</div>
