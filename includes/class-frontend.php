<?php
if (!defined('ABSPATH')) {
    exit;
}

class WHTPRole_Pricing_Frontend {

    public function __construct() {
        add_action('woocommerce_single_product_summary', array($this, 'display_pricing_table'), 15);
        add_filter('woocommerce_quantity_input_args', array($this, 'modify_quantity_args'), 10, 2);
        add_filter('woocommerce_add_to_cart_validation', array($this, 'validate_add_to_cart'), 10, 3);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('woocommerce_before_add_to_cart_button', array($this, 'display_quantity_messages'));
    }

    public function enqueue_scripts() {
        if (is_product()) {
            wp_enqueue_script('wholesale-tiered-pricing-for-woocommerce', WHTPROLE_PRICING_PLUGIN_URL . 'assets/frontend.js', array('jquery'), WHTPROLE_PRICING_VERSION, true);
            
            wp_localize_script('wholesale-tiered-pricing-for-woocommerce', 'wholesaleTieredPricingVars', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wholesale-tiered-pricing-for-woocommerce-ajax')
            ));
        }
    }

    public function display_pricing_table() {
        global $product;
        $helper = new WHTPRole_Pricing_Helper();
        if (!$helper->validation($product->get_id())) {
            return;
        }
        if (!$helper->enableToShowsTable($product->get_id())) {
            return;
        }
        $rules = get_post_meta($product->get_id(), '_role_pricing_rules', true);
        $globalRules = get_option('wc_role_pricing_global_rules', []);

        if (empty($rules)) {
            if (empty($globalRules)) {
                return;
            }
            $rules = $globalRules;
        }

        $current_user_role = $this->get_current_user_role();
        $applicable_rules = array();

        foreach ($rules as $rule) {
            if ($rule['role'] === $current_user_role) {
                $applicable_rules[] = $rule;
            }
        }

        if (empty($applicable_rules)) {
            return;
        }

        $templatePath = $helper->getTemplatePath();
   
        include_once($templatePath);
    }

    public function modify_quantity_args($args, $product) {
        $rules = get_post_meta($product->get_id(), '_role_pricing_rules', true);
        if (empty($rules)) {
            return $args;
        }

        $current_user_role = $this->get_current_user_role();
        
        foreach ($rules as $rule) {
            if ($rule['role'] === $current_user_role) {
                if ($rule['min_qty'] > 0) {
                    $args['min_value'] = $rule['min_qty'];
                }
                
                if ($rule['max_qty'] > 0) {
                    $args['max_value'] = $rule['max_qty'];
                }
                
                if ($rule['step_qty'] > 1) {
                    $args['step'] = $rule['step_qty'];
                }
                
                break;
            }
        }

        return $args;
    }

    public function display_quantity_messages() {
        global $product;
        
        $rules = get_post_meta($product->get_id(), '_role_pricing_rules', true);
        if (empty($rules)) {
            return;
        }

        $current_user_role = $this->get_current_user_role();
        
        foreach ($rules as $rule) {
            if ($rule['role'] === $current_user_role) {
                $messages = array();
                
                if ($rule['min_qty'] > 1) {
                    /* translators: %s = the savings amount formatted as a price */
                    $messages[] = sprintf(__('Minimum order: %d items', 'wholesale-tiered-pricing-for-woocommerce'), $rule['min_qty']);
                }
                
                if ($rule['max_qty'] > 0) {
                    /* translators: %s = the savings amount formatted as a price */
                    $messages[] = sprintf(__('Maximum order: %d items', 'wholesale-tiered-pricing-for-woocommerce'), $rule['max_qty']);
                }
                
                if ($rule['step_qty'] > 1) {
                    /* translators: %s = the savings amount formatted as a price */
                    $messages[] = sprintf(__('Must order in multiples of %d', 'wholesale-tiered-pricing-for-woocommerce'), $rule['step_qty']);
                }
                
                if (!empty($messages)) {
                    echo '<div class="wholesale-tiered-pricing-for-woocommerce-notice">';
                    echo '<ul>';
                    foreach ($messages as $message) {
                        echo '<li>' . esc_html($message) . '</li>';
                    }
                    echo '</ul>';
                    echo '</div>';
                }
                
                break;
            }
        }
    }

    public function validate_add_to_cart($passed, $product_id, $quantity) {
        $rules = get_post_meta($product_id, '_role_pricing_rules', true);
        if (empty($rules)) {
            $globalRules = get_option('wc_role_pricing_global_rules', []);
            if (empty($globalRules)) {
                return $passed;
            } else {
                $rules = $globalRules;
            }
            return $passed;
        }

        $current_user_role = $this->get_current_user_role();
        
        foreach ($rules as $rule) {
            if ($rule['role'] === $current_user_role) {
                
                if ($rule['min_qty'] > 0 && $quantity < $rule['min_qty']) {
                    wc_add_notice(
                        /* translators: %s = the savings amount formatted as a price */
                        sprintf(__('Minimum order quantity is %d for this product.', 'wholesale-tiered-pricing-for-woocommerce'), $rule['min_qty']),
                        'error'
                    );
                    return false;
                }
                
                if ($rule['max_qty'] > 0 && $quantity > $rule['max_qty']) {
                    wc_add_notice(
                        /* translators: %s = the savings amount formatted as a price */
                        sprintf(__('Maximum order quantity is %d for this product.', 'wholesale-tiered-pricing-for-woocommerce'), $rule['max_qty']),
                        'error'
                    );
                    return false;
                }
                
                if ($rule['step_qty'] > 1) {
                    $remainder = ($quantity - $rule['min_qty']) % $rule['step_qty'];
                    if ($remainder !== 0) {
                        wc_add_notice(
                            /* translators: %s = the savings amount formatted as a price */
                            sprintf(__('Quantity must be in multiples of %d.', 'wholesale-tiered-pricing-for-woocommerce'), $rule['step_qty']),
                            'error'
                        );
                        return false;
                    }
                }
                
                break;
            }
        }

        return $passed;
    }

    private function get_current_user_role() {
        if (!is_user_logged_in()) {
            return 'guest';
        }
        
        $user = wp_get_current_user();
        return !empty($user->roles) ? $user->roles[0] : 'customer';
    }
}