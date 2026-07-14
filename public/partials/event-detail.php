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
<section class="eab-detail__section eab-detail__header">
    <?php 
        if ( function_exists('get_field') && get_field('one_off_date') ) :
        ?>
            <h5 class="eab-item--subtitle"><?php echo get_field('one_off_date'); ?></h5>

    <?php
        endif;
    ?>
</section>
<section class="eab-detail__section even-cols" id="eab-detail-<?php echo esc_attr($post_id); ?>">
    <div class="eab-detail__col even-cols--item border-T">
        <h2 class="eab-detail__title"><?php echo esc_html(get_the_title($post_id)); ?></h2>
    </div>
    <div class="eab-detail__col even-cols--item border-T">
        <?php $synopsis = get_field('synopsis', $post_id); 
        if ($synopsis) : ?>
            <!-- <h5 class="eab-item--subtitle border-B"><?php #esc_html_e('Synopse', 'events-and-bookings'); ?></h5> -->
            <div class="eab-detail__prose"><?php echo wp_kses_post(wpautop($synopsis)); ?></div>
        <?php endif; ?>
    </div>
</section>
<section class="eab-detail__section even-cols pad-B pad-T" >
    <div class="eab-detail__col even-cols--item">
        <?php if ($img_id) : ?>
            <figure class="eab-detail__hero">
                <?php echo wp_get_attachment_image($img_id, 'large', false, array('class' => 'eab-detail__hero-img')); ?>
            </figure>
        <?php endif; ?>
    </div>
    <div class="eab-detail__col even-cols--item">
        <h5 class="eab-item--subtitle border-B caps"><?php esc_html_e('Detaily', 'events-and-bookings'); ?></h5>
        <p class="eab-detail__type"><?php echo esc_html(EAB_Event::get_type_label($post_id)); ?></p>
        
        <?php if ($schedule) : ?>
            <p class="eab-detail__schedule"><?php echo esc_html($schedule); ?></p>
        <?php endif; ?>
        <?php echo EAB_Event::render_tags($post_id, array('class' => 'eab-detail__tags', 'link' => true)); ?>

        <?php if (function_exists('get_field')) :
            $place_text = get_field('place_text', $post_id);
            $location   = get_field('location', $post_id);
            if ($place_text || $location) : ?>
                
                    <h6 class="caps"><?php esc_html_e('Místo', 'events-and-bookings'); ?></h6>
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
                
            <?php endif;
        endif; ?>

        <?php if (function_exists('get_field')) :
            $instructors = get_field('instructors', $post_id);
            if (!empty($instructors)) :
                $ids = is_array($instructors) ? $instructors : array($instructors);
                ?>
                    <h6 class="caps"><?php esc_html_e('Instruktoři', 'events-and-bookings'); ?></h6>
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
            <?php endif;
        endif; ?>

<!-- VOLITELNÉ SERVICES -->
        <?php if ($show_services && function_exists('get_field')) :
            $services = get_field('optional_services', $post_id);
            if (!empty($services) && is_array($services)) : ?>
                
                    <h6 class="caps border-T"><?php esc_html_e('Volitelné služby', 'events-and-bookings'); ?></h6>
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
            <?php endif;
        endif; ?>

        <div class="eab-detail__cta-container">
            <?php if ($price) : ?>
                <h6 class="caps"><?php esc_html_e('Cena celkem', 'events-and-bookings'); ?></h6>
                <h3 class="eab-detail__price"><?php echo esc_html($price); ?></h3>
            <?php endif; ?>

            <?php
                $post_id = $post_id;
                include EAB_PLUGIN_DIR . 'public/partials/book-button.php';
            ?>
        </div>
    </div>
</section>

    <!-- <?php #if (has_excerpt($post_id)) : ?>
        <p class="eab-detail__excerpt">
            <?php #echo esc_html(get_the_excerpt($post_id)); ?>
        </p>
    <?php #endif; ?> -->

    

<section class="eab-detail__section even-cols pad-B pad-T" data-theme="silky-blue">
    <div class="eab-detail__col even-cols--item">
        <h5 class="eab-item--subtitle border-B caps"><?php esc_html_e('O Kempu', 'events-and-bookings'); ?></h5>
        <?php echo apply_filters('the_content', $post->post_content); ?>
    </div>

    <?php if ($show_program && function_exists('get_field')) :
        
        $program  = get_field('program', $post_id);
        
        if ($program) : ?>
                <div class="eab-detail__col even-cols--item">
                    <h5 class="eab-item--subtitle border-B caps"><?php esc_html_e('Program', 'events-and-bookings'); ?></h5>
                    <div class="eab-detail__prose"><?php echo wp_kses_post($program); ?></div>
                </div>
        <?php endif;
    endif; ?>
</section>

<section class="eab-detail__section even-cols pad-B pad-T" data-theme="silky-blue">
    <div class="eab-detail__col even-cols--item">
        <h5 class="eab-item--subtitle border-B caps"><?php esc_html_e('Účastníci', 'events-and-bookings'); ?></h5>
            <?php if ($show_attendees && EAB_Access::can_view_attendee_list($post_id)) : ?>
            
                <?php
                $attendees = apply_filters('eab_event_attendees', EAB_Capacity::get_public_attendee_names($post_id), $post_id);
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
        <?php endif; ?>
    </div>
</section>
<?php if ($show_gallery && function_exists('get_field')) :
    $gallery = get_field('gallery', $post_id);
    if (!empty($gallery) && is_array($gallery)) : ?>
        <section class="eab-detail__section eab-detail__gallery" data-theme="default">
            <?php foreach ($gallery as $image) :
                $id = is_array($image) ? ($image['ID'] ?? 0) : (int) $image;
                if ($id) :
                    $CLS = 'lazyload eab-gallery__img';
                    $size = 'medium';
                    $img_atts = wp_get_attachment_image_src( $id, $size );
                    $img_W = $img_atts[1];
                    $img_H = $img_atts[2];
                    $ratio = $img_W / $img_H;
                    $is_portrait = ($ratio < 1) ? ' is-portrait' : '';
                    $CLS .= $is_portrait;
                        // echo wp_get_attachment_image($id, 'medium_large', false, array('class' => 'lazyload eab-gallery__img', 'loading' => 'lazy'));
                    echo wp_get_attachment_image($id, 'medium_large', false, array('class' => $CLS, 'loading' => 'lazy'));
                endif;
            endforeach; ?>
        </section>
    <?php endif;
endif; ?>