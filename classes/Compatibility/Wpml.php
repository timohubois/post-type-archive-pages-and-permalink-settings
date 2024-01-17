<?php

namespace Ptatap\Compatibility;

use Ptatap\Features\OptionsPermalinksPostTypes;
use Ptatap\Features\OptionsPermalinksTaxonomies;
use Ptatap\Features\OptionsReadingPostTypes;
use WP_Post;

defined('ABSPATH') || exit;

final class Wpml
{
    public function __construct()
    {
        if (in_array('sitepress-multilingual-cms/sitepress.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            // Modifies the stored options for the post type archive pages based on the current language.
            add_filter('ptatap_post_type_reading_settings', [$this, 'setTranslatedPostTypeReadingSettings'], 10, 1);

            add_filter('init', [$this, 'setPostTypeTranslationStrings'], 10);
            add_filter('init', [$this, 'setTaxonomyTranslationStrings'], 15);

            add_filter('rewrite_rules_array', [$this, 'setRewriteRulesArray'], PHP_INT_MAX, 1);
            add_filter('post_type_link', [$this, 'getPostTypeLink'], 10, 2);
            add_filter('post_type_archive_link', [$this, 'getPostTypeArchiveLink'], 10, 2);

            add_filter('icl_ls_languages', [$this, 'setIcLsLanguages'], 10, 1);
            add_filter('wpml_ls_language_url', [$this, 'setWpmlLsLanguageUrls'], 10, 2);
            add_filter('wpml_alternate_hreflang', [$this, 'setWpmlAlternateHrefLang'], 10, 2);
        }
    }

    public function setTranslatedPostTypeReadingSettings(array $postTypeReadingSettings): array|bool
    {
        if (!$postTypeReadingSettings) {
            return $postTypeReadingSettings;
        }

        $currentLanguage = isset($_GET['lang']) ? sanitize_text_field(wp_unslash($_GET['lang'])) : apply_filters('wpml_current_language', null);

        foreach ($postTypeReadingSettings as $postType => $value) {
            $postTypeReadingSettings[$postType] = $this->getWpmlObjectId($value, $postType, $currentLanguage);
        }

        return $postTypeReadingSettings;
    }

    public function setPostTypeTranslationStrings(): void
    {
        $optionsReadingPostTypes = OptionsReadingPostTypes::getInstance()->getOptions();
        $optionsPermalinksPostTypes = OptionsPermalinksPostTypes::getInstance()->getOptions();
        $languages = apply_filters('wpml_active_languages', [], 'skip_missing=0');

        if (empty($optionsReadingPostTypes) || empty($optionsPermalinksPostTypes) || empty($languages)) {
            return;
        }

        $defaultLanguageCode = apply_filters('wpml_default_language', null);

        $translations = [];
        foreach ($optionsReadingPostTypes as $postType => $postId) {
            foreach ($languages as $language) {
                $wpmlObjectId = $this->getWpmlObjectId($postId, null, $language['code']);
                $pageUri = get_page_uri($wpmlObjectId);
                $wpmlPermalink = apply_filters('wpml_permalink', home_url($pageUri), $language['code']);
                $wpmlUri = trim(wp_make_link_relative($wpmlPermalink), '/');

                $wpmlUri = explode('/', $wpmlUri);
                if ($wpmlUri[0] === $language['code']) {
                    array_shift($wpmlUri);
                }

                $wpmlUri = implode('/', $wpmlUri);

                $translations[$postType][$language['code']] = [
                    'permalink' => $optionsPermalinksPostTypes[$postType]
                        ?? $wpmlUri
                ];
            }
        }

        foreach ($translations as $postType => $languageTranslations) {
            $defaultLanguagePermalink = $translations[$postType][$defaultLanguageCode]['permalink'];

            foreach ($languageTranslations as $languageCode => $translationData) {
                $isDefaultLanguage = $languageCode === $defaultLanguageCode;
                $hasPermalink = !empty($translationData['permalink']);
                $name = 'URL slug: ' . $postType;

                if ($isDefaultLanguage && $hasPermalink) {
                    do_action(
                        'wpml_register_single_string',
                        'WordPress',
                        $name,
                        $defaultLanguagePermalink,
                        true,
                        $languageCode
                    );
                    continue;
                }

                $targetLanguageString = $translationData['permalink'];
                $isTranslationSameAsDefault = $targetLanguageString === $defaultLanguagePermalink;

                if ($isTranslationSameAsDefault) {
                    continue;
                }

                $translatedString = apply_filters(
                    'wpml_translate_single_string',
                    $defaultLanguagePermalink,
                    'WordPress',
                    $name,
                    $languageCode
                );
                if ($translatedString !== $defaultLanguagePermalink) {
                    continue;
                }
                if (!$hasPermalink) {
                    continue;
                }
                do_action(
                    'wpml_register_single_string',
                    'WordPress',
                    $name,
                    $targetLanguageString,
                    true,
                    $languageCode
                );
            }
        }
    }

    public function setTaxonomyTranslationStrings(): void
    {
        $optionsPermalinksTaxonomies = OptionsPermalinksTaxonomies::getInstance()->getOptions();
        $languages = apply_filters('wpml_active_languages', [], 'skip_missing=0');
        $defaultLanguageCode = apply_filters('wpml_default_language', null);

        if (empty($optionsPermalinksTaxonomies) || empty($languages) || empty($defaultLanguageCode)) {
            return;
        }

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

    public function setRewriteRulesArray(array $rules): array
    {
        $optionsReadingPostTypes = OptionsReadingPostTypes::getInstance()->getOptions();
        $languages = apply_filters('wpml_active_languages', [], 'skip_missing=0');

        if (empty($optionsReadingPostTypes) || empty($languages)) {
            return $rules;
        }

        $defaultLanguageCode = apply_filters('wpml_default_language', null);
        foreach ($optionsReadingPostTypes as $postType => $postId) {
            $pageForArchiveUri = $this->getPageForArchiveUri($postId, $defaultLanguageCode);
            $postTypeObject = get_post_type_object($postType);
            if (empty($pageForArchiveUri)) {
                continue;
            }
            if (empty($postTypeObject)) {
                continue;
            }

            $pageForArchiveUris = [];
            foreach ($languages as $language) {
                $pageForArchiveUris[] = $this->getPageForArchiveUri($postId, $language['code']);
            }

            if (empty($pageForArchiveUris)) {
                continue;
            }

            $replace = '(?:' . implode('|', $pageForArchiveUris) . ')';
            $keys = array_keys($rules);
            $values = array_values($rules);
            foreach ($keys as &$key) {
                if (str_contains($key, $pageForArchiveUri) || str_contains($key, $postTypeObject->has_archive)) {
                    $key = str_replace($postTypeObject->has_archive ?? $pageForArchiveUri, $replace, $key);
                }
            }

            $rules = array_combine($keys, $values);
        }

        return $rules;
    }

    public function getPostTypeLink(string $postLink, WP_Post $wpPost): string
    {
        $optionsPermalinksPostTypes = OptionsPermalinksPostTypes::getInstance()->getOptions();
        $postType = get_post_field('post_type', $wpPost);

        if (empty($optionsPermalinksPostTypes) || $optionsPermalinksPostTypes[$postType]) {
            return $postLink;
        }

        $currentLanguage = apply_filters('wpml_current_language', null);
        $optionsReadingPostTypes = OptionsReadingPostTypes::getInstance()->getOptions();
        $pageForArchiveId = $optionsReadingPostTypes[$wpPost->post_type] ?? null;

        if (!$pageForArchiveId) {
            return $postLink;
        }

        $pageForArchiveUri = $this->getPageForArchiveUri($pageForArchiveId, $currentLanguage);
        return home_url($pageForArchiveUri) ?: $postLink;
    }

    public function getPostTypeArchiveLink(string $link, string $post_type): string
    {
        $optionsReadingPostTypes = OptionsReadingPostTypes::getInstance()->getOptions();
        $pageForArchiveId = $optionsReadingPostTypes[$post_type] ?? null;
        $currentLanguage = apply_filters('wpml_current_language', null);

        if (empty($optionsReadingPostTypes) || empty($currentLanguage) || empty($currentLanguage)) {
            return $link;
        }

        $pageForArchiveUri = $this->getPageForArchiveUri($pageForArchiveId, $currentLanguage);
        return home_url($pageForArchiveUri) ?: $link;
    }

    public function setWpmlLsLanguageUrls(string $url, array $langs): string
    {
        $optionsReadingPostTypes = OptionsReadingPostTypes::getInstance()->getOptions();

        if (empty($optionsReadingPostTypes)) {
            return $url;
        }

        foreach ($optionsReadingPostTypes as $postType => $postId) {
            if (!is_post_type_archive($postType)) {
                continue;
            }

            $wpmlObjectId = $this->getWpmlObjectId($postId, $postType, $langs['code']);
            $pageUri = get_page_uri($wpmlObjectId);
            return apply_filters('wpml_permalink', home_url($pageUri), $langs['code']);
        }

        return $url;
    }

    public function setWpmlAlternateHrefLang(string $url, string $code): string
    {
        $optionsReadingPostTypes = OptionsReadingPostTypes::getInstance()->getOptions();

        if (empty($optionsReadingPostTypes)) {
            return $url;
        }

        foreach ($optionsReadingPostTypes as $postType => $postId) {
            if (!is_post_type_archive($postType)) {
                continue;
            }

            $wpmlObjectId = $this->getWpmlObjectId($postId, $postType, $code);
            $pageUri = get_page_uri($wpmlObjectId);
            return apply_filters('wpml_permalink', home_url($pageUri), $code);
        }

        return $url;
    }

    public function setIcLsLanguages(array $languages): array
    {
        if (is_admin()) {
            return $languages;
        }

        $optionsReadingPostTypes = OptionsReadingPostTypes::getInstance()->getOptions();

        if (empty($optionsReadingPostTypes)) {
            return $languages;
        }

        $postId = get_the_ID();

        // Custom post type single pages handling
        if (is_singular()) {
            $postType = get_post_field('post_type', $postId);
            $isPage = ($postType === 'page');
            $isCustomPostTypeSingle = ($postType !== 'post' && $postType !== 'page');

            if ($isPage || !$isCustomPostTypeSingle) {
                return $languages;
            }

            foreach ($languages as $key => $language) {
                $wpmlHomeUrl = apply_filters('wpml_permalink', home_url(), $language['code']);
                $wpmlHomeUrl = trim($wpmlHomeUrl, '/');
                $languages[$key]['url'] = trim($languages[$key]['url'], '/');

                $isUrlHomeUrl = $languages[$key]['url'] === $wpmlHomeUrl;

                if (!$isUrlHomeUrl) {
                    continue;
                }

                $pageForArchiveId = $optionsReadingPostTypes[$postType];
                $pageForArchiveUri = $this->getPageForArchiveUri($pageForArchiveId, $language['code']);
                $languages[$key]['url'] = apply_filters('wpml_permalink', home_url($pageForArchiveUri), $language['code']);
            }

            return $languages;
        }

        // Archive pages handling
        foreach ($optionsReadingPostTypes as $postType => $postId) {
            if (!is_post_type_archive($postType)) {
                continue;
            }

            foreach ($languages as $key => $language) {
                $wpmlObjectId = $this->getWpmlObjectId($postId, $postType, $language['code']);
                $pageUri = get_page_uri($wpmlObjectId);
                $languages[$key]['url'] = apply_filters('wpml_permalink', home_url($pageUri), $language['code']);
            }
        }

        return $languages;
    }

    private function getPageForArchiveUri(string|int|null|bool $pageForArchiveId = null, string|null $languageCode = null): string
    {

        $wpmlObjectId = $this->getWpmlObjectId($pageForArchiveId, null, $languageCode);
        $pageUri = get_page_uri($wpmlObjectId);
        $wpmlPermalink = apply_filters('wpml_permalink', home_url($pageUri), $languageCode);
        $wpmlUri = trim(wp_make_link_relative($wpmlPermalink), '/');

        $wpmlUri = explode('/', $wpmlUri);
        if ($wpmlUri[0] === $languageCode) {
            array_shift($wpmlUri);
        }

        return implode('/', $wpmlUri);
    }

    private function getWpmlObjectId(string|int|null|bool $postId = 0, string|null $object = null, string|null $language = null): string|int|null
    {
        if (empty($object)) {
            $isPage = (get_post_field('post_type', $postId) === 'page');
            if ($isPage) {
                $object = get_post_type($postId);
            }

            $object = get_post_type($postId) ?: null;
        }

        return apply_filters('wpml_object_id', $postId, $object, true, $language);
    }
}
