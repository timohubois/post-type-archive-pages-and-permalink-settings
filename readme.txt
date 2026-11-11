=== PTAPS - Post Type Archive Pages and Permalink Settings ===
Contributors: timohubois
Tags: custom post types, custom taxonomy, archives, permalink
Requires at least: 6.5
Tested up to: 7.1
Stable tag: 2.2.8
Requires PHP: 8.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Choose archive pages for custom post types and manage URL bases for custom post types and taxonomies in native WordPress settings.

== Description ==

Want your Projects archive to live at the address of an existing page, with individual projects underneath it? PTAPS - Post Type Archive Pages and Permalink Settings connects your custom post types to regular WordPress pages and gives you control over their URL bases.

Choose a page under **Settings > Reading**. Its path becomes the post type's archive address and the default base for individual entries. For example, a page at /projects/ can provide the archive address, with individual entries at /projects/example-project/ on a site using post-name permalinks without an additional front prefix.

Need a different base for individual entries or custom taxonomy terms? Set it under **Settings > Permalinks**. There is no separate settings dashboard to learn.

**Features**

* Assign an existing page to each public custom post type, including a page nested under a parent.
* Use the selected page's title for the post type archive through WordPress's archive-title filters.
* Override the URL base for individual custom post type entries independently of the archive address.
* Set URL bases for public custom taxonomies.
* Add archive labels in the Pages list and update classic navigation-menu states for assigned pages.
* Include targeted integrations for Yoast SEO, WPML and Timber. See the FAQs for their behavior and limits.

**Before you start**

Your theme or another plugin must register the custom post types and taxonomies. PTAPS does not create content types, pages or archive layouts. The archive remains a post type archive rendered by your theme, not a normal page displaying its editor content automatically.

URL changes can affect existing links. Try them on a staging site first, choose distinct bases and plan redirects for old URLs. Assign only pages intended for public archives; selecting a page is not an access-control mechanism.

== Installation ==

1. Ensure your site meets the WordPress and PHP requirements above and that your theme or another plugin registers public custom post types or taxonomies.
2. In **Plugins > Add New**, search for **PTAPS - Post Type Archive Pages and Permalink Settings**, then install and activate it. Alternatively, upload the extracted plugin to /wp-content/plugins/post-type-archive-pages-and-permalink-settings/ and activate it. Composer is not required to run the plugin.
3. Create and publish the pages you want to use as archive references. Choose a non-Plain permalink structure under **Settings > Permalinks** for path-based URLs.
4. Open **Settings > Reading**, select a page for each desired custom post type under **Archive Pages**, and save changes.
5. Optionally open **Settings > Permalinks** and enter bases under **Custom Post Types** or **Custom Taxonomies**. Save changes. Leave a base blank to use the selected archive page's path for a post type, or the registered default when no archive page is selected; a blank taxonomy base uses its registered default.
6. Check the archive, individual entries, taxonomy terms and pagination. On multilingual sites, check each language too. Add redirects for any old URLs you need to preserve.

Yoast SEO, WPML and Timber are optional, not required dependencies. Your theme still needs an appropriate archive template.

== Frequently Asked Questions ==

= Which post types and taxonomies appear in the settings? =

The settings list public, non-built-in post types and taxonomies registered on your site. WordPress's built-in posts, pages, categories and tags are not managed by these fields.

A custom post type does not need an archive enabled beforehand: assigning a page sets its archive path. Saved settings can also enable URL rewrites where the original registration disabled them. If your theme or content-type plugin manages the same URLs, check for conflicting settings.

= Does the selected page become a normal page with a list of posts? =

No. The page provides the archive path and title, while WordPress continues to serve a post type archive using your theme's archive template and query. The plugin does not insert a post list, copy page blocks into the archive or apply the selected page's page template automatically.

To display the page's content or custom fields on the archive, your theme must retrieve and render them. Developers can find a page-retrieval example in the [GitHub README](https://github.com/timohubois/post-type-archive-pages-and-permalink-settings/#how-to-programmatically-get-the-post-used-as-the-archive-page-for-a-custom-post-type).

= Can I use different bases for archives, individual entries and taxonomy terms? =

Yes. The archive address follows the selected page's path. A non-empty **Custom Post Types** base overrides the base for individual entries without changing that archive address. **Custom Taxonomies** bases apply to term archive URLs; there is no page selector for taxonomy archives.

Enter a base such as projects or project-topics, not a full URL. These are base-slug settings, not a general permalink-template builder: substitution tags such as %year% or %category% in these fields are not supported. The final URL also depends on the content type's registered rewrite options and WordPress's permalink settings.

= Will old URLs redirect automatically? =

Do not rely on that. The plugin has limited 404-to-archive handling for configured post type bases, but it does not provide a general redirect map for renamed archives, individual entries or taxonomy terms. Set up and verify redirects separately. Changing a base alone does not guarantee better search rankings.

= How does the Yoast SEO integration work? =

When Yoast SEO is active, the plugin adjusts canonical and pagination links for configured post type archives and related taxonomy archives. An editor notice explains that the assigned page's own Yoast settings do not control the post type archive. Configure archive metadata in Yoast SEO's content-type archive settings instead; exact menu labels depend on your Yoast version.

= How does the WPML integration work? =

The archive assignment is stored against the default-language page and uses its translations, rather than a separately selected archive page per language. Publish the assigned page in every language that needs an archive; a missing translation can cause a 404. The Reading dropdown preserves an existing assignment and marks it when its translation is missing.

The integration also adjusts translated archive and entry links, language-switcher URLs and hreflang links, and registers configured URL-base strings with WPML. Use WPML String Translation where translated bases or Yoast metadata need translation. Check all configured languages after URL changes; this is not a blanket compatibility guarantee for every WPML setup.

= How does the Timber integration work? =

Through the timber/context filter, the plugin sets the context's post value to the assigned archive page when it resolves an associated post type. For taxonomy contexts it uses the taxonomy's first associated post type. Your Twig template must explicitly render the page fields or content you want to show and retain the archive's post-list query separately. The integration does not supply templates.

= What should I check if a URL returns 404? =

Save **Settings > Permalinks** once to regenerate WordPress's rewrite rules. The plugin also schedules a rewrite refresh when an archive assignment or an assigned page's slug, parent or status changes.

If the issue persists, confirm that the content type is registered and public, the assigned page is published, and the base does not collide with another page, post type or taxonomy. Check your server's WordPress rewrite configuration and any conflicting permalink plugins. With WPML, confirm that the archive page's translation exists in the requested language.

= Does an archive assignment restrict access to content? =

No. Do not use an assigned page's password or visibility as protection for a public post type archive. Use appropriate WordPress permissions or a dedicated content-restriction solution, and verify access on the archive and individual entries separately.

= What happens when I deactivate or delete the plugin? =

Deactivation stops applying the archive and URL overrides and refreshes rewrite rules, but keeps the saved settings. Deleting the plugin removes its saved archive assignments and permalink settings, not your pages or custom posts. The registering theme or plugin then controls the original URLs again; review links and redirects before removing PTAPS.

== Screenshots ==

1. Choose existing pages for custom post type archives under Settings > Reading.
2. Set bases for custom post type entries and custom taxonomy terms under Settings > Permalinks.

== Want to contribute? ==

Visit the [GitHub repository](https://github.com/timohubois/post-type-archive-pages-and-permalink-settings/) to report issues, view developer examples or contribute.

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
