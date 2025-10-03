<?php
/**
 * AJAX Handlers for Role-Based Pricing
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Role_Pricing_Ajax {

    public function __construct() {
        add_action('wp_ajax_get_role_based_price', array($this, 'get_role_based_price'));
        add_action('wp_ajax_nopriv_get_role_based_price', array($this, 'get_role_based_price'));
        add_action('wp_ajax_get_variation_pricing_rules', array($this, 'get_variation_pricing_rules'));
        add_action('wp_ajax_nopriv_get_variation_pricing_rules', array($this, 'get_variation_pricing_rules'));
        add_action('wp_ajax_validate_quantity_rules', array($this, 'validate_quantity_rules'));
        add_action('wp_ajax_nopriv_validate_quantity_rules', array($this, 'validate_quantity_rules'));
        add_action('wp_ajax_wc_role_pricing_get_pricing_rules', array($this, 'get_pricing_rules'));
        add_action('wp_ajax_nopriv_wc_role_pricing_get_pricing_rules', array($this, 'get_pricing_rules'));
        add_action('wp_ajax_wc_role_pricing_save_pricing_rules', array($this, 'save_pricing_rules'));
        add_action('wp_ajax_nopriv_wc_role_pricing_save_pricing_rules', array($this, 'save_pricing_rules'));
        add_action('wp_ajax_wc_role_pricing_get_product_settings', array($this, 'get_product_settings'));
        add_action('wp_ajax_nopriv_wc_role_pricing_get_product_settings', array($this, 'get_product_settings'));
        add_action('wp_ajax_wc_role_pricing_save_product_settings', array($this, 'save_product_settings'));
        add_action('wp_ajax_nopriv_wc_role_pricing_save_product_settings', array($this, 'save_product_settings'));
        add_action('wp_ajax_wc_role_pricing_save_general_settings', array($this, 'save_general_settings'));
        add_action('wp_ajax_nopriv_wc_role_pricing_save_general_settings', array($this, 'save_general_settings'));
        add_action('wp_ajax_wc_role_pricing_get_general_settings', array($this, 'get_general_settings'));
        add_action('wp_ajax_nopriv_wc_role_pricing_get_general_settings', array($this, 'get_general_settings'));
    }
    public function get_general_settings() {
        $nonce = $_POST['nonce'];
        if (!wp_verify_nonce($nonce, 'wc_role_pricing_get_pricing_rules')) {
            wp_send_json_error(array('message' => 'Invalid nonce'));
        }

        $settings = get_option('wc_role_pricing_save_general_settings', []);
        $settings = json_decode($settings, true);
        wp_send_json_success($settings);
    }
    public function save_general_settings() {
        $nonce = $_POST['nonce'];
        if (!wp_verify_nonce($nonce, 'wc_role_pricing_get_pricing_rules')) {
            wp_send_json_error(array('message' => 'Invalid nonce'));
        }

        $settings = stripslashes($_POST['settings']);
        update_option('wc_role_pricing_save_general_settings', $settings);
        wp_send_json_success(array('message' => 'General settings saved successfully'));
    }
    public function get_product_settings() {
        $nonce = $_POST['nonce'];
        if (!wp_verify_nonce($nonce, 'wc_role_pricing_get_pricing_rules')) {
            wp_send_json_error(array('message' => 'Invalid nonce'));
        }

        $settings = get_option('wc_role_global_product_settings', []);
        $settings = json_decode($settings, true);
        wp_send_json_success($settings);
    }
    public function save_product_settings() {
        $nonce = $_POST['nonce'];
        if (!wp_verify_nonce($nonce, 'wc_role_pricing_get_pricing_rules')) {
            wp_send_json_error(array('message' => 'Invalid nonce'));
        }

        $settings = stripslashes($_POST['settings']);
        update_option('wc_role_global_product_settings', $settings);
        wp_send_json_success(array('message' => 'Product settings saved successfully'));
    }

    public function get_pricing_rules() {

        $nonce = $_POST['nonce'];
        if (!wp_verify_nonce($nonce, 'wc_role_pricing_get_pricing_rules')) {
            wp_send_json_error(array('message' => 'Invalid nonce'));
        }

        $rules = get_option('wc_role_pricing_global_rules', []);
        wp_send_json_success($rules);
    }

    public function save_pricing_rules() {
        $nonce = $_POST['nonce'];
        if (!wp_verify_nonce($nonce, 'wc_role_pricing_get_pricing_rules')) {
            wp_send_json_error(array('message' => 'Invalid nonce'));
        }

        $rules = json_decode(stripslashes($_POST['rules']), true);
        update_option('wc_role_pricing_global_rules', $rules);
        wp_send_json_success(array('message' => 'Pricing rules saved successfully'));
    }

    /**
     * Get role-based price for a product and quantity
     */
    public function get_role_based_price() {
        check_ajax_referer('wc_add_to_cart_nonce', 'nonce');
        
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
            if ($rule['role'] === $current_user_role) {
                $new_price = $this->calculate_price($product->get_regular_price(), $rule, $quantity);
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
    public function get_variation_pricing_rules() {
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
    public function validate_quantity_rules() {
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
     * Calculate price based on rule and quantity
     */
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
                return floatval($applicable_tier['price']);
            }
        }
        
        // Apply regular pricing rule
        switch ($rule['price_type']) {
            case 'fixed':
                return floatval($rule['price_value']);
            
            case 'discount':
                return $base_price * (1 - (floatval($rule['price_value']) / 100));
            
            case 'markup':
                return $base_price * (1 + (floatval($rule['price_value']) / 100));
            
            default:
                return $base_price;
        }
    }
    
    /**
     * Get current user role
     */
    private function get_current_user_role() {
        if (!is_user_logged_in()) {
            return '';
        }
        
        $user = wp_get_current_user();
        return !empty($user->roles) ? $user->roles[0] : '';
    }
}

// Initialize AJAX handlers
new WC_Role_Pricing_Ajax();