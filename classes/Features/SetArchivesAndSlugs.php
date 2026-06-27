<?php

namespace Ptatap\Features;

defined('ABSPATH') || exit;

use Ptatap\Features\OptionsPermalinksPostTypes;
use Ptatap\Features\OptionsPermalinksTaxonomies;
use Ptatap\Features\OptionsReadingPostTypes;

final class SetArchivesAndSlugs
{
    public function __construct()
    {
        add_filter('register_post_type_args', [$this, 'setPostTypeArgs'], 10, 2);
        add_filter('register_taxonomy_args', [$this, 'setTaxonomyArgs'], 10, 3);
    }

    public function setPostTypeArgs(array $args, string $postType): array
    {
        $optionsReadingPostTypes = OptionsReadingPostTypes::getInstance()->getOptions();
        if (!empty($optionsReadingPostTypes[$postType])) {
            $postTypeArchivePageId = $optionsReadingPostTypes[$postType];

            $args['has_archive'] = get_page_uri($postTypeArchivePageId);
            $args = $this->setRewriteSlug($args, get_page_uri($postTypeArchivePageId));
        }

        $optionsPermalinksPostTypes = OptionsPermalinksPostTypes::getInstance()->getOptions();
        if (!empty($optionsPermalinksPostTypes[$postType])) {
            $args = $this->setRewriteSlug($args, $optionsPermalinksPostTypes[$postType]);
        }

        return $args;
    }

    public function setTaxonomyArgs(array $args, string $taxonomy): array
    {
        $optionsPermalinksTaxonomies = OptionsPermalinksTaxonomies::getInstance()->getOptions();
        if (!empty($optionsPermalinksTaxonomies[$taxonomy])) {
            $args = $this->setRewriteSlug($args, $optionsPermalinksTaxonomies[$taxonomy]);
        }

        return $args;
    }

    private function setRewriteSlug(array $args, string $slug): array
    {
        if (!isset($args['rewrite']) || !is_array($args['rewrite'])) {
            $args['rewrite'] = [];
        }

        $args['rewrite']['slug'] = $slug;

        return $args;
    }
}
