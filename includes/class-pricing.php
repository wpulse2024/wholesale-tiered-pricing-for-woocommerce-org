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
        $rules = get_post_meta($product_id, '_role_pricing_rules', true);
        
        if (empty($rules)) {
            $globalRules = get_option('whtprole_pricing_global_rules', []);
            if (empty($globalRules)) {
                return $price;
            } else {
                $rules = $globalRules;
            }
            return $price;
        }

        $current_user_role = $this->get_current_user_role();
        $is_guest = ($current_user_role === 'guest');
        $quantity = 1;

        foreach ($rules as $rule) {
            // Use helper to check if rule applies
            $rule_roles = isset($rule['roles']) ? $rule['roles'] : (isset($rule['role']) ? $rule['role'] : array());
            $also_for_guest = isset($rule['also_for_guest']) ? $rule['also_for_guest'] : false;
            
            if (WHTPRole_Pricing_Helper::rule_applies_to_user($rule_roles, $current_user_role, $is_guest, $also_for_guest)) {
                $new_price = $this->calculate_price($price, $rule, $quantity);
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
        $rules = get_post_meta($product_id, '_role_pricing_rules', true);
        
        if (empty($rules)) {
            $globalRules = get_option('whtprole_pricing_global_rules', []);
            if (empty($globalRules)) {
                return $price_html;
            } else {
                $rules = $globalRules;
            }
            return $price_html;
        }

        $current_user_role = $this->get_current_user_role();
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
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }
        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            $product = $cart_item['data'];
            $product_id = $product->get_id();
            $quantity = $cart_item['quantity'];
            $rules = get_post_meta($product_id, '_role_pricing_rules', true);
            
            if (empty($rules)) {
                $globalRules = get_option('whtprole_pricing_global_rules', []);
                if (empty($globalRules)) {
                    continue;
                } else {
                    $rules = $globalRules;
                }
            }
            $rules = is_array($rules) ? $rules : json_decode($rules, true);

            $current_user_role = $this->get_current_user_role();
            $is_guest = ($current_user_role === 'guest');
            $original_price = $product->get_price();
            $rules = is_array($rules) ? $rules : json_decode($rules, true);
            foreach ($rules as $rule) {
                // Use helper to check if rule applies
                $rule_roles = isset($rule['roles']) ? $rule['roles'] : (isset($rule['role']) ? $rule['role'] : array());
                $also_for_guest = isset($rule['also_for_guest']) ? $rule['also_for_guest'] : false;
                
                if (WHTPRole_Pricing_Helper::rule_applies_to_user($rule_roles, $current_user_role, $is_guest, $also_for_guest)) {
                    $new_price = $this->calculate_price($original_price, $rule, $quantity);
                    $cart_item['data']->set_price($new_price);
                    break;
                }
            }
        }
    }

    public static function getPrice($price, $discount_type, $base_price ) {
        if ($discount_type === 'percentage') {
            return $base_price - ($base_price * $price / 100);
        } else if ($discount_type === 'fixed') {
            return $base_price - $price;
        } else {
            return $price;
        }
    }

    private function calculate_price($base_price, $rule, $quantity) {
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

    private function find_applicable_tier($tiers, $quantity) {
        $applicable_tier = null;
        $highest_min_qty = 0;
        foreach ($tiers as $tier) {
            if (empty($tier['min_qty']) || empty($tier['price'])) {
                continue;
            }
            
            $tier_min_qty = intval($tier['min_qty']);
            
            if ($quantity >= $tier_min_qty && $tier_min_qty > $highest_min_qty) {
                $applicable_tier = $tier;
                $highest_min_qty = $tier_min_qty;
            }
        }
        
        return $applicable_tier;
    }

    private function get_current_user_role() {
        if (!is_user_logged_in()) {
            return 'guest';
        }
        
        $user = wp_get_current_user();
        return !empty($user->roles) ? $user->roles[0] : 'customer';
    }
}