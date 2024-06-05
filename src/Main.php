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

        // cross-sell hooks
        add_filter('wdr_cross_sells_product_ids', [Controller::class, 'filterInvalidProductIds']);

        // check product language
        add_filter('wdr_is_current_language_product', [Controller::class, 'checkIsValidProduct'], 100, 2);

        // suppress ignorable callbacks
        add_filter('wdr_suppress_allowed_hooks', function ($allowed_hooks) {
            $allowed_hooks['woocommerce_before_calculate_totals'][] = 'WCML_Cart|woocommerce_calculate_totals';
            $allowed_hooks['woocommerce_before_calculate_totals'][] = 'WDR_WPML\Controllers\Controller|removeInvalidAutoAddProducts';
            return $allowed_hooks;
        });

        // remove wpml term hooks
        add_action('wdr_remove_third_party_term_hooks', function () {
            global $sitepress;
            if (!empty($sitepress)) {
                remove_filter('get_terms_args', array($sitepress, 'get_terms_args_filter'), 10);
                remove_filter('get_term', array($sitepress, 'get_term_adjust_id'), 1);
                remove_filter('terms_clauses', array($sitepress, 'terms_clauses'), 10);
            }
        });
    }
}