<?php

namespace Ptapas\Features;

defined('ABSPATH') || exit;

use Ptapas\Features\FlushRewriteRules;
use Ptapas\Features\SupportedPostTypes;

class OptionsReadingPostTypes
{
    const OPTION_NAME = 'ptapapst_post_type_reading_settings';

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
        $this->options = apply_filters(APAPS_TEXT_DOMAIN . '_post_type_reading_settings', $this->options);

        if ($this->allOptionsAreEmpty($this->options)) {
            return false;
        }

        return $this->options;
    }

    private function allOptionsAreEmpty(array|bool $options): bool
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

    public static function deleteOptions() {
        delete_option(self::OPTION_NAME);
    }

    public function addSettings()
    {
        $supportedPostTypes = SupportedPostTypes::getInstance()->getPostTypes();

        if (empty($supportedPostTypes)) {
            return;
        }

        $optionName = self::OPTION_NAME;

        add_settings_field(
            $optionName,
            __('Archive Pages', APAPS_TEXT_DOMAIN),
            [$this, 'renderSettings'],
            'reading'
        );

        register_setting(
            'reading',
            $optionName
        );
    }

    public function renderSettings()
    {
        $supportedPostTypes = SupportedPostTypes::getInstance()->getPostTypes();

        if (empty($supportedPostTypes)) {
            return;
        }

        $optionName = self::OPTION_NAME;
        ?>
        <p class="description"><?php _e('Select the page to display the archive for each post type.', APAPS_TEXT_DOMAIN); ?></p>
        <br>
        <fieldset>
            <?php foreach ($supportedPostTypes as $post_type) { ?>
                <label for="<?php echo $optionName ?>">
                    <?php
                    printf(
                        '%1$s %2$s',
                        $post_type->label,
                        __('page:', APAPS_TEXT_DOMAIN)
                    )
                    ?>
                    <?php
                    printf(
                        '%s',
                        wp_dropdown_pages(
                            [
                                'name' => "{$optionName}[{$post_type->name}]",
                                'echo' => 0,
                                'show_option_none' => __('&mdash; Select &mdash;'),
                                'option_none_value' => '0',
                                'selected' => is_array($this->options) && $this->options[$post_type->name] ? $this->options[$post_type->name] : null
                            ]
                        )
                    );
                    ?>
                </label><br>
            <?php } ?>
        </fieldset>
        <?php
    }

    public function maybeFlushRewriteRules(mixed $old_value, mixed $value)
    {
        if ($old_value === $value) {
            return;
        }

        FlushRewriteRules::getInstance()->setup();
    }
}
