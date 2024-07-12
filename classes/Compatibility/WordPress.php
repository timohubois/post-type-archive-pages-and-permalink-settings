<?php

namespace Ptatap\Compatibility;

use Ptatap\Features\FlushRewriteRules;
use Ptatap\Features\OptionsPermalinksPostTypes;
use Ptatap\Features\OptionsPermalinksTaxonomies;
use Ptatap\Features\OptionsReadingPostTypes;
use Ptatap\Features\SupportedPostTypes;
use WP_Admin_Bar;
use WP_Post;
use WP_Query;

defined('ABSPATH') || exit;

final class WordPress
{
    public function __construct()
    {
        add_filter('rewrite_rules_array', [$this, 'updateTaxonomyRewriteRulesOrder']);
        add_action('template_redirect', [$this, 'handle404']);

        add_filter('wp_title', [$this, 'updateTitle']);
        add_filter('get_the_archive_title', [$this, 'updateTitle']);

        add_filter('wp_nav_menu_objects', [$this, 'updateNavMenuObjects'], 10, 1);

        add_action('admin_bar_menu', [$this, 'addAdminBarEditLink'], 80);
        add_action('display_post_states', [$this, 'addPostStateLabel'], 10, 2);
        add_action('post_updated', [$this, 'postUpdated'], 10, 3);
    }

    public function updateTaxonomyRewriteRulesOrder(array $rules): array
    {
        $optionsPermalinksTaxonomies = OptionsPermalinksTaxonomies::getInstance()->getOptions();

        if ($optionsPermalinksTaxonomies === [] || $optionsPermalinksTaxonomies === false) {
            return $rules;
        }

        foreach (array_keys($optionsPermalinksTaxonomies) as $taxonomy) {
            $taxonomySlug = $optionsPermalinksTaxonomies[$taxonomy] ?? null;

            if ($taxonomySlug) {
                $filteredRules = array_filter(
                    $rules,
                    static fn ($key): bool => str_starts_with($key, $taxonomySlug),
                    ARRAY_FILTER_USE_KEY
                );
                $nonMatchingRules = array_diff_key($rules, $filteredRules);
                $rules = $filteredRules + $nonMatchingRules;
            }
        }

        return $rules;
    }

    public function handle404()
    {
        if (is_404()) {
            $optionsPermalinksPostTypes = OptionsPermalinksPostTypes::getInstance()->getOptions();
            if ($optionsPermalinksPostTypes === false || $optionsPermalinksPostTypes === []) {
                return;
            }

            $optionsReadingPostTypes = OptionsReadingPostTypes::getInstance()->getOptions();
            if ($optionsReadingPostTypes === false || $optionsReadingPostTypes === []) {
                return;
            }

            $requestUri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
            $requestUri = trim($requestUri, '/');

            foreach ($optionsPermalinksPostTypes as $postType => $postTypeSlug) {
                $postTypeArchivePageId = $optionsReadingPostTypes[$postType] ?? null;
                if (!$postTypeArchivePageId) {
                    continue;
                }

                if (!$postTypeSlug) {
                    continue;
                }

                $postTypeArchivePageUri = get_page_uri($postTypeArchivePageId);
                if (str_ends_with($requestUri, $postTypeSlug) || str_ends_with($requestUri, $postTypeArchivePageUri)) {
                    wp_redirect(get_post_type_archive_link($postType), 301);
                    exit;
                }
            }
        }
    }

    public static function updateTitle(string $title): string
    {
        if (is_search()) {
            return $title;
        }

        if (is_archive() && !is_tax()) {
            $title = self::updateCustomArchiveTitle($title);
        }

        if (is_tax()) {
            return self::updateTaxonomyTitle($title);
        }

        return $title;
    }

    private static function updateCustomArchiveTitle(string $title): string
    {
        $queriedObject = get_queried_object();
        $postType = $queriedObject->name ?? null;
        $postTypeArchivePageId = OptionsReadingPostTypes::getInstance()->getOptions()[$postType] ?? null;

        if ($postTypeArchivePageId) {
            return esc_attr(get_post($postTypeArchivePageId)->post_title);
        }

        return $title;
    }

    private static function updateTaxonomyTitle(string $title): string
    {
        $queriedObject = get_queried_object();
        $taxonomy = $queriedObject->taxonomy ?? null;
        $taxonomyPermalinkOption = OptionsPermalinksTaxonomies::getInstance()->getOptions()[$taxonomy] ?? null;

        if ($taxonomyPermalinkOption) {
            return esc_attr($queriedObject->name);
        }

        return $title;
    }

