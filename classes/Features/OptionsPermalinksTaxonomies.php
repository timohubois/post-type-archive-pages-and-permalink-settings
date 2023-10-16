<?php

namespace Ptapas\Features;

defined('ABSPATH') || exit;

use Ptapas\Features\SupportedTaxonomies;

class OptionsPermalinksTaxonomies
{
    const OPTION_NAME = 'ptapapst_taxonomy_permalink';

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
        return $this->options;
    }

    public static function deleteOptions() {
        delete_option(self::OPTION_NAME);
    }

    public function addSettings()
    {
        $supportedTaxonomies = SupportedTaxonomies::getInstance()->getTaxonomies();

        if (empty($supportedTaxonomies)) {
            return;
        }

        $optionName = self::OPTION_NAME;

        if (isset($_POST[$optionName])) {
            update_option($optionName, $_POST[$optionName]);
        }

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
            <strong><?php _e('Notice: Tags are not tested nor supported!', APAPS_TEXT_DOMAIN); ?></strong>
        </p>
        <br>
        <fieldset>
            <?php foreach ($supportedTaxonomies as $taxonomy) {
                $name = "{$optionName}[{$taxonomy->name}]";
                $value = is_array($this->options) && isset($this->options[$taxonomy->name]) ? $this->options[$taxonomy->name] : '';
                $placeholder = $taxonomy->rewrite['slug'] ?? $taxonomy->name;
                ?>
                <label for="<?php echo $optionName ?>">
                    <strong><?php echo $taxonomy->label; ?> <?php _e('base', APAPS_TEXT_DOMAIN) ?> </strong><br>
                    <code><?php echo home_url() . '/'; ?></code>
                    <input type="text" name="<?php echo $name ?>" value="<?php echo $value ?>" placeholder="<?php echo $placeholder ?>" />
                    <code>/%taxonomyname%/</code>
                </label><br>
            <?php } ?>
        </fieldset>
        <?php
    }
}
