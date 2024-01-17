<?php

namespace Ptatap\Compatibility;

use Ptatap\Features\OptionsReadingPostTypes;
use WP_Post;

defined('ABSPATH') || exit;

final class Yoast
{
    public function __construct()
    {
        if (in_array('wordpress-seo/wp-seo.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            add_action('add_meta_boxes', [$this, 'removeYoastMetaBoxOnCustomArchivePage'], PHP_INT_MAX, 2);
            add_action('edit_form_after_title', [$this, 'addAdminNoticeOnCustomArchivePage']);
        }
    }

    public function removeYoastMetaBoxOnCustomArchivePage(string $post_type, WP_Post $wpPost): void
    {
        if ($post_type === 'page') {
            $optionsReadingPostTypes = OptionsReadingPostTypes::getInstance()->getOptions();

            if (empty($optionsReadingPostTypes)) {
                return;
            }

            foreach ($optionsReadingPostTypes as $optionReadingPostType) {
                if ((int)$optionReadingPostType === $wpPost->ID) {
                    remove_meta_box('wpseo_meta', 'page', 'normal');
                }
            }
        }
    }

    public function addAdminNoticeOnCustomArchivePage(): void
    {
        global $post;
        $optionsReadingPostTypes = OptionsReadingPostTypes::getInstance()->getOptions();

        if (empty($optionsReadingPostTypes)) {
            return;
        }

        foreach ($optionsReadingPostTypes as $postType => $postTypeArchivePageId) {
            if ((int)$postTypeArchivePageId === $post->ID) {
                $title = 'YOAST SEO meta box is disabled for this Custom Post Type archive page!';
                $postTypeObject = get_post_type_object($postType);

                $slug =  $postTypeObject->rewrite["slug"] !== '' ? $postTypeObject->rewrite["slug"] : $post->post_name;
                $yoastSettingsPageUrl = admin_url('admin.php?page=wpseo_page_settings#/post-type/' . $slug);
                $message = sprintf(
                    __('Change its settings on the %1$sYoast SEO > Content Types > %2$s%3$s page at the %4$s %5$s %6$s %7$s section.', 'flynt'),
                    "<a href='{$yoastSettingsPageUrl}' target='_blank' rel='noopener noreferrer'>",
                    $postTypeObject->labels->name,
                    "</a>",
                    "<strong>",
                    $postTypeObject->labels->name,
                    __(" archive", 'post-type-and-taxonomy-archive-pages'),
                    "</strong>"
                );

                echo '<div class="notice notice-warning"><p><strong>' . esc_html($title) . '</strong></p><p>' . wp_kses_post($message) . '</p></div>';
            }
        }
    }
}