    public function updateNavMenuObjects(array $sortedMenuItems): array
    {
        global $wp_query;

        $queriedObject = get_queried_object();

        if (!$queriedObject) {
            return $sortedMenuItems;
        }

        $queriedPostType = false;
        $queriedTaxonomy = false;

        if (is_singular()) {
            $queriedPostType = $queriedObject->post_type;
        }

        if (is_post_type_archive()) {
            $queriedPostType = $queriedObject->name;
        }

        if (is_archive() && is_string($wp_query->get('post_type'))) {
            $queryPostType  = $wp_query->get('post_type');
            $queriedPostType = $queryPostType ?: 'post';
        }

        if (is_tax()) {
            $queriedTaxonomy = $queriedObject->taxonomy;
            $optionsPermalinksTaxonomies = OptionsPermalinksTaxonomies::getInstance()->getOptions() ?: [];
            if (array_key_exists($queriedTaxonomy, $optionsPermalinksTaxonomies)) {
                $queriedTaxonomy = $queriedObject->taxonomy;
            }
        }

        if (!$queriedPostType) {
            return $sortedMenuItems;
        }

        $postTypeArchivePageId = OptionsReadingPostTypes::getInstance()->getOptions()[$queriedPostType] ?? null;

        if (!$postTypeArchivePageId) {
            return $sortedMenuItems;
        }

        foreach ($sortedMenuItems as &$sortedMenuItem) {
            if ($sortedMenuItem->type === 'post_type' && $sortedMenuItem->object === 'page' && (int) $sortedMenuItem->object_id === (int) $postTypeArchivePageId) {
                if (is_singular($queriedPostType) || is_tax($queriedTaxonomy)) {
                    $sortedMenuItem->classes[] = 'current-menu-item-ancestor';
                    $sortedMenuItem->current_item_ancestor = true;
                    $sortedMenuItems = $this->recursiveAddAncestor($sortedMenuItem, $sortedMenuItems);
                }

                if (is_post_type_archive($queriedPostType)) {
                    $sortedMenuItem->classes[] = 'current-menu-item';
                    $sortedMenuItem->current = true;
                    $sortedMenuItems = $this->recursiveAddAncestor($sortedMenuItem, $sortedMenuItems);
                }

                if (is_archive() && $queriedPostType === $wp_query->get('post_type')) {
                    $sortedMenuItems = $this->recursiveAddAncestor($sortedMenuItem, $sortedMenuItems);
                }
            }
        }

        return $sortedMenuItems;
    }

    private function recursiveAddAncestor($child, $items): array
    {

        if ((int) $child->menu_item_parent === 0) {
            return $items;
        }

        foreach ($items as $item) {
            if ((int) $item->ID === (int) $child->menu_item_parent) {
                $item->classes[] = 'current-menu-item-ancestor';
                $item->current_item_ancestor = true;
                if ((int) $item->menu_item_parent !== 0) {
                    $items = $this->recursiveAddAncestor($item, $items);
                }

                break;
            }
        }

        return $items;
    }

    public static function addAdminBarEditLink(WP_Admin_Bar $wpAdminBar): void
    {
        if ((is_admin() || !is_admin_bar_showing()) && !is_archive() && !is_404()) {
            return;
        }

        $queriedObject = get_queried_object();
        $postType = $queriedObject->name ?? null;
        $postTypeArchivePageId = OptionsReadingPostTypes::getInstance()->getOptions()[$postType] ?? null;
        if ($postTypeArchivePageId) {
            $editPostLink = get_edit_post_link($postTypeArchivePageId);
            $wpAdminBar->add_menu(
                [
                    'id' => 'edit',
                    'title' => sprintf(
                        '%1$s %2$s',
                        __('Edit', 'post-type-archive-pages-and-permalink-settings'),
                        self::getArchivePagePostTypeName($postTypeArchivePageId)
                    ),
                    'href' => $editPostLink,
                    'parent' => false,
                ]
            );
        }
    }

    public function addPostStateLabel(array $postStates, WP_Post $wpPost): array
    {
        $optionsReadingPostTypes = OptionsReadingPostTypes::getInstance()->getOptions();

        if ($optionsReadingPostTypes === [] || $optionsReadingPostTypes === false) {
            return $postStates;
        }

        foreach ($optionsReadingPostTypes as $postType => $postTypeArchivePageId) {
            $supportedPostTypes = SupportedPostTypes::getInstance()->getPostTypes();
            $postTypeLabel = $supportedPostTypes[$postType]->labels->archives ??
                $supportedPostTypes[$postType]->labels->name ?? null;
            if ((int)$postTypeArchivePageId === 0) {
                continue;
            }

            if ((int)$wpPost->ID !== (int)$postTypeArchivePageId) {
                continue;
            }

            $postStates[] = sprintf(
                '%1$s',
                $postTypeLabel
            );
        }

        return $postStates;
    }

    public function postUpdated(int $postId, WP_Post $postAfter, WP_Post $postBefore): void
    {

        if ($postAfter->post_type !== 'page') {
            return;
        }

        if (
            $postAfter->post_name === $postBefore->post_name &&
            $postAfter->post_status === $postBefore->post_status &&
            $postAfter->post_parent === $postBefore->post_parent
        ) {
            return;
        }

        $optionsReadingPostTypes = OptionsReadingPostTypes::getInstance()->getOptions();
        if ($optionsReadingPostTypes === [] || $optionsReadingPostTypes === false) {
            return;
        }

        foreach ($optionsReadingPostTypes as $optionReadingPostType) {
            if ((int)$optionReadingPostType === $postId) {
                FlushRewriteRules::getInstance()->setup();
                return;
            }
        }
    }

    private static function getArchivePagePostTypeName($postTypeArchivePageId): string
    {
        $isPage = (get_post_field('post_type', $postTypeArchivePageId) === 'page');
        if ($isPage) {
            return __('Page', 'post-type-archive-pages-and-permalink-settings');
        }

        $archivePagePostType = get_post_type($postTypeArchivePageId);
        if (!is_object($archivePagePostType)) {
            return '';
        }

        if (!isset($archivePagePostType->labels->name)) {
            return '';
        }

        return $archivePagePostType->labels->name;
    }
}
