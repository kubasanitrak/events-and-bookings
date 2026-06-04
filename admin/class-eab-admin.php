<?php
/**
 * Admin menu and CPT integration.
 */

if (!defined('ABSPATH')) {
    exit;
}

class EAB_Admin {

    const MENU_SLUG = 'eab-main-menu';

    public function __construct() {
        add_action('admin_menu', array($this, 'register_menu'), 5);
        add_action('admin_menu', array($this, 'reorder_submenu'), 999);
        add_filter('register_post_type_args', array($this, 'attach_cpts_to_menu'), 10, 2);
    }

    public function attach_cpts_to_menu($args, $post_type) {
        $types = array(
            EAB_Post_Types::POST_TYPE_EVENT,
            EAB_Post_Types::POST_TYPE_TRAINING,
            EAB_Post_Types::POST_TYPE_INSTRUCTOR,
        );

        if (in_array($post_type, $types, true)) {
            $args['show_in_menu'] = self::MENU_SLUG;
        }

        return $args;
    }

    public function register_menu() {
        add_menu_page(
            __('Akce a rezervace', 'events-and-bookings'),
            __('Akce a rezervace', 'events-and-bookings'),
            'edit_posts',
            self::MENU_SLUG,
            array($this, 'render_dashboard_placeholder'),
            'dashicons-tickets-alt',
            26
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Přehled', 'events-and-bookings'),
            __('Přehled', 'events-and-bookings'),
            'edit_posts',
            self::MENU_SLUG,
            array($this, 'render_dashboard_placeholder')
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Akce', 'events-and-bookings'),
            __('Akce', 'events-and-bookings'),
            'edit_posts',
            'edit.php?post_type=' . EAB_Post_Types::POST_TYPE_EVENT
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Tréninky', 'events-and-bookings'),
            __('Tréninky', 'events-and-bookings'),
            'edit_posts',
            'edit.php?post_type=' . EAB_Post_Types::POST_TYPE_TRAINING
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Instruktoři', 'events-and-bookings'),
            __('Instruktoři', 'events-and-bookings'),
            'edit_posts',
            'edit.php?post_type=' . EAB_Post_Types::POST_TYPE_INSTRUCTOR
        );

        $this->register_taxonomy_submenus();
    }

    private function register_taxonomy_submenus() {
        $taxonomies = array(
            EAB_Post_Types::TAX_AUDIENCE      => __('Publikum', 'events-and-bookings'),
            EAB_Post_Types::TAX_SCHEDULE_TYPE => __('Rozvržení', 'events-and-bookings'),
            EAB_Post_Types::TAX_EVENT_KIND    => __('Druh', 'events-and-bookings'),
            EAB_Post_Types::TAX_REGION        => __('Region', 'events-and-bookings'),
        );

        foreach ($taxonomies as $taxonomy => $label) {
            add_submenu_page(
                self::MENU_SLUG,
                $label,
                $label,
                'manage_categories',
                sprintf(
                    'edit-tags.php?taxonomy=%s&post_type=%s',
                    $taxonomy,
                    EAB_Post_Types::POST_TYPE_EVENT
                )
            );
        }
    }

    public function reorder_submenu() {
        global $submenu;
        if (!isset($submenu[self::MENU_SLUG])) {
            return;
        }

        $order = array();
        $wanted = array(
            self::MENU_SLUG,
            'edit.php?post_type=' . EAB_Post_Types::POST_TYPE_EVENT,
            'post-new.php?post_type=' . EAB_Post_Types::POST_TYPE_EVENT,
            'edit.php?post_type=' . EAB_Post_Types::POST_TYPE_TRAINING,
            'post-new.php?post_type=' . EAB_Post_Types::POST_TYPE_TRAINING,
            'edit.php?post_type=' . EAB_Post_Types::POST_TYPE_INSTRUCTOR,
            'post-new.php?post_type=' . EAB_Post_Types::POST_TYPE_INSTRUCTOR,
        );

        foreach ($wanted as $slug) {
            foreach ($submenu[self::MENU_SLUG] as $item) {
                if (isset($item[2]) && $item[2] === $slug) {
                    $order[] = $item;
                    break;
                }
            }
        }

        foreach ($submenu[self::MENU_SLUG] as $item) {
            if (!in_array($item, $order, true)) {
                $order[] = $item;
            }
        }

        $submenu[self::MENU_SLUG] = $order;
    }

    public function render_dashboard_placeholder() {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Akce a rezervace', 'events-and-bookings') . '</h1>';
        echo '<p>' . esc_html__('Přehled rezervací a statistik bude doplněn v další fázi.', 'events-and-bookings') . '</p>';
        if (!EAB_ACF::is_active()) {
            echo '<div class="notice notice-warning"><p>';
            echo esc_html__('ACF Pro není aktivní — pole pro termíny, ceny a kapacitu zatím nelze spravovat přes ACF.', 'events-and-bookings');
            echo '</p></div>';
        }
        echo '</div>';
    }
}
