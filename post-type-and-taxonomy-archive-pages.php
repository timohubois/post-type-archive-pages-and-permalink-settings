<?php

/**
 * Plugin Name:       Post Type Archive Pages and Permalink Settings
 * Plugin URI:        https://github.com/timohubois/post-type-and-taxonomy-archive-pages/
 * Description:       Set the archive for your custom post types to display it on a specific page and control the permalinks of custom post type single pages and custom taxonomies.
 * Version:           0.1
 * Requires at least: 5.0
 * Requires PHP:      8.0
 * Author:            Timo Hubois
 * Author URI:        https://pixelsaft.wtf
 * Text Domain:       ptapaps
 * Domain Path:       /languages
 * License:           GPLv3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Ptapas;

defined('ABSPATH') || exit;

if (!defined('PTAPAPS_PLUGIN_FILE')) {
    define('PTAPAPS_PLUGIN_FILE', __FILE__);
}

if (file_exists(plugin_dir_path(APAPS_PLUGIN_FILE) . 'vendor/autoload.php')) {
    require plugin_dir_path(APAPS_PLUGIN_FILE) . 'vendor/autoload.php';
}

if (!defined('APAPS_TEXT_DOMAIN')) {
    define('APAPS_TEXT_DOMAIN', get_file_data(PTAPAPS_PLUGIN_FILE, [ 'TextDomain' => 'Text Domain'], 'plugin')['TextDomain']);
}

load_plugin_textdomain(PTAPAPS_PLUGIN_FILE);

register_activation_hook(PTAPAPS_PLUGIN_FILE, [Plugin::class, 'onPluginActivation']);
register_deactivation_hook(PTAPAPS_PLUGIN_FILE, [Plugin::class, 'onPluginDeactivation']);

Plugin::init();
