<?php

namespace WDR_WPML;

defined('ABSPATH') || exit;

/**
 * Plugin lifecycle: activation, deactivation, and uninstall.
 */
class Setup
{
    /**
     * Addon slug tracked in Woo Discount Rules' own `wdr_active_addons` option.
     *
     * Must match the slug used by WDR's remote add-ons catalog for this addon (`compact_wpml`).
     * Having it in the option is also what makes WDR register its "WDR Add-ons" admin menu.
     *
     * @var string
     */
    const ADDON_SLUG = 'compact_wpml';

    /**
     * Register plugin lifecycle hooks.
     *
     * Runs on every load, not gated behind dependency checks, so WordPress can reliably fire
     * the activation/deactivation hooks even if WooCommerce/WDR/WPML aren't active yet.
     *
     * @return void
     */
    public static function init()
    {
        register_activation_hook(WDR_WPML_PLUGIN_FILE, [self::class, 'activate']);
        register_deactivation_hook(WDR_WPML_PLUGIN_FILE, [self::class, 'deactivate']);
        register_uninstall_hook(WDR_WPML_PLUGIN_FILE, [self::class, 'uninstall']);
    }

    /**
     * Register this addon in WDR's add-ons tracking option.
     *
     * @return bool
     */
    public static function activate()
    {
        $active_addons = (array)get_option('wdr_active_addons', []);
        if (in_array(self::ADDON_SLUG, $active_addons, true)) {
            return true;
        }
        $active_addons[] = self::ADDON_SLUG;
        return update_option('wdr_active_addons', $active_addons);
    }

    /**
     * Unregister this addon from WDR's add-ons tracking option.
     *
     * @return bool
     */
    public static function deactivate()
    {
        $active_addons = (array)get_option('wdr_active_addons', []);
        $key = array_search(self::ADDON_SLUG, $active_addons, true);
        if ($key === false) {
            return true;
        }
        unset($active_addons[$key]);
        return update_option('wdr_active_addons', $active_addons);
    }

    /**
     * Uninstall: this addon stores no options of its own, so only unregister it.
     *
     * @return void
     */
    public static function uninstall()
    {
        self::deactivate();
    }
}
