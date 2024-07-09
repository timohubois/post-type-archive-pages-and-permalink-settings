<?php

/**
 * Plugin Name:       PTAPS - Post Type Archive Pages and Permalink Settings
 * Plugin URI:        https://github.com/timohubois/post-type-archive-pages-and-permalink-settings/
 * Description:       Set the archive for your custom post types to display it on a specific page and control the permalinks of custom post type single pages and custom taxonomies.
 * Version:           1.5
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Timo Hubois
 * Author URI:        https://pixelsaft.wtf
 * Text Domain:       post-type-archive-pages-and-permalink-settings
 * Domain Path:       /languages
 * License:           GPLv3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 */

 /**
 * Ptatap: Acronym for Post Type Archive and Permalink Settings
 */
namespace Ptatap;

defined('ABSPATH') || exit;

if (!defined('PTATAP_PLUGIN_FILE')) {
    define('PTATAP_PLUGIN_FILE', __FILE__);
}

// Autoloader via Composer if available.
if (file_exists(plugin_dir_path(PTATAP_PLUGIN_FILE) . 'vendor/autoload.php')) {
    require plugin_dir_path(PTATAP_PLUGIN_FILE) . 'vendor/autoload.php';
}

// Custom autoloader if Composer is not available.
if (!file_exists(plugin_dir_path(PTATAP_PLUGIN_FILE) . 'vendor/autoload.php')) {
    spl_autoload_register(static function ($className): void {
        $prefix = 'Ptatap\\';
        $baseDir = plugin_dir_path(PTATAP_PLUGIN_FILE) . 'classes/';
        $length = strlen($prefix);
        if (strncmp($prefix, $className, $length) !== 0) {
            return;
        }

        $relativeClass = substr($className, $length);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    });
}

load_plugin_textdomain(PTATAP_PLUGIN_FILE);

register_activation_hook(PTATAP_PLUGIN_FILE, [Plugin::class, 'onPluginActivation']);
register_deactivation_hook(PTATAP_PLUGIN_FILE, [Plugin::class, 'onPluginDeactivation']);

Plugin::init();
