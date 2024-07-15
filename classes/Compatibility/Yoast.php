<?php

namespace Ptatap\Compatibility;

use Ptatap\Features\OptionsReadingPostTypes;

defined('ABSPATH') || exit;

final class Yoast
{
    public function __construct()
    {
        if (in_array('wordpress-seo/wp-seo.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            add_action('edit_form_after_title', [$this, 'renderAdminNoticeClassicEditor']);
            add_action('admin_print_footer_scripts', [$this, 'renderBlockEditorNotice']);
        }
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
                                    <?php echo json_encode('<p><strong>' . esc_html($title) . '</strong></p>' . wp_kses_post($adminNoticeContent)); ?>,
                                    {
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
}
