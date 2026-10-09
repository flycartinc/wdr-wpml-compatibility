<?php

namespace WDR_WPML\Controllers;

use WDR_WPML\Helpers\WPML;

defined('ABSPATH') || exit;

class Controller
{
    /**
     * To hold removed item key and product id.
     *
     * @var array
     */
    private static $removed_items = [];

    /**
     * Load active languages.
     *
     * @return array
     */
    public static function loadActiveLanguages(): array
    {
        return WPML::getActiveLanguages();
    }

    /**
     * Load current language.
     *
     * @return string
     */
    public static function loadCurrentLanguage(): string
    {
        return WPML::getCurrentLanguage();
    }

    /**
     * Get internal key.
     *
     * @param string $cart_item_key
     * @return string
     */
    public static function getAddedItemKey(string $cart_item_key): string
    {
        foreach (\WDR\Core\Models\WC\Cart::getItems() as $key => $item) {
            if (isset($item['key']) && $item['key'] == $cart_item_key) {
                return (string)$key;
            }
        }
        return $cart_item_key;
    }

    /**
     * Recalculate cart total with processed language.
     */
    public static function recalculateTotal($changes_detected = null)
    {
        $current_language = WPML::getCurrentLanguage();
        if (!empty($current_language)) {
            \WDR\Core\Helpers\WC::setSession('wdr_processed_wpml_language', $current_language);
        }
    }

    /**
     * Remove invalid auto added products.
     */
    public static function removeInvalidAutoAddProducts()
    {
        global $woocommerce;
        $current_language = apply_filters('wpml_current_language', '');
        $processed_language = \WDR\Core\Helpers\WC::getSession('wdr_processed_wpml_language', '');
        if (!empty($current_language) && !empty($processed_language) && $current_language != $processed_language) {
            $auto_added_items = \WDR\Core\Helpers\WC::getSession('wdr_auto_added_items', []);
            if (!empty($auto_added_items) && is_object($woocommerce) && isset($woocommerce->cart)) {
                foreach ($auto_added_items as $key => $data) {
                    if (isset($woocommerce->cart->cart_contents[$key]['product_id'])) {
                        self::$removed_items[$key] = $woocommerce->cart->cart_contents[$key]['product_id'];
                    }
                    unset($woocommerce->cart->cart_contents[$key]);
                }
                \WDR\Core\Helpers\WC::setSession('wdr_auto_added_items', null);
                \WDR\Core\Helpers\WC::setSession('wdr_processed_wpml_language', $current_language);
            }
        } elseif (!empty(self::$removed_items) && is_object($woocommerce) && isset($woocommerce->cart)) {
            foreach (self::$removed_items as $key => $product_id) {
                if (isset($woocommerce->cart->cart_contents[$key]['product_id']) && $woocommerce->cart->cart_contents[$key]['product_id'] == $product_id) {
                    unset($woocommerce->cart->cart_contents[$key]);
                }
            }
        }
    }

    /**
     * Filter different language product ids.
     *
     * @param array $product_ids
     * @return array
     */
    public static function filterInvalidProductIds(array $product_ids): array
    {
        return array_filter($product_ids, [WPML::class, 'isCurrentLanguageProduct']);
    }

    /**
     * Check is current language product.
     *
     * @param bool $status
     * @param int|\WC_Product $product
     * @return bool
     */
    public static function checkIsValidProduct(bool $status, $product): bool
    {
        return $status && WPML::isCurrentLanguageProduct($product);
    }

    /**
     * Allow this addon's and WPML's cart recalculation callbacks when WDR suppresses other
     * plugins' `woocommerce_before_calculate_totals` callbacks.
     *
     * @param array $allowed_hooks
     * @return array
     */
    public static function allowSuppressedHooks($allowed_hooks)
    {
        $allowed_hooks = (array)$allowed_hooks;
        $allowed_hooks['woocommerce_before_calculate_totals'][] = __CLASS__ . '|removeInvalidAutoAddProducts';
        return $allowed_hooks;
    }

    /**
     * Unhook WPML's language-scoping term filters before WDR queries terms across languages.
     */
    public static function removeThirdPartyTermHooks()
    {
        global $sitepress;
        if (empty($sitepress)) {
            return;
        }
        remove_filter('get_terms_args', [$sitepress, 'get_terms_args_filter'], 10);
        remove_filter('get_term', [$sitepress, 'get_term_adjust_id'], 1);
        remove_filter('terms_clauses', [$sitepress, 'terms_clauses'], 10);
    }
}
