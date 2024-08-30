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
        if (self::isWpmlActive()) {
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

            add_action('template_redirect', [$this, 'redirectTo404IfArchivePageNotFoundInCurrentLanguage'], 10);
            add_action('pre_update_option_' . OptionsReadingPostTypes::OPTION_NAME, [$this, 'setOptionValueToDefaultLanguage'], PHP_INT_MAX, 2);
        }
    }

    public static function isWpmlActive(): bool
    {
        if (!function_exists('is_plugin_active')) {
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }

        return is_plugin_active('sitepress-multilingual-cms/sitepress.php');
    }

    public function setTranslatedPostTypeReadingSettings(array|bool $postTypeReadingSettings): array|bool
    {
        if ($postTypeReadingSettings === [] || $postTypeReadingSettings === false) {
            return $postTypeReadingSettings;
        }

        $currentLanguage = isset($_GET['lang']) ? sanitize_text_field(wp_unslash($_GET['lang'])) : apply_filters('wpml_current_language', null);

        foreach ($postTypeReadingSettings as $postType => $value) {
            $returnOriginalIfMissing = true;
            $postTypeReadingSettings[$postType] = $this->getWpmlObjectId($value, $postType, $returnOriginalIfMissing, $currentLanguage);
        }

        return $postTypeReadingSettings;
    }

    public function setPostTypeTranslationStrings(): void
    {
        $optionsReadingPostTypes = OptionsReadingPostTypes::getInstance()->getOptions();
        $optionsPermalinksPostTypes = OptionsPermalinksPostTypes::getInstance()->getOptions();
        $languages = apply_filters('wpml_active_languages', [], 'skip_missing=0');

        if ($optionsReadingPostTypes === [] || $optionsReadingPostTypes === false || ($optionsPermalinksPostTypes === false || $optionsPermalinksPostTypes === []) || empty($languages)) {
            return;
        }

        $defaultLanguageCode = apply_filters('wpml_default_language', null);

        $translations = [];
        foreach ($optionsReadingPostTypes as $postType => $postId) {
            foreach ($languages as $language) {
                $returnOriginalIfMissing = true;
                $wpmlObjectId = $this->getWpmlObjectId($postId, null, $returnOriginalIfMissing, $language['code']);
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

        if ($optionsPermalinksTaxonomies === [] || $optionsPermalinksTaxonomies === false || empty($languages) || empty($defaultLanguageCode)) {
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

        if ($optionsReadingPostTypes === [] || $optionsReadingPostTypes === false) {
            return $rules;
        }

        $languages = apply_filters('wpml_active_languages', [], 'skip_missing=0');
        $currentLanguage = apply_filters('wpml_current_language', null);

        foreach ($optionsReadingPostTypes as $postType => $postId) {
            $currentLanguageArchiveUri = $this->getPageForArchiveUri($postId, $currentLanguage);
            foreach ($languages as $language) {
                $languageArchiveUri = $this->getPageForArchiveUri($postId, $language['code']);
                if ($currentLanguageArchiveUri === $languageArchiveUri || $currentLanguageArchiveUri === '' || $languageArchiveUri === '') {
                    continue;
                }

                $updatedRules = [];
                foreach ($rules as $key => $rule) {
                    // Always add the original rule
                    $updatedRules[$key] = $rule;

                    if (str_contains($key, $currentLanguageArchiveUri)) {
                        $newKey = str_replace($currentLanguageArchiveUri, $languageArchiveUri, $key);
                        $updatedRules[$newKey] = $rule;

                        $translatedPermalinkBaseUri = $this->getTranslatedPermalinkBaseUri($currentLanguageArchiveUri, $postType, $language['code']);
                        if ($translatedPermalinkBaseUri !== null) {
                            $newKey = str_replace($currentLanguageArchiveUri, $translatedPermalinkBaseUri, $key);
                            $updatedRules[$newKey] = $rule;
                        }
                    }

                    if (str_contains($key, $languageArchiveUri)) {
                        $newKey = str_replace($languageArchiveUri, $currentLanguageArchiveUri, $key);
                        $updatedRules[$newKey] = $rule;

                        $translatedPermalinkBaseUri = $this->getTranslatedPermalinkBaseUri($currentLanguageArchiveUri, $postType, $language['code']);
                        if ($translatedPermalinkBaseUri !== null) {
                            $newKey = str_replace($languageArchiveUri, $translatedPermalinkBaseUri, $key);
                            $updatedRules[$newKey] = $rule;
                        }
                    }
                }

                $rules = $updatedRules;
            }
        }
        return $rules;
    }

    private function getTranslatedPermalinkBaseUri(string $baseUri, string $postType, string $language): string|null
    {
        $defaultLanguage = apply_filters('wpml_default_language', null);
        $baseUriDefaultLanguage = apply_filters('wpml_translate_single_string', $baseUri, 'WordPress', 'URL slug: ' . $postType, $defaultLanguage);
        $baseUriCurrentLanguage = apply_filters('wpml_translate_single_string', $baseUri, 'WordPress', 'URL slug: ' . $postType, $language);

        if ($baseUriDefaultLanguage === $baseUriCurrentLanguage) {
            return null;
        }

        return $baseUriCurrentLanguage;
    }

    public function getPostTypeLink(string $postLink, WP_Post $wpPost): string
    {
        $optionsPermalinksPostTypes = OptionsPermalinksPostTypes::getInstance()->getOptions();
        $postType = get_post_field('post_type', $wpPost);

        $optionsReadingPostTypes = OptionsReadingPostTypes::getInstance()->getOptions();

        if ($optionsReadingPostTypes === [] || $optionsReadingPostTypes === false) {
            return trailingslashit($postLink);
        }

        $pageForArchiveId = $optionsReadingPostTypes[$postType] ?? null;

        if (!$pageForArchiveId) {
            return trailingslashit($postLink);
        }

        $postLanguageDetails = apply_filters('wpml_post_language_details', null, $wpPost->ID);
        $postLanguage = $postLanguageDetails['language_code'];
        $defaultLanguage = apply_filters('wpml_default_language', null);

        if ($optionsPermalinksPostTypes === false || $optionsPermalinksPostTypes === []) {
            $pageForArchiveUriDefaultLanguage = $this->getPageForArchiveUri($pageForArchiveId, $defaultLanguage);
            $pageForArchiveUriCurrentLanguage = $this->getPageForArchiveUri($pageForArchiveId, $postLanguage);

            if ($pageForArchiveUriDefaultLanguage !== $pageForArchiveUriCurrentLanguage) {
                return str_replace($pageForArchiveUriDefaultLanguage, $pageForArchiveUriCurrentLanguage, $postLink);
            }
        }

        if ($postLanguage === $defaultLanguage) {
            return trailingslashit($postLink);
        }

        $baseUri = $optionsPermalinksPostTypes[$postType] ?? null;
        if (!$baseUri) {
            return trailingslashit($postLink);
        }

        // Get translation of the base URI
        $baseUriDefaultLanguage = apply_filters('wpml_translate_single_string', $baseUri, 'WordPress', 'URL slug: ' . $postType, $defaultLanguage);
        $baseUriCurrentLanguage = apply_filters('wpml_translate_single_string', $baseUri, 'WordPress', 'URL slug: ' . $postType, $postLanguage);
        $postLink = str_replace($baseUriDefaultLanguage, $baseUriCurrentLanguage, $postLink);

        return trailingslashit($postLink);
    }

    public function getPostTypeArchiveLink(string $link, string $postType): string
    {
        $optionsReadingPostTypes = OptionsReadingPostTypes::getInstance()->getOptions();

        if ($optionsReadingPostTypes === [] || $optionsReadingPostTypes === false) {
            return trailingslashit($link);
        }

        $pageForArchiveId = $optionsReadingPostTypes[$postType] ?? null;

        if ($pageForArchiveId === null) {
            return trailingslashit($link);
        }

        $currentLanguage = apply_filters('wpml_current_language', null);

        if (empty($currentLanguage)) {
            return trailingslashit($link);
        }

        $pageForArchiveUri = $this->getPageForArchiveUri($pageForArchiveId, $currentLanguage);
        if ($pageForArchiveUri === '') {
            return '';
        }

        return apply_filters('wpml_permalink', home_url($pageForArchiveUri), $currentLanguage);
    }

    public function setWpmlLsLanguageUrls(string $url, array $langs): string
    {
        $optionsReadingPostTypes = OptionsReadingPostTypes::getInstance()->getOptions();

        if ($optionsReadingPostTypes === [] || $optionsReadingPostTypes === false) {
            return trailingslashit($url);
        }

        foreach ($optionsReadingPostTypes as $postType => $postId) {
            if (!is_post_type_archive($postType)) {
                continue;
            }

            $returnOriginalIfMissing = false;
            $wpmlObjectId = $this->getWpmlObjectId($postId, $postType, $returnOriginalIfMissing, $langs['code']);
            $pageUri = get_page_uri($wpmlObjectId);
            return trailingslashit(apply_filters('wpml_permalink', home_url($pageUri), $langs['code']));
        }

        return trailingslashit($url);
    }

    public function setWpmlAlternateHrefLang(string $url, string $code): string
    {

        $optionsReadingPostTypes = OptionsReadingPostTypes::getInstance()->getOptions();

        if ($optionsReadingPostTypes === [] || $optionsReadingPostTypes === false) {
            return trailingslashit($url);
        }

        foreach ($optionsReadingPostTypes as $postType => $postId) {
            if (!is_post_type_archive($postType)) {
                continue;
            }

            $returnOriginalIfMissing = false;
            $wpmlObjectId = $this->getWpmlObjectId($postId, $postType, $returnOriginalIfMissing, $code);
            $pageUri = get_page_uri($wpmlObjectId);
            return trailingslashit(apply_filters('wpml_permalink', home_url($pageUri), $code));
        }

        return trailingslashit($url);
    }

    public function setIcLsLanguages(array $languages): array
    {
        if (is_admin()) {
            return $languages;
        }

        if (is_404()) {
            return [];
        }

        $optionsReadingPostTypes = OptionsReadingPostTypes::getInstance()->getOptions();

        if ($optionsReadingPostTypes === [] || $optionsReadingPostTypes === false) {
            return $this->addTrailingSlashToIcLsLanguagesUrls($languages);
        }

        $postId = get_the_ID();

        // Custom post type single pages handling
        if (is_singular()) {
            $postType = get_post_field('post_type', $postId);
            $isPage = ($postType === 'page');
            $isCustomPostTypeSingle = ($postType !== 'post' && $postType !== 'page');

            if ($isPage || !$isCustomPostTypeSingle) {
                return $this->addTrailingSlashToIcLsLanguagesUrls($languages);
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

            return $this->addTrailingSlashToIcLsLanguagesUrls($languages);
        }

        // Archive pages handling
        foreach ($optionsReadingPostTypes as $postType => $postId) {
            if (!is_post_type_archive($postType) || empty($postId)) {
                continue;
            }

            foreach ($languages as $key => $language) {
                $returnOriginalIfMissing = false;
                $wpmlObjectId = $this->getWpmlObjectId($postId, $postType, $returnOriginalIfMissing, $language['code']);
                $pageUri = get_page_uri($wpmlObjectId);
                $languages[$key]['url'] = apply_filters('wpml_permalink', home_url($pageUri), $language['code']);
            }
        }

        return $this->addTrailingSlashToIcLsLanguagesUrls($languages);
    }

    private function addTrailingSlashToIcLsLanguagesUrls(array $languages): array
    {
        foreach ($languages as $key => $language) {
            if (!isset($language['url']) || !is_string($language['url'])) {
                continue;
            }

            $languages[$key]['url'] = trailingslashit($language['url']);
        }

        return $languages;
    }

    public function redirectTo404IfArchivePageNotFoundInCurrentLanguage(): void
    {

        global $wp_query;
        $queriedPostType = $wp_query->query_vars['post_type'];

        $isPostTypeArchive = is_post_type_archive($queriedPostType);
        if (!$isPostTypeArchive) {
            return;
        }

        $supportedPostTypes = OptionsReadingPostTypes::getInstance()->getOptions();

        if ($supportedPostTypes === [] || $supportedPostTypes === false) {
            return;
        }

        $pageForArchiveId = $supportedPostTypes[$queriedPostType] ?? null;
        if (!$pageForArchiveId) {
            return;
        }

        $currentLanguage = apply_filters('wpml_current_language', null);
        $returnOriginalIfMissing = false;
        $getWpmlObjectId = $this->getWpmlObjectId($pageForArchiveId, $queriedPostType, $returnOriginalIfMissing, $currentLanguage);

        if ($getWpmlObjectId) {
            return;
        }

        $wp_query->set_404();
        status_header(404);
        nocache_headers();
    }

    public function setOptionValueToDefaultLanguage($value, $old_value): array|string|bool
    {
        if ($value === [] || $value === false) {
            return $value;
        }

        if (json_encode($old_value) === json_encode($value)) {
            return $value;
        }

        global $wpdb;

        $defaultLanguage = apply_filters('wpml_default_language', null);

        if (!$defaultLanguage) {
            return $value;
        }

        // Inline function to get the default language page directly from the database, due to the WPML API not being available at this point.
        $getDefaultLanguagePage = function ($pageId) use ($wpdb, $defaultLanguage) {
            $query = $wpdb->prepare(
                "SELECT trid, element_id
                FROM {$wpdb->prefix}icl_translations
                WHERE element_id = %d AND element_type = %s",
                $pageId,
                'post_page'
            );
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared --its prepared at $query
            $result = $wpdb->get_row($query);

            if (!$result) {
                return $pageId;
            }

            $query = $wpdb->prepare(
                "SELECT element_id
                FROM {$wpdb->prefix}icl_translations
                WHERE trid = %d AND language_code = %s AND element_type = %s",
                $result->trid,
                $defaultLanguage,
                'post_page'
            );
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared --its prepared at $query
            $defaultPageId = $wpdb->get_var($query);

            return $defaultPageId ? $defaultPageId : $pageId;
        };

        if (is_string($value) || is_numeric($value)) {
            return (string) $getDefaultLanguagePage($value);
        }

        if (is_array($value)) {
            foreach ($value as $postType => $pageId) {
                $value[$postType] = (string) $getDefaultLanguagePage($pageId);
            }
        }

        return $value;
    }

    private function getPageForArchiveUri(string|int|null|bool $pageForArchiveId = null, string|null $languageCode = null): string
    {

        $returnOriginalIfMissing = false;
        $wpmlObjectId = $this->getWpmlObjectId($pageForArchiveId, null, $returnOriginalIfMissing, $languageCode);
        $pageUri = get_page_uri($wpmlObjectId);
        $wpmlPermalink = apply_filters('wpml_permalink', home_url($pageUri), $languageCode);
        $wpmlUri = trim(wp_make_link_relative($wpmlPermalink), '/');

        $wpmlUri = explode('/', $wpmlUri);
        if ($wpmlUri[0] === $languageCode) {
            array_shift($wpmlUri);
        }

        return implode('/', $wpmlUri);
    }

    private function getWpmlObjectId(string|int|null|bool $postId = 0, string|null $object = null, bool $returnOriginalIfMissing = false, string|null $language = null): string|int|null
    {
        if ($object === null || $object === '' || $object === '0') {
            $isPage = (get_post_field('post_type', $postId) === 'page');
            if ($isPage) {
                $object = get_post_type($postId);
            }

            $object = get_post_type($postId) ?: null;
        }

        return apply_filters('wpml_object_id', $postId, $object, $returnOriginalIfMissing, $language);
    }
}
