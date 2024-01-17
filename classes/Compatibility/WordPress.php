<?php

namespace Ptatap\Compatibility;

use Ptatap\Features\FlushRewriteRules;
use Ptatap\Features\OptionsPermalinksPostTypes;
use Ptatap\Features\OptionsPermalinksTaxonomies;
use Ptatap\Features\OptionsReadingPostTypes;
use Ptatap\Features\SupportedPostTypes;
use WP_Admin_Bar;
use WP_Post;

defined('ABSPATH') || exit;

class WordPress
{
    public function __construct()
    {
        add_action('wp_loaded', [$this, 'addPostTypeRewriteRule'], 20);
        add_filter('rewrite_rules_array', [$this, 'updateTaxonomyRewriteRulesOrder']);
        add_action('template_redirect', [$this, 'redirectPostTypeSlugToArchivePage']);

        add_filter('wp_title', [$this, 'updateTitle']);
        add_filter('get_the_archive_title', [$this, 'updateTitle']);

        add_filter('wp_nav_menu_objects', [$this, 'updateNavMenuObjects'], 10, 1);

        add_action('admin_bar_menu', [$this, 'addAdminBarEditLink'], 80);
        add_action('display_post_states', [$this, 'addPostStateLabel'], 10, 2);
        add_action('post_updated', [$this, 'postUpdated'], 10, 3);
    }

    public function addPostTypeRewriteRule(): void
    {
        $optionsReadingPostTypes = OptionsReadingPostTypes::getInstance()->getOptions();
        $optionsPermalinksPostTypes = OptionsPermalinksPostTypes::getInstance()->getOptions();

        if (empty($optionsReadingPostTypes) || empty($optionsPermalinksPostTypes)) {
            return;
        }

        foreach ($optionsPermalinksPostTypes as $postType => $postTypeSlug) {
            $postTypeArchivePageId = $optionsReadingPostTypes[$postType] ?? null;

            if ($postTypeArchivePageId && $postTypeSlug) {
                add_rewrite_rule(
                    $postTypeSlug . '/?$',
                    'index.php?pagename=' . $postTypeSlug,
                    'top'
                );
            }
        }
    }

    public function updateTaxonomyRewriteRulesOrder(array $rules): array
    {
        $optionsPermalinksTaxonomies = OptionsPermalinksTaxonomies::getInstance()->getOptions();

        if (empty($optionsPermalinksTaxonomies)) {
            return $rules;
        }

        foreach (array_keys($optionsPermalinksTaxonomies) as $taxonomy) {
            $taxonomySlug = $optionsPermalinksTaxonomies[$taxonomy] ?? null;

            if ($taxonomySlug) {
                $filteredRules = array_filter(
                    $rules,
                    fn ($key): bool => str_starts_with($key, $taxonomySlug),
                    ARRAY_FILTER_USE_KEY
                );
                $nonMatchingRules = array_diff_key($rules, $filteredRules);
                $rules = $filteredRules + $nonMatchingRules;
            }
        }

        return $rules;
    }

