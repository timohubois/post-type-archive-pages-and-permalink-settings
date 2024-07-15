===  PTAPS - Post Type Archive Pages and Permalink Settings  ===
Contributors: timohubois
Tags: custom post types, custom taxonomy, archives, permalink
Requires at least: 6.0
Tested up to: 6.5
Stable tag: 1.8.1
Requires PHP: 8.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Use archive pages for custom post types and improve WordPress SEO by managing permalinks for custom post types and taxonomies.

== Description ==

Select a regular page that should interact as archive for custom post types and allows to change the slug for custom post type single pages or custom taxonomies.

The Plugin integrates seamlessly with **Reading** and **Permalinks** settings:

* Settings > **Reading** > Choose a page to as the archive for each custom post type.
* Settings > **Permalinks** > Easily modify slugs for custom post types and taxonomies, where by default the selected archive page from **Reading** settings is used as base slug.

Perfect for developers and site owners looking to optimize their WordPress site structure and improve SEO.

== Key Features ==

* Custom archive page selection for post types to use any regular page as archive page
* Flexible permalink customization of custom post types or custom taxonomies
* More SEO-friendly URL structures
* Easy integration with existing WordPress settings
* Compatible with YOAST, WPML and Timber

== Want to contribute? ==
Check out the Plugin [GitHub Repository](https://github.com/timohubois/post-type-archive-pages-and-permalink-settings/).

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

== Frequently Asked Questions ==

= How to programmatically get the post used as the archive page for a custom post type? =

An example can be found in the [GitHub Repository](https://github.com/timohubois/post-type-archive-pages-and-permalink-settings/).

= Is this plugin compatible with WPML? =
The plugin works with WPML in the same as WPML interacts with WordPress. The archive page should be available in all languages (otherwise you get an 404) and it is not possible to select a archive page per language.

= How do I set a custom archive page for a post type? =
Go to Settings > Reading in your WordPress admin. You'll find a new section where you can select a specific page to act as the archive for each of your custom post types.

= Can I change the URL structure for my custom post types? =
Yes. Navigate to Settings > Permalinks in your admin panel. You'll see a new section where you can modify the slug for each custom post type.

= What about custom taxonomies - can I change their URLs too? =
Absolutely. In the same Permalinks settings page, you'll find options to change the URL structure for your custom taxonomies.

= Will this plugin automatically create new pages for my archives? =
No, the plugin doesn't create new pages. It allows you to designate existing pages as archives for your custom post types. You need to create these pages yourself.

= Does this work for all custom post types on my site? =
The plugin supports all registered custom post types, including those created by themes or other plugins. However, it won't show options for post types that are not public or don't have archives enabled.

= Is it possible to have different permalink structures for different post types? =
Yes, the plugin allows you to set unique permalink structures for each custom post type and taxonomy independently.

= Why aren't my permalink changes taking effect? =
If you notice that your permalink changes aren't reflecting on your site, try the following steps:

1. Go to Settings > Permalinks in your WordPress admin panel.
2. Without making any changes, click the "Save Changes" button at the bottom of the page.

This action flushes the rewrite rules and often resolves issues with permalinks not updating. If the problem persists, try deactivating and reactivating the plugin, then save your permalinks again.

= Do I need to update permalinks after changing archive pages or custom post type/taxonomy slugs? =
Regular not but if you have any trouble it's a good practice to resave your permalinks after making changes to archive pages or modifying slugs. To do this:

1. Go to Settings > Permalinks
2. Scroll to the bottom and click "Save Changes"

This ensures that WordPress regenerates its rewrite rules with your new settings.

== Changelog ==
= 1.8.1 =
* use is_plugin_active function

= 1.8 =
* add notification for custom archive pages to block editor
* really store reading options always in default language

= 1.7 =
* improve post_type_link archive URI handling when using wpml and yoast
* store reading options always in default language

= 1.6 =
* Improved compatibility with wpml

= 1.5 =
* Flush rewrite rules when post_parent changes
* Renamed plugin main file
**Notice:** The plugin may gets deactivated after the update, due a file rename. Just activate it again and resave Permalinks and Reading settings.

= 1.4 =
* Display a 404 page when archive page does not exists in combination with wpml

= 1.3 =
* Do not add a rewrite rule for the post type if the archive slug is equal to the post type slug
* Sort options

= 1.2 =
* Show post state label if optionsPermalinksPostTypes are not set

= 1.1 =
* Fixed an issue at activation in combination with wpml

= 1.0 =
* Initial Release
