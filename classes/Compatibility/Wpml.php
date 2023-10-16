<?php

namespace Ptapas\Compatibility;

use Ptapas\Features\OptionsPermalinksPostTypes;
use Ptapas\Features\OptionsPermalinksTaxonomies;
use Ptapas\Features\OptionsReadingPostTypes;

defined('ABSPATH') || exit;

class Wpml
{
    public function __construct()
    {
        if (in_array('sitepress-multilingual-cms/sitepress.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            // Modifies the stored options for the post type archive pages.
            add_filter(APAPS_TEXT_DOMAIN . '_post_type_reading_settings', [$this, 'setTranslatedPostTypeReadingSettings'], 10, 1);

            add_filter('rewrite_rules_array', [$this, 'setRewriteRulesArray'], PHP_INT_MAX, 1);
            add_filter('icl_ls_languages', [$this, 'setIcLsLanguages'], 10, 1);
            add_filter('init', [$this, 'setTranslationStrings'], 10);
        }
    }

    public function setTranslatedPostTypeReadingSettings($postTypeReadingSettings)
    {
        if (empty($postTypeReadingSettings)) {
            return $postTypeReadingSettings;
        }

        $currentLanguage = isset($_GET['lang']) ? $_GET['lang'] : apply_filters('wpml_current_language', null);

        foreach ($postTypeReadingSettings as $postType => $value) {
            $postTypeReadingSettings[$postType] = apply_filters('wpml_object_id', $value, 'post', true, $currentLanguage);
        }

        return $postTypeReadingSettings;
    }

    public function setRewriteRulesArray($rules)
    {
        $optionsReadingPostTypes = OptionsReadingPostTypes::getInstance()->getOptions() ?? [];
        $languages = apply_filters('wpml_active_languages', [], 'skip_missing=0');

        if (empty($optionsReadingPostTypes) || empty($languages)) {
            return $rules;
        }

        $defaultLanguage = apply_filters('wpml_default_language', null);
        foreach ($optionsReadingPostTypes as $postType => $postId) {
            $pageForArchiveSlug = $this->getPageForArchiveSlug($postId, $defaultLanguage);
            $postTypeObject = get_post_type_object($postType);

            if (empty($pageForArchiveSlug) || empty($postTypeObject)) {
                continue;
            }

            $slugs = [];
            foreach ($languages as $language) {
                $slugs[] = $this->getPageForArchiveSlug($postId, $language['code']);
            }

            if (empty($slugs)) {
                continue;
            }

            $replace = '(?:' . implode('|', $slugs) . ')';
            $keys = array_keys($rules);
            $values = array_values($rules);
            foreach ($keys as &$key) {
                if (strpos($key, $pageForArchiveSlug) !== false || strpos($key, $postTypeObject->has_archive) !== false) {
                    $key = str_replace($postTypeObject->has_archive ?? $pageForArchiveSlug, $replace, $key);
                }
            }

            $rules = array_combine($keys, $values);
        }

        return $rules;
    }

    public function setIcLsLanguages($languages)
    {
        if (is_admin()) {
            return $languages;
        }

        $optionsReadingPostTypes = OptionsReadingPostTypes::getInstance()->getOptions() ?? [];

        if (empty($optionsReadingPostTypes)) {
            return $languages;
        }

        foreach ($optionsReadingPostTypes as $postType => $postId) {
            $postTypeObject = get_post_type_object($postType);

            if (empty($postTypeObject->has_archive)) {
                continue;
            }

            $slugs = [];
            foreach ($languages as $language) {
                $slugs[$language['code']] = $this->getPageForArchiveSlug($postId, $language['code']);
            }

            if (empty($slugs)) {
                continue;
            }

            foreach ($languages as $key => $language) {
                $languages[$key]['url'] = str_replace($postTypeObject->has_archive ?? $slugs[$language['code']], $slugs[$language['code']], $language['url']);
            }
        }

        return $languages;
    }

    public function setTranslationStrings()
    {
        $this->setPostTypeTranslationStrings();
        $this->setTaxonomyTranslationStrings();
    }

    public function setPostTypeTranslationStrings()
    {
        $optionsReadingPostTypes = OptionsReadingPostTypes::getInstance()->getOptions() ?? [];
        $optionsPermalinksPostTypes = OptionsPermalinksPostTypes::getInstance()->getOptions() ?? [];
        $languages = apply_filters('wpml_active_languages', [], 'skip_missing=0');

        if (empty($optionsReadingPostTypes) || empty($languages)) {
            return;
        }

        $defaultLanguageCode = apply_filters('wpml_default_language', null);
        foreach ($optionsReadingPostTypes as $postType => $postId) {
            $pageForArchiveSlug = $this->getPageForArchiveSlug($postId, $defaultLanguageCode);
            $postTypePermalink = $optionsPermalinksPostTypes[$postType] ?? '';
            $originalStringValue = $postTypePermalink ?? $pageForArchiveSlug ?? '';

            foreach ($languages as $language) {
                $pageForArchiveSlug = $this->getPageForArchiveSlug($postId, $language['code']);
                $postTypePermalink = $optionsPermalinksPostTypes[$postType] ?? '';
                $translationStringValue = $postTypePermalink ?? $pageForArchiveSlug ?? '';
                $translationStringValue = apply_filters('wpml_translate_single_string', $originalStringValue, 'WordPress', 'URL slug: ' . $postType, $language['code']);

                if ($originalStringValue !== $translationStringValue && !empty($postTypePermalink)) {
                    do_action('wpml_register_single_string', 'WordPress', 'URL slug: ' . $postType, $translationStringValue, true, $language['code']);
                }

                if ($defaultLanguageCode === $language['code'] && !empty($pageForArchiveSlug) && empty($postTypePermalink)) {
                    do_action('wpml_register_single_string', 'WordPress', 'URL slug: ' . $postType, $pageForArchiveSlug, true, $language['code']);
                }

                if ($defaultLanguageCode === $language['code'] && !empty($pageForArchiveSlug) && !empty($postTypePermalink)) {
                    do_action('wpml_register_single_string', 'WordPress', 'URL slug: ' . $postType, $originalStringValue, true, $language['code']);
                }
            }
        }
    }

    public function setTaxonomyTranslationStrings()
    {
        $optionsPermalinksTaxonomies = OptionsPermalinksTaxonomies::getInstance()->getOptions() ?? [];
        $languages = apply_filters('wpml_active_languages', [], 'skip_missing=0');

        if (empty($optionsPermalinksTaxonomies) || empty($languages)) {
            return;
        }

        $defaultLanguageCode = apply_filters('wpml_default_language', null);
        foreach ($optionsPermalinksTaxonomies as $taxonomy => $slug) {
            foreach ($languages as $language) {
                if ($defaultLanguageCode === $language['code']) {
                    if (!empty($slug)) {
                        do_action('wpml_register_single_string', 'WordPress', 'URL ' . $taxonomy . ' tax slug', $slug, true, $language['code']);
                    } else {
                        do_action('wpml_register_single_string', 'WordPress', 'URL ' . $taxonomy . ' tax slug', $taxonomy, true, $language['code']);
                    }
                }
            }
        }
    }

    private function getPageForArchiveSlug(string|int|null|bool $postId = 0, mixed $language = null): string
    {
        $postsPageId = apply_filters('wpml_object_id', $postId, 'post', true, $language);
        return get_page_uri($postsPageId);
    }
}
