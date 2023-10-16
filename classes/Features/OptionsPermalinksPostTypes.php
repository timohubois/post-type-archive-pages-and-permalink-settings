<?php

namespace Ptapas\Features;

defined('ABSPATH') || exit;

use Ptapas\Features\SupportedPostTypes;

class OptionsPermalinksPostTypes
{
    const OPTION_NAME = 'apaps_post_type_permalink';

    private static $instance = null;
    private $options = false;

    public function __construct()
    {
        $this->options = get_option(self::OPTION_NAME);

        add_action('load-options-permalink.php', [$this, 'addSettings']);
    }

    public static function getInstance(): OptionsPermalinksPostTypes
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getOptions(): array|bool
    {
        return $this->options;
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

        if (isset($_POST[$optionName])) {
            update_option($optionName, $_POST[$optionName]);
        }

        add_settings_field(
            $optionName,
            __('Custom Post Types', APAPS_TEXT_DOMAIN),
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
        $supportedPostTypes = SupportedPostTypes::getInstance()->getPostTypes();

        if (empty($supportedPostTypes)) {
            return;
        }

        $optionName = self::OPTION_NAME; ?>
        <p class="description">
            <strong><?php _e('Notice: Tags are not tested nor supported!', APAPS_TEXT_DOMAIN); ?></strong>
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
                <label for="<?php echo $optionName ?>">
                    <strong><?php echo $post_type->label; ?> <?php _e('base', APAPS_TEXT_DOMAIN) ?> </strong><br>
                    <code><?php echo home_url() . '/'; ?></code>
                    <input type="text" name="<?php echo $name ?>" value="<?php echo $value ?>" placeholder="<?php echo $placeholder ?>" />
                    <code>/%postname%/</code><br>
                    <span class="description">
                        <?php echo $description ?>
                    </span>
                </label><br>
            <?php } ?>
        </fieldset>
        <?php
    }
}
