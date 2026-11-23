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
    private array $singleRewriteRules = [];

    public function __construct()
    {
        if (self::isWpmlActive()) {
            // Modifies the stored options for the post type archive pages based on the current language.
            add_filter('ptatap_post_type_reading_settings', [$this, 'setTranslatedPostTypeReadingSettings'], 10, 1);
            add_filter('wp_dropdown_pages', [$this, 'addMissingTranslationToArchivePageDropdown'], 10, 2);

            add_filter('init', [$this, 'setPostTypeTranslationStrings'], 10);
            add_filter('init', [$this, 'setTaxonomyTranslationStrings'], 15);

            add_action('registered_post_type', [$this, 'trackPostTypeRewriteRules']);
            add_filter('rewrite_rules_array', [$this, 'setRewriteRulesArray'], PHP_INT_MAX, 1);
            add_filter('post_type_link', [$this, 'getPostTypeLink'], 10, 3);
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
                $translations[$postType][$language['code']] = [
                    'permalink' => $optionsPermalinksPostTypes[$postType]
                        ?? $this->getPageForArchiveUri($postId, $language['code'], true)
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

    public function trackPostTypeRewriteRules(string $postType): void
    {
        if (empty(OptionsReadingPostTypes::getInstance()->getOptions()[$postType])) {
            return;
        }

        add_filter($postType . '_rewrite_rules', function (array $rules) use ($postType): array {
            // Core groups the single-post rules here, including feeds and attachment rules.
            $this->singleRewriteRules[$postType] = $rules;
            return $rules;
        }, PHP_INT_MAX);
    }

    public function setRewriteRulesArray(array $rules): array
    {
        $assignments = OptionsReadingPostTypes::getInstance()->getOptions() ?: [];
        $customBases = OptionsPermalinksPostTypes::getInstance()->getOptions() ?: [];
        $languages = apply_filters('wpml_active_languages', [], 'skip_missing=0');

        foreach ($assignments as $postType => $postId) {
            $type = get_post_type_object($postType);
            if (!$type || !is_array($type->rewrite) || !$type->has_archive || empty($postId)) {
                continue;
            }

            $prefix = $this->getArchiveRewritePrefix($postType);
            $archiveBase = $type->has_archive === true ? $type->rewrite['slug'] : $type->has_archive;
            $archiveQuery = 'index.php?post_type=' . $postType;
            $singleRules = $this->singleRewriteRules[$postType] ?? [];
            $archiveBases = [];
            $singleBases = [];
            foreach ($languages as $language) {
                $code = $language['code'];
                $archiveUri = $this->getPageForArchiveUri($postId, $code);
                $archiveBases[] = $archiveUri;
                $singleBases[] = !empty($customBases[$postType])
                    ? $this->translatePermalinkBase($customBases[$postType], $postType, $code)
                    : $archiveUri;
            }

            $updatedRules = [];
            foreach ($rules as $key => $rule) {
                $updatedRules[$key] = $rule;
                if (($singleRules[$key] ?? null) === $rule) {
                    $source = $prefix . $type->rewrite['slug'];
                    $targets = $singleBases;
                } elseif (
                    $rule === $archiveQuery ||
                    str_starts_with($rule, $archiveQuery . '&paged=') ||
                    str_starts_with($rule, $archiveQuery . '&feed=')
                ) {
                    $source = $prefix . $archiveBase;
                    $targets = $archiveBases;
                } else {
                    continue;
                }

                $sourceLength = strlen($source);
                if (!str_starts_with($key, $source) || ($key[$sourceLength] ?? '') !== '/') {
                    continue;
                }

                foreach ($targets as $target) {
                    if ($target === '') {
                        continue;
                    }
                    $translatedKey = $prefix . $target . substr($key, $sourceLength);
                    if (!isset($rules[$translatedKey]) && !isset($updatedRules[$translatedKey])) {
                        $updatedRules[$translatedKey] = $rule;
                    }
                }
            }
            $rules = $updatedRules;
        }

        $this->singleRewriteRules = [];
        return $rules;
    }

    private function translatePermalinkBase(string $base, string $postType, string $language): string
    {
        return apply_filters('wpml_translate_single_string', $base, 'WordPress', 'URL slug: ' . $postType, $language);
    }

    public function getPostTypeLink(string $postLink, WP_Post $wpPost, bool $leaveName = false): string
    {
        $optionsPermalinksPostTypes = OptionsPermalinksPostTypes::getInstance()->getOptions();
        $postType = get_post_field('post_type', $wpPost);

        $optionsReadingPostTypes = OptionsReadingPostTypes::getInstance()->getOptions();

        if ($optionsReadingPostTypes === [] || $optionsReadingPostTypes === false) {
            return $postLink;
        }

        $pageForArchiveId = $optionsReadingPostTypes[$postType] ?? null;

        if (!$pageForArchiveId) {
            return $postLink;
        }

        $postLanguageDetails = apply_filters('wpml_post_language_details', null, $wpPost->ID);
        $postLanguage = $postLanguageDetails['language_code'] ?? null;
        $defaultLanguage = apply_filters('wpml_default_language', null);

        if (!$postLanguage || !$defaultLanguage || $postLanguage === $defaultLanguage) {
            return $postLink;
        }

        $base = $optionsPermalinksPostTypes[$postType] ?? '';
        $originalBase = $base
            ? $this->translatePermalinkBase($base, $postType, $defaultLanguage)
            : $this->getPageForArchiveUri($pageForArchiveId, $defaultLanguage);
        $translatedBase = $base
            ? $this->translatePermalinkBase($base, $postType, $postLanguage)
            : $this->getPageForArchiveUri($pageForArchiveId, $postLanguage);
        if ($originalBase === '' || $translatedBase === '' || $originalBase === $translatedBase) {
            return $postLink;
        }

        $postPath = is_post_type_hierarchical($postType) ? get_page_uri($wpPost) : $wpPost->post_name;
        if ($leaveName) {
            $postPath = '%' . $postType . '%';
        }

        // Match the full post path, including ancestors, without touching the URL suffix or WordPress's slash.
        $path = wp_parse_url($postLink, PHP_URL_PATH);
        if (!$postPath || !is_string($path)) {
            return $postLink;
        }

        $pathWithoutSlash = untrailingslashit($path);
        $baseAndPost = '/' . $originalBase . '/' . $postPath;
        if (!str_ends_with($pathWithoutSlash, $baseAndPost)) {
            return $postLink;
        }

        $offset = strcspn($postLink, '?#') - strlen($path) + strlen($pathWithoutSlash) - strlen($baseAndPost);
        return substr_replace($postLink, '/' . $translatedBase, $offset, strlen('/' . $originalBase));
    }

    public function getPostTypeArchiveLink(string $link, string $postType): string
    {
        $pageForArchiveId = OptionsReadingPostTypes::getInstance()->getOptions()[$postType] ?? null;
        if (!$pageForArchiveId) {
            return $link;
        }

        $currentLanguage = apply_filters('wpml_current_language', null);

        if (empty($currentLanguage)) {
            return $link;
        }

        $pageForArchiveUri = $this->getPageForArchiveUri($pageForArchiveId, $currentLanguage);
        if ($pageForArchiveUri === '') {
            return '';
        }

        return $this->hasPrettyArchiveLink($postType)
            ? $this->getTranslatedArchiveUrl($postType, $pageForArchiveUri, $currentLanguage)
            : $link;
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

        global $wp_the_query;

        // WPML saved the archive before template_redirect; its switcher must see the resulting 404.
        if ($wp_the_query instanceof WP_Query && $wp_the_query->is_404()) {
            $query->set_404();
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
        // A 404 may offer navigation fallbacks, but they are not equivalent content for hreflang.
        if (!is_admin() && is_404()) {
            return [];
        }

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
            return $url;
        }

        foreach ($optionsReadingPostTypes as $postType => $postId) {
            if (!is_post_type_archive($postType) || empty($postId)) {
                continue;
            }

            $pageUri = $this->getPageForArchiveUri($postId, $code);
            if ($pageUri === '') {
                return '';
            }

            return $this->hasPrettyArchiveLink($postType)
                ? $this->getTranslatedArchiveUrl($postType, $pageUri, $code)
                : $this->getPlainTranslatedArchiveUrl($postType, $code);
        }

        return $url;
    }

    public function setIcLsLanguages(array $languages): array
    {
        global $wp_query, $wp_rewrite;

        if (is_admin() || !$wp_query instanceof WP_Query || !$this->isTranslatedPageArchiveQuery($wp_query)) {
            return $languages;
        }

        $frontPageId = get_option('show_on_front') === 'page' ? get_option('page_on_front') : 0;
        $options = OptionsReadingPostTypes::getInstance()->getOptions();
        if ($options === [] || $options === false) {
            return $languages;
        }

        foreach ($options as $postType => $pageId) {
            if (!$wp_query->is_post_type_archive($postType) || empty($pageId)) {
                continue;
            }

            $hasPrettyLink = $this->hasPrettyArchiveLink($postType);
            $hasDifferentPrefix = $hasPrettyLink
                && $this->getArchiveRewritePrefix($postType) !== ltrim($wp_rewrite->root, '/');
            if ($hasPrettyLink && !$frontPageId && !$hasDifferentPrefix) {
                continue;
            }

            foreach ($languages as $key => $language) {
                // Native omission and missing-translation fallback destinations must remain untouched.
                if (!empty($language['missing'])) {
                    continue;
                }

                $code = $language['code'];
                $translatedPageId = $this->getWpmlObjectId($pageId, 'page', false, $code);
                $translatedFrontPageId = $frontPageId
                    ? $this->getWpmlObjectId($frontPageId, 'page', false, $code)
                    : null;
                $isFrontPage = (int) $translatedPageId === (int) $translatedFrontPageId;
                if (!$translatedPageId || ($hasPrettyLink && !$isFrontPage && !$hasDifferentPrefix)) {
                    continue;
                }

                if (!$hasPrettyLink) {
                    $languages[$key]['url'] = $this->getPlainArchiveSwitcherUrl(
                        $language['url'],
                        $postType,
                        $code,
                        (int) $translatedPageId,
                        $isFrontPage
                    );
                    continue;
                }

                $pageUri = get_page_uri($translatedPageId);
                if (!$pageUri) {
                    continue;
                }

                if (!$isFrontPage) {
                    // Match the translated Page path without resolving its permalink in the current language.
                    $pageUrl = home_url(user_trailingslashit($wp_rewrite->root . $pageUri, 'page'));
                    $pageUrl = apply_filters('wpml_permalink', $pageUrl, $code);
                    if (explode('?', $language['url'], 2)[0] !== explode('?', $pageUrl, 2)[0]) {
                        continue;
                    }
                }

                // The assigned Page may be the homepage or lack the post type's rewrite front.
                $archiveUrl = $this->getTranslatedArchiveUrl($postType, $pageUri, $code);
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

    private function getPlainArchiveSwitcherUrl(
        string $url,
        string $postType,
        string $language,
        int $pageId,
        bool $isFrontPage
    ): string {
        $archiveUrl = $this->getPlainTranslatedArchiveUrl($postType, $language);
        $archiveBase = untrailingslashit(substr($archiveUrl, 0, strcspn($archiveUrl, '?#')));
        $switcherBase = untrailingslashit(substr($url, 0, strcspn($url, '?#')));
        wp_parse_str(wp_parse_url($url, PHP_URL_QUERY) ?: '', $args);

        // Rewrite WPML's native Page destination only, leaving custom links alone.
        $targetPageId = $args['page_id'] ?? null;
        $isAssignedPage = $targetPageId === (string) $pageId || ($isFrontPage && $targetPageId === null);
        if ($switcherBase !== $archiveBase || !$isAssignedPage) {
            return $url;
        }

        unset($args['page_id'], $args['post_type']);
        $fragment = wp_parse_url($url, PHP_URL_FRAGMENT);
        $suffix = is_string($fragment) && $fragment !== '' ? '#' . $fragment : '';
        return add_query_arg(urlencode_deep($args), $archiveUrl) . $suffix;
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

        $archiveUri = $this->getPageForArchiveUri($pageForArchiveId, $currentLanguage);
        if (
            $archiveUri !== '' &&
            (!$this->hasPrettyArchiveLink($queriedPostType) || str_contains($wp->request, $archiveUri))
        ) {
            return;
        }

        $wp_query->set_404();
        // Discard request-local switcher URLs built before this request became a 404.
        (new \WPML_WP_Cache('ls_languages'))->flush_group_cache();
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

    private function hasPrettyArchiveLink(string $postType): bool
    {
        return get_option('permalink_structure') && is_array(get_post_type_object($postType)->rewrite ?? null);
    }

    private function getArchiveRewritePrefix(string $postType): string
    {
        global $wp_rewrite;

        // Match WordPress archive routing: the root can contain index.php even without the front.
        $useFront = get_post_type_object($postType)->rewrite['with_front'] ?? false;
        return ltrim($useFront ? $wp_rewrite->front : $wp_rewrite->root, '/');
    }

    private function getTranslatedArchiveUrl(string $postType, string $pageUri, string $language): string
    {
        $archivePath = $this->getArchiveRewritePrefix($postType) . $pageUri;
        $archiveUrl = home_url(user_trailingslashit($archivePath, 'post_type_archive'));
        return apply_filters('wpml_permalink', $archiveUrl, $language);
    }

    private function getPlainTranslatedArchiveUrl(string $postType, string $language): string
    {
        // Localize the base first so WPML does not resolve the archive query to its assigned Page.
        $home = apply_filters('wpml_permalink', home_url('/'), $language);
        $pathEnd = strcspn($home, '?#');
        $base = trailingslashit(substr($home, 0, $pathEnd)) . substr($home, $pathEnd);
        return add_query_arg('post_type', $postType, $base);
    }

    private function getPageForArchiveUri(
        string|int|null|bool $pageForArchiveId,
        ?string $languageCode,
        bool $returnOriginalIfMissing = false
    ): string {
        $translatedPageId = $this->getWpmlObjectId($pageForArchiveId, 'page', $returnOriginalIfMissing, $languageCode);

        // A rewrite slug is relative to the site, with no language directory, host, or query string.
        return $translatedPageId ? (string) get_page_uri($translatedPageId) : '';
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
