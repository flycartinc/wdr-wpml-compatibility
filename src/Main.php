<?php

namespace WDR_WPML;

use WDR_WPML\Controllers\Controller;

defined('ABSPATH') || exit;

class Main
{
    /**
     * Init hooks.
     */
    public static function init()
    {
        // common hooks
        add_filter('wdr_get_languages', [Controller::class, 'loadActiveLanguages']);
        add_filter('wdr_get_current_language', [Controller::class, 'loadCurrentLanguage']);

        // auto add hooks
        add_filter('wdr_added_cart_item_key', [Controller::class, 'getAddedItemKey']);
        add_action('wdr_auto_add_products_added', [Controller::class, 'recalculateTotal']);
        add_action('woocommerce_before_calculate_totals', [Controller::class, 'removeInvalidAutoAddProducts']);

        // a free variable product keeps the size the shopper chose when the language switches
        add_filter('wdr_free_product_variation_ids', [Controller::class, 'preferMovedFreeVariation']);

        // a picked gift is picked again in the new language
        add_action('woocommerce_before_calculate_totals', [Controller::class, 'removeOtherLanguageChosenGifts']);

        // cart lines WooCommerce Multilingual moved to another language
        add_filter('wcml_cart_contents', [Controller::class, 'translateSwitchedItemTerms'], 10, 4);

        // cross-sell hooks
        add_filter('wdr_cross_sells_product_ids', [Controller::class, 'filterInvalidProductIds']);

        // check product language
        add_filter('wdr_is_current_language_product', [Controller::class, 'checkIsValidProduct'], 100, 2);

        // suppress ignorable callbacks
        add_filter('wdr_suppress_allowed_hooks', [Controller::class, 'allowSuppressedHooks']);

        // remove wpml term hooks
        add_action('wdr_remove_third_party_term_hooks', [Controller::class, 'removeThirdPartyTermHooks']);
    }
}
