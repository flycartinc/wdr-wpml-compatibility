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
        if (!method_exists('\WDR\Core\Models\WC\Cart', 'getItems')) {
            return $cart_item_key;
        }

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
    public static function recalculateTotal($changes_detected)
    {
        $current_language = WPML::getCurrentLanguage();
        if (!empty($current_language) && method_exists('\WDR\Core\Helpers\WC', 'setSession')) {
            \WDR\Core\Helpers\WC::setSession('wdr_processed_wpml_language', $current_language);
        }
    }

    /**
     * Remove invalid auto added products.
     */
    public static function removeInvalidAutoAddProducts()
    {
        if (!method_exists('\WDR\Core\Helpers\WC', 'setSession')) {
            return;
        }

        global $woocommerce;
        $current_language = apply_filters('wpml_current_language', '');
        $processed_language = \WDR\Core\Helpers\WC::getSession('wdr_processed_wpml_language', '');
        if (!empty($current_language) && !empty($processed_language) && $current_language != $processed_language) {
            $auto_added_items = \WDR\Core\Helpers\WC::getSession('wdr_auto_added_items', []);
            if (!empty($auto_added_items) && is_object($woocommerce) && isset($woocommerce->cart)) {
                foreach ($auto_added_items as $key => $data) {
                    self::$removed_items[$key] = $woocommerce->cart->cart_contents[$key]['product_id'];
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
}