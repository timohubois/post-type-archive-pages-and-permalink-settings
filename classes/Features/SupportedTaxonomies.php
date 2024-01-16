<?php

namespace Ptatap\Features;

defined('ABSPATH') || exit;

class SupportedTaxonomies
{
    private static $instance = null;
    private $taxonomies = [];

    public function __construct()
    {
        $this->taxonomies = $this->getSupportedTaxonomies();

        add_action('wp_loaded', function () {
            $this->taxonomies = $this->getSupportedTaxonomies();
        });
    }

    public static function getInstance(): SupportedTaxonomies
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function getSupportedTaxonomies()
    {

        $taxonomies = get_taxonomies(
            [
                'public' => true,
                '_builtin' => false,
            ]
        );

        $taxonomies = array_filter($taxonomies, 'taxonomy_exists');
        $taxonomies = array_map('get_taxonomy', $taxonomies);

        return $taxonomies;
    }

    public function getTaxonomies()
    {
        if (empty($this->taxonomies)) {
            $this->taxonomies = $this->getSupportedTaxonomies();
        }

        return $this->taxonomies;
    }
}
