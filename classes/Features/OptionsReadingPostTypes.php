<?php

namespace Ptatap\Features;

defined('ABSPATH') || exit;

use Ptatap\Features\FlushRewriteRules;
use Ptatap\Features\SupportedPostTypes;

class OptionsReadingPostTypes
{
    const OPTION_NAME = 'ptatap_post_type_reading_settings';

    private static $instance = null;
    private $options = false;

    public function __construct()
    {
        $this->options = get_option(self::OPTION_NAME);

        add_action('admin_init', [$this, 'addSettings']);
        add_action('update_option_' . self::OPTION_NAME, [$this, 'maybeFlushRewriteRules'], 10, 3);
    }

    public static function getInstance(): OptionsReadingPostTypes
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getOptions(): array|bool
    {
        $this->options = apply_filters('ptatap_post_type_reading_settings', $this->options);

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

        $optionName = self::OPTION_NAME;

        add_settings_field(
            $optionName,
            __('Archive Pages', 'post-type-and-taxonomy-archive-pages'),
            [$this, 'renderSettings'],
            'reading'
        );

        register_setting(
            'reading',
            $optionName
        );
    }

    public function renderSettings(): void
    {
        $supportedPostTypes = SupportedPostTypes::getInstance()->getPostTypes();

        if (empty($supportedPostTypes)) {
            return;
        }

        ?>
        <p class="description"><?php esc_html_e('Select the page to display the archive for each post type.', 'post-type-and-taxonomy-archive-pages'); ?></p>
        <br>
        <fieldset>
            <?php foreach ($supportedPostTypes as $postType) {
                $optionName = self::OPTION_NAME;
                $selected = is_array($this->options) && $this->options[$postType->name] ? $this->options[$postType->name] : null
                ?>
                <label for="<?php echo esc_attr($optionName) ?>">
                    <?php
                    printf(
                        '%1$s %2$s',
                        esc_html($postType->label),
                        esc_html__('page:', 'post-type-and-taxonomy-archive-pages')
                    )
                    ?>
                    <?php
                    wp_dropdown_pages(
                        [
                            'name' => esc_attr("{$optionName}[{$postType->name}]"),
                            'echo' => 1,
                            'show_option_none' => esc_attr__('&mdash; Select &mdash;'),
                            'option_none_value' => '0',
                            'selected' => esc_attr($selected)
                        ]
                    );
                    ?>
                </label><br>
            <?php } ?>
        </fieldset>
        <?php
    }

    public function maybeFlushRewriteRules(mixed $old_value, mixed $value): void
    {
        if ($old_value === $value) {
            return;
        }

        FlushRewriteRules::getInstance()->setup();
    }
}
