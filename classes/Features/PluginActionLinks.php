<?php

namespace Ptatap\Features;

defined('ABSPATH') || exit;

final class PluginActionLinks
{
    public function __construct()
    {
        add_filter('plugin_action_links_' . plugin_basename(PTATAP_PLUGIN_FILE), [$this, 'addActionLinks']);
    }

    public function addActionLinks(array $links): array
    {
        array_unshift($links, '<a href="options-permalink.php">' . __('Permalinks Settings', 'post-type-and-taxonomy-archive-pages') . '</a>');
        array_unshift($links, '<a href="options-reading.php">' . __('Reading Settings', 'post-type-and-taxonomy-archive-pages') . '</a>');
        return $links;
    }
}
