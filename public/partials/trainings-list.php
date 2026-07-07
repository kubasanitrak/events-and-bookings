<?php
/**
 * @var WP_Query $kids_query
 * @var WP_Query $adults_query
 * @var string   $kids_title
 * @var string   $adults_title
 * @var string   $filter_action
 */

if (!defined('ABSPATH')) {
    exit;
}

$filter_action = $filter_action ?: get_permalink();
?>
<div class="eab-trainings">
    <section class="eab-trainings__section eab-trainings__section--kids" id="pro-deti">
        <header class="eab-trainings__header">
            <p class="eab-trainings__label caps"><?php esc_html_e('Pro děti', 'events-and-bookings'); ?></p>
            <div class="eab-trainings__rule" aria-hidden="true"></div>
            <?php if (!empty($kids_title)) : ?>
                <h2 class="eab-trainings__title"><?php echo esc_html($kids_title); ?></h2>
            <?php endif; ?>
        </header>

        <?php
        $filter_context = 'kids';
        $reset_params   = array(EAB_Query::GET_AGE_GROUP);
        include EAB_PLUGIN_DIR . 'public/partials/filters-pills.php';
        ?>

        <?php if ($kids_query->have_posts()) : ?>
            <div class="eab-training-list">
                <?php
                while ($kids_query->have_posts()) :
                    $kids_query->the_post();
                    $post_id = get_the_ID();
                    $layout  = 'training-row';
                    include EAB_PLUGIN_DIR . 'public/partials/event-card.php';
                endwhile;
                wp_reset_postdata();
                ?>
            </div>
        <?php else : ?>
            <p class="eab-empty"><?php esc_html_e('Žádné položky nevyhovují filtru.', 'events-and-bookings'); ?></p>
        <?php endif; ?>
    </section>

    <section class="eab-trainings__section eab-trainings__section--adults" id="pro-dospele">
        <header class="eab-trainings__header">
            <p class="eab-trainings__label caps"><?php esc_html_e('Pro dospělé', 'events-and-bookings'); ?></p>
            <div class="eab-trainings__rule" aria-hidden="true"></div>
            <?php if (!empty($adults_title)) : ?>
                <h2 class="eab-trainings__title"><?php echo esc_html($adults_title); ?></h2>
            <?php endif; ?>
        </header>

        <?php
        $filter_context = 'adults';
        $reset_params   = array(EAB_Query::GET_SKILL_LEVEL, EAB_Query::GET_GENDER);
        include EAB_PLUGIN_DIR . 'public/partials/filters-pills.php';
        ?>

        <?php if ($adults_query->have_posts()) : ?>
            <div class="eab-training-grid">
                <?php
                while ($adults_query->have_posts()) :
                    $adults_query->the_post();
                    $post_id = get_the_ID();
                    $layout  = 'training-row';
                    include EAB_PLUGIN_DIR . 'public/partials/event-card.php';
                endwhile;
                wp_reset_postdata();
                ?>
            </div>
        <?php else : ?>
            <p class="eab-empty"><?php esc_html_e('Žádné položky nevyhovují filtru.', 'events-and-bookings'); ?></p>
        <?php endif; ?>
    </section>
</div>
