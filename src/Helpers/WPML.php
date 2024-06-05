<?php

namespace WDR_WPML\Helpers;

defined('ABSPATH') || exit;

class WPML
{
    /**
     * Get active languages.
     *
     * @return array
     */
    public static function getActiveLanguages(): array
    {
        return (array)apply_filters('wpml_active_languages', array(), 'orderby=id&order=desc');
    }

    /**
     * Get current language code.
     *
     * @return string
     */
    public static function getCurrentLanguage(): string
    {
        return (string)apply_filters('wpml_current_language', '');
    }

    /**
     * Check if the product id is associated with current language.
     *
     * @param int|\WC_Product $product
     * @return bool
     */
    public static function isCurrentLanguageProduct($product): bool
    {
        $product_id = is_object($product) && is_a($product, 'WC_Product') ? $product->get_id() : (int)$product;
        $language_info = apply_filters('wpml_post_language_details', array(), $product_id);
        return empty($language_info['different_language']);
    }
}