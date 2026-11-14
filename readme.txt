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

PTAPS associates existing pages with custom post type archives and lets you customize URL bases through the native WordPress settings screens.

* **Settings > Reading:** select an existing page for each public custom post type's archive.
* **Settings > Permalinks:** set single-post and taxonomy URL bases. A selected archive page's path supplies the default post type base unless you enter an override.

The selected page supplies the archive path and title. Your theme still controls archive rendering; assigning a page does not replace the archive template with the page template.

Yoast SEO, WPML and Timber integrations are included when those plugins or libraries are present.

**Before you start**

The plugin does not create pages or register post types and taxonomies. It provides settings for public, non-built-in types and taxonomies registered by your theme or other plugins.

Create the pages you want to assign first. Test URL changes on staging and review existing links and redirects before changing a public site. Changing a selected page's slug or parent can also change the associated archive path.

== Installation ==

Requires WordPress 6.5 or later and PHP 8.3 or later. Composer, Yoast SEO, WPML and Timber are not required for basic use. Your server must support your chosen WordPress permalink structure.

1. Install **PTAPS - Post Type Archive Pages and Permalink Settings** through **Plugins > Add New**, or upload the plugin folder to /wp-content/plugins/post-type-archive-pages-and-permalink-settings/.
2. Activate the plugin.
3. Create and publish the pages you want to associate with your archives, then assign them under **Settings > Reading > Archive Pages** and save.
4. If needed, set custom post type or taxonomy bases under **Settings > Permalinks** and save.
5. Check archive, single-post and taxonomy links. If rules remain stale, save **Settings > Permalinks** once more to rebuild them.

== Frequently Asked Questions ==

= How do I set a custom archive page for a post type? =

Create and publish the page, then open **Settings > Reading > Archive Pages** and select it for the relevant post type.

The selected page supplies the archive path and title. Your theme controls how the archive and any page content are displayed.

= Can I change post type and taxonomy URLs independently? =

Yes. Under **Settings > Permalinks**, enter the base you want for each custom post type or taxonomy.

A post type's single-post base defaults to its selected archive page's path unless overridden. Taxonomy settings change term URL bases, not individual term slugs.

These fields accept bases, not full URLs or permalink templates such as `%year%`.

= Does this work for every post type and taxonomy? =

The settings include public, non-built-in post types and taxonomies. They do not include private types or WordPress's built-in posts, pages, categories and tags.

The plugin does not register missing types or taxonomies. Assigning a page sets the archive path, even if an archive was not enabled beforehand.

= Is this plugin compatible with WPML? =

Assign archive pages in the default language and provide their translations. Assignments are shared, not selected independently per language. Missing archive-page translations can produce a 404; the saved assignment is retained.

On available pages and archives, language switchers follow WPML's Skip language or Link to home policy. Caller-specific `skip_missing` and `link_empty_to` arguments remain supported.

On a 404 request, including a directly requested unavailable archive translation, the response remains a WordPress 404. WPML's native 404 navigation can offer language-homepage links even with Skip language. The theme or widget controls switcher visibility.

WPML's “use translation if available or fallback to default language” setting can make default-language content available at another language's URL. A switcher item can therefore remain with Skip language. This does not make a missing assigned archive-page translation available.

Missing single-post translations no longer fall back to a translated archive. Homepage and custom switcher fallbacks do not become head hreflang alternates.

Site-specific switcher filters can override native behavior. Review and test those customizations when updating PTAPS or WPML; PTAPS does not reproduce or compensate for them.

= Will the plugin create archive pages or replace my templates? =

No. It associates existing pages with archives and changes their paths and titles. Your theme still needs to render the archive.

The Timber integration exposes the assigned page as `post` in the template context when applicable.

Assignments are not access controls: a page's password or visibility does not protect a public archive.

= Why aren't my permalink changes taking effect? =

Save **Settings > Permalinks** once more to rebuild rewrite rules, then clear relevant caches and check the links again.

The plugin schedules rule updates when relevant settings or selected page paths change, so manually saving permalinks is normally only a troubleshooting step.

Check for conflicting page paths, post type or taxonomy bases, other routing plugins and server rules. Make sure pretty permalinks already work on your site.

= Can I customize archive-page assignments with a filter? =

Yes. The `ptatap_post_type_reading_settings` filter receives the current assignments as an array or boolean. Assignment arrays map post type names to page IDs. Return the modified array, or the original value when no change is needed.

For example, replace `book` and `123` with your registered post type and published archive page ID:

```php
add_filter('ptatap_post_type_reading_settings', static function ($assignments) {
    $assignments = is_array($assignments) ? $assignments : [];
    $assignments['book'] = 123;
    return $assignments;
}, 5);
```

The callback preserves other assignments and can run repeatedly without changing its result. Register it before your post types are registered, not during template rendering.

With WPML, use default-language page IDs for this example. Priority 5 runs before PTAPS's WPML assignment translation at priority 10. A later callback may receive translated IDs and must account for the current language.

The filter changes the assignments used by the plugin; it does not save them to the database. After changing code-controlled assignments, save **Settings > Permalinks** to rebuild rewrite rules and test the affected archives and languages. Do not flush rewrite rules on every request.

= How can I retrieve the assigned archive page in code? =

The post type's archive path can be used to look up the assigned page. See the [configuration example](https://github.com/timohubois/post-type-archive-pages-and-permalink-settings/#configuration-in-code), which handles missing assignments and non-string archive settings.

== Screenshots ==

1. Choose existing pages for custom post type archives under Settings > Reading.
2. Set bases for custom post type entries and custom taxonomy terms under Settings > Permalinks.

== Want to contribute? ==

Report issues or contribute on [GitHub](https://github.com/timohubois/post-type-archive-pages-and-permalink-settings/).

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

See [earlier releases](https://github.com/timohubois/post-type-archive-pages-and-permalink-settings/releases) for older changes.

== Upgrade Notice ==

= 2.3.0 =
Requires PHP 8.3 or later. WPML switchers now use native fallback policies. Missing single-post translations no longer link to archives; 404s can show language-homepage links. Test site-specific switcher customizations on staging; removing them is not required.
