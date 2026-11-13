# PTAPS - Post Type Archive Pages and Permalink Settings

PTAPS - Post Type Archive Pages and Permalink Settings associates existing pages with custom post type archives and lets you set URL bases for custom post types and taxonomies through the native WordPress settings screens.

- **Settings > Reading:** select an existing page for each public custom post type's archive.
- **Settings > Permalinks:** set single-post and taxonomy URL bases. A selected archive page's path supplies the default post type base unless you enter an override.

## Requirements

- WordPress 6.5 or later.
- PHP 8.0 or later.
- Public custom post types or taxonomies registered by your theme or another plugin.
- Working WordPress pretty permalinks for the archive and slug settings.

Composer is not required to run the plugin. Yoast SEO, WPML and Timber integrations are available when those plugins or libraries are present; they are not required for basic use.

## Installation

1. Clone this repository into `wp-content/plugins/post-type-archive-pages-and-permalink-settings/`.
2. Activate **PTAPS - Post Type Archive Pages and Permalink Settings**.
3. Create the pages you want to associate with your archives, then assign them under **Settings > Reading > Archive Pages** and save.
4. If needed, set custom post type or taxonomy bases under **Settings > Permalinks** and save.
5. Check archive, single-post and taxonomy links. If rules remain stale, save **Settings > Permalinks** once more to rebuild them.

## Limitations

The plugin does not create archive pages or register post types and taxonomies. It provides settings for public, non-built-in types and taxonomies. Your theme still controls archive rendering; assigning a page does not replace the archive template with the page template. Assigning a page enables its archive path even if no archive was enabled beforehand. Publish assigned pages; their passwords and visibility do not protect public archives. Permalink fields accept bases, not full URLs or templates such as `%year%`.

Test URL changes on staging and review existing links and redirects before changing a public site. Changing a page's slug or parent can also change the associated archive path.

With WPML, assign archive pages in the default language and provide their translations. Archive assignments are shared, not selected independently per language. Missing archive-page translations can produce a 404; the saved assignment is retained so it can be translated or changed later.

When viewing an available page or archive, language switchers follow WPML's availability rules: omit unavailable languages with **Skip language**, or link to the target-language homepage with **Link to home**. Caller-specific `skip_missing` and `link_empty_to` arguments remain supported. Unlike earlier plugin versions, missing single-post translations no longer fall back to a translated archive. Homepage and custom switcher fallbacks do not become head hreflang alternates.

If the current frontend request returns a 404, including a directly requested unavailable archive translation, the response remains a WordPress 404. The switcher follows WPML's native 404 behavior, offering language-homepage links rather than presenting an archive as a translation of the missing page. This also applies with Skip language; the per-language Skip/Home/custom policy above concerns available pages and archives. The theme or widget controls switcher visibility.

WPML's **Translatable — use translation if available or fallback to default language** setting can make default-language content available at another language's URL. In that mode, a switcher item can remain even with **Skip language**, because WPML provides a usable content URL. This plugin neither enables that setting nor automatically translates content. A missing assigned archive-page translation still follows the unavailable-archive/404 policy above.

PTAPS adapts assigned archive pages to WPML's native language-switcher policies. Additional site-specific filtering, fallback destinations and visibility rules belong in the site's theme or a site-specific plugin. They may override native behavior; PTAPS does not reproduce or compensate for them. Review existing switcher customizations when updating PTAPS or WPML rather than assuming their behavior will stay unchanged.

## Configuration in code

Retrieve the page associated with a custom post type archive through the post type's archive path:

```php
/**
 * Retrieves the post object for a given post type's archive page.
 *
 * @param string|null $postType The post type to retrieve the archive page for.
 * @return WP_Post|null WP_Post on success, or null on failure.
 */
function getCustomPostTypeArchivePage(?string $postType = null): ?\WP_Post
{
    $postType = $postType ?? getCurrentQueryPostType();

    if ($postType === null) {
        return null;
    }

    $postTypeObject = get_post_type_object($postType);

    if (!$postTypeObject || !$postTypeObject->has_archive) {
        return null;
    }

    $archiveSlug = $postTypeObject->has_archive;

    if (!is_string($archiveSlug)) {
        return null;
    }

    $archivePage = get_page_by_path($archiveSlug);

    return ($archivePage instanceof \WP_Post) ? $archivePage : null;
}

/**
 * Retrieves the current post type from the global $wp_query object.
 *
 * @return string|null The current post type, or null if not found.
 */
function getCurrentQueryPostType(): ?string
{
    global $wp_query;

    if (!$wp_query instanceof \WP_Query) {
        return null;
    }

    $postType = $wp_query->get('post_type');
    return is_string($postType) && $postType !== '' ? $postType : null;
}

$archivePage = getCustomPostTypeArchivePage('your_custom_post_type');
if ($archivePage) {
    echo "Archive page title: " . esc_html($archivePage->post_title);
    echo "Archive page ID: " . (int) $archivePage->ID;
    echo "Archive page URL: " . esc_url(get_permalink($archivePage->ID));
} else {
    echo "No archive page found for this post type.";
}
```

## Development

Use PHP 8.3 for development and CI. The released plugin still supports PHP 8.0; Composer development dependencies are not included in the release.

```sh
composer install
composer check-platform-reqs
composer validate --strict
composer php:lint
```

Keep plugin code compatible with PHP 8.0 when using newer development tools.

## License

GPLv3 or later. See [LICENSE](LICENSE).
