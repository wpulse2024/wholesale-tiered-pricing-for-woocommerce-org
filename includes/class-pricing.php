<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_Role_Pricing_Engine {

    public function __construct() {
        add_filter('woocommerce_product_get_price', array($this, 'get_role_based_price'), 99, 2);
        add_filter('woocommerce_product_variation_get_price', array($this, 'get_role_based_price'), 99, 2);
        add_filter('woocommerce_get_price_html', array($this, 'get_price_html'), 99, 2);
        add_action('woocommerce_before_calculate_totals', array($this, 'update_cart_prices'), 99);
        add_filter('woocommerce_cart_item_price', array($this, 'cart_item_price_html'), 10, 3);
    }

    public function get_role_based_price($price, $product) {
        if (is_admin() && !wp_doing_ajax()) {
            return $price;
        }

        $product_id = $product->get_id();
        $rules = get_post_meta($product_id, '_role_pricing_rules', true);
        
        if (empty($rules)) {
            return $price;
        }

        $current_user_role = $this->get_current_user_role();
        $quantity = 1;
        
        foreach ($rules as $rule) {
            if ($rule['role'] === $current_user_role) {
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
            return $price_html;
        }

        $current_user_role = $this->get_current_user_role();
        $original_price = $product->get_regular_price();
        
        foreach ($rules as $rule) {
            if ($rule['role'] === $current_user_role) {
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
                continue;
            }

            $current_user_role = $this->get_current_user_role();
            $original_price = $product->get_regular_price();
            
            foreach ($rules as $rule) {
                if ($rule['role'] === $current_user_role) {
                    $new_price = $this->calculate_price($original_price, $rule, $quantity);
                    $cart_item['data']->set_price($new_price);
                    break;
                }
            }
        }
    }

    public function cart_item_price_html($price_html, $cart_item, $cart_item_key) {
        $product = $cart_item['data'];
        $product_id = $product->get_id();
        
        $rules = get_post_meta($product_id, '_role_pricing_rules', true);
        
        if (empty($rules)) {
            return $price_html;
        }

        $original_price = $product->get_regular_price();
        $current_price = $product->get_price();
        
        if ($current_price < $original_price) {
            return '<del>' . wc_price($original_price) . '</del> <ins>' . wc_price($current_price) . '</ins>';
        }

        return $price_html;
    }

    private function calculate_price($base_price, $rule, $quantity) {
        if (!empty($rule['tiered_pricing'])) {
            $applicable_tier = $this->find_applicable_tier($rule['tiered_pricing'], $quantity);
            
            if ($applicable_tier && !empty($applicable_tier['price'])) {
                return floatval($applicable_tier['price']);
            }
        }

        switch ($rule['price_type']) {
            case 'fixed':
                return !empty($rule['price_value']) ? floatval($rule['price_value']) : $base_price;
            
            case 'discount':
                $discount_percent = floatval($rule['price_value']);
                return $base_price * (1 - ($discount_percent / 100));
            
            case 'markup':
                $markup_percent = floatval($rule['price_value']);
                return $base_price * (1 + ($markup_percent / 100));
            
            default:
                return $base_price;
        }
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