<?php
/**
 * Single card in grid/list/training-row.
 *
 * @var int    $post_id
 * @var string $layout grid|list|training-row
 */

if (!defined('ABSPATH')) {
    exit;
}

$post_id   = isset($post_id) ? (int) $post_id : get_the_ID();
$layout    = isset($layout) ? $layout : 'grid';
$permalink = get_permalink($post_id);
$title     = get_the_title($post_id);
$schedule  = EAB_Event::get_card_schedule_line($post_id);
$price     = EAB_Event::get_price_label($post_id);
$img_id    = EAB_Event::get_tile_image_id($post_id);
$type_label = EAB_Event::get_type_label($post_id);

if ($layout === 'training-row') :
    $meta = EAB_Event::get_training_row_meta($post_id);
    ?>
    <article class="eab-training-row" data-post-id="<?php echo esc_attr($post_id); ?>">
        <h3 class="eab-training-row__title"><?php echo esc_html($title); ?></h3>
        <div class="eab-training-row__meta">
            <?php if (!empty($meta['location'])) : ?>
                <span class="eab-training-row__location caps"><?php echo esc_html($meta['location']); ?></span>
            <?php endif; ?>
            <?php if (!empty($meta['time'])) : ?>
                <span class="eab-training-row__time caps"><?php echo esc_html($meta['time']); ?></span>
            <?php endif; ?>
        </div>
        <span class="eab-training-row__icon icon icon-circ icon-arrow" aria-hidden="true"></span>
        <a class="eab-training-row__link abs-link" href="<?php echo esc_url($permalink); ?>">
            <span class="screen-reader-text"><?php echo esc_html($title); ?></span>
        </a>
    </article>
    <?php
    return;
endif;
?>
<div class="eab-card eab-card--<?php echo esc_attr($layout); ?>" data-post-id="<?php echo esc_attr($post_id); ?>">
    <?php if ($layout === 'grid') : ?>
        <?php if ($schedule) : ?>
            <time class="eab-card__date"><?php echo esc_html($schedule); ?></time>
            <div class="eab-card__rule" aria-hidden="true"></div>
        <?php endif; ?>
        <h3 class="eab-card__title"><?php echo esc_html($title); ?></h3>
        <div class="eab-card__media">
            <?php if ($img_id) : ?>
                <?php echo wp_get_attachment_image($img_id, 'medium_large', false, array('class' => 'eab-card__img', 'loading' => 'lazy')); ?>
            <?php else : ?>
                <span class="eab-card__img eab-card__img--placeholder" aria-hidden="true"></span>
            <?php endif; ?>
        </div>
        <?php echo EAB_Event::render_tags($post_id, array('class' => 'eab-card__tags')); ?>
    <?php else : ?>
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
    <?php endif; ?>
    <a class="eab-card__link abs-link" href="<?php echo esc_url($permalink); ?>">
        <span class="screen-reader-text"><?php echo esc_html($title); ?></span>
    </a>
</div>
