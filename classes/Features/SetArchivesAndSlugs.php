<?php

namespace Ptapas\Features;

defined('ABSPATH') || exit;

use Ptapas\Features\OptionsPermalinksPostTypes;
use Ptapas\Features\OptionsPermalinksTaxonomies;
use Ptapas\Features\OptionsReadingPostTypes;

class SetArchivesAndSlugs
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
            $postName = get_post_field('post_name', $postTypeArchivePageId);

            $args['has_archive'] = get_page_uri($postTypeArchivePageId);
            $args['rewrite']['slug'] = $postName;
        }

        $optionsPermalinksPostTypes = OptionsPermalinksPostTypes::getInstance()->getOptions();
        if (!empty($optionsPermalinksPostTypes[$postType])) {
            $args['rewrite']['slug'] = $optionsPermalinksPostTypes[$postType];
        }

        return $args;
    }

    public function setTaxonomyArgs(array $args, string $taxonomy): array
    {
        $optionsPermalinksTaxonomies = OptionsPermalinksTaxonomies::getInstance()->getOptions();
        if (!empty($optionsPermalinksTaxonomies[$taxonomy])) {
            $args['rewrite']['slug'] = $optionsPermalinksTaxonomies[$taxonomy];
        }

        return $args;
    }
}
