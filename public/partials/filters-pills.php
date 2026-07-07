<?php
/**
 * Pill-style GET filters for listings.
 *
 * @var string $filter_action
 * @var string $filter_context events|kids|adults
 * @var array  $reset_params
 */

if (!defined('ABSPATH')) {
    exit;
}

$filter_action  = !empty($filter_action) ? $filter_action : '';
$filter_context = !empty($filter_context) ? $filter_context : 'events';
$reset_params   = !empty($reset_params) && is_array($reset_params) ? $reset_params : EAB_Query::get_all_filter_params();
$pills          = EAB_Query::get_filter_pills($filter_context);
$reset_url      = EAB_Query::get_filter_reset_url($filter_action, $reset_params);
?>
<div class="eab-filters eab-filters--pills" data-eab-filter-context="<?php echo esc_attr($filter_context); ?>">
    <div class="eab-filters__head">
        <p class="eab-filters__label caps"><?php esc_html_e('Filtrace', 'events-and-bookings'); ?></p>
        <a class="eab-filters__reset caps" href="<?php echo esc_url($reset_url); ?>">
            <?php esc_html_e('Obnovit filtr', 'events-and-bookings'); ?>
        </a>
    </div>

    <div class="eab-filters__rule" aria-hidden="true"></div>

    <div class="eab-filters__pills" role="group" aria-label="<?php esc_attr_e('Filtrace', 'events-and-bookings'); ?>">
        <?php foreach ($pills as $pill) : ?>
            <?php
            $url = EAB_Query::get_filter_toggle_url(
                $filter_action,
                $pill['param'],
                $pill['slug'],
                $pill['key']
            );
            $classes = array('eab-filter-pill', 'caps');
            if (!empty($pill['active'])) {
                $classes[] = 'is-active';
            }
            ?>
            <a
                class="<?php echo esc_attr(implode(' ', $classes)); ?>"
                href="<?php echo esc_url($url); ?>"
                data-eab-filter-param="<?php echo esc_attr($pill['param']); ?>"
                data-eab-filter-slug="<?php echo esc_attr($pill['slug']); ?>"
                data-eab-filter-key="<?php echo esc_attr($pill['key']); ?>"
                <?php echo !empty($pill['active']) ? ' aria-current="true"' : ''; ?>
            ><?php echo esc_html($pill['label']); ?></a>
        <?php endforeach; ?>
    </div>
</div>
