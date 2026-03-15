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
        add_action('woocommerce_before_add_to_cart_button', array($this, 'display_savings_calculator'), 5);
    }

    public function enqueue_scripts() {
        if (is_product()) {
            // Ensure WooCommerce scripts are loaded first
            wp_enqueue_script('wc-add-to-cart');
            
            wp_enqueue_script('wholesale-tiered-pricing-for-woocommerce', WHTPROLE_PRICING_PLUGIN_URL . 'plugin-assets/frontend.js', array('jquery', 'wc-add-to-cart'), WHTPROLE_PRICING_VERSION, true);
            
            wp_localize_script('wholesale-tiered-pricing-for-woocommerce', 'whtproleTieredPricingVar', array(
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
        
        $product_id = $product->get_id();
        
        // For variations, get rules from parent product
        $parent_id = $product_id;
        if ($product->is_type('variation')) {
            $parent_id = $product->get_parent_id();
        }
        
        if (!$helper->enableToShowsTable($parent_id)) {
            return;
        }

        $rules = WHTPRole_Pricing_Helper::get_rules_for_product($parent_id);
        if (empty($rules)) {
            return;
        }

        $current_user_role = WHTPRole_Pricing_Helper::get_current_user_role();
        $is_guest = ($current_user_role === 'guest');
        $applicable_rules = array();
        
        // Get current variation ID if viewing a variation
        $variation_id = $product->is_type('variation') ? $product->get_id() : null;

        foreach ($rules as $rule) {
            // Check if rule applies to this variation (if viewing a variation)
            // Note: We removed rule-level variations, so this check is for backward compatibility only
            if ($variation_id) {
                $rule_variations = isset($rule['variations']) && is_array($rule['variations']) ? $rule['variations'] : array();
                // If variations are specified and current variation is not in the list, skip this rule
                if (!empty($rule_variations) && !in_array($variation_id, $rule_variations)) {
                    continue;
                }
            }
            
            // Use helper to check if rule applies
            $rule_roles = isset($rule['roles']) ? $rule['roles'] : (isset($rule['role']) ? $rule['role'] : array());
            $also_for_guest = isset($rule['also_for_guest']) ? $rule['also_for_guest'] : false;
            
            if (WHTPRole_Pricing_Helper::rule_applies_to_user($rule_roles, $current_user_role, $is_guest, $also_for_guest)) {
                // Filter tiers by variation if viewing a variation
                $filtered_rule = $rule;
                if ($variation_id && !empty($rule['tiered_pricing']) && is_array($rule['tiered_pricing'])) {
                    $filtered_tiers = array();
                    foreach ($rule['tiered_pricing'] as $tier) {
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
                        // Tier applies to this variation (or all variations)
                        $filtered_tiers[] = $tier;
                    }
                    $filtered_rule['tiered_pricing'] = $filtered_tiers;
                }
                $applicable_rules[] = $filtered_rule;
            }
        }

        if (empty($applicable_rules)) {
            return;
        }

        // Pass variation_id to template if available (for variable products)
        $template_variation_id = $variation_id;

        $templatePath = $helper->getTemplatePath();
   
        include_once($templatePath);
    }

    public function modify_quantity_args($args, $product) {
        $product_id = $product->get_id();
        
        // For variations, get rules from parent product
        $parent_id = $product_id;
        if ($product->is_type('variation')) {
            $parent_id = $product->get_parent_id();
        }
        
        $rules = WHTPRole_Pricing_Helper::get_rules_for_product($parent_id);
        if (empty($rules)) {
            return $args;
        }

        $current_user_role = WHTPRole_Pricing_Helper::get_current_user_role();
        $is_guest = ($current_user_role === 'guest');

        // Get variation ID if product is a variation
        $variation_id = $product->is_type('variation') ? $product->get_id() : null;

        foreach ($rules as $rule) {
            // Check if rule applies to this variation (if product is a variation)
            if ($variation_id) {
                $rule_variations = isset($rule['variations']) && is_array($rule['variations']) ? $rule['variations'] : array();
                // If variations are specified and current variation is not in the list, skip this rule
                if (!empty($rule_variations) && !in_array($variation_id, $rule_variations)) {
                    continue;
                }
            }
            
            // Use helper to check if rule applies
            $rule_roles = isset($rule['roles']) ? $rule['roles'] : (isset($rule['role']) ? $rule['role'] : array());
            $also_for_guest = isset($rule['also_for_guest']) ? $rule['also_for_guest'] : false;
            
            if (WHTPRole_Pricing_Helper::rule_applies_to_user($rule_roles, $current_user_role, $is_guest, $also_for_guest)) {
                if ($rule['min_qty'] > 0) {
                    $args['min_value'] = $rule['min_qty'];
                }

                if ($rule['max_qty'] > 0) {
                    $args['max_value'] = $rule['max_qty'];
                }

                if ($rule['step_qty'] > 1) {
                    $args['step'] = $rule['step_qty'];
                }

                // Set the default qty field value to the first tier's min_qty
                // so the first tier is pre-selected on page load.
                if (!empty($rule['tiered_pricing']) && is_array($rule['tiered_pricing'])) {
                    $tiers = array_filter($rule['tiered_pricing'], function($t) {
                        return !empty($t['min_qty']) && !empty($t['price']);
                    });
                    usort($tiers, function($a, $b) {
                        return intval($a['min_qty']) - intval($b['min_qty']);
                    });
                    $first_tier = reset($tiers);
                    if ($first_tier && intval($first_tier['min_qty']) > 1) {
                        $args['input_value'] = intval($first_tier['min_qty']);
                    }
                }

                break;
            }
        }

        return $args;
    }

    public function display_quantity_messages() {
        global $product;
        
        $product_id = $product->get_id();
        
        // For variations, get rules from parent product
        $parent_id = $product_id;
        if ($product->is_type('variation')) {
            $parent_id = $product->get_parent_id();
        }
        
        $rules = WHTPRole_Pricing_Helper::get_rules_for_product($parent_id);
        if (empty($rules)) {
            return;
        }

        $current_user_role = WHTPRole_Pricing_Helper::get_current_user_role();
        $is_guest = ($current_user_role === 'guest');

        // Get variation ID if product is a variation
        $variation_id = $product->is_type('variation') ? $product->get_id() : null;

        foreach ($rules as $rule) {
            // Check if rule applies to this variation (if product is a variation)
            if ($variation_id) {
                $rule_variations = isset($rule['variations']) && is_array($rule['variations']) ? $rule['variations'] : array();
                if (!empty($rule_variations) && !in_array($variation_id, $rule_variations)) {
                    continue;
                }
            }

            // Use helper to check if rule applies
            $rule_roles = isset($rule['roles']) ? $rule['roles'] : (isset($rule['role']) ? $rule['role'] : array());
            $also_for_guest = isset($rule['also_for_guest']) ? $rule['also_for_guest'] : false;

            if (WHTPRole_Pricing_Helper::rule_applies_to_user($rule_roles, $current_user_role, $is_guest, $also_for_guest)) {
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
        $product_obj = wc_get_product($product_id);
        
        // For variations, get rules from parent product
        $parent_id = $product_id;
        if ($product_obj && $product_obj->is_type('variation')) {
            $parent_id = $product_obj->get_parent_id();
        }
        
        $rules = WHTPRole_Pricing_Helper::get_rules_for_product($parent_id);
        if (empty($rules)) {
            return $passed;
        }

        $current_user_role = WHTPRole_Pricing_Helper::get_current_user_role();
        $is_guest = ($current_user_role === 'guest');
        
        // Get variation ID if product is a variation
        $variation_id = ($product_obj && $product_obj->is_type('variation')) ? $product_obj->get_id() : null;
        
        foreach ($rules as $rule) {
            // Check if rule applies to this variation (if product is a variation)
            if ($variation_id) {
                $rule_variations = isset($rule['variations']) && is_array($rule['variations']) ? $rule['variations'] : array();
                // If variations are specified and current variation is not in the list, skip this rule
                if (!empty($rule_variations) && !in_array($variation_id, $rule_variations)) {
                    continue;
                }
            }
            
            // Use helper to check if rule applies
            $rule_roles = isset($rule['roles']) ? $rule['roles'] : (isset($rule['role']) ? $rule['role'] : array());
            $also_for_guest = isset($rule['also_for_guest']) ? $rule['also_for_guest'] : false;
            
            if (WHTPRole_Pricing_Helper::rule_applies_to_user($rule_roles, $current_user_role, $is_guest, $also_for_guest)) {
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

    public function display_savings_calculator() {
        global $product;
        
        if (!$product) {
            return;
        }

        $helper = new WHTPRole_Pricing_Helper();
        $generalSettings = $helper->getGeneralSettings();
        if (isset($generalSettings['showSavingsCalculator']) && $generalSettings['showSavingsCalculator'] === false) {
            return;
        }

        if (!$helper->validation($product->get_id())) {
            return;
        }
        
        $product_id = $product->get_id();
        
        // For variations, get rules from parent product
        $parent_id = $product_id;
        if ($product->is_type('variation')) {
            $parent_id = $product->get_parent_id();
        }
        
        $rules = WHTPRole_Pricing_Helper::get_rules_for_product($parent_id);
        if (empty($rules)) {
            return;
        }

        $current_user_role = WHTPRole_Pricing_Helper::get_current_user_role();
        $is_guest = ($current_user_role === 'guest');
        $has_applicable_rules = false;
        
        // Get variation ID if product is a variation
        $variation_id = $product->is_type('variation') ? $product->get_id() : null;
        
        foreach ($rules as $rule) {
            // Check if rule applies to this variation (if product is a variation)
            if ($variation_id) {
                $rule_variations = isset($rule['variations']) && is_array($rule['variations']) ? $rule['variations'] : array();
                // If variations are specified and current variation is not in the list, skip this rule
                if (!empty($rule_variations) && !in_array($variation_id, $rule_variations)) {
                    continue;
                }
            }
            
            // Use helper to check if rule applies
            $rule_roles = isset($rule['roles']) ? $rule['roles'] : (isset($rule['role']) ? $rule['role'] : array());
            $also_for_guest = isset($rule['also_for_guest']) ? $rule['also_for_guest'] : false;
            
            if (WHTPRole_Pricing_Helper::rule_applies_to_user($rule_roles, $current_user_role, $is_guest, $also_for_guest)) {
                if (!empty($rule['tiered_pricing']) && is_array($rule['tiered_pricing'])) {
                    $has_applicable_rules = true;
                    break;
                }
            }
        }
        
        if (!$has_applicable_rules) {
            return;
        }
        
        $product_id = $product->get_id();
        $regular_price = floatval($product->get_price());
        
        if ($regular_price <= 0) {
            return;
        }
        
        echo '<div class="whtprole-savings-calculator" data-product-id="' . esc_attr($product_id) . '" data-regular-price="' . esc_attr($regular_price) . '">';
        echo '<div class="savings-calculator-header">';
        echo '<span class="dashicons dashicons-lightbulb"></span>';
        echo '<h4>' . esc_html__('See Your Savings', 'wholesale-tiered-pricing-for-woocommerce') . '</h4>';
        echo '</div>';
        echo '<div class="savings-calculator-content">';
        echo '<div class="savings-row">';
        echo '<span class="savings-label">' . esc_html__('Regular Price:', 'wholesale-tiered-pricing-for-woocommerce') . '</span>';
        echo '<span class="savings-value regular-total">' . wc_price($regular_price) . '</span>';
        echo '</div>';
        echo '<div class="savings-row">';
        echo '<span class="savings-label">' . esc_html__('Your Price:', 'wholesale-tiered-pricing-for-woocommerce') . '</span>';
        echo '<span class="savings-value discounted-total">' . wc_price($regular_price) . '</span>';
        echo '</div>';
        echo '<div class="savings-row savings-highlight">';
        echo '<span class="savings-label">' . esc_html__('You Save:', 'wholesale-tiered-pricing-for-woocommerce') . '</span>';
        echo '<span class="savings-value total-savings">' . wc_price(0) . ' <span class="savings-percent">(0%)</span></span>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }

}