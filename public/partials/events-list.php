<?php
/**
 * @var WP_Query $query
 * @var string   $title
 * @var bool     $show_filters
 * @var string   $filter_action
 * @var array    $preset_atts
 */

if (!defined('ABSPATH')) {
    exit;
}

$filter_action = $filter_action ?: get_permalink();
?>
<div class="eab-listing eab-listing--list">
    <?php if (!empty($title)) : ?>
        <header class="eab-listing__header">
            <h2 class="eab-listing__title"><?php echo esc_html($title); ?></h2>
        </header>
    <?php endif; ?>

    <?php if (!empty($show_filters)) : ?>
        <?php
        $show_type_toggle = true;
        include EAB_PLUGIN_DIR . 'public/partials/filters-form.php';
        ?>
    <?php endif; ?>

    <?php if ($query->have_posts()) : ?>
        <div class="eab-list">
            <?php
            while ($query->have_posts()) :
                $query->the_post();
                $post_id = get_the_ID();
                $layout = 'list';
                include EAB_PLUGIN_DIR . 'public/partials/event-card.php';
            endwhile;
            ?>
        </div>
        <?php
        $pagination = paginate_links(array(
            'total'   => $query->max_num_pages,
            'current' => max(1, (int) get_query_var('paged')),
            'type'    => 'list',
        ));
        if ($pagination) {
            echo '<nav class="eab-pagination">' . wp_kses_post($pagination) . '</nav>';
        }
        ?>
    <?php else : ?>
        <p class="eab-empty"><?php esc_html_e('Žádné položky nevyhovují filtru.', 'events-and-bookings'); ?></p>
    <?php endif; ?>
</div>
