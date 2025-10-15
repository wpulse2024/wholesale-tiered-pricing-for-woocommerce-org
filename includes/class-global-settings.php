<?php
if (!defined('ABSPATH')) exit;

// -----------------------------
// 1. Add Tiered Pricing Tab
// -----------------------------
add_filter('woocommerce_settings_tabs_array', function($tabs) {
    $tabs['tiered_pricing'] = __('Tiered Pricing', 'wholesale-tiered-pricing-for-woocommerce');
    return $tabs;
}, 50);

// -----------------------------
// 2. Show Tiered Pricing Settings
// -----------------------------
add_action('woocommerce_settings_tabs_tiered_pricing', function() {
    $rules = get_option('wc_role_pricing_global_rules', []);
    $products = wc_get_products(['limit' => -1]); // Get all products
    $products_array = [];
    foreach ( $products as $product ) {
        $products_array[] = [
            'id'          => $product->get_id(),
            'name'        => $product->get_name(),
            // 'categories'  => wp_get_post_terms( $product->get_id(), 'product_cat', ['fields' => 'names'] ),
        ];
    }

    $categories = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);
    
    include_once WHTPROLE_PRICING_PLUGIN_PATH . 'templates/admin/global-settings.php';
    $userRoles = wp_roles()->roles;


    wp_enqueue_script('wholesale-tiered-pricing-for-woocommerce-admin', WHTPROLE_PRICING_PLUGIN_URL . 'assets/global-settings.js', array('jquery'), WHTPROLE_PRICING_VERSION, true);
    wp_enqueue_script('wholesale-tiered-pricing-for-woocommerce-admin-vua-app', WHTPROLE_PRICING_PLUGIN_URL . 'assets/admin/app.js', array('jquery'), WHTPROLE_PRICING_VERSION, true);
    wp_enqueue_style('wholesale-tiered-pricing-for-woocommerce-admin', WHTPROLE_PRICING_PLUGIN_URL . 'assets/global-setting.css', array(), WHTPROLE_PRICING_VERSION);
    wp_localize_script('wholesale-tiered-pricing-for-woocommerce-admin', 'wholesaleTieredPricingVars', array(
        'userRoles' => $userRoles,
        'nonce' => wp_create_nonce('wc_role_pricing_get_pricing_rules'),
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'products' => $products_array,
        'categories' => $categories
    ));
});