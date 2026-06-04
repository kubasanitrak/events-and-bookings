<?php
/**
 * Basket (virtual booking lines).
 */

if (!defined('ABSPATH')) {
    exit;
}

class EAB_Basket {

    public function __construct() {
        add_action('wp_ajax_eab_add_to_basket', array($this, 'ajax_add_to_basket'));
        add_action('wp_ajax_eab_remove_from_basket', array($this, 'ajax_remove_from_basket'));
        add_action('wp_ajax_eab_update_basket_line', array($this, 'ajax_update_basket_line'));
        add_action('wp_ajax_eab_get_basket_count', array($this, 'ajax_get_basket_count'));
        add_shortcode('eab_basket_count', array($this, 'shortcode_basket_count'));
    }

    public static function should_replace_basket_on_add() {
        return !EAB_Settings::basket_allows_multiple_events();
    }

    public static function validate_spot_count($spots) {
        $spots = (int) $spots;
        $min   = EAB_Settings::basket_min_spots_per_item();
        if ($spots < $min) {
            return new WP_Error(
                'eab_invalid_spots',
                sprintf(__('Minimální počet míst na položku je %d.', 'events-and-bookings'), $min)
            );
        }
        return $spots;
    }

    public static function can_add_post($post_id, $user_id = 0) {
        $post = get_post($post_id);
        if (!$post || $post->post_status !== 'publish') {
            return false;
        }
        if (!EAB_Post_Types::is_bookable_post_type($post->post_type)) {
            return false;
        }
        if (function_exists('get_field')) {
            $open = get_field('booking_open', $post_id);
            if ($open === false || $open === 0 || $open === '0') {
                return false;
            }
        }
        return true;
    }

    public static function default_line_meta($spots = 1) {
        return array(
            'spots'     => max(1, (int) $spots),
            'services'  => array(),
            'attendees' => array(),
            'spot_type' => EAB_Capacity::SPOT_REGULAR,
        );
    }

    public function add_item($post_id, $user_id = null, $line_meta = null) {
        global $wpdb;

        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!$user_id || !$post_id || !self::can_add_post($post_id, $user_id)) {
            return new WP_Error('eab_invalid_item', __('Položku nelze rezervovat.', 'events-and-bookings'));
        }

        $post = get_post($post_id);
        $spots = self::validate_spot_count(
            is_array($line_meta) && isset($line_meta['spots']) ? $line_meta['spots'] : 1
        );
        if (is_wp_error($spots)) {
            return $spots;
        }

        $capacity = EAB_Capacity::can_reserve($post_id, $spots);
        if (is_wp_error($capacity)) {
            return $capacity;
        }

        if (self::should_replace_basket_on_add()) {
            self::clear($user_id);
        }

        if ($this->is_in_basket($post_id, $user_id)) {
            return new WP_Error('eab_already_in_basket', __('Položka je již v košíku.', 'events-and-bookings'));
        }

        $meta = is_array($line_meta) ? wp_parse_args($line_meta, self::default_line_meta($spots)) : self::default_line_meta($spots);
        $meta['spots']     = $spots;
        $meta['spot_type'] = $capacity['spot_type'];

        $table = $wpdb->prefix . 'eab_basket';
        $ok = $wpdb->insert(
            $table,
            array(
                'user_id'     => $user_id,
                'object_id'   => $post_id,
                'object_type' => $post->post_type,
                'line_meta'   => wp_json_encode($meta),
                'added_at'    => current_time('mysql'),
            ),
            array('%d', '%d', '%s', '%s', '%s')
        );

