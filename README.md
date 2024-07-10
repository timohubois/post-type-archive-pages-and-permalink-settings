# Post Type and Taxonomy Archive Pages Settings

Post Type and Taxonomy Archive Pages Settings enables to select a page that should interact as archive for custom post types. It also enables to change the slug for custom post type single pages or custom taxonomies.

The Plugin extends the native **Reading** and **Permalinks** settings pages:

* Settings > **Reading** > Adds a section to select a page which should interact as archive for a custom post type.
* Settings > **Permalinks** > Adds a section to change the slug for custom post types and custom taxonomies.

## How to programmatically get the post used as the archive page for a custom post type?

Example how to retrieve the post object of the page set as the archive for a custom post type:

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
    return $wp_query->query['post_type'] ?? null;
}

$archivePage = getCustomPostTypeArchivePage('your_custom_post_type');
if ($archivePage) {
    echo "Archive page title: " . $archivePage->post_title;
    echo "Archive page ID: " . $archivePage->ID;
    echo "Archive page URL: " . get_permalink($archivePage->ID);
} else {
    echo "No archive page found for this post type.";
}
```

## Requirements

* PHP >= 8.0

## Installation

1. Make sure you have the correct [requirements](#requirements).
2. Clone the repository and place it in `wp-content/plugins/` folder.

## Development

1. Make sure you have the correct [requirements](#requirements).
2. Perform [Installation](#installation).
3. Run `composer i` to install composer dependency.

## License

GPLv3
