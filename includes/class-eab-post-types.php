<?php
/**
 * Custom post types and taxonomies (Czech rewrite slugs).
 */

if (!defined('ABSPATH')) {
    exit;
}

class EAB_Post_Types {

    const POST_TYPE_EVENT     = 'eab_event';
    const POST_TYPE_TRAINING  = 'eab_training';
    const POST_TYPE_INSTRUCTOR = 'eab_instructor';

    const TAX_AUDIENCE       = 'eab_audience';
    const TAX_SCHEDULE_TYPE  = 'eab_schedule_type';
    const TAX_EVENT_KIND     = 'eab_event_kind';
    const TAX_REGION         = 'eab_region';
    const TAX_AGE_GROUP      = 'eab_age_group';
    const TAX_SKILL_LEVEL    = 'eab_skill_level';
    const TAX_GENDER         = 'eab_gender';

    /** @var string[] */
    private static $bookable_post_types = array(
        self::POST_TYPE_EVENT,
        self::POST_TYPE_TRAINING,
    );

    public function __construct() {
        add_action('init', array($this, 'register_post_types'), 5);
        add_action('init', array($this, 'register_taxonomies'), 6);
        add_action('init', array($this, 'maybe_seed_default_terms'), 20);
        add_action('init', array($this, 'maybe_upgrade_terms'), 21);
        add_filter('manage_' . self::POST_TYPE_EVENT . '_posts_columns', array($this, 'add_list_columns'));
        add_filter('manage_' . self::POST_TYPE_TRAINING . '_posts_columns', array($this, 'add_list_columns'));
        add_action('manage_' . self::POST_TYPE_EVENT . '_posts_custom_column', array($this, 'render_list_columns'), 10, 2);
        add_action('manage_' . self::POST_TYPE_TRAINING . '_posts_custom_column', array($this, 'render_list_columns'), 10, 2);
    }

    /**
     * Post types that accept bookings (trainings + events).
     *
     * @return string[]
     */
    public static function get_bookable_post_types() {
        return self::$bookable_post_types;
    }

    public static function is_bookable_post_type($post_type) {
        return in_array($post_type, self::$bookable_post_types, true);
    }

    public function register_post_types() {
        $this->register_event();
        $this->register_training();
        $this->register_instructor();
    }

