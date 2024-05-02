=== Post Type and Taxonomy Archive Pages ===
Contributors: timohubois
Tags: Tags: custom post types, custom taxonomy, archives, permalink
Requires at least: 6.0
Tested up to: 6.5
Stable tag: 1.3
Requires PHP: 8.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Set the archive for your custom post types to display it on a specific page and control the permalinks of custom post type single pages and custom taxonomies.

== Description ==

Post Type and Taxonomy Archive Pages enables to select a page that should interact as archive for custom post types. It also enables to change the slug for custom post type single pages or custom taxonomies.

The Plugin extends the native **Reading** and **Permalinks** settings pages:

* Settings > **Reading** > Adds a section to select a page which should interact as archive for a custom post type.
* Settings > **Permalinks** > Adds a section to change the slug for custom post types and custom taxonomies.

== Want to contribute? ==
Check out the Plugin [GitHub Repository](https://github.com/timohubois/archive-pages-and-permalink-settings-for-post-types-and-taxonomies/).

== Installation ==

= INSTALL WITHIN WORDPRESS =
(recommended)

1. Open **Plugins > Add new**
2. Search for **Post Type and Taxonomy Archive Pages**
3. Click **install and activate** the plugin

= INSTALL MANUALLY THROUGH FTP =

1. Download the plugin on the WordPress plugin page
2. Upload the ‘archive-pages-and-permalink-settings-for-post-types-and-taxonomies’ folder to the /wp-content/plugins/ directory
3. Activate the plugin through the ‘Plugins’ menu in WordPress

== Changelog ==
= 1.3 =
* Do not add a rewrite rule for the post type if the archive slug is equal to the post type slug
* Sort options

= 1.2 =
* Show post state label if optionsPermalinksPostTypes are not set

= 1.1 =
* Fixed an issue at activation in combination with wpml

= 1.0 =
* Initial Release
