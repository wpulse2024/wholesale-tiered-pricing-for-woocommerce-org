<?php
class WHTPRole_Pricing_Show_Message {
    public function __construct() {
        // Cart items
        add_filter('woocommerce_cart_item_name', array($this, 'show_discount_in_cart'), 20, 3);

        // Customer Order details (frontend)
        add_action('woocommerce_order_item_meta_end', array($this, 'show_discount_in_order_details'), 10, 3);

        // Admin Order details (backend)
        add_action('woocommerce_before_order_itemmeta', array($this, 'show_discount_in_admin_order_details'), 10, 3);
    }

    /* ----------------- CART ------------------ */
    public function show_discount_in_cart($product_name, $cart_item, $cart_item_key) {
        if (isset($cart_item['data']) && is_object($cart_item['data'])) {
            $product = $cart_item['data'];
            $quantity = $cart_item['quantity'];
            ob_start();
            $this->show_discount_message($product, true, false, $quantity);
            $product_name .= ob_get_clean();
        }
        return $product_name;
    }

    /* ----------------- CUSTOMER ORDER DETAILS ------------------ */
    public function show_discount_in_order_details($item_id, $item, $order) {
        $product = $item->get_product();
        if ($product) {
            $this->show_discount_message($product);
        }
    }

    /* ----------------- ADMIN ORDER DETAILS ------------------ */
    public function show_discount_in_admin_order_details($item_id, $item, $product) {
        if (!is_admin() || !$product instanceof WC_Product) {
            return;
        }
        $quantity = $item->get_quantity();
        echo '<div style="margin-top:4px;font-size:12px;color:#555;">';
        $this->show_discount_message($product, true, true, $quantity);
        echo '</div>';
    }

    /* ----------------- CORE MESSAGE ------------------ */
    private function show_discount_message($product, $echo = true, $for_admin = false, $quantity = 1) {
        global $helper;

        if (!$product) {
            return;
        }

        $product_id   = $product->get_id();
        $rules        = get_post_meta($product_id, '_role_pricing_rules', true);
        $globalRules  = get_option('whtprole_pricing_global_rules', []);
        if (empty($rules) || (isset($helper) && !$helper->enableToShowsTable($product_id))) {
            if (empty($globalRules)) {
                return;
            }
            $rules = $globalRules;
        }

        $current_user_role = $this->get_current_user_role();
        $original_price    = (float) $product->get_regular_price();
        $rules             = is_array($rules) ? $rules : json_decode($rules, true);

        foreach ($rules as $rule) {
            if ($rule['role'] === $current_user_role) {
                $new_price = $this->calculate_price($original_price, $rule, $quantity);
                if ($new_price < $original_price) {
                    $savings          = ($original_price - $new_price);
                    $savings_percent  = ($savings / $original_price) * 100;

                    $role_label = ucfirst($rule['role']);
                    $discount_text = sprintf(
                        esc_html__('Applied Tier: %s – Save %s (%.1f%%)', 'wholesale-tiered-pricing-for-woocommerce'),
                        esc_html($role_label),
                        wc_price($savings),
                        $savings_percent
                    );

                    $html = '<div class="role-pricing-savings" style="font-size:12px;color:#128219;margin-top:3px;">' . $discount_text . '</div>';

                    if ($echo) {
                        echo wp_kses_post($html);
                    } else {
                        return $html;
                    }
                }
                break;
            }
        }
    }

    /* ----------------- PRICE CALCULATION ------------------ */
    private function calculate_price($base_price, $rule, $quantity) {
        // Check tiered pricing first
        if (!empty($rule['tiered_pricing'])) {
            $applicable_tier = null;
            // Sort tiers by quantity descending to find the highest applicable tier
            usort($rule['tiered_pricing'], function($a, $b) {
                return $b['min_qty'] - $a['min_qty'];
            });
            
            foreach ($rule['tiered_pricing'] as $tier) {
                if (!empty($tier['min_qty']) && !empty($tier['price']) && $quantity >= $tier['min_qty']) {
                    $applicable_tier = $tier;
                    break;
                }
            }
            if ($applicable_tier) {
                switch ($applicable_tier['discount_type']) {
                    case 'percentage':
                        return $base_price - ($base_price * floatval($applicable_tier['price']) / 100);
                    case 'fixed':
                        return $base_price - floatval($applicable_tier['price']);
                    default:
                        return floatval($applicable_tier['price']);
                }
            } else {
                return $base_price;
            }
        }
    }

    /* ----------------- USER ROLE ------------------ */
    private function get_current_user_role() {
        if (!is_user_logged_in()) {
            return 'guest';
        }

        $user = wp_get_current_user();
        return !empty($user->roles) ? $user->roles[0] : 'customer';
    }
}
