<?php

namespace Ptatap\Compatibility;

use Ptatap\Features\OptionsReadingPostTypes;

defined('ABSPATH') || exit;

final class Timber
{
    public function __construct()
    {
        add_filter('timber/context', [$this, 'maybeAddArchivePageToContext']);
    }

    public static function maybeAddArchivePageToContext(array $context): array
    {
        $queriedObject = get_queried_object();
        $taxonomy = isset($queriedObject->taxonomy) ? get_taxonomy($queriedObject->taxonomy) : null;
        $postType = $taxonomy->object_type[0] ?? $queriedObject->name ?? null;
        if ($postType === null) {
            return $context;
        }

        $postTypeArchivePageId = OptionsReadingPostTypes::getInstance()->getOptions()[$postType] ?? null;

        if ($postTypeArchivePageId) {
            $context['post'] = \Timber\Timber::get_post($postTypeArchivePageId);
        }

        return $context;
    }
}
