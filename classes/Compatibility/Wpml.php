<?php

namespace Ptatap\Compatibility;

use Ptatap\Features\OptionsPermalinksPostTypes;
use Ptatap\Features\OptionsPermalinksTaxonomies;
use Ptatap\Features\OptionsReadingPostTypes;
use WP_Post;
use WP_Query;

defined('ABSPATH') || exit;

final class Wpml
{
    public function __construct()
    {
        if (self::isWpmlActive()) {
            // Modifies the stored options for the post type archive pages based on the current language.
            add_filter('ptatap_post_type_reading_settings', [$this, 'setTranslatedPostTypeReadingSettings'], 10, 1);
            add_filter('wp_dropdown_pages', [$this, 'addMissingTranslationToArchivePageDropdown'], 10, 2);

            add_filter('init', [$this, 'setPostTypeTranslationStrings'], 10);
            add_filter('init', [$this, 'setTaxonomyTranslationStrings'], 15);

            add_filter('rewrite_rules_array', [$this, 'setRewriteRulesArray'], PHP_INT_MAX, 1);
            add_filter('post_type_link', [$this, 'getPostTypeLink'], 10, 2);
            add_filter('post_type_archive_link', [$this, 'getPostTypeArchiveLink'], 10, 2);

            add_filter('wpml_get_ls_translations', [$this, 'setWpmlLsTranslations'], 10, 2);
            add_filter('icl_ls_languages', [$this, 'setIcLsLanguages'], 10, 1);
            add_filter('wpml_ls_language_url', [$this, 'setWpmlLsLanguageUrls'], 10, 2);
            add_filter('wpml_head_langs', [$this, 'setWpmlHeadLangs'], 10, 1);
            add_filter('wpml_alternate_hreflang', [$this, 'setWpmlAlternateHrefLang'], 10, 2);

            add_action('template_redirect', [$this, 'redirectTo404IfArchivePageNotFoundInCurrentLanguage'], 10);
            add_action(
                'pre_update_option_' . OptionsReadingPostTypes::OPTION_NAME,
                [$this, 'setOptionValueToDefaultLanguage'],
                PHP_INT_MAX,
                2
            );
        }
    }

    public static function isWpmlActive(): bool
    {
        if (!function_exists('is_plugin_active')) {
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }

        return is_plugin_active('sitepress-multilingual-cms/sitepress.php');
    }

    public function addMissingTranslationToArchivePageDropdown(string $output, array $args): string
    {
        $name = $args['name'] ?? '';
        $selectedPageId = absint($args['selected'] ?? 0);
        $optionNamePrefix = OptionsReadingPostTypes::OPTION_NAME . '[';

        if (!str_starts_with($name, $optionNamePrefix) || !$selectedPageId) {
            return $output;
        }

        if (str_contains($output, sprintf('value="%d"', $selectedPageId))) {
            return $output;
        }

        $currentLanguage = apply_filters('wpml_current_language', null);
        $defaultLanguage = apply_filters('wpml_default_language', null);

        if (!is_string($currentLanguage) || $currentLanguage === 'all' || $currentLanguage === $defaultLanguage) {
            return $output;
        }

        $translatedPageId = apply_filters('wpml_object_id', $selectedPageId, 'page', false, $currentLanguage);
        $pageTitle = get_the_title($selectedPageId);

        if ($translatedPageId || $pageTitle === '') {
            return $output;
        }

        $optionLabel = sprintf(
            /* translators: %s: Title of the archive page. */
            __('%s — translation missing', 'post-type-archive-pages-and-permalink-settings'),
            $pageTitle
        );
        $option = sprintf(
            '<option value="%1$d" selected="selected">%2$s</option>',
            $selectedPageId,
            esc_html($optionLabel)
        );

        return str_replace('</select>', $option . '</select>', $output);
    }

