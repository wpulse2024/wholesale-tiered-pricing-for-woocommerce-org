<?php
if (!defined('ABSPATH')) exit;

// -----------------------------
// 1. Add Tiered Pricing Tab
// -----------------------------
add_filter('woocommerce_settings_tabs_array', function($tabs) {
    $tabs['tiered_pricing'] = __('Tiered Pricing', 'text-domain');
    return $tabs;
}, 50);

// -----------------------------
// 2. Show Tiered Pricing Settings
// -----------------------------
add_action('woocommerce_settings_tabs_tiered_pricing', function() {
    $rules = get_option('wc_role_pricing_global_rules', []);
    $products = wc_get_products(['limit' => -1]); // Get all products
    include_once WC_ROLE_PRICING_PLUGIN_PATH . 'templates/admin/global-settings.php';
    $userRoles = $wp_roles->roles;
    wp_enqueue_script('wholesale-tiered-pricing-for-woocommerce-admin', WC_ROLE_PRICING_PLUGIN_URL . 'assets/global-settings.js', array('jquery'), WC_ROLE_PRICING_VERSION, true);
    wp_enqueue_style('wholesale-tiered-pricing-for-woocommerce-admin', WC_ROLE_PRICING_PLUGIN_URL . 'assets/global-setting.css', array(), WC_ROLE_PRICING_VERSION);
    wp_localize_script('wholesale-tiered-pricing-for-woocommerce-admin', 'wholesaleTieredPricingVars', array(
        'userRoles' => $userRoles
    ));
});

// -----------------------------
// 3. Save Tiered Pricing Rules
// -----------------------------
if (isset($_POST['tiered_pricing_nonce']) && wp_verify_nonce($_POST['tiered_pricing_nonce'], 'save_tiered_pricing_rules')) {
    if (isset($_POST['pricing_rules'])) {
        update_option('wc_role_pricing_global_rules', $_POST['pricing_rules']);
    } else {
        update_option('wc_role_pricing_global_rules', []);
    }
}

// -----------------------------
// 4. Get Tiered Price
// -----------------------------
function get_tiered_price($product_id, $product_price, $quantity, $user_role) {
    $rules = get_option('wc_role_pricing_global_rules', []);

    foreach ($rules as $rule) {
        if ($rule['role'] !== $user_role) continue;
        if ($quantity < $rule['min_qty']) continue;
        if ($rule['max_qty'] !== 'Unlimited' && $quantity > $rule['max_qty']) continue;

        if (!empty($rule['include_products']) && !in_array($product_id, $rule['include_products'])) continue;
        if (!empty($rule['exclude_products']) && in_array($product_id, $rule['exclude_products'])) continue;

        foreach ($rule['tiered_pricing'] as $tier) {
            if ($quantity >= $tier['min_qty']) {
                if ($tier['discount_type'] === 'percentage') {
                    return $product_price - ($product_price * $tier['value'] / 100);
                }
                if ($tier['discount_type'] === 'fixed') {
                    return $product_price - $tier['value'];
                }
            }
        }
    }
    return $product_price;
}