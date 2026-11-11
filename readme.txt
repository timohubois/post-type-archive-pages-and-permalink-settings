=== PTAPS - Post Type Archive Pages and Permalink Settings ===
Contributors: timohubois
Tags: custom post types, custom taxonomy, archives, permalink
Requires at least: 6.5
Tested up to: 7.1
Stable tag: 2.2.8
Requires PHP: 8.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Choose archive pages and manage URL bases for custom post types and taxonomies.

== Description ==

Select an existing page as the archive reference for a custom post type, and change URL bases for individual entries or custom taxonomies.

The plugin extends WordPress's **Reading** and **Permalinks** settings:

* Settings > **Reading** > Choose an existing page for each custom post type archive. Its path becomes the archive address and default entry base.
* Settings > **Permalinks** > Override entry bases independently of archive addresses, or set custom taxonomy bases.

Your theme or another plugin must register the content types. Your theme still renders the archive; PTAPS does not copy the selected page's body or layout. Check existing links and redirects when changing URLs.

== Key Features ==

* Custom archive page selection for post types to use any regular page as archive page
* Flexible permalink customization of custom post types or custom taxonomies
* Control archive and entry addresses separately
* Easy integration with existing WordPress settings
* Integrations for Yoast SEO, WPML and Timber

== Want to contribute? ==
Check out the Plugin [GitHub Repository](https://github.com/timohubois/post-type-archive-pages-and-permalink-settings/).

== Installation ==

= INSTALL WITHIN WORDPRESS =
(recommended)

1. Open **Plugins > Add new**
2. Search for **PTAPS - Post Type Archive Pages and Permalink Settings**
3. Click **install and activate** the plugin

= INSTALL MANUALLY THROUGH FTP =

1. Download the plugin on the WordPress plugin page
2. Upload the 'post-type-archive-pages-and-permalink-settings' folder to the /wp-content/plugins/ directory
3. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= How to programmatically get the post used as the archive page for a custom post type? =

An example can be found in the [GitHub Repository](https://github.com/timohubois/post-type-archive-pages-and-permalink-settings/).

= Is this plugin compatible with WPML? =
The assignment uses the default-language page and its translations, rather than a separate selection per language. Publish the assigned page in every language that needs an archive; missing translations can cause a 404.

= How do I set a custom archive page for a post type? =
Go to Settings > Reading in your WordPress admin. You'll find a new section where you can select a specific page to act as the archive for each of your custom post types.

= Can I change the URL structure for my custom post types? =
Yes. Navigate to Settings > Permalinks in your admin panel. You'll see a new section where you can modify the slug for each custom post type.

= What about custom taxonomies - can I change their URLs too? =
Absolutely. In the same Permalinks settings page, you'll find options to change the URL structure for your custom taxonomies.

= Will this plugin automatically create new pages for my archives? =
No. Create and publish the pages first, then assign them under Settings > Reading. Assignments are not access controls; a page's password or visibility does not protect a public archive.

= Does this work for all custom post types on my site? =
The settings show public, non-built-in custom post types registered by your theme or another plugin. Assigning a page sets the archive path, even if an archive was not enabled beforehand.

= Is it possible to have different permalink structures for different post types? =
Yes. Set a base slug for each custom post type or taxonomy independently. These fields accept bases, not full URLs or permalink templates such as %year%.

= Why aren't my permalink changes taking effect? =
If you notice that your permalink changes aren't reflecting on your site, try the following steps:

1. Go to Settings > Permalinks in your WordPress admin panel.
2. Without making any changes, click the "Save Changes" button at the bottom of the page.

This action flushes the rewrite rules and often resolves issues with permalinks not updating. If the problem persists, try deactivating and reactivating the plugin, then save your permalinks again.

= Do I need to update permalinks after changing archive pages or custom post type/taxonomy slugs? =
Usually not. If URLs return 404 after a change, resave your permalink settings:

1. Go to Settings > Permalinks
2. Scroll to the bottom and click "Save Changes"

This ensures that WordPress regenerates its rewrite rules with your new settings.

== Screenshots ==

1. Choose existing pages for custom post type archives under Settings > Reading.
2. Set bases for custom post type entries and custom taxonomy terms under Settings > Permalinks.

== Changelog ==
= 2.2.8 =
* Show and preserve assigned archive pages when their WPML translation is missing.
* Improve settings sanitization, request handling, and translation compatibility.

= 2.2.7 =
* Raise the minimum supported WordPress version to 6.5.

= 2.2.6 =
* Fix duplicate initialization of plugin features and WordPress hooks.
* Test compatibility with WordPress 7.1.

= 2.2.5 =
* fix PHP deprecation when setting permalinks for post types or taxonomies with disabled rewrites

= 2.2.4 =
* fix Yoast SEO canonical compatibility when no canonical URL is provided

= 2.2.3 =
* fix canonical URL trailing slash for paginated archive pages in Yoast SEO compatibility

= 2.2.2 =
* avoid 404 redirect on search results in WPML compatibility

= 2.2.1 =
* fix trim deprecation warnings in Yoast SEO compatibility

= 2.2.0 =
* enhance WordPress archive title compatibility with filters

= 2.1.0 =
* improve WordPress archive title compatibility

= 2.0.0 =
* improve Yoast SEO compatibility

= 1.9.0.1 =
* don't create critical error on handle next and previous rel links (Yoast SEO compatibility)

= 1.9.0 =
* handle next and previous rel links (Yoast SEO compatibility)

= 1.8.7 =
* improve post type archive handling when using wpml

= 1.8.6 =
* handle empty postId in post type archive check

= 1.8.5 =
* get correct link of translated pages when using wpml

= 1.8.4 =
* get correct link of archive pages when using wpml

= 1.8.3 =
* return wpml links with trailing slash
* remove slashes before saving permalink options

= 1.8.2 =
* fix use is_plugin_active function

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
