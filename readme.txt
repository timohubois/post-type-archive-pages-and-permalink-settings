=== PTAPS - Post Type Archive Pages and Permalink Settings ===
Contributors: timohubois
Tags: custom post types, custom taxonomy, archives, permalink
Requires at least: 6.5
Tested up to: 7.1
Stable tag: 2.3.1
Requires PHP: 8.3
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Associate existing pages with custom post type archives and customize URL bases for custom post types and taxonomies.

== Description ==

PTAPS - Post Type Archive Pages and Permalink Settings associates existing pages with custom post type archives and lets you set URL bases for custom post types and taxonomies through the native WordPress settings screens.

* **Settings > Reading:** select an existing page for each public custom post type's archive.
* **Settings > Permalinks:** set single-post and taxonomy URL bases. A selected archive page's path supplies the default post type base unless you enter an override.

The selected page supplies the archive path and title. Your theme still controls archive rendering; assigning a page does not replace the archive template with the page template. Yoast SEO, WPML and Timber integrations are included when those plugins or libraries are present.

**Before you start**

The plugin does not create pages or register post types and taxonomies. It provides settings for public, non-built-in types and taxonomies registered by your theme or other plugins. Create the pages you want to assign first.

Test URL changes on staging and review existing links and redirects before changing a public site. Changing a selected page's slug or parent can also change the associated archive path.

== Installation ==

Requires WordPress 6.5 or later and PHP 8.3 or later. Composer, Yoast SEO, WPML and Timber are not required for basic use. Your server must support your chosen WordPress permalink structure.

1. Install **PTAPS - Post Type Archive Pages and Permalink Settings** through **Plugins > Add New**, or upload the plugin folder to /wp-content/plugins/post-type-archive-pages-and-permalink-settings/.
2. Activate the plugin.
3. Create the pages you want to associate with your archives, then assign them under **Settings > Reading > Archive Pages** and save.
4. If needed, set custom post type or taxonomy bases under **Settings > Permalinks** and save.
5. Check archive, single-post and taxonomy links. If rules remain stale, save **Settings > Permalinks** once more to rebuild them.

== Frequently Asked Questions ==

= How do I set a custom archive page for a post type? =

Create the page first, then open **Settings > Reading > Archive Pages** and select it for the relevant post type. The selected page supplies the archive path and title; your theme controls how the archive and any page content are displayed.

= Can I change post type and taxonomy URLs independently? =

Yes. Under **Settings > Permalinks**, enter the base you want for each custom post type or taxonomy. A post type's single-post base defaults to its selected archive page's path unless overridden. Taxonomy settings change term URL bases, not individual term slugs. These fields accept bases, not full URLs or permalink templates such as %year%.

= Does this work for every post type and taxonomy? =

The settings include public, non-built-in post types and taxonomies. They do not include private types or WordPress's built-in posts, pages, categories and tags. The plugin does not register missing types or taxonomies for you. Assigning a page sets the archive path, even if an archive was not enabled beforehand.

= Is this plugin compatible with WPML? =

Assign archive pages in the default language and provide their translations. Archive assignments are shared, not selected independently per language. Missing archive-page translations can produce a 404; the saved assignment is retained so it can be translated or changed later.

When viewing an available page or archive, language switchers follow WPML's availability rules: omit unavailable languages with Skip language, or link to the target-language homepage with Link to home. Caller-specific skip_missing and link_empty_to arguments remain supported. Unlike earlier plugin versions, missing single-post translations no longer fall back to a translated archive. Homepage and custom switcher fallbacks do not become head hreflang alternates.

If the current frontend request returns a 404, including a directly requested unavailable archive translation, the response remains a WordPress 404. The switcher follows WPML's native 404 behavior, offering language-homepage links rather than presenting an archive as a translation of the missing page. This also applies with Skip language; the per-language Skip/Home/custom policy above concerns available pages and archives. The theme or widget controls switcher visibility.

WPML's "Translatable — use translation if available or fallback to default language" setting can make default-language content available at another language's URL. In that mode, a switcher item can remain even with Skip language, because WPML provides a usable content URL. This plugin neither enables that setting nor automatically translates content. A missing assigned archive-page translation still follows the unavailable-archive/404 policy above.

PTAPS adapts assigned archive pages to WPML's native language-switcher policies. Additional site-specific filtering, fallback destinations and visibility rules belong in the site's theme or a site-specific plugin. They may override native behavior; PTAPS does not reproduce or compensate for them. Review existing switcher customizations when updating PTAPS or WPML rather than assuming their behavior will stay unchanged.

= Will the plugin create archive pages or replace my templates? =

No. It associates existing pages with archives and changes their paths and titles. Your theme still needs to render the archive. The Timber integration exposes the assigned page as post in the template context when applicable. Create and publish the pages before assigning them. Assignments are not access controls; a page's password or visibility does not protect a public archive.

= Why aren't my permalink changes taking effect? =

Save **Settings > Permalinks** once more to rebuild rewrite rules, then clear relevant caches and check the links again. The plugin schedules rule updates when relevant settings or selected page paths change, so manually saving permalinks is normally only a troubleshooting step.

Check for conflicting page paths, post type or taxonomy bases, other routing plugins and server rules. Make sure pretty permalinks already work on your site.

= How can I retrieve the assigned archive page in code? =

See the [configuration example in the GitHub README](https://github.com/timohubois/post-type-archive-pages-and-permalink-settings/#configuration-in-code).

== Want to contribute? ==

Report issues or contribute through the [GitHub repository](https://github.com/timohubois/post-type-archive-pages-and-permalink-settings/). Include your WordPress and PHP versions, permalink settings and relevant post type or taxonomy registration when reporting a problem. For WPML reports, include the affected languages and whether the assigned page has a translation.

== Screenshots ==

1. Choose existing pages for custom post type archives under Settings > Reading.
2. Set bases for custom post type entries and custom taxonomy terms under Settings > Permalinks.

== Changelog ==
= 2.3.1 =
* Restore the archive Edit Page toolbar icon on WordPress 7.2 using the native icon API while preserving legacy WordPress rendering.

= 2.3.0 =
* Raise the minimum supported PHP version to 8.3.
* Use WPML's native language-switcher policies for assigned archives, including caller-specific fallbacks.
* Stop substituting translated archive links for missing single-post translations.
* Keep missing archive translations as real 404s with native language-homepage navigation and no fallback hreflang equivalents.
* Preserve native taxonomy and other non-archive language links, and fix assigned static-front archive links.
* Refresh archive rewrite rules after assigned pages or their ancestors change paths.
* Normalize WPML archive assignments through native translation APIs while retaining missing translations.
* Make permalink sanitization stable across repeated saves and correct the displayed with_front setting.
* Preserve explicit ports in archive SEO URLs and encode Yoast editor notices safely.
* Improve plugin documentation and PHP quality checks.

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

== Upgrade Notice ==

= 2.3.0 =
Requires PHP 8.3 or later. WPML switchers now use native fallback policies. Missing single-post translations no longer link to archives; 404s can show language-homepage links. Test site-specific switcher customizations on staging; removing them is not required.
