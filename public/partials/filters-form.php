<?php
/**
 * GET filter form for listings.
 *
 * @var string $filter_action
 * @var array  $preset_atts
 * @var bool   $show_type_toggle
 */

if (!defined('ABSPATH')) {
    exit;
}

$filter_action = !empty($filter_action) ? $filter_action : '';
$active = EAB_Query::get_active_filters();
$groups = EAB_Query::get_filter_term_groups();
$show_type_toggle = !empty($show_type_toggle);

$preset_type = isset($preset_atts['type']) ? sanitize_key($preset_atts['type']) : '';
$current_type = $active['type'] ?: $preset_type;
?>
<form class="eab-filters" method="get" action="<?php echo esc_url($filter_action); ?>">
    <?php if ($show_type_toggle) : ?>
        <fieldset class="eab-filters__group">
            <legend><?php esc_html_e('Typ', 'events-and-bookings'); ?></legend>
            <label>
                <input type="radio" name="<?php echo esc_attr(EAB_Query::GET_TYPE); ?>" value=""
                    <?php checked($current_type, ''); ?>>
                <?php esc_html_e('Vše', 'events-and-bookings'); ?>
            </label>
            <label>
                <input type="radio" name="<?php echo esc_attr(EAB_Query::GET_TYPE); ?>" value="event"
                    <?php checked($current_type, 'event'); ?>>
                <?php esc_html_e('Akce', 'events-and-bookings'); ?>
            </label>
            <label>
                <input type="radio" name="<?php echo esc_attr(EAB_Query::GET_TYPE); ?>" value="training"
                    <?php checked($current_type, 'training'); ?>>
                <?php esc_html_e('Tréninky', 'events-and-bookings'); ?>
            </label>
        </fieldset>
    <?php endif; ?>

    <?php
    $fields = array(
        'audience' => array(EAB_Query::GET_AUDIENCE, __('Publikum', 'events-and-bookings'), $groups['audience']),
        'schedule' => array(EAB_Query::GET_SCHEDULE, __('Rozvržení', 'events-and-bookings'), $groups['schedule']),
        'kind'     => array(EAB_Query::GET_KIND, __('Druh', 'events-and-bookings'), $groups['kind']),
        'region'   => array(EAB_Query::GET_REGION, __('Region', 'events-and-bookings'), $groups['region']),
    );
    $preset_map = array(
        'audience' => isset($preset_atts['audience']) ? $preset_atts['audience'] : '',
        'schedule' => isset($preset_atts['schedule']) ? $preset_atts['schedule'] : '',
        'kind'     => isset($preset_atts['kind']) ? $preset_atts['kind'] : '',
        'region'   => isset($preset_atts['region']) ? $preset_atts['region'] : '',
    );

    foreach ($fields as $key => $field) :
        list($param, $label, $terms) = $field;
        if (is_wp_error($terms) || empty($terms)) {
            continue;
        }
        $selected = $active[$key] ?: sanitize_title($preset_map[$key]);
        ?>
        <div class="eab-filters__group">
            <label for="<?php echo esc_attr('eab-filter-' . $key); ?>"><?php echo esc_html($label); ?></label>
            <select name="<?php echo esc_attr($param); ?>" id="<?php echo esc_attr('eab-filter-' . $key); ?>">
                <option value=""><?php esc_html_e('Vše', 'events-and-bookings'); ?></option>
                <?php foreach ($terms as $term) : ?>
                    <option value="<?php echo esc_attr($term->slug); ?>" <?php selected($selected, $term->slug); ?>>
                        <?php echo esc_html($term->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endforeach; ?>

    <div class="eab-filters__actions">
        <button type="submit" class="eab-btn"><?php esc_html_e('Filtrovat', 'events-and-bookings'); ?></button>
        <a class="eab-btn eab-btn--ghost" href="<?php echo esc_url($filter_action ?: remove_query_arg(EAB_Query::get_all_filter_params())); ?>"><?php esc_html_e('Zrušit filtry', 'events-and-bookings'); ?></a>
    </div>
</form>
