<?php
if (!defined('ABSPATH')) {
    exit;
}

class WHTPRole_Pricing_Engine {

    public function __construct() {
        // add_filter('woocommerce_product_get_price', array($this, 'whtprole_get_role_based_price'), 99, 2);
        add_filter('woocommerce_product_variation_get_price', array($this, 'whtprole_get_role_based_price'), 99, 2);
        add_filter('woocommerce_get_price_html', array($this, 'get_price_html'), 99, 2);
        add_action('woocommerce_before_calculate_totals', array($this, 'update_cart_prices'), 99);
    }

    public function whtprole_get_role_based_price($price, $product) {
        if (is_admin() && !wp_doing_ajax()) {
            return $price;
        }

        $product_id = $product->get_id();
        
        // For variations, get rules from parent product
        $parent_id = $product_id;
        if ($product->is_type('variation')) {
            $parent_id = $product->get_parent_id();
        }
        
        $rules = WHTPRole_Pricing_Helper::get_rules_for_product($parent_id);

        if (empty($rules)) {
            return $price;
        }

        $current_user_role = WHTPRole_Pricing_Helper::get_current_user_role();
        $is_guest = ($current_user_role === 'guest');
        $quantity = 1;
        
        // Get variation ID if product is a variation
        $variation_id = $product->is_type('variation') ? $product->get_id() : null;

        foreach ($rules as $rule) {
            // Use helper to check if rule applies to user role
            $rule_roles = isset($rule['roles']) ? $rule['roles'] : (isset($rule['role']) ? $rule['role'] : array());
            $also_for_guest = isset($rule['also_for_guest']) ? $rule['also_for_guest'] : false;
            
            if (WHTPRole_Pricing_Helper::rule_applies_to_user($rule_roles, $current_user_role, $is_guest, $also_for_guest)) {
                $new_price = $this->calculate_price($price, $rule, $quantity, $variation_id);
                return $new_price;
            }
        }

        return $price;
    }

    public function get_price_html($price_html, $product) {
        if (is_admin() && !wp_doing_ajax()) {
            return $price_html;
        }

        $product_id = $product->get_id();
        // For variations, get rules from parent product
        if ($product->is_type('variation')) {
            $product_id = $product->get_parent_id();
        }
        $rules = WHTPRole_Pricing_Helper::get_rules_for_product($product_id);

        if (empty($rules)) {
            return $price_html;
        }

        $current_user_role = WHTPRole_Pricing_Helper::get_current_user_role();
        $is_guest = ($current_user_role === 'guest');
        $original_price = $product->get_price();
        
        foreach ($rules as $rule) {
            // Use helper to check if rule applies
            $rule_roles = isset($rule['roles']) ? $rule['roles'] : (isset($rule['role']) ? $rule['role'] : array());
            $also_for_guest = isset($rule['also_for_guest']) ? $rule['also_for_guest'] : false;
            
            if (WHTPRole_Pricing_Helper::rule_applies_to_user($rule_roles, $current_user_role, $is_guest, $also_for_guest)) {
                $new_price = $this->calculate_price($original_price, $rule, 1);
                
                if ($new_price < $original_price) {
                    $savings = $original_price - $new_price;
                    $savings_percent = ($savings / $original_price) * 100;
                    
                    $price_html = '<del>' . wc_price($original_price) . '</del> ';
                    $price_html .= '<ins>' . wc_price($new_price) . '</ins> ';
                    $price_html .= '<span class="role-pricing-savings">';
                    /* translators: %s = the savings amount formatted as a price */
                    $price_html .= sprintf(esc_html__('Save %s', 'wholesale-tiered-pricing-for-woocommerce'), wc_price($savings));
                    $price_html .= ' (' . round($savings_percent, 1) . '%)';
                    $price_html .= '</span>';
                    
                    return $price_html;
                }
                
                break;
            }
        }

        return $price_html;
    }

