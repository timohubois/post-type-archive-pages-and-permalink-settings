<?php

namespace Ptatap\Compatibility;

use Ptatap\Features\OptionsReadingPostTypes;

defined('ABSPATH') || exit;

final class Yoast
{
    public function __construct()
    {
        if (!function_exists('is_plugin_active')) {
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }

        if (is_plugin_active('wordpress-seo/wp-seo.php')) {
            add_action('edit_form_after_title', [$this, 'renderAdminNoticeClassicEditor']);
            add_action('admin_print_footer_scripts', [$this, 'renderBlockEditorNotice']);

            add_filter('wpseo_canonical', [$this, 'wpseoCanonical']);
            add_filter('wpseo_next_rel_link', [$this, 'wpseoNextRelLink']);
            add_filter('wpseo_prev_rel_link', [$this, 'wpseoPrevRelLink']);
            add_filter('wpseo_adjacent_rel_url', [$this, 'wpseoAdjacentRelUrl'], 10, 3);
        }
    }

    public function renderAdminNoticeClassicEditor(): void
    {
        global $post;
        $optionsReadingPostTypes = OptionsReadingPostTypes::getInstance()->getOptions();

        if ($optionsReadingPostTypes === [] || $optionsReadingPostTypes === false) {
            return;
        }

        foreach ($optionsReadingPostTypes as $postType => $postTypeArchivePageId) {
            if ((int)$postTypeArchivePageId === $post->ID) {
                $title = $this->getAdminNoticeTitle();
                $postTypeObject = get_post_type_object($postType);
                $slug =  $postTypeObject->rewrite["slug"] !== '' ? $postTypeObject->rewrite["slug"] : $post->post_name;
                $message = $this->getAdminNoticeContent($postType, $slug);

                echo '<div class="notice notice-warning"><p><strong>' . esc_html($title) . '</strong></p><p>' . wp_kses_post($message) . '</p></div>';
            }
        }
    }

    public function renderBlockEditorNotice(): void
    {
        global $post;
        $current_screen = get_current_screen();
        if (method_exists($current_screen, 'is_block_editor') && $current_screen->is_block_editor()) {
            $optionsReadingPostTypes = OptionsReadingPostTypes::getInstance()->getOptions();

            if ($optionsReadingPostTypes === [] || $optionsReadingPostTypes === false) {
                return;
            }

            foreach ($optionsReadingPostTypes as $postType => $postTypeArchivePageId) {
                if ((int)$postTypeArchivePageId === $post->ID) {
                    $title = $this->getAdminNoticeTitle();
                    $postTypeObject = get_post_type_object($postType);
                    $slug =  $postTypeObject->rewrite["slug"] !== '' ? $postTypeObject->rewrite["slug"] : $post->post_name;
                    $adminNoticeContent = $this->getAdminNoticeContent($postType, $slug);

                    ?>
                    <script type="text/javascript">
                        (function($) {
                            $(document).ready(function() {
                                wp.data.dispatch('core/notices').createNotice(
                                    'warning',
                                    <?php echo json_encode('<p><strong>' . esc_html($title) . '</strong></p>' . wp_kses_post($adminNoticeContent)); ?>, {
                                        __unstableHTML: true,
                                        isDismissible: false,
                                    }
                                );
                            });
                        })(jQuery);
                    </script>
                    <?php
                }
            }
        }
    }

    public function wpseoCanonical(string $canonical): string
    {
        if (!is_post_type_archive() && !is_tax()) {
            return $canonical;
        }

        $queriedObject = get_queried_object();
        $taxonomy = $queriedObject->taxonomy ?? null;
        $postType = $taxonomy ? get_taxonomy($taxonomy)->object_type[0] : ($queriedObject->name ?? null);

        $optionsReadingPostTypes = OptionsReadingPostTypes::getInstance()->getOptions();
        if (!isset($optionsReadingPostTypes[$postType])) {
            return $canonical;
        }

        global $wp;
        $url = home_url(add_query_arg($wp->query_vars, $wp->request));
        return $this->getQueriedArchiveUrl($url);
    }

    public function wpseoNextRelLink(string $link): string
    {
        if (!is_post_type_archive() && !is_tax()) {
            return $link;
        }

        $queriedObject = get_queried_object();
        $taxonomy = $queriedObject->taxonomy ?? null;
        $postType = $taxonomy ? get_taxonomy($taxonomy)->object_type[0] : ($queriedObject->name ?? null);

        $optionsReadingPostTypes = OptionsReadingPostTypes::getInstance()->getOptions();
        if (!isset($optionsReadingPostTypes[$postType])) {
            return $link;
        }

        return $this->fixRelLink($link);
    }

    public function wpseoPrevRelLink(string $link): string
    {
        if (!is_post_type_archive() && !is_tax()) {
            return $link;
        }

        $queriedObject = get_queried_object();
        $taxonomy = $queriedObject->taxonomy ?? null;
        $postType = $taxonomy ? get_taxonomy($taxonomy)->object_type[0] : ($queriedObject->name ?? null);

        $optionsReadingPostTypes = OptionsReadingPostTypes::getInstance()->getOptions();
        if (!isset($optionsReadingPostTypes[$postType])) {
            return $link;
        }

        return $this->fixRelLink($link);
    }

