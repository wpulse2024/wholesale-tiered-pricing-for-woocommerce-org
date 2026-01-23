<?php
/**
 * AJAX Handlers for Role-Based Pricing
 */

if (!defined('ABSPATH')) {
    exit;
}

class WHTPRole_Pricing_Ajax {

    public function __construct() {
        // when plugin activate save save_pricing_rules
        add_action('admin_init', array($this, 'save_pricing_global_settings_on_activation')); 
        add_action('wp_ajax_whtprole_get_role_based_price', array($this, 'whtprole_get_role_based_price'));
        add_action('wp_ajax_nopriv_whtprole_get_role_based_price', array($this, 'whtprole_get_role_based_price'));
        add_action('wp_ajax_whtprole_get_variation_pricing_rules', array($this, 'whtprole_get_variation_pricing_rules'));
        add_action('wp_ajax_nopriv_whtprole_get_variation_pricing_rules', array($this, 'whtprole_get_variation_pricing_rules'));
        add_action('wp_ajax_whtprole_validate_quantity_rules', array($this, 'whtprole_validate_quantity_rules'));
        add_action('wp_ajax_nopriv_whtprole_validate_quantity_rules', array($this, 'whtprole_validate_quantity_rules'));
        add_action('wp_ajax_whtprole_pricing_get_pricing_rules', array($this, 'get_pricing_rules'));
        add_action('wp_ajax_nopriv_whtprole_pricing_get_pricing_rules', array($this, 'get_pricing_rules'));
        add_action('wp_ajax_whtprole_pricing_save_pricing_rules', array($this, 'save_pricing_rules'));
        add_action('wp_ajax_nopriv_whtprole_pricing_save_pricing_rules', array($this, 'save_pricing_rules'));
        add_action('wp_ajax_whtprole_pricing_get_product_settings', array($this, 'get_product_settings'));
        add_action('wp_ajax_nopriv_whtprole_pricing_get_product_settings', array($this, 'get_product_settings'));
        add_action('wp_ajax_whtprole_pricing_save_product_settings', array($this, 'save_product_settings'));
        add_action('wp_ajax_nopriv_whtprole_pricing_save_product_settings', array($this, 'save_product_settings'));
        add_action('wp_ajax_whtprole_pricing_save_general_settings', array($this, 'save_general_settings'));
        add_action('wp_ajax_nopriv_whtprole_pricing_save_general_settings', array($this, 'save_general_settings'));
        add_action('wp_ajax_whtprole_pricing_get_general_settings', array($this, 'get_general_settings'));
        add_action('wp_ajax_nopriv_whtprole_pricing_get_general_settings', array($this, 'get_general_settings'));
        add_action('wp_ajax_whtprole_calculate_savings', array($this, 'calculate_savings'));
        add_action('wp_ajax_nopriv_whtprole_calculate_savings', array($this, 'calculate_savings'));
    }
    public function get_general_settings() {
        $nonce = sanitize_text_field($_POST['nonce']);
        if (!wp_verify_nonce(wp_unslash($nonce), 'whtprole_pricing_get_pricing_rules')) {
            wp_send_json_error(array('message' => 'Invalid nonce'));
        }

        $settings = get_option('whtprole_pricing_save_general_settings', []);
        $settings = is_array($settings) ? $settings : json_decode($settings, true);
        wp_send_json_success($settings);
    }
    public function save_general_settings() {
        $nonce = sanitize_text_field($_POST['nonce']);
        if (!wp_verify_nonce(wp_unslash($nonce), 'whtprole_pricing_get_pricing_rules')) {
            wp_send_json_error(array('message' => 'Invalid nonce'));
        }

        $settings = is_array($_POST['settings']) ? $_POST['settings'] : json_decode(stripslashes($_POST['settings']), true);
        $settings = array(
            'showTieredPricing' => isset($settings['showTieredPricing']) && $settings['showTieredPricing'] == true ? true : false,
            'defaultTemplate' => sanitize_text_field($settings['defaultTemplate']),
            'pricingTitle' => sanitize_text_field($settings['pricingTitle']),
            'position' => sanitize_text_field($settings['position']),
            'showQuantityColumn' => isset($settings['showQuantityColumn']) && $settings['showQuantityColumn'] == true ? true : false,
            'showDiscountColumn' => isset($settings['showDiscountColumn']) && $settings['showDiscountColumn'] == true ? true : false,
            'responsiveTable' => isset($settings['responsiveTable']) && $settings['responsiveTable'] == true ? true : false,
            'activePricingColor' => sanitize_text_field($settings['activePricingColor']),
            'quantityLabel' => sanitize_text_field($settings['quantityLabel']),
            'discountLabel' => sanitize_text_field($settings['discountLabel']),
            'priceLabel' => sanitize_text_field($settings['priceLabel'])
        );
        update_option('whtprole_pricing_save_general_settings', sanitize_text_field(json_encode($settings)));
        wp_send_json_success(array('message' => 'General settings saved successfully'));
    }

