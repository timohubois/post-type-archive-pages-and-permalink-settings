<?php

namespace Ptatap\Features;

defined('ABSPATH') || exit;

use Ptatap\Features\SupportedPostTypes;

final class OptionsPermalinksPostTypes
{
    public const OPTION_NAME = 'ptatap_post_type_permalink';

    private static ?\Ptatap\Features\OptionsPermalinksPostTypes $instance = null;

    private $options = false;

    public function __construct()
    {
        $this->options = get_option(self::OPTION_NAME);

        add_action('load-options-permalink.php', [$this, 'addSettings']);
    }

    public static function getInstance(): OptionsPermalinksPostTypes
    {
        if (!self::$instance instanceof \Ptatap\Features\OptionsPermalinksPostTypes) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getOptions(): array|bool
    {
        if ($this->isOptionsEmpty($this->options)) {
            return false;
        }

        return $this->options;
    }

    private function isOptionsEmpty(array|bool $options): bool
    {
        if (is_bool($options)) {
            return $options;
        }

        foreach ($options as $value) {
            if (!empty($value)) {
                return false;
            }
        }

        return true;
    }

    public static function deleteOptions(): void
    {
        delete_option(self::OPTION_NAME);
    }

    public function addSettings(): void
    {
        $supportedPostTypes = SupportedPostTypes::getInstance()->getPostTypes();

        if (empty($supportedPostTypes)) {
            return;
        }

        if (isset($_POST[self::OPTION_NAME])) {
            if (!isset($_POST['optionsPermalinksPostTypes_nonce'])) {
                return;
            }

            $nonce = sanitize_text_field(wp_unslash($_POST['optionsPermalinksPostTypes_nonce']));
            if (!wp_verify_nonce($nonce, 'optionsPermalinksPostTypes_update_option')) {
                return;
            }

            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --sanitized below
            $array = wp_unslash($_POST[self::OPTION_NAME]);

            $keys = array_keys($array);
            $keys = array_map('sanitize_key', $keys);

            $values = array_values($array);
            $values = array_map('sanitize_text_field', $values);

            // Remove trailing slashes from values.
            $values = array_map(function ($value) {
                return trim($value, '/');
            }, $values);

            $optionValue = array_combine($keys, $values);

            update_option(self::OPTION_NAME, $optionValue);
        }

        add_settings_field(
            self::OPTION_NAME,
            __('Custom Post Types', 'post-type-archive-pages-and-permalink-settings'),
            [$this, 'renderOptionsSettingsField'],
            'permalink',
            'optional'
        );

        register_setting(
            'permalink',
            self::OPTION_NAME
        );
    }

    public function renderOptionsSettingsField(): void
    {
        $supportedPostTypes = SupportedPostTypes::getInstance()->getPostTypes();
        if (empty($supportedPostTypes)) {
            return;
        }

        $optionName = self::OPTION_NAME;

        wp_nonce_field('optionsPermalinksPostTypes_update_option', 'optionsPermalinksPostTypes_nonce');
        ?>

        <p class="description">
            <strong><?php esc_html_e('Notice: Tags are not tested nor supported!', 'post-type-archive-pages-and-permalink-settings'); ?></strong>
        </p>
        <br>
        <fieldset>
            <?php foreach ($supportedPostTypes as $post_type) {
                $name = sprintf('%s[%s]', $optionName, $post_type->name);
                $value = is_array($this->options) && isset($this->options[$post_type->name]) ? $this->options[$post_type->name] : '';
                $placeholder = $post_type->rewrite['slug'] ?? $post_type->name;
                $hasArchive = $post_type->has_archive ?? false;
                $withFront = $post_type->rewrite['with_front'] ?? false;

                $description = sprintf(
                    'post-type: %1$s | has_archive: %2$s | with_front: %2$s',
                    $post_type->name,
                    is_bool($hasArchive) ? ($hasArchive ? 'true' : 'false') : $hasArchive,
                    $withFront ? 'true' : 'false'
                );
                ?>
                <label for="<?php echo esc_attr($optionName) ?>">
                    <strong><?php echo esc_html($post_type->label); ?> <?php esc_html_e('base', 'post-type-archive-pages-and-permalink-settings') ?> </strong><br>
                    <code><?php echo esc_url(home_url()) . '/'; ?></code>
                    <input type="text" name="<?php echo esc_attr($name) ?>" value="<?php echo esc_attr($value) ?>" placeholder="<?php echo esc_attr($placeholder) ?>" />
                    <code>/%postname%/</code><br>
                    <span class="description">
                        <?php echo esc_html($description) ?>
                    </span>
                </label><br>
            <?php }
            ?>
        </fieldset>
        <?php
    }
}
