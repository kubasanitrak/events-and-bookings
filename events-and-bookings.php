<?php
/**
 * Plugin Name: Events and Bookings
 * Plugin URI: https://github.com/kubasanitrak/events-and-bookings
 * Description: Akce, tréninky a rezervace pro WordPress.
 * Version: 0.7.16
 * Author: kubasanitrak
 * Author URI: https://github.com/kubasanitrak
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: events-and-bookings
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('EAB_VERSION', '0.7.16');

/** Save ACF field group exports into plugin `acf-json/` (set false to disable). */
if (!defined('EAB_ACF_SAVE_JSON')) {
    define('EAB_ACF_SAVE_JSON', true);
}
define('EAB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('EAB_PLUGIN_URL', plugin_dir_url(__FILE__));
define('EAB_PLUGIN_BASENAME', plugin_basename(__FILE__));

$eab_autoload = EAB_PLUGIN_DIR . 'vendor/autoload.php';
if (is_readable($eab_autoload)) {
    require_once $eab_autoload;
}

/**
 * GitHub release updates (Plugin Update Checker).
 */
require_once EAB_PLUGIN_DIR . 'lib/plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$eab_update_checker = PucFactory::buildUpdateChecker(
    'https://github.com/kubasanitrak/events-and-bookings/',
    __FILE__,
    'events-and-bookings'
);
$eab_update_checker->getVcsApi()->enableReleaseAssets('/events-and-bookings\.zip($|[?&#])/i');

/**
 * Activation / deactivation.
 */
function eab_activate() {
    require_once EAB_PLUGIN_DIR . 'includes/class-eab-activator.php';
    EAB_Activator::activate();
}
register_activation_hook(__FILE__, 'eab_activate');

function eab_deactivate() {
    require_once EAB_PLUGIN_DIR . 'includes/class-eab-deactivator.php';
    EAB_Deactivator::deactivate();
}
register_deactivation_hook(__FILE__, 'eab_deactivate');

require_once EAB_PLUGIN_DIR . 'includes/class-eab-loader.php';

/**
 * Initialize the plugin.
 */
function eab_init() {
    $loader = new EAB_Loader();
    $loader->run();
}
add_action('plugins_loaded', 'eab_init');

/**
 * Load translations.
 */
function eab_load_textdomain() {
    load_plugin_textdomain(
        'events-and-bookings',
        false,
        dirname(EAB_PLUGIN_BASENAME) . '/languages'
    );
}
add_action('init', 'eab_load_textdomain');