    public function save_pricing_global_settings_on_activation() {
        $getGeneralSettings = get_option('whtprole_pricing_save_general_settings', []);
        if (empty($getGeneralSettings)) {
            $default_settings = [
                'showTieredPricing'   => true,
                'defaultTemplate'     => 'table',
                'pricingTitle'        => __('Buy more save more!', 'wholesale-tiered-pricing-for-woocommerce'),
                'position'            => 'above_add_to_cart',
                'showQuantityColumn'  => true,
                'showDiscountColumn'  => true,
                'responsiveTable'     => true,
                'activePricingColor'  => '#ff9a00',
                'quantityLabel'       => __('Quantity', 'wholesale-tiered-pricing-for-woocommerce'),
                'discountLabel'       => __('Discount', 'wholesale-tiered-pricing-for-woocommerce'),
                'priceLabel'          => __('Price', 'wholesale-tiered-pricing-for-woocommerce'),
            ];
            update_option('whtprole_pricing_save_general_settings', sanitize_text_field(json_encode($default_settings)));
        }
    }

    public function get_product_settings() {
        $nonce = sanitize_text_field($_POST['nonce']);
        if (!wp_verify_nonce(wp_unslash($nonce), 'whtprole_pricing_get_pricing_rules')) {
            wp_send_json_error(array('message' => 'Invalid nonce'));
        }

        $settings = get_option('whtprole_global_product_settings', []);
        $settings = is_array($settings) ? $settings : json_decode($settings, true);
        wp_send_json_success($settings);
    }
    public function save_product_settings() {
        $nonce = sanitize_text_field($_POST['nonce']);
        if (!wp_verify_nonce(wp_unslash($nonce), 'whtprole_pricing_get_pricing_rules')) {
            wp_send_json_error(array('message' => 'Invalid nonce'));
        }

        $settings = is_array($_POST['settings']) ? $_POST['settings'] : json_decode(stripslashes($_POST['settings']), true);
        $settings =array(
            'include_products' => !empty($settings['include_products']) ? array_map('intval', $settings['include_products']) : [],
            'include_categories' => !empty($settings['include_categories']) ? array_map('intval', $settings['include_categories']) : [],
            'exclude_products' => !empty($settings['exclude_products']) ? array_map('intval', $settings['exclude_products']) : [],
            'exclude_categories' => !empty($settings['exclude_categories']) ? array_map('intval', $settings['exclude_categories']) : [],
            'apply_type' => sanitize_text_field($settings['apply_type'])
        );
        update_option('whtprole_global_product_settings', sanitize_text_field(json_encode($settings)));
        wp_send_json_success(array('message' => 'Product settings saved successfully'));
    }

    

    public function get_pricing_rules() {

        $nonce = sanitize_text_field($_POST['nonce']);
        if (!wp_verify_nonce(wp_unslash($nonce), 'whtprole_pricing_get_pricing_rules')) {
            wp_send_json_error(array('message' => 'Invalid nonce'));
        }

        $rules = get_option('whtprole_pricing_global_rules', []);
        $rules = is_array($rules) ? $rules : json_decode($rules, true);
        wp_send_json_success($rules);
    }

