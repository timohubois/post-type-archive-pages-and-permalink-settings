<?php

namespace Ptatap\Features;

defined('ABSPATH') || exit;

final class SupportedPostTypes
{
    private static ?\Ptatap\Features\SupportedPostTypes $instance = null;

    private $postTypes = [];

    public function __construct()
    {
        add_action('wp_loaded', [$this, 'getPostTypes']);
    }

    public static function getInstance(): SupportedPostTypes
    {
        if (!self::$instance instanceof \Ptatap\Features\SupportedPostTypes) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function getSupportedPostTypes(): array
    {

        $postTypes = get_post_types(
            [
                'public' => true,
                '_builtin' => false,
            ]
        );

        $postTypes = array_filter($postTypes, 'post_type_exists');
        $postTypes = array_map('get_post_type_object', $postTypes);

        return $postTypes ?? [];
    }

    public function getPostTypes(): array
    {
        return $this->postTypes = $this->postTypes ?: $this->getSupportedPostTypes();
    }
}
