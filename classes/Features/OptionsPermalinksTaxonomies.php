<?php

namespace Ptatap\Features;

defined('ABSPATH') || exit;

use Ptatap\Features\SupportedTaxonomies;

class OptionsPermalinksTaxonomies
{
    public const OPTION_NAME = 'ptatap_taxonomy_permalink';

    private static ?\Ptatap\Features\OptionsPermalinksTaxonomies $instance = null;
    private $options = false;

    public function __construct()
    {
        $this->options = get_option(self::OPTION_NAME);

        add_action('load-options-permalink.php', [$this, 'addSettings']);
    }

    public static function getInstance(): OptionsPermalinksTaxonomies
    {
        if (self::$instance === null) {
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
        $supportedTaxonomies = SupportedTaxonomies::getInstance()->getTaxonomies();

        if (empty($supportedTaxonomies)) {
            return;
        }

        if (isset($_POST[self::OPTION_NAME])) {
            if (!isset($_POST['optionsPermalinksTaxonomies_nonce'])) {
                return;
            }

            $nonce = sanitize_text_field(wp_unslash($_POST['optionsPermalinksTaxonomies_nonce']));
            if (!wp_verify_nonce($nonce, 'optionsPermalinksTaxonomies_update_option')) {
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
            __('Custom Taxonomies', 'post-type-and-taxonomy-archive-pages'),
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
        $supportedTaxonomies = SupportedTaxonomies::getInstance()->getTaxonomies();

        if (empty($supportedTaxonomies)) {
            return;
        }
        $optionName = self::OPTION_NAME;

        wp_nonce_field('optionsPermalinksTaxonomies_update_option', 'optionsPermalinksTaxonomies_nonce');
        ?>
        <p class="description">
            <strong><?php esc_html_e('Notice: Tags are not tested nor supported!', 'post-type-and-taxonomy-archive-pages'); ?></strong>
        </p>
        <br>
        <fieldset>
            <?php foreach ($supportedTaxonomies as $taxonomy) {
                $name = "{$optionName}[{$taxonomy->name}]";
                $value = is_array($this->options) && isset($this->options[$taxonomy->name]) ? $this->options[$taxonomy->name] : '';
                $placeholder = $taxonomy->rewrite['slug'] ?? $taxonomy->name;
                ?>
                <label for="<?php echo esc_attr($optionName) ?>">
                    <strong><?php echo esc_html($taxonomy->label); ?> <?php esc_html_e('base', 'post-type-and-taxonomy-archive-pages') ?> </strong><br>
                    <code><?php echo esc_url(home_url()) . '/'; ?></code>
                    <input type="text" name="<?php echo esc_attr($name) ?>" value="<?php echo esc_attr($value) ?>" placeholder="<?php echo esc_attr($placeholder) ?>" />
                    <code>/%taxonomyname%/</code>
                </label><br>
            <?php } ?>
        </fieldset>
        <?php
    }
}