    public function save_pricing_rules() {
        $nonce = sanitize_text_field( $_POST['nonce'] );
        if (!wp_verify_nonce(wp_unslash($nonce), 'whtprole_pricing_get_pricing_rules')) {
            wp_send_json_error(array('message' => 'Invalid nonce'));
        }

        $rules = is_array($_POST['rules']) ? $this->sanitizeRulesData($_POST['rules']) : $this->sanitizeRulesData(stripslashes($_POST['rules']));
        $sanitized_rules = array();
        $rules = is_array($rules) ? $rules : json_decode(stripslashes($rules), true);
        foreach ($rules as $rule) {
            $sanitized_rules[] = array(
                'id' => sanitize_text_field($rule['id']),
                'role' => sanitize_text_field($rule['role']),
                'min_qty' => intval($rule['min_qty']),
                'max_qty' => !empty($rule['max_qty']) ? intval($rule['max_qty']) : 0,
                'step_qty' => intval($rule['step_qty']),
                'tiered_pricing' => $this->sanitizeTieredPricingData($rule['tiered_pricing'])
            );
        }

        update_option('whtprole_pricing_global_rules', sanitize_text_field(json_encode($sanitized_rules)));
        wp_send_json_success(array('message' => 'Pricing rules saved successfully'));
    }

    private function sanitizeRulesData($rules) {
        $sanitized_rules = array();
        $rules = is_array($rules) ? $rules : json_decode(stripslashes($rules), true);
        foreach ($rules as $rule) {
            $sanitized_rules[] = array(
                'id' => sanitize_text_field($rule['id']),
                'role' => sanitize_text_field($rule['role']),
                'min_qty' => intval($rule['min_qty']),
                'max_qty' => !empty($rule['max_qty']) ? intval($rule['max_qty']) : 0,
                'step_qty' => intval($rule['step_qty']),
                'tiered_pricing' => $this->sanitizeTieredPricingData($rule['tiered_pricing'])
            );
        }
        return $sanitized_rules;
    }

    private function sanitizeTieredPricingData($tiers) {
        $sanitized_tiers = array();
        foreach ($tiers as $tier) {
            $sanitized_tiers[] = array(
                'id' => sanitize_text_field($tier['id']),
                'min_qty' => intval($tier['min_qty']),
                'discount_type' => sanitize_text_field($tier['discount_type']),
                'price' => floatval($tier['price'])
            );
        }
        return $sanitized_tiers;
    }

    /**
     * Get role-based price for a product and quantity
     */
    public function whtprole_get_role_based_price() {
        // Check nonce - support both WooCommerce and custom nonce
        if (isset($_POST['nonce'])) {
            $nonce = sanitize_text_field($_POST['nonce']);
            if (!wp_verify_nonce($nonce, 'wholesale-tiered-pricing-for-woocommerce-ajax') && 
                !wp_verify_nonce($nonce, 'wc_add_to_cart_nonce')) {
                wp_send_json_error(array('message' => 'Invalid nonce'));
                return;
            }
        }
        
        $product_id = intval($_POST['product_id']);
        $quantity = intval($_POST['quantity']);
        
        if (!$product_id || !$quantity) {
            wp_die();
        }
        
        $product = wc_get_product($product_id);
        if (!$product) {
            wp_die();
        }
        
        $rules = get_post_meta($product_id, '_role_pricing_rules', true);
        if (empty($rules)) {
            wp_send_json_success(array('price_html' => $product->get_price_html()));
            return;
        }
        
        $current_user_role = $this->get_current_user_role();
        $new_price = $product->get_price();
        
        foreach ($rules as $rule) {
            // Global role (guest) applies to all users
            if ($rule['role'] === 'guest' || $rule['role'] === $current_user_role) {
                $new_price = $this->calculate_price($product->get_price(), $rule, $quantity);
                break;
            }
        }
        
        // Create a temporary product object with the new price
        $temp_product = clone $product;
        $temp_product->set_price($new_price);
        
        wp_send_json_success(array(
            'price_html' => $temp_product->get_price_html(),
            'price' => $new_price
        ));
    }
    
