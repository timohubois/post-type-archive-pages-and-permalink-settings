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

            $optionValue = array_combine($keys, $values);

            update_option(self::OPTION_NAME, $optionValue);
        }

        add_settings_field(
            self::OPTION_NAME,
            __('Custom Post Types', 'post-type-and-taxonomy-archive-pages'),
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
            <strong><?php esc_html_e('Notice: Tags are not tested nor supported!', 'post-type-and-taxonomy-archive-pages'); ?></strong>
        </p>
        <br>
        <fieldset>
            <?php foreach ($supportedPostTypes as $post_type) {
                $name = "{$optionName}[{$post_type->name}]";
                $value = is_array($this->options) && isset($this->options[$post_type->name]) ? $this->options[$post_type->name] : '';
                $placeholder = $post_type->rewrite['slug'] ?? $post_type->name;
                $hasArchive = $post_type->has_archive ?? false;
                $withFront = $post_type->rewrite['with_front'] ?? false;

                $description = sprintf(
                    '%1$s: %2$s | %3$s: %4$s | %5$s: %6$s',
                    "post-type",
                    $post_type->name,
                    "has_archive",
                    is_bool($hasArchive) ? ($hasArchive ? 'true' : 'false') : $hasArchive,
                    "with_front",
                    $withFront ? 'true' : 'false'
                );
                ?>
                <label for="<?php echo esc_attr($optionName) ?>">
                    <strong><?php echo esc_html($post_type->label); ?> <?php esc_html_e('base', 'post-type-and-taxonomy-archive-pages') ?> </strong><br>
                    <code><?php echo esc_url(home_url()) . '/'; ?></code>
                    <input type="text" name="<?php echo esc_attr($name) ?>" value="<?php echo esc_attr($value) ?>" placeholder="<?php echo esc_attr($placeholder) ?>" />
                    <code>/%postname%/</code><br>
                    <span class="description">
                        <?php echo esc_html($description) ?>
                    </span>
                </label><br>
            <?php } ?>
        </fieldset>
        <?php
    }
}
