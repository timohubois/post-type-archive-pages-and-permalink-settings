<?php

namespace Ptapas\Compatibility;

use Ptapas\Features\OptionsReadingPostTypes;

defined('ABSPATH') || exit;

class Timber
{
    public function __construct()
    {
        add_filter('timber/context', [$this, 'maybeAddArchivePageToContext']);
    }

    public static function maybeAddArchivePageToContext(array $context): array
    {
        $queriedObject = get_queried_object();
        $taxonomy = $queriedObject->taxonomy ?? null;
        $postType = isset(get_taxonomy($taxonomy)->object_type[0])
            ? get_taxonomy($taxonomy)->object_type[0] : $queriedObject->name ?? null;

        $postTypeArchivePageId = OptionsReadingPostTypes::getInstance()->getOptions()[$postType] ?? null;

        if ($postTypeArchivePageId) {
            $context['post'] = \Timber\Timber::get_post($postTypeArchivePageId);
        }

        return $context;
    }
}