    public function setTranslatedPostTypeReadingSettings(array|bool $postTypeReadingSettings): array|bool
    {
        if ($postTypeReadingSettings === [] || $postTypeReadingSettings === false) {
            return $postTypeReadingSettings;
        }

        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only language selection.
        $currentLanguage = isset($_GET['lang'])
            ? sanitize_text_field(wp_unslash($_GET['lang']))
            : apply_filters('wpml_current_language', null);
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

        foreach ($postTypeReadingSettings as $postType => $value) {
            $returnOriginalIfMissing = true;
            $postTypeReadingSettings[$postType] = $this->getWpmlObjectId(
                $value,
                $postType,
                $returnOriginalIfMissing,
                $currentLanguage
            );
        }

        return $postTypeReadingSettings;
    }

    public function setPostTypeTranslationStrings(): void
    {
        $optionsReadingPostTypes = OptionsReadingPostTypes::getInstance()->getOptions();
        $optionsPermalinksPostTypes = OptionsPermalinksPostTypes::getInstance()->getOptions();
        $languages = apply_filters('wpml_active_languages', [], 'skip_missing=0');

        if (
            $optionsReadingPostTypes === [] ||
            $optionsReadingPostTypes === false ||
            ($optionsPermalinksPostTypes === false || $optionsPermalinksPostTypes === []) ||
            empty($languages)
        ) {
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

        if (
            $optionsPermalinksTaxonomies === [] ||
            $optionsPermalinksTaxonomies === false ||
            empty($languages) ||
            empty($defaultLanguageCode)
        ) {
            return;
        }

        foreach ($optionsPermalinksTaxonomies as $taxonomy => $slug) {
            foreach ($languages as $language) {
                if ($defaultLanguageCode === $language['code']) {
                    if (!empty($slug)) {
                        do_action(
                            'wpml_register_single_string',
                            'WordPress',
                            'URL ' . $taxonomy . ' tax slug',
                            $slug,
                            true,
                            $language['code']
                        );
                    } else {
                        do_action(
                            'wpml_register_single_string',
                            'WordPress',
                            'URL ' . $taxonomy . ' tax slug',
                            $taxonomy,
                            true,
                            $language['code']
                        );
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
                if (
                    $currentLanguageArchiveUri === $languageArchiveUri ||
                    $currentLanguageArchiveUri === '' ||
                    $languageArchiveUri === ''
                ) {
                    continue;
                }

                $updatedRules = [];
                foreach ($rules as $key => $rule) {
                    // Always add the original rule
                    $updatedRules[$key] = $rule;

                    if (str_contains($key, $currentLanguageArchiveUri)) {
                        $newKey = str_replace($currentLanguageArchiveUri, $languageArchiveUri, $key);
                        $updatedRules[$newKey] = $rule;

                        $translatedPermalinkBaseUri = $this->getTranslatedPermalinkBaseUri(
                            $currentLanguageArchiveUri,
                            $postType,
                            $language['code']
                        );
                        if ($translatedPermalinkBaseUri !== null) {
                            $newKey = str_replace($currentLanguageArchiveUri, $translatedPermalinkBaseUri, $key);
                            $updatedRules[$newKey] = $rule;
                        }
                    }

                    if (str_contains($key, $languageArchiveUri)) {
                        $newKey = str_replace($languageArchiveUri, $currentLanguageArchiveUri, $key);
                        $updatedRules[$newKey] = $rule;

                        $translatedPermalinkBaseUri = $this->getTranslatedPermalinkBaseUri(
                            $currentLanguageArchiveUri,
                            $postType,
                            $language['code']
                        );
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
        $baseUriDefaultLanguage = apply_filters(
            'wpml_translate_single_string',
            $baseUri,
            'WordPress',
            'URL slug: ' . $postType,
            $defaultLanguage
        );
        $baseUriCurrentLanguage = apply_filters(
            'wpml_translate_single_string',
            $baseUri,
            'WordPress',
            'URL slug: ' . $postType,
            $language
        );

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
        $baseUriDefaultLanguage = apply_filters(
            'wpml_translate_single_string',
            $baseUri,
            'WordPress',
            'URL slug: ' . $postType,
            $defaultLanguage
        );
        $baseUriCurrentLanguage = apply_filters(
            'wpml_translate_single_string',
            $baseUri,
            'WordPress',
            'URL slug: ' . $postType,
            $postLanguage
        );
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

    /**
     * Give WPML the assigned page's translations for its switcher-only cloned query.
     * Native skip/home, caller overrides and single-post fallback modes remain WPML's responsibility.
     */
    public function setWpmlLsTranslations(mixed $translations, WP_Query $query): mixed
    {
        if (!$this->isTranslatedPageArchiveQuery($query)) {
            return $translations;
        }

        $options = OptionsReadingPostTypes::getInstance()->getOptions();
        if ($options === [] || $options === false) {
            return $translations;
        }

        global $sitepress;

        foreach ($options as $postType => $pageId) {
            if (!$query->is_post_type_archive($postType) || empty($pageId)) {
                continue;
            }

            $trid = apply_filters('wpml_element_trid', null, $pageId, 'post_page');
            $pages = $trid
                ? apply_filters('wpml_get_element_translations', [], $trid, 'post_page', true, false, true)
                : [];
            $defaultLanguage = apply_filters('wpml_default_language', null);

            foreach ($pages as $code => $page) {
                if (
                    ($page->element_type ?? null) !== 'post_page' ||
                    !$sitepress->get_query_utils()->archive_query_has_posts(
                        $code,
                        $defaultLanguage,
                        null,
                        null,
                        null,
                        $postType
                    )
                ) {
                    unset($pages[$code]);
                }
            }

            // Only WPML's temporary switcher query changes; the archive request stays an archive.
            $query->is_singular = true;
            return $pages;
        }

        return $translations;
    }

    public function setWpmlLsLanguageUrls(string $url, array $langs): string
    {
        // Keep the public callback without overriding WPML's equivalent or fallback destination.
        return $url;
    }

    public function setWpmlHeadLangs(array $languages): array
    {
        global $wp_query;

        if (!$wp_query instanceof WP_Query || !$this->isTranslatedPageArchiveQuery($wp_query)) {
            return $languages;
        }

        $optionsReadingPostTypes = OptionsReadingPostTypes::getInstance()->getOptions();

        if ($optionsReadingPostTypes === [] || $optionsReadingPostTypes === false) {
            return $languages;
        }

        foreach ($optionsReadingPostTypes as $postType => $postId) {
            if (!is_post_type_archive($postType) || empty($postId)) {
                continue;
            }

            foreach ($languages as $key => $language) {
                $translatedPageId = $this->getWpmlObjectId($postId, 'page', false, $language['code']);
                if (!$translatedPageId) {
                    unset($languages[$key]);
                }
            }
        }

        return $languages;
    }

    public function setWpmlAlternateHrefLang(string $url, string $code): string
    {
        global $wp_query;

        if (!$wp_query instanceof WP_Query || !$this->isTranslatedPageArchiveQuery($wp_query)) {
            return $url;
        }

        $optionsReadingPostTypes = OptionsReadingPostTypes::getInstance()->getOptions();

        if ($optionsReadingPostTypes === [] || $optionsReadingPostTypes === false) {
            return trailingslashit($url);
        }

        foreach ($optionsReadingPostTypes as $postType => $postId) {
            if (!is_post_type_archive($postType) || empty($postId)) {
                continue;
            }

            $returnOriginalIfMissing = false;
            $wpmlObjectId = $this->getWpmlObjectId($postId, 'page', $returnOriginalIfMissing, $code);
            if (!$wpmlObjectId) {
                return '';
            }

            $pageUri = get_page_uri($wpmlObjectId);
            return trailingslashit(apply_filters('wpml_permalink', home_url($pageUri), $code));
        }

        return trailingslashit($url);
    }

    public function setIcLsLanguages(array $languages): array
    {
        global $wp_query;

        if (!is_admin() && is_404()) {
            return [];
        }

        if (
            is_admin() ||
            !$wp_query instanceof WP_Query ||
            !$this->isTranslatedPageArchiveQuery($wp_query) ||
            get_option('show_on_front') !== 'page'
        ) {
            return $languages;
        }

        $frontPageId = get_option('page_on_front');
        $options = OptionsReadingPostTypes::getInstance()->getOptions();
        if (!$frontPageId || $options === [] || $options === false) {
            return $languages;
        }

        foreach ($options as $postType => $pageId) {
            if (!$wp_query->is_post_type_archive($postType) || empty($pageId)) {
                continue;
            }

            foreach ($languages as $key => $language) {
                // Native omission and missing-translation fallback destinations must remain untouched.
                if (!empty($language['missing'])) {
                    continue;
                }

                $translatedPageId = $this->getWpmlObjectId($pageId, 'page', false, $language['code']);
                $translatedFrontPageId = $this->getWpmlObjectId($frontPageId, 'page', false, $language['code']);
                if (!$translatedPageId || (int) $translatedPageId !== (int) $translatedFrontPageId) {
                    continue;
                }

                // A front page's permalink is home, but its assigned archive still uses the page URI.
                $archiveUrl = home_url(user_trailingslashit(get_page_uri($translatedPageId), 'post_type_archive'));
                $archiveUrl = apply_filters('wpml_permalink', $archiveUrl, $language['code']);
                $queryString = wp_parse_url($language['url'], PHP_URL_QUERY);
                if (is_string($queryString) && $queryString !== '') {
                    // Retain WPML's language parameter and already encoded copied arguments verbatim.
                    $archiveUrl = explode('?', $archiveUrl, 2)[0] . '?' . $queryString;
                }
                $languages[$key]['url'] = $archiveUrl;
            }
        }

        return $languages;
    }

    private function isTranslatedPageArchiveQuery(WP_Query $query): bool
    {
        return $query->is_post_type_archive()
            && !$query->is_tax() && !$query->is_category() && !$query->is_tag()
            && !$query->is_author() && !$query->is_date() && !$query->is_search()
            && apply_filters('wpml_is_translated_post_type', false, 'page');
    }

    public function redirectTo404IfArchivePageNotFoundInCurrentLanguage(): void
    {

        global $wp_query;
        global $wp;

        if (!$this->isTranslatedPageArchiveQuery($wp_query)) {
            return;
        }

        $queriedPostType = $wp_query->query_vars['post_type'];

        $supportedPostTypes = OptionsReadingPostTypes::getInstance()->getOptions();

        if ($supportedPostTypes === [] || $supportedPostTypes === false) {
            return;
        }

        $pageForArchiveId = $supportedPostTypes[$queriedPostType] ?? null;
        if (!$pageForArchiveId) {
            return;
        }

        $currentLanguage = apply_filters('wpml_current_language', null);

        $translatedPageId = $this->getWpmlObjectId($pageForArchiveId, 'page', false, $currentLanguage);
        if (
            (int) $translatedPageId > 0 &&
            str_contains($wp->request, $this->getPageForArchiveUri($pageForArchiveId, $currentLanguage))
        ) {
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

        // Stored option maps must match in keys, order and scalar value types.
        if ($old_value === $value) {
            return $value;
        }

        $defaultLanguage = apply_filters('wpml_default_language', null);

        if (!$defaultLanguage) {
            return $value;
        }

        // Normalize translation records even when page translation is disabled or pages are not public.
        $getDefaultLanguagePage = function ($pageId) use ($defaultLanguage) {
            $elementId = (int) $pageId;
            $languageDetails = apply_filters(
                'wpml_element_language_details',
                null,
                ['element_id' => $elementId, 'element_type' => 'page']
            );
            $trid = $languageDetails->trid ?? null;

            if (!$trid) {
                return $pageId;
            }

            // Retain empty/non-public records and bypass stale group caches after translation relinking.
            $translations = apply_filters(
                'wpml_get_element_translations',
                [],
                $trid,
                'post_page',
                false,
                true,
                true
            );
            $sourcePages = wp_list_filter($translations, ['element_id' => $elementId, 'element_type' => 'post_page']);
            $defaultPage = $translations[$defaultLanguage] ?? null;

            if (!$sourcePages || ($defaultPage->element_type ?? null) !== 'post_page') {
                return $pageId;
            }

            return $defaultPage->element_id ?: $pageId;
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

    private function getPageForArchiveUri(
        string|int|null|bool $pageForArchiveId = null,
        string|null $languageCode = null
    ): string {

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

    private function getWpmlObjectId(
        string|int|null|bool $postId = 0,
        string|null $object = null,
        bool $returnOriginalIfMissing = false,
        string|null $language = null
    ): string|int|null {
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