    public function wpseoAdjacentRelUrl(?string $url, ?string $rel = null, $presentation = null)
    {
        if (is_null($rel)) {
            return $url;
        }

        if (!is_post_type_archive() && !is_tax()) {
            return $url;
        }

        $queriedObject = get_queried_object();
        $taxonomy = $queriedObject->taxonomy ?? null;
        $postType = $taxonomy ? get_taxonomy($taxonomy)->object_type[0] : ($queriedObject->name ?? null);

        $optionsReadingPostTypes = OptionsReadingPostTypes::getInstance()->getOptions();
        if (!isset($optionsReadingPostTypes[$postType])) {
            return $url;
        }

        global $wp_query;
        $paged = (int) $wp_query->get('paged');
        $isPaged = $paged > 1;

        // If rel=prev and not paged, do not output a prev URL
        if ($rel === 'prev' && !$isPaged) {
            return '';
        }

        // Only reconstruct for rel=prev on page 2 if $url is empty so that archive link is used.
        if ($rel === 'prev' && $paged === 2 && (!$url || $url === '')) {
            $queriedObject = get_queried_object();
            $taxonomy = $queriedObject->taxonomy ?? null;
            $postType = $taxonomy ? get_taxonomy($taxonomy)->object_type[0] : ($queriedObject->name ?? null);

            if ($taxonomy) {
                $url = get_term_link($queriedObject);
            } else {
                $url = get_post_type_archive_link($postType);
            }
        }

        if (empty($url)) {
            return '';
        }

        return $this->getQueriedArchiveUrl($url);
    }

    private function fixRelLink(string $link): string
    {
        if (empty($link)) {
            return $link;
        }

        $urlFromLink = explode('href="', $link);
        $urlFromLink = $urlFromLink[1];
        $urlFromLink = explode('"', $urlFromLink);
        $urlFromLink = $urlFromLink[0];

        if (!is_post_type_archive() && !is_tax()) {
            return $link;
        }

        $newUrl = $this->getQueriedArchiveUrl($urlFromLink);

        if ($newUrl === $urlFromLink) {
            return $link;
        }

        return preg_replace('/href="[^"]*"/', 'href="' . $newUrl . '"', $link);
    }

    private function getQueriedArchiveUrl(string $originalUrl): string
    {
        $queriedObject = get_queried_object();
        if (is_null($queriedObject)) {
            return $originalUrl;
        }

        $taxonomy = $queriedObject->taxonomy ?? null;
        $postType = get_taxonomy($taxonomy)->object_type[0] ?? $queriedObject->name ?? null;

        if (!is_null($taxonomy)) {
            $archiveUrl = get_term_link($queriedObject);
        } else {
            $archiveUrl = get_post_type_archive_link($postType);
        }

        // Remove all existing query params from the archive url, they get may added later.
        $archiveUrl = parse_url($archiveUrl);
        unset($archiveUrl['query']);
        $archiveUrl = $archiveUrl['scheme'] . '://' . $archiveUrl['host'] . $archiveUrl['path'];
        $archiveUrl = rtrim($archiveUrl, '/');

        global $wp_rewrite;
        $pagedPaginationBase = $wp_rewrite->pagination_base;
        $pagedPaginationBase = untrailingslashit($pagedPaginationBase);

        $pageNumberFromUrl = explode($pagedPaginationBase . '/', $originalUrl);
        $pageNumberFromUrl = (int)$pageNumberFromUrl[count($pageNumberFromUrl) - 1];

        $isUrlPaged = $pageNumberFromUrl > 0;
        if ($isUrlPaged) {
            $newLink = trailingslashit(trailingslashit($archiveUrl) . $pagedPaginationBase . '/' . $pageNumberFromUrl);
        } else {
            $newLink = trailingslashit($archiveUrl);
        }

        return $this->maybeAddQueryStringToUrl($newLink);
    }

    private function maybeAddQueryStringToUrl(string $link): string
    {
        global $wp;
        $queryVars = $wp->query_vars;

        foreach ($queryVars as $key => $value) {
            if (isset($_GET[$key])) {
                $link = add_query_arg($key, $value, $link);
            }
        }

        return $link;
    }

    private function getAdminNoticeTitle(): string
    {
        return __('Yoast SEO settings are not applied because this is a Custom Post Type archive page and they are handled different', 'post-type-archive-pages-and-permalink-settings');
    }

    private function getAdminNoticeContent($postType, $slug): string
    {
        $postTypeObject = get_post_type_object($postType);
        $yoastSettingsPageUrl = admin_url('admin.php?page=wpseo_page_settings#/post-type/' . $slug);
        $message = sprintf(
            /* translators %1$s: open <a> tag, %2$s: post type name, %3$s: close </a> tag, %4$s: open <strong> tag, %5$s: post type name, %6$s: archive, %7$s: close </strong> tag */
            __('Change settings at %1$sYoast SEO > Content Types > %2$s%3$s at the %4$s %5$s %6$s %7$s section.', 'flynt'),
            sprintf("<a href='%s' target='_blank' rel='noopener noreferrer'>", $yoastSettingsPageUrl),
            $postTypeObject->labels->name,
            "</a>",
            "<strong>",
            $postTypeObject->labels->name,
            __("archive", 'post-type-archive-pages-and-permalink-settings'),
            "</strong>"
        );

        if (\Ptatap\Compatibility\WPML::isWpmlActive()) {
            $message .= ' ' . sprintf(
                /* translators %1$s: open <a> tag, %2$s: close </a> tag */
                __('And then translate them at %1$sWPML String Translation%2$s page (Domain: admin_texts_wpseo_titles).', 'flynt'),
                sprintf("<a href='%s' target='_blank' rel='noopener noreferrer'>", admin_url('admin.php?page=wpml-string-translation/menu/string-translation.php&strings_per_page=100&context=admin_texts_wpseo_titles')),
                "</a>"
            );
        }

        $message .= '<br>' . __('This is the native behavior how Custom Post Type Archives are handled in this cases, currently.', 'post-type-archive-pages-and-permalink-settings');

        return $message;
    }
}