    /**
     * Get pricing rules for a variation
     */
    public function whtprole_get_variation_pricing_rules() {
        check_ajax_referer('wc_add_to_cart_nonce', 'nonce');
        
        $variation_id = intval($_POST['variation_id']);
        
        if (!$variation_id) {
            wp_die();
        }
        
        $variation = wc_get_product($variation_id);
        if (!$variation) {
            wp_die();
        }
        
        // Get parent product ID
        $parent_id = $variation->get_parent_id();
        $rules = get_post_meta($parent_id, '_role_pricing_rules', true);
        
        if (empty($rules)) {
            wp_send_json_success(array('pricing_table' => ''));
            return;
        }
        
        $current_user_role = $this->get_current_user_role();
        $applicable_rules = array();
        
        foreach ($rules as $rule) {
            if ($rule['role'] === $current_user_role || empty($current_user_role)) {
                $applicable_rules[] = $rule;
            }
        }
        
        if (empty($applicable_rules)) {
            wp_send_json_success(array('pricing_table' => ''));
            return;
        }
        
        ob_start();
        $this->render_pricing_table($applicable_rules);
        $pricing_table = ob_get_clean();
        
        wp_send_json_success(array('pricing_table' => $pricing_table));
    }
    
    /**
     * Validate quantity rules
     */
    public function whtprole_validate_quantity_rules() {
        check_ajax_referer('wc_add_to_cart_nonce', 'nonce');
        
        $product_id = intval($_POST['product_id']);
        $quantity = intval($_POST['quantity']);
        
        if (!$product_id || !$quantity) {
            wp_send_json_error(array('message' => 'Invalid parameters'));
            return;
        }
        
        $rules = get_post_meta($product_id, '_role_pricing_rules', true);
        if (empty($rules)) {
            wp_send_json_success(array('valid' => true));
            return;
        }
        
        $current_user_role = $this->get_current_user_role();
        
        foreach ($rules as $rule) {
            if ($rule['role'] === $current_user_role) {
                $validation = $this->validate_quantity($quantity, $rule);
                if (!$validation['valid']) {
                    wp_send_json_error($validation);
                    return;
                }
            }
        }
        
        wp_send_json_success(array('valid' => true));
    }
    
    /**
     * Validate quantity against rules
     */
    private function validate_quantity($quantity, $rule) {
        $messages = array();
        
        // Check minimum quantity
        if ($rule['min_qty'] > 0 && $quantity < $rule['min_qty']) {
            return array(
                'valid' => false,
                /* translators: %s = the savings amount formatted as a price */
                'message' => sprintf(__('Minimum quantity required: %d', 'wholesale-tiered-pricing-for-woocommerce'), $rule['min_qty'])
            );
        }
        
        // Check maximum quantity
        if ($rule['max_qty'] > 0 && $quantity > $rule['max_qty']) {
            return array(
                'valid' => false,
                /* translators: %s = the savings amount formatted as a price */
                'message' => sprintf(__('Maximum quantity allowed: %d', 'wholesale-tiered-pricing-for-woocommerce'), $rule['max_qty'])
            );
        }
        
        // Check step quantity
        if ($rule['step_qty'] > 1 && ($quantity - $rule['min_qty']) % $rule['step_qty'] !== 0) {
            return array(
                'valid' => false,
                /* translators: %s = the savings amount formatted as a price */
                'message' => sprintf(__('Quantity must be in multiples of %d', 'wholesale-tiered-pricing-for-woocommerce'), $rule['step_qty'])
            );
        }
        
        return array('valid' => true);
    }
    
