<?php
/**
 * Plugin Name:          Discount Rules: WPML Compatibility
 * Plugin URI:           https://www.flycart.org/products
 * Description:          WPML compatibility add-on.
 * Version:              1.1.0
 * Requires at least:    6.0
 * Requires PHP:         7.4
 * Author:               Flycart
 * Author URI:           https://www.flycart.org
 * Text Domain:          wdr-wpml-compatibility
 * Domain Path:          /i18n/languages
 * License:              GPL v3 or later
 * License URI:          https://www.gnu.org/licenses/gpl-3.0.html
 * WC requires at least: 7.0
 * WC tested up to:      10.2
 * Requires Plugins:     woocommerce
 */

defined('ABSPATH') || exit;

// Declare WooCommerce feature compatibility (HPOS).
add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

// define plugin file constant
defined('WDR_WPML_PLUGIN_FILE') || define('WDR_WPML_PLUGIN_FILE', __FILE__);
defined('WDR_WPML_PLUGIN_PATH') || define('WDR_WPML_PLUGIN_PATH', plugin_dir_path(__FILE__));

// load composer autoload file
if (!file_exists(WDR_WPML_PLUGIN_PATH . 'vendor/autoload.php')) {
    return;
}
require WDR_WPML_PLUGIN_PATH . 'vendor/autoload.php';

// init updater (runs regardless of dependencies so existing installs keep receiving updates)
if (class_exists('YahnisElsts\PluginUpdateChecker\v5\PucFactory')) {
    $updater = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
        'https://github.com/flycartinc/wdr-wpml-compatibility/',
        __FILE__,
        'wdr-wpml-compatibility'
    );
    $updater->setBranch('master');
    $updater->getVcsApi()->enableReleaseAssets();
}

if (!class_exists('WDR_WPML\Main') || !class_exists('WDR_WPML\Setup')) {
    return;
}

// register activation/deactivation/uninstall hooks (not gated behind dependency checks)
\WDR_WPML\Setup::init();

// load hooks
add_action('init', function () {
    $i18n_path = dirname(plugin_basename(WDR_WPML_PLUGIN_FILE)) . '/i18n/languages';
    load_plugin_textdomain('wdr-wpml-compatibility', false, $i18n_path);

    if (\WDR_WPML\Helpers\Plugin::checkAddonDependencies('Discount Rules: WPML Compatibility')) {
        // existing installs updated from the legacy version never ran the activation hook
        \WDR_WPML\Setup::activate();
        \WDR_WPML\Main::init();
    }
});
