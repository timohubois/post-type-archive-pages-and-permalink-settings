<?php

namespace Ptapas\Features;

defined('ABSPATH') || exit;

class PluginActionLinks
{
    public function __construct()
    {
        add_filter('plugin_action_links_' . plugin_basename(APAPS_PLUGIN_FILE), [$this, 'addActionLinks']);
    }

    public function addActionLinks(array $links): array
    {
        array_unshift($links, '<a href="options-permalink.php">' . __('Permalinks Settings', APAPS_TEXT_DOMAIN) . '</a>');
        array_unshift($links, '<a href="options-reading.php">' . __('Reading Settings', APAPS_TEXT_DOMAIN) . '</a>');
        return $links;
    }
}