    public function update_cart_prices($cart) {
        if (is_admin() && !wp_doing_ajax()) {
            return;
        }
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            $product = $cart_item['data'];
            $product_id = $product->get_id();
            
            // For variations, get rules from parent product
            $parent_id = $product_id;
            if ($product->is_type('variation')) {
                $parent_id = $product->get_parent_id();
            }
            
            $quantity = $cart_item['quantity'];
            $rules = WHTPRole_Pricing_Helper::get_rules_for_product($parent_id);

            if (empty($rules)) {
                continue;
            }

            $current_user_role = WHTPRole_Pricing_Helper::get_current_user_role();
            $is_guest = ($current_user_role === 'guest');
            $original_price = $product->get_regular_price();
            if (empty($original_price)) {
                $original_price = $product->get_price();
            }
            
            // Get variation ID from cart item data or product type
            $variation_id = null;
            if (isset($cart_item['variation_id']) && !empty($cart_item['variation_id'])) {
                $variation_id = intval($cart_item['variation_id']);
            } elseif ($product->is_type('variation')) {
                $variation_id = $product->get_id();
            }
            
            foreach ($rules as $rule) {
                // Use helper to check if rule applies
                $rule_roles = isset($rule['roles']) ? $rule['roles'] : (isset($rule['role']) ? $rule['role'] : array());
                $also_for_guest = isset($rule['also_for_guest']) ? $rule['also_for_guest'] : false;

                if (WHTPRole_Pricing_Helper::rule_applies_to_user($rule_roles, $current_user_role, $is_guest, $also_for_guest)) {
                    // Check minimum order value — only activate pricing if cart subtotal meets threshold
                    $min_order_value = isset($rule['min_order_value']) ? floatval($rule['min_order_value']) : 0;
                    if ($min_order_value > 0 && $cart->get_subtotal() < $min_order_value) {
                        break; // MOV not met; leave price unchanged
                    }

                    $new_price = $this->calculate_price($original_price, $rule, $quantity, $variation_id);
                    $cart_item['data']->set_price($new_price);
                    break;
                }
            }
        }
    }

    private function calculate_price($base_price, $rule, $quantity, $variation_id = null) {
        // Validate inputs
        if (empty($base_price) || $base_price <= 0) {
            return $base_price;
        }
        
        if (empty($quantity) || $quantity <= 0) {
            $quantity = 1;
        }
        
        // Check tiered pricing first
        if (!empty($rule['tiered_pricing']) && is_array($rule['tiered_pricing'])) {
            $applicable_tier = null;
            // Sort tiers by quantity descending to find the highest applicable tier
            usort($rule['tiered_pricing'], function($a, $b) {
                $qty_a = isset($a['min_qty']) ? intval($a['min_qty']) : 0;
                $qty_b = isset($b['min_qty']) ? intval($b['min_qty']) : 0;
                return $qty_b - $qty_a;
            });
            
            foreach ($rule['tiered_pricing'] as $tier) {
                // Check if tier applies to this variation (if viewing a variation)
                if ($variation_id) {
                    // Check new single variation format first
                    $tier_variation = isset($tier['variation']) ? $tier['variation'] : null;
                    // Backward compatibility: check old variations array format
                    if ($tier_variation === null && isset($tier['variations']) && is_array($tier['variations'])) {
                        $tier_variations = $tier['variations'];
                        // If variations are specified and current variation is not in the list, skip this tier
                        if (!empty($tier_variations) && !in_array($variation_id, $tier_variations)) {
                            continue;
                        }
                    } elseif ($tier_variation !== null && $tier_variation !== 'all' && intval($tier_variation) !== $variation_id) {
                        // If a specific variation is set and it doesn't match, skip this tier
                        continue;
                    }
                }
                
                if (!empty($tier['min_qty']) && !empty($tier['price']) && $quantity >= intval($tier['min_qty'])) {
                    // Check max_qty constraint if set
                    if (!empty($tier['max_qty']) && $quantity > intval($tier['max_qty'])) {
                        continue;
                    }
                    $applicable_tier = $tier;
                    break;
                }
            }
            
            if ($applicable_tier) {
                $discount_type = isset($applicable_tier['discount_type']) ? $applicable_tier['discount_type'] : 'fixed';
                $tier_price = floatval($applicable_tier['price']);
                
                switch ($discount_type) {
                    case 'percentage':
                        $calculated_price = $base_price - ($base_price * $tier_price / 100);
                        // Ensure price doesn't go negative
                        return max(0, $calculated_price);
                    case 'fixed':
                        $calculated_price = $base_price - $tier_price;
                        // Ensure price doesn't go negative
                        return max(0, $calculated_price);
                    default:
                        // Direct price override
                        return max(0, $tier_price);
                }
            } else {
                return $base_price;
            }
        }
        
        return $base_price;
    }

}