        return $ok ? true : new WP_Error('eab_db_error', __('Košík se nepodařilo uložit.', 'events-and-bookings'));
    }

    public function update_line($post_id, $line_meta, $user_id = null) {
        global $wpdb;

        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $post = get_post($post_id);
        if (!$post || !$this->is_in_basket($post_id, $user_id)) {
            return new WP_Error('eab_not_in_basket', __('Položka není v košíku.', 'events-and-bookings'));
        }

        $spots = self::validate_spot_count($line_meta['spots'] ?? 1);
        if (is_wp_error($spots)) {
            return $spots;
        }

        $capacity = EAB_Capacity::can_reserve($post_id, $spots);
        if (is_wp_error($capacity)) {
            return $capacity;
        }

        $line_meta['spots']     = $spots;
        $line_meta['spot_type'] = $capacity['spot_type'];

        $table = $wpdb->prefix . 'eab_basket';
        return $wpdb->update(
            $table,
            array('line_meta' => wp_json_encode($line_meta)),
            array(
                'user_id'     => $user_id,
                'object_id'   => $post_id,
                'object_type' => $post->post_type,
            ),
            array('%s'),
            array('%d', '%d', '%s')
        );
    }

    public function remove_item($post_id, $user_id = null) {
        global $wpdb;

        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $post = get_post($post_id);
        if (!$post) {
            return false;
        }

        $table = $wpdb->prefix . 'eab_basket';
        return (bool) $wpdb->delete(
            $table,
            array(
                'user_id'     => $user_id,
                'object_id'   => $post_id,
                'object_type' => $post->post_type,
            ),
            array('%d', '%d', '%s')
        );
    }

    public function get_items($user_id = null) {
        global $wpdb;

        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!EAB_DB::table_exists('eab_basket')) {
            return array();
        }

        $table = $wpdb->prefix . 'eab_basket';
        $rows  = $wpdb->get_results($wpdb->prepare(
            "SELECT b.*, p.post_title
             FROM $table b
             LEFT JOIN {$wpdb->posts} p ON b.object_id = p.ID
             WHERE b.user_id = %d
             ORDER BY b.added_at DESC",
            $user_id
        ));

        foreach ($rows as &$row) {
            $row->line_meta = json_decode($row->line_meta, true);
            if (!is_array($row->line_meta)) {
                $row->line_meta = self::default_line_meta();
            }
            $pricing = EAB_Pricing::calculate_line($row->object_id, $row->line_meta);
            $row->line_total = $pricing['line_total'];
            $row->permalink  = get_permalink($row->object_id);
            $row->schedule   = EAB_Event::get_schedule_summary($row->object_id);
        }

        return $rows;
    }

    public function get_count($user_id = null) {
        global $wpdb;

        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!EAB_DB::table_exists('eab_basket') || !$user_id) {
            return 0;
        }

        $table = $wpdb->prefix . 'eab_basket';
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE user_id = %d",
            $user_id
        ));
    }

    public function get_total($user_id = null) {
        $total = 0;
        foreach ($this->get_items($user_id) as $item) {
            $total += (float) $item->line_total;
        }
        return round($total, 2);
    }

    public function is_in_basket($post_id, $user_id = null) {
        global $wpdb;

        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        $post = get_post($post_id);
        if (!$post || !EAB_DB::table_exists('eab_basket')) {
            return false;
        }

        $table = $wpdb->prefix . 'eab_basket';
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE user_id = %d AND object_id = %d AND object_type = %s",
            $user_id,
            $post_id,
            $post->post_type
        ));
    }

    public static function clear($user_id = null) {
        global $wpdb;

        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        if (!EAB_DB::table_exists('eab_basket')) {
            return false;
        }

        $table = $wpdb->prefix . 'eab_basket';
        return $wpdb->delete($table, array('user_id' => $user_id), array('%d'));
    }

    public function ajax_add_to_basket() {
        check_ajax_referer('eab_public', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Přihlaste se prosím.', 'events-and-bookings')));
        }

        $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
        $spots   = isset($_POST['spots']) ? (int) $_POST['spots'] : 1;

        $result = $this->add_item($post_id, null, self::default_line_meta($spots));

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        $checkout = EAB_Auth::get_page_url('checkout');
        wp_send_json_success(array(
            'message'      => __('Přidáno do košíku.', 'events-and-bookings'),
            'count'        => $this->get_count(),
            'checkout_url' => $checkout ?: '',
        ));
    }

    public function ajax_remove_from_basket() {
        check_ajax_referer('eab_public', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Přihlaste se prosím.', 'events-and-bookings')));
        }

        $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
        $this->remove_item($post_id);

        wp_send_json_success(array(
            'count' => $this->get_count(),
            'total' => $this->get_total(),
        ));
    }

    public function ajax_update_basket_line() {
        check_ajax_referer('eab_public', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Přihlaste se prosím.', 'events-and-bookings')));
        }

        $post_id = isset($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
        $meta    = isset($_POST['line_meta']) ? json_decode(wp_unslash($_POST['line_meta']), true) : array();

        if (!is_array($meta)) {
            wp_send_json_error(array('message' => __('Neplatná data.', 'events-and-bookings')));
        }

        $result = $this->update_line($post_id, $meta);
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array('total' => $this->get_total()));
    }

    public function ajax_get_basket_count() {
        check_ajax_referer('eab_public', 'nonce');
        wp_send_json_success(array('count' => is_user_logged_in() ? $this->get_count() : 0));
    }

    public function shortcode_basket_count() {
        if (!is_user_logged_in()) {
            return '';
        }
        $count = $this->get_count();
        $checkout = EAB_Auth::get_page_url('checkout');
        ob_start();
        ?>
        <a class="eab-basket-count" href="<?php echo esc_url($checkout ?: '#'); ?>">
            <?php
            printf(
                /* translators: %d: item count */
                esc_html__('Košík (%d)', 'events-and-bookings'),
                $count
            );
            ?>
        </a>
        <?php
        return ob_get_clean();
    }
}
