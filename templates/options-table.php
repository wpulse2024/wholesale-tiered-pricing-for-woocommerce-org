<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wholesale-tiered-pricing-for-woocommerce-radio">
    <?php 
    wp_enqueue_style('wholesale-tiered-pricing-for-woocommerce-radio-select', WHTPROLE_PRICING_PLUGIN_URL . 'plugin-assets/options-table.css', array(), WHTPROLE_PRICING_VERSION);
    
    // Get the correct base price - use sale price if available, otherwise regular price
    // For variable products, we need to handle the case where variation is selected via JavaScript
    // On initial page load, use the parent product's price (sale price if available, otherwise regular)
    $base_price = 0;
    
    // Check if we have a variation_id passed from the frontend class
    $template_variation_id = isset($template_variation_id) ? $template_variation_id : null;
    
    // For variable products, try to get the selected variation price
    if ($product->is_type('variable')) {
        // If variation_id is provided (from frontend class), use that variation's price
        if ($template_variation_id) {
            $variation = wc_get_product($template_variation_id);
            if ($variation && $variation->is_type('variation')) {
                // Get sale price if available, otherwise regular price
                $sale_price = $variation->get_sale_price();
                $regular_price = $variation->get_regular_price();
                $base_price = ($sale_price && $sale_price > 0) ? $sale_price : $regular_price;
                
                // Fallback to current price if both are empty
                if ($base_price <= 0) {
                    $base_price = $variation->get_price();
                }
            }
        }
        
        // If we still don't have a price, use the parent product's price
        if ($base_price <= 0) {
            $sale_price = $product->get_sale_price();
            $regular_price = $product->get_regular_price();
            $base_price = ($sale_price && $sale_price > 0) ? $sale_price : $regular_price;
            
            // Fallback to current price if both are empty
            if ($base_price <= 0) {
                $base_price = $product->get_price();
            }
        }
    } elseif ($product->is_type('variation')) {
        // If product is already a variation, use sale price if available, otherwise regular price
        $sale_price = $product->get_sale_price();
        $regular_price = $product->get_regular_price();
        $base_price = ($sale_price && $sale_price > 0) ? $sale_price : $regular_price;
        
        // Fallback to current price if both are empty
        if ($base_price <= 0) {
            $base_price = $product->get_price();
        }
    } else {
        // For simple products, use sale price if available, otherwise regular price
        $sale_price = $product->get_sale_price();
        $regular_price = $product->get_regular_price();
        $base_price = ($sale_price && $sale_price > 0) ? $sale_price : $regular_price;
        
        // Fallback to current price if both are empty
        if ($base_price <= 0) {
            $base_price = $product->get_price();
        }
    }
    
    // Ensure we have a valid price - final validation
    $base_price = floatval($base_price);
    if ($base_price <= 0) {
        return; // Don't display pricing table if we can't determine a valid price
    }
    
    // Keep $regular_price variable for backward compatibility in calculations
    // But use $base_price as the actual base for tier calculations
    $regular_price = $base_price;
    
    $helper = new WHTPRole_Pricing_Helper();
    $general_settings = $helper->get_general_settings();
    $activePricingColor = !empty($general_settings['activePricingColor']) ? $general_settings['activePricingColor'] : '#7f54b3';
    
    foreach ($applicable_rules as $rule) {
        if (!empty($rule['tiered_pricing'])):
            // Filter tiers by variation if viewing a variation
            if ($template_variation_id && !empty($rule['tiered_pricing']) && is_array($rule['tiered_pricing'])) {
                $filtered_tiers = array();
                foreach ($rule['tiered_pricing'] as $tier) {
                    $tier_variation = isset($tier['variation']) ? $tier['variation'] : null;
                    // Backward compatibility: check old variations array format
                    if ($tier_variation === null && isset($tier['variations']) && is_array($tier['variations'])) {
                        $tier_variations = $tier['variations'];
                        // If variations are specified and current variation is not in the list, skip this tier
                        if (!empty($tier_variations) && !in_array($template_variation_id, $tier_variations)) {
                            continue;
                        }
                    } elseif ($tier_variation !== null && $tier_variation !== 'all' && intval($tier_variation) !== $template_variation_id) {
                        // If a specific variation is set and it doesn't match, skip this tier
                        continue;
                    }
                    // Tier applies to this variation (or all variations)
                    $filtered_tiers[] = $tier;
                }
                $rule['tiered_pricing'] = $filtered_tiers;
            }
            
            usort($rule['tiered_pricing'], function($a, $b) {
                return intval($a['min_qty']) - intval($b['min_qty']);
            });
    ?>
        <div class="radio-pricing-wrapper" style="--wtp-primary-color: <?php echo esc_attr($activePricingColor); ?>;" data-base-regular-price="<?php echo esc_attr($base_price); ?>" data-product-id="<?php echo esc_attr($product->get_id()); ?>">
            <?php
            foreach ($rule['tiered_pricing'] as $index => $tier):
                if (!empty($tier['min_qty']) && !empty($tier['price'])):
                    $min_qty = intval($tier['min_qty']);
                    $discount = $helper->calculation_discount($regular_price, $tier);
                    $tier_price = $discount['price'];
                    $savings = $discount['savings'];
                    $savings_percent = $discount['savings_percent'];
                    
                    // Determine max quantity for range display
                    $next_tier_qty = isset($rule['tiered_pricing'][$index + 1]) ? intval($rule['tiered_pricing'][$index + 1]['min_qty']) - 1 : null;
                    $is_last_tier = ($index === count($rule['tiered_pricing']) - 1);
                    
                    // Get variation name if this tier is for a specific variation
                    $variation_name = '';
                    $tier_variation = isset($tier['variation']) ? $tier['variation'] : null;
                    if ($tier_variation && $tier_variation !== 'all' && $product->is_type('variable')) {
                        $variation_product = wc_get_product(intval($tier_variation));
                        if ($variation_product && $variation_product->is_type('variation')) {
                            // Get variation attributes as plain text
                            $attributes = $variation_product->get_variation_attributes();
                            if (!empty($attributes)) {
                                $attribute_parts = array();
                                foreach ($attributes as $attr_name => $attr_value) {
                                    $taxonomy = str_replace('attribute_', '', $attr_name);
                                    $term = get_term_by('slug', $attr_value, $taxonomy);
                                    if ($term) {
                                        $attribute_parts[] = wc_attribute_label($taxonomy) . ': ' . $term->name;
                                    } else {
                                        $attribute_parts[] = wc_attribute_label($taxonomy) . ': ' . $attr_value;
                                    }
                                }
                                if (!empty($attribute_parts)) {
                                    $variation_name = implode(', ', $attribute_parts);
                                }
                            }
                            
                            // If no attributes found, use variation ID
                            if (empty($variation_name)) {
                                $variation_name = sprintf(esc_html__('Variation #%d', 'wholesale-tiered-pricing-for-woocommerce'), intval($tier_variation));
                            }
                        }
                    } elseif ($tier_variation === 'all' && $product->is_type('variable')) {
                        $variation_name = esc_html__('All Variations', 'wholesale-tiered-pricing-for-woocommerce');
                    }
            ?>
            <label class="radio-tier" 
                   data-min-qty="<?php echo esc_attr($min_qty); ?>" 
                   data-price="<?php echo esc_attr($tier_price); ?>"
                   data-tier-price="<?php echo esc_attr($tier['price']); ?>"
                   data-tier-discount-type="<?php echo esc_attr(isset($tier['discount_type']) ? $tier['discount_type'] : ''); ?>">
                <input type="radio" 
                       name="tier_selection_<?php echo esc_attr($product->get_id()); ?>" 
                       value="<?php echo esc_attr($min_qty); ?>" 
                       class="tier-radio-input"
                       data-min-qty="<?php echo esc_attr($min_qty); ?>"
                       data-price="<?php echo esc_attr($tier_price); ?>">
                
                <span class="radio-custom"></span>
                
                <div class="tier-info">
                    <?php if (!empty($variation_name)): ?>
                        <span class="tier-variation-name">
                            <?php echo esc_html($variation_name); ?>
                        </span>
                    <?php endif; ?>
                    <span class="tier-label">
                        <?php 
                        if ($next_tier_qty) {
                            echo esc_html(sprintf(esc_html__('Buy %d - %d pieces', 'wholesale-tiered-pricing-for-woocommerce'), $min_qty, $next_tier_qty));
                        } else {
                            echo esc_html(sprintf(esc_html__('Buy %d+ pieces', 'wholesale-tiered-pricing-for-woocommerce'), $min_qty));
                        }
                        
                        if ($savings > 0) {
                            echo esc_html(' ' . sprintf(esc_html__('and save %d%%', 'wholesale-tiered-pricing-for-woocommerce'), round($savings_percent)));
                        }
                        ?>
                    </span>

                    <div class="tier-total">
                        <span class="total-label"><?php echo esc_html__('Total:', 'wholesale-tiered-pricing-for-woocommerce'); ?></span>
                        <span class="total-regular" data-regular-total="">
                            <?php echo wp_kses_post(wc_price($regular_price)); ?>
                        </span>
                        <span class="total-sale" data-sale-total="">
                            <?php echo wp_kses_post(wc_price($tier_price)); ?>
                        </span>
                    </div>
                    
                </div>
                
                <div class="tier-pricing">
                    <?php if ($savings > 0 && $regular_price != $tier_price): ?>
                        <span class="tier-regular-price"><?php echo wp_kses_post(wc_price($regular_price)); ?></span>
                    <?php endif; ?>
                    <span class="tier-sale-price"><?php echo wp_kses_post(wc_price($tier_price)); ?></span>
                </div>
            </label>
            <?php endif; endforeach; ?>
        </div>
    <?php 
        wp_enqueue_script('wholesale-tiered-pricing-for-woocommerce-radio-select', WHTPROLE_PRICING_PLUGIN_URL . 'plugin-assets/options-table.js', array('jquery'), WHTPROLE_PRICING_VERSION, true);
        wp_localize_script('wholesale-tiered-pricing-for-woocommerce-radio-select', 'whtproleTieredPricingVar', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wholesale-tiered-pricing-for-woocommerce-ajax'),
            'productId' => $product->get_id(),
            'regularPrice' => $regular_price
        ));
        endif;
    } 
    ?>
</div>