    /**
     * Render pricing table
     */
    private function render_pricing_table($rules) {
        echo '<h3>' . esc_html(__('Pricing Information', 'wholesale-tiered-pricing-for-woocommerce')) . '</h3>';
        
        foreach ($rules as $rule) {
            if (!empty($rule['tiered_pricing'])) {
                echo '<table class="pricing-table">';
                echo '<thead><tr><th>' .esc_html(__('Quantity', 'wholesale-tiered-pricing-for-woocommerce')) . '</th><th>' . esc_html(__('Price', 'wholesale-tiered-pricing-for-woocommerce')) . '</th></tr></thead>';
                echo '<tbody>';
                
                // Sort tiered pricing by quantity
                usort($rule['tiered_pricing'], function($a, $b) {
                    return $a['min_qty'] - $b['min_qty'];
                });
                
                foreach ($rule['tiered_pricing'] as $tier) {
                    if (!empty($tier['min_qty']) && !empty($tier['price'])) {
                        echo '<tr>';
                        echo '<td>' . esc_html($tier['min_qty']) . '+</td>';
                        echo '<td>' . esc_html(wc_price($tier['price'])) . '</td>';
                        echo '</tr>';
                    }
                }
                
                echo '</tbody></table>';
            }
        }
    }
    
    /**
     * Calculate price based on rule and quantity (Improved with better edge case handling)
     */
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
    
    /**
     * Calculate savings for a given quantity (Dynamic Savings Calculator)
     */
    public function calculate_savings() {
        check_ajax_referer('wholesale-tiered-pricing-for-woocommerce-ajax', 'nonce');
        
        $product_id = intval($_POST['product_id']);
        $quantity = intval($_POST['quantity']);
        
        if (!$product_id || !$quantity) {
            wp_send_json_error(array('message' => 'Invalid parameters'));
            return;
        }
        
        $product = wc_get_product($product_id);
        if (!$product) {
            wp_send_json_error(array('message' => 'Product not found'));
            return;
        }
        
        $rules = get_post_meta($product_id, '_role_pricing_rules', true);
        if (empty($rules)) {
            $globalRules = get_option('whtprole_pricing_global_rules', []);
            if (empty($globalRules)) {
                wp_send_json_success(array(
                    'has_discount' => false,
                    'regular_price' => $product->get_price(),
                    'discounted_price' => $product->get_price(),
                    'savings' => 0,
                    'savings_percent' => 0,
                    'total_regular' => $product->get_price() * $quantity,
                    'total_discounted' => $product->get_price() * $quantity,
                    'total_savings' => 0
                ));
                return;
            } else {
                $rules = $globalRules;
            }
        }
        
        if (!is_array($rules)) {
            $rules = json_decode($rules, true);
        }
        
        $current_user_role = $this->get_current_user_role();
        $regular_price = floatval($product->get_price());
        $discounted_price = $regular_price;
        $applicable_rule = null;
        
        foreach ($rules as $rule) {
            // Global role (guest) applies to all users
            if ($rule['role'] === 'guest' || $rule['role'] === $current_user_role) {
                $applicable_rule = $rule;
                $discounted_price = $this->calculate_price($regular_price, $rule, $quantity);
                break;
            }
        }
        
        $savings = $regular_price - $discounted_price;
        $savings_percent = $regular_price > 0 ? ($savings / $regular_price) * 100 : 0;
        $total_regular = $regular_price * $quantity;
        $total_discounted = $discounted_price * $quantity;
        $total_savings = $total_regular - $total_discounted;
        
        wp_send_json_success(array(
            'has_discount' => $savings > 0,
            'regular_price' => $regular_price,
            'discounted_price' => $discounted_price,
            'savings' => $savings,
            'savings_percent' => round($savings_percent, 2),
            'total_regular' => $total_regular,
            'total_discounted' => $total_discounted,
            'total_savings' => $total_savings,
            'quantity' => $quantity,
            'formatted_regular_total' => wc_price($total_regular),
            'formatted_discounted_total' => wc_price($total_discounted),
            'formatted_total_savings' => wc_price($total_savings)
        ));
    }
    
    /**
     * Get current user role
     */
    private function get_current_user_role() {
        if (!is_user_logged_in()) {
            return 'guest';
        }
        
        $user = wp_get_current_user();
        return !empty($user->roles) ? $user->roles[0] : 'customer';
    }
}

// Initialize AJAX handlers
new WHTPRole_Pricing_Ajax();