    public function redirectPostTypeSlugToArchivePage(): void
    {
        global $wp_query;

        $isNameOrPageNameSet = isset($wp_query->query['name']) || isset($wp_query->query['pagename']);

        if (!$isNameOrPageNameSet) {
            return;
        }

        $optionsReadingPostTypes = OptionsReadingPostTypes::getInstance()->getOptions();
        $optionsPermalinksPostTypes = OptionsPermalinksPostTypes::getInstance()->getOptions();

        if (empty($optionsReadingPostTypes) || empty($optionsPermalinksPostTypes)) {
            return;
        }

        foreach ($optionsPermalinksPostTypes as $postType => $postTypeSlug) {
            $postTypeArchivePageId = $optionsReadingPostTypes[$postType] ?? null;

            if ($postTypeArchivePageId && $postTypeSlug) {
                if (
                    (isset($wp_query->query['name']) && $wp_query->query['name'] === $postTypeSlug && $postTypeArchivePageId) ||
                    (isset($wp_query->query['pagename']) && $wp_query->query['pagename'] === $postTypeSlug && $postTypeArchivePageId)
                ) {
                    wp_redirect(get_post_type_archive_link($postType), 301);
                    exit();
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
            $title = self::updateTaxonomyTitle($title);
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

        foreach ($sortedMenuItems as &$item) {
            if ($item->type === 'post_type' && $item->object === 'page' && intval($item->object_id) === intval($postTypeArchivePageId)) {
                if (is_singular($queriedPostType) || is_tax($queriedTaxonomy)) {
                    $item->classes[] = 'current-menu-item-ancestor';
                    $item->current_item_ancestor = true;
                    $sortedMenuItems = $this->recursiveAddAncestor($item, $sortedMenuItems);
                }
                if (is_post_type_archive($queriedPostType)) {
                    $item->classes[] = 'current-menu-item';
                    $item->current = true;
                    $sortedMenuItems = $this->recursiveAddAncestor($item, $sortedMenuItems);
                }
                if (is_archive() && $queriedPostType === $wp_query->get('post_type')) {
                    $sortedMenuItems = $this->recursiveAddAncestor($item, $sortedMenuItems);
                }
            }
        }

        return $sortedMenuItems;
    }

    protected function recursiveAddAncestor($child, $items): array
    {

        if (!intval($child->menu_item_parent)) {
            return $items;
        }

        foreach ($items as $item) {
            if (intval($item->ID) === intval($child->menu_item_parent)) {
                $item->classes[] = 'current-menu-item-ancestor';
                $item->current_item_ancestor = true;
                if (intval($item->menu_item_parent)) {
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
                        __('Edit', 'post-type-and-taxonomy-archive-pages'),
                        self::getArchivePagePostTypeName($postTypeArchivePageId)
                    ),
                    'href' => $editPostLink,
                    'parent' => false,
                ]
            );
        }
    }

    public function addPostStateLabel(array $postStates, WP_Post $post): array
    {
        $optionsReadingPostTypes = OptionsReadingPostTypes::getInstance()->getOptions();
        $optionsPermalinksPostTypes = OptionsPermalinksPostTypes::getInstance()->getOptions();

        if (empty($optionsReadingPostTypes) || empty($optionsPermalinksPostTypes)) {
            return $postStates;
        }

        foreach ($optionsReadingPostTypes as $postType => $postTypeArchivePageId) {
            $supportedPostTypes = SupportedPostTypes::getInstance()->getPostTypes();
            $postTypeLabel = $supportedPostTypes[$postType]->labels->archives ??
                $supportedPostTypes[$postType]->labels->name ?? null;

            if ((int)$postTypeArchivePageId && (int)$post->ID === (int)$postTypeArchivePageId) {
                $postStates[] = sprintf(
                    '%1$s',
                    $postTypeLabel
                );
            }
        }

        return $postStates;
    }

    public function postUpdated(int $postId, WP_Post $postAfter, WP_Post $postBefore): void
    {

        if ($postAfter->post_type !== 'page') {
            return;
        }

        if ($postAfter->post_name === $postBefore->post_name && $postAfter->post_status === $postBefore->post_status) {
            return;
        }

        $optionsReadingPostTypes = OptionsReadingPostTypes::getInstance()->getOptions();
        if (empty($optionsReadingPostTypes)) {
            return;
        }
        foreach ($optionsReadingPostTypes as $postType => $postTypeArchivePageId) {
            if ((int)$postTypeArchivePageId === $postId) {
                FlushRewriteRules::getInstance()->setup();
                return;
            }
        }
    }

    private static function getArchivePagePostTypeName($postTypeArchivePageId): string
    {
        $isPage = (get_post_field('post_type', $postTypeArchivePageId) === 'page');
        if ($isPage) {
            return __('Page', 'post-type-and-taxonomy-archive-pages');
        }

        $archivePagePostType = get_post_type($postTypeArchivePageId);
        if (is_object($archivePagePostType) && isset($archivePagePostType->labels->name)) {
            return $archivePagePostType->labels->name;
        }

        return '';
    }
}