    private function register_event() {
        $labels = array(
            'name'               => __('Akce', 'events-and-bookings'),
            'singular_name'      => __('Akce', 'events-and-bookings'),
            'menu_name'          => __('Akce', 'events-and-bookings'),
            'add_new'            => __('Přidat akci', 'events-and-bookings'),
            'add_new_item'       => __('Přidat novou akci', 'events-and-bookings'),
            'edit_item'          => __('Upravit akci', 'events-and-bookings'),
            'new_item'           => __('Nová akce', 'events-and-bookings'),
            'view_item'          => __('Zobrazit akci', 'events-and-bookings'),
            'search_items'       => __('Hledat akce', 'events-and-bookings'),
            'not_found'          => __('Žádné akce nenalezeny', 'events-and-bookings'),
            'not_found_in_trash' => __('V koši nejsou žádné akce', 'events-and-bookings'),
        );

        register_post_type(self::POST_TYPE_EVENT, array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => false,
            'show_in_rest'        => true,
            'query_var'           => true,
            'rewrite'             => array(
                'slug'       => 'akce',
                'with_front' => false,
            ),
            // Archive disabled: /akce/ is served by a real WP page holding
            // the [eab_events_list] shortcode. Singles stay at /akce/{slug}/.
            'has_archive'         => false,
            'capability_type'     => 'post',
            'hierarchical'        => false,
            'menu_icon'           => 'dashicons-calendar-alt',
            'supports'            => array('title', 'editor', 'thumbnail', 'excerpt'),
        ));
    }

    private function register_training() {
        $labels = array(
            'name'               => __('Tréninky', 'events-and-bookings'),
            'singular_name'      => __('Trénink', 'events-and-bookings'),
            'menu_name'          => __('Tréninky', 'events-and-bookings'),
            'add_new'            => __('Přidat trénink', 'events-and-bookings'),
            'add_new_item'       => __('Přidat nový trénink', 'events-and-bookings'),
            'edit_item'          => __('Upravit trénink', 'events-and-bookings'),
            'new_item'           => __('Nový trénink', 'events-and-bookings'),
            'view_item'          => __('Zobrazit trénink', 'events-and-bookings'),
            'search_items'       => __('Hledat tréninky', 'events-and-bookings'),
            'not_found'          => __('Žádné tréninky nenalezeny', 'events-and-bookings'),
            'not_found_in_trash' => __('V koši nejsou žádné tréninky', 'events-and-bookings'),
        );

        register_post_type(self::POST_TYPE_TRAINING, array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => false,
            'show_in_rest'        => true,
            'query_var'           => true,
            'rewrite'             => array(
                'slug'       => 'treninky',
                'with_front' => false,
            ),
            // Archive disabled: /treninky/ is served by a real WP page holding
            // the [eab_events_list] shortcode. Singles stay at /treninky/{slug}/.
            'has_archive'         => false,
            'capability_type'     => 'post',
            'hierarchical'        => false,
            'menu_icon'           => 'dashicons-universal-access-alt',
            'supports'            => array('title', 'editor', 'thumbnail', 'excerpt'),
        ));
    }

    private function register_instructor() {
        $labels = array(
            'name'               => __('Instruktoři', 'events-and-bookings'),
            'singular_name'      => __('Instruktor', 'events-and-bookings'),
            'menu_name'          => __('Instruktoři', 'events-and-bookings'),
            'add_new'            => __('Přidat instruktora', 'events-and-bookings'),
            'add_new_item'       => __('Přidat nového instruktora', 'events-and-bookings'),
            'edit_item'          => __('Upravit instruktora', 'events-and-bookings'),
            'new_item'           => __('Nový instruktor', 'events-and-bookings'),
            'view_item'          => __('Zobrazit instruktora', 'events-and-bookings'),
            'search_items'       => __('Hledat instruktory', 'events-and-bookings'),
            'not_found'          => __('Žádní instruktoři nenalezeni', 'events-and-bookings'),
            'not_found_in_trash' => __('V koši nejsou žádní instruktoři', 'events-and-bookings'),
        );

        register_post_type(self::POST_TYPE_INSTRUCTOR, array(
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => false,
            'show_in_rest'        => true,
            'query_var'           => true,
            'rewrite'             => array(
                'slug'       => 'instruktori',
                'with_front' => false,
            ),
            'has_archive'         => 'instruktori',
            'capability_type'     => 'post',
            'hierarchical'        => false,
            'menu_icon'           => 'dashicons-groups',
            'supports'            => array('title', 'editor', 'thumbnail', 'excerpt'),
        ));
    }

    public function register_taxonomies() {
        foreach (self::$bookable_post_types as $post_type) {
            $this->register_audience_taxonomy($post_type);
            $this->register_schedule_type_taxonomy($post_type);
            $this->register_event_kind_taxonomy($post_type);
            $this->register_region_taxonomy($post_type);
        }

        $this->register_age_group_taxonomy(self::POST_TYPE_TRAINING);
        $this->register_skill_level_taxonomy(self::POST_TYPE_TRAINING);
        $this->register_gender_taxonomy(self::POST_TYPE_TRAINING);
    }

    private function register_audience_taxonomy($post_type) {
        register_taxonomy(self::TAX_AUDIENCE, $post_type, array(
            'labels' => array(
                'name'          => __('Publikum', 'events-and-bookings'),
                'singular_name' => __('Publikum', 'events-and-bookings'),
            ),
            'hierarchical'      => false,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => array(
                'slug'         => 'publikum',
                'with_front'   => false,
            ),
        ));
    }

    private function register_schedule_type_taxonomy($post_type) {
        register_taxonomy(self::TAX_SCHEDULE_TYPE, $post_type, array(
            'labels' => array(
                'name'          => __('Rozvržení', 'events-and-bookings'),
                'singular_name' => __('Rozvržení', 'events-and-bookings'),
            ),
            'hierarchical'      => false,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => array(
                'slug'         => 'rozvrzeni',
                'with_front'   => false,
            ),
        ));
    }

    private function register_event_kind_taxonomy($post_type) {
        register_taxonomy(self::TAX_EVENT_KIND, $post_type, array(
            'labels' => array(
                'name'          => __('Druh', 'events-and-bookings'),
                'singular_name' => __('Druh', 'events-and-bookings'),
            ),
            'hierarchical'      => false,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => array(
                'slug'         => 'druh',
                'with_front'   => false,
            ),
        ));
    }

    private function register_region_taxonomy($post_type) {
        register_taxonomy(self::TAX_REGION, $post_type, array(
            'labels' => array(
                'name'          => __('Region', 'events-and-bookings'),
                'singular_name' => __('Region', 'events-and-bookings'),
            ),
            'hierarchical'      => false,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => array(
                'slug'         => 'region',
                'with_front'   => false,
            ),
        ));
    }

    private function register_age_group_taxonomy($post_type) {
        register_taxonomy(self::TAX_AGE_GROUP, $post_type, array(
            'labels' => array(
                'name'          => __('Věková skupina', 'events-and-bookings'),
                'singular_name' => __('Věková skupina', 'events-and-bookings'),
            ),
            'hierarchical'      => false,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => array(
                'slug'       => 'vek',
                'with_front' => false,
            ),
        ));
    }

    private function register_skill_level_taxonomy($post_type) {
        register_taxonomy(self::TAX_SKILL_LEVEL, $post_type, array(
            'labels' => array(
                'name'          => __('Úroveň', 'events-and-bookings'),
                'singular_name' => __('Úroveň', 'events-and-bookings'),
            ),
            'hierarchical'      => false,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => array(
                'slug'       => 'uroven',
                'with_front' => false,
            ),
        ));
    }

    private function register_gender_taxonomy($post_type) {
        register_taxonomy(self::TAX_GENDER, $post_type, array(
            'labels' => array(
                'name'          => __('Pohlaví / skupina', 'events-and-bookings'),
                'singular_name' => __('Pohlaví / skupina', 'events-and-bookings'),
            ),
            'hierarchical'      => false,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => array(
                'slug'       => 'pohlavi',
                'with_front' => false,
            ),
        ));
    }

    public function maybe_seed_default_terms() {
        if (get_option('eab_default_terms_seeded')) {
            return;
        }
        self::seed_default_terms();
        update_option('eab_default_terms_seeded', 1);
    }

    /**
     * Seed default filter terms (idempotent).
     */
    public static function seed_default_terms() {
        $groups = array(
            self::TAX_AUDIENCE => array(
                'deti'     => __('Děti', 'events-and-bookings'),
                'dospeli'  => __('Dospělí', 'events-and-bookings'),
            ),
            self::TAX_SCHEDULE_TYPE => array(
                'tydenni'   => __('Týdenní', 'events-and-bookings'),
                'vikend'    => __('Víkend', 'events-and-bookings'),
                'cely-den'  => __('Jednodenní', 'events-and-bookings'),
            ),
            self::TAX_EVENT_KIND => array(
                'turnaj'      => __('Turnaj', 'events-and-bookings'),
                'tabor'       => __('Tábor', 'events-and-bookings'),
                'letni-tabor' => __('Letní tábor', 'events-and-bookings'),
                'kemp'        => __('Kemp', 'events-and-bookings'),
            ),
            self::TAX_REGION => array(
                'zahranici' => __('Zahraničí', 'events-and-bookings'),
                'domaci'    => __('Domácí', 'events-and-bookings'),
            ),
            self::TAX_AGE_GROUP => array(
                '3-6-let'    => __('3–6 let', 'events-and-bookings'),
                '6-9-let'    => __('6–9 let', 'events-and-bookings'),
                '10-15-let'  => __('10–15 let', 'events-and-bookings'),
            ),
            self::TAX_SKILL_LEVEL => array(
                'zacatecnici'       => __('Začátečníci', 'events-and-bookings'),
                'stredne-pokrocili' => __('Středně pokročilí', 'events-and-bookings'),
                'pokrocili'         => __('Pokročilí', 'events-and-bookings'),
            ),
            self::TAX_GENDER => array(
                'zeny' => __('Ženy', 'events-and-bookings'),
                'muzi' => __('Muži', 'events-and-bookings'),
            ),
        );

        foreach ($groups as $taxonomy => $terms) {
            foreach ($terms as $slug => $name) {
                if (!term_exists($slug, $taxonomy)) {
                    wp_insert_term($name, $taxonomy, array('slug' => $slug));
                }
            }
        }
    }

    /**
     * Upgrade terms on existing installs (idempotent).
     */
    public function maybe_upgrade_terms() {
        $version = (int) get_option('eab_terms_version', 0);
        if ($version >= 2) {
            return;
        }

        self::seed_default_terms();

        $cely_den = get_term_by('slug', 'cely-den', self::TAX_SCHEDULE_TYPE);
        if ($cely_den && !is_wp_error($cely_den)) {
            wp_update_term((int) $cely_den->term_id, self::TAX_SCHEDULE_TYPE, array(
                'name' => __('Jednodenní', 'events-and-bookings'),
            ));
        }

        update_option('eab_terms_version', 2);

        $trainings_page = get_page_by_path('treninky');
        if ($trainings_page) {
            $expected = '[eab_trainings_list filter_action="/treninky/"]';
            if (strpos($trainings_page->post_content, 'eab_trainings_list') === false) {
                wp_update_post(array(
                    'ID'           => (int) $trainings_page->ID,
                    'post_content' => $expected,
                ));
            }
        }
    }

    public function add_list_columns($columns) {
        $new = array();
        foreach ($columns as $key => $label) {
            $new[$key] = $label;
            if ($key === 'title') {
                $new['eab_booking'] = __('Rezervace', 'events-and-bookings');
            }
        }
        return $new;
    }

    public function render_list_columns($column, $post_id) {
        if ($column !== 'eab_booking') {
            return;
        }
        if (function_exists('get_field') && get_field('booking_open', $post_id) !== null) {
            echo get_field('booking_open', $post_id) ? esc_html__('Otevřeno', 'events-and-bookings') : esc_html__('Zavřeno', 'events-and-bookings');
            return;
        }
        echo '—';
    }
}
