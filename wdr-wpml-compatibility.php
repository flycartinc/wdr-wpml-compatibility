<?php
/**
 * Plugin Name:          Discount Rules: WPML Compatibility
 * Plugin URI:           https://www.flycart.org/products
 * Description:          WPML compatibility add-on.
 * Version:              1.0.0
 * Requires at least:    5.3
 * Requires PHP:         7.4
 * Author:               Flycart
 * Author URI:           https://www.flycart.org
 * Text Domain:          wdr-wpml-compatibility
 * Domain Path:          /i18n/languages
 * License:              GPL v3 or later
 * License URI:          https://www.gnu.org/licenses/gpl-3.0.html
 */

defined('ABSPATH') || exit;

// define plugin file constant
defined('WDR_WPML_PLUGIN_FILE') || define('WDR_WPML_PLUGIN_FILE', __FILE__);
defined('WDR_WPML_PLUGIN_PATH') || define('WDR_WPML_PLUGIN_PATH', plugin_dir_path(__FILE__));

// load composer autoload file
if (!file_exists(WDR_WPML_PLUGIN_PATH . '/vendor/autoload.php')) {
    return;
}
if (!class_exists('WDR/Core/Route')) {
    require WDR_WPML_PLUGIN_PATH . '/vendor/autoload.php';
}

// load hooks
if (class_exists('WDR_WPML\Main')) {
    add_action('plugins_loaded', function () {
        $requires = [
            'php' => '7.4',
            'wordpress' => '5.3',
            'wdr_pro' => '3.0',
            'plugins' => [
                [
                    'name' => 'WPML',
                    'url' => 'https://wpml.org/',
                    'file' => 'sitepress-multilingual-cms/sitepress.php',
                ],
            ],
        ];
        $addon_name = 'Discount Rules: WPML Compatibility';
        if (\WDR_WPML\Helpers\Plugin::checkDependencies($requires, $addon_name)) {
            \WDR_WPML\Main::init();
        }
        $i18n_path = dirname(plugin_basename(WDR_WPML_PLUGIN_FILE)) . '/i18n/languages';
        load_plugin_textdomain('wdr-wpml-compatibility', false, $i18n_path);
    });
}

// init updater
if (class_exists('YahnisElsts\PluginUpdateChecker\v5\PucFactory')) {
    $updater = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
        'https://github.com/flycartinc/wdr-wpml-compatibility/',
        __FILE__,
        'wdr-wpml-compatibility'
    );
    $updater->setBranch('master');
    $updater->getVcsApi()->enableReleaseAssets();
}
