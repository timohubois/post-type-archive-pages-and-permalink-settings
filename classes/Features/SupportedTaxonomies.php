<?php

namespace Ptatap\Features;

defined('ABSPATH') || exit;

class SupportedTaxonomies
{
    private static $instance = null;
    private $taxonomies = [];

    public function __construct()
    {
        add_action('wp_loaded', [$this, 'getTaxonomies']);
    }

    public static function getInstance(): SupportedTaxonomies
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function getSupportedTaxonomies(): array
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
        return $this->taxonomies = $this->taxonomies ?: $this->getSupportedTaxonomies();
    }
}
