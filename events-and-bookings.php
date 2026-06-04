<?php
/**
 * Plugin Name: Events and Bookings
 * Plugin URI: https://github.com/kubasanitrak/events-and-bookings
 * Description: Events and bookings for WordPress.
 * Version: 0.1.0
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

define('EAB_VERSION', '0.1.0');
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
 * Initialize the plugin.
 */
function eab_init() {
    // Plugin features load here as the codebase grows.
}
add_action('plugins_loaded', 'eab_init');
