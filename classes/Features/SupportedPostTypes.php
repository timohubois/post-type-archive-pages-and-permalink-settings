<?php

namespace Ptatap\Features;

defined('ABSPATH') || exit;

class SupportedPostTypes
{
    private static $instance = null;
    private $postTypes = [];

    public function __construct()
    {
        $this->postTypes = $this->getSupportedPostTypes();

        add_action('wp_loaded', function () {
            $this->postTypes = $this->getSupportedPostTypes();
        });
    }

    public static function getInstance(): SupportedPostTypes
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function getSupportedPostTypes()
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

    public function getPostTypes()
    {
        if (empty($this->postTypes)) {
            $this->postTypes = $this->getSupportedPostTypes();
        }

        return $this->postTypes;
    }
}
