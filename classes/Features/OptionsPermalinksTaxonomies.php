<?php

namespace Ptapas\Features;

defined('ABSPATH') || exit;

use Ptapas\Features\SupportedTaxonomies;

class OptionsPermalinksTaxonomies
{
    const OPTION_NAME = 'ptapaps_taxonomy_permalink';

    private static $instance = null;
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

    public static function deleteOptions()
    {
        delete_option(self::OPTION_NAME);
    }

    public function addSettings()
    {
        $supportedTaxonomies = SupportedTaxonomies::getInstance()->getTaxonomies();

        if (empty($supportedTaxonomies)) {
            return;
        }

        $optionName = self::OPTION_NAME;

        // phpcs:disable WordPress.Security.NonceVerification.Missing
        if (isset($_POST[$optionName])) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized --its sanitized
            update_option($optionName, $this->sanitizeOptionsArray(wp_unslash($_POST[$optionName])));
        }
        // phpcs:enable WordPress.Security.NonceVerification.Missing

        add_settings_field(
            $optionName,
            __('Custom Taxonomies', APAPS_TEXT_DOMAIN),
            [$this, 'renderOptionsSettingsField'],
            'permalink',
            'optional'
        );

        register_setting(
            'permalink',
            $optionName
        );
    }

    public function renderOptionsSettingsField()
    {
        $supportedTaxonomies = SupportedTaxonomies::getInstance()->getTaxonomies();

        if (empty($supportedTaxonomies)) {
            return;
        }

        $optionName = self::OPTION_NAME;
        ?>
        <p class="description">
            <strong><?php esc_html_e('Notice: Tags are not tested nor supported!', APAPS_TEXT_DOMAIN); ?></strong>
        </p>
        <br>
        <fieldset>
            <?php foreach ($supportedTaxonomies as $taxonomy) {
                $name = "{$optionName}[{$taxonomy->name}]";
                $value = is_array($this->options) && isset($this->options[$taxonomy->name]) ? $this->options[$taxonomy->name] : '';
                $placeholder = $taxonomy->rewrite['slug'] ?? $taxonomy->name;
                ?>
                <label for="<?php echo esc_attr($optionName) ?>">
                    <strong><?php echo esc_html($taxonomy->label); ?> <?php esc_html_e('base', APAPS_TEXT_DOMAIN) ?> </strong><br>
                    <code><?php echo esc_url(home_url()) . '/'; ?></code>
                    <input type="text" name="<?php echo esc_attr($name) ?>" value="<?php echo esc_attr($value) ?>" placeholder="<?php echo esc_attr($placeholder) ?>" />
                    <code>/%taxonomyname%/</code>
                </label><br>
            <?php } ?>
        </fieldset>
        <?php
    }

    public function sanitizeOptionsArray($array)
    {
        foreach ($array as $key => &$value) {
            if (is_array($value)) {
                $value = $this->sanitizeOptionsArray($value);
            } else {
                $value = sanitize_text_field($value);
            }
        }

        return $array;
    }
}
