<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wholesale-tiered-pricing-for-woocommerce-premium">
    <?php 
    wp_enqueue_style('wholesale-tiered-pricing-for-woocommerce-premium', WHTPROLE_PRICING_PLUGIN_URL . 'plugin-assets/minimal-template.css', array(), WHTPROLE_PRICING_VERSION);
    
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
    foreach ($applicable_rules as $rule) {
        if (!empty($rule['tiered_pricing'])):
            // Filter out empty tiers
            $rule['tiered_pricing'] = array_filter($rule['tiered_pricing'], function($tier) {
                return !empty($tier['min_qty']) && !empty($tier['price']);
            });
            
            if (empty($rule['tiered_pricing'])) {
                continue;
            }
            
            usort($rule['tiered_pricing'], function($a, $b) {
                return intval($a['min_qty']) - intval($b['min_qty']);
            });
            $tier_count = count($rule['tiered_pricing']);
    ?>
        <div class="premium-pricing-wrapper" data-base-regular-price="<?php echo esc_attr($base_price); ?>" data-product-id="<?php echo esc_attr($product->get_id()); ?>">
            <div class="premium-header">
                <h4 class="premium-title">
                    <svg class="premium-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/>
                    </svg>
                    <?php echo esc_html__('Volume Savings', 'wholesale-tiered-pricing-for-woocommerce'); ?>
                </h4>
            </div>

            <div class="premium-tiers">
                <?php
                // Calculate featured tier based on best savings percentage
                $tieredFeatured = $helper->getTieredFeatured($rule['tiered_pricing'], $regular_price);
                
                foreach ($rule['tiered_pricing'] as $index => $tier):
                    if (!empty($tier['min_qty']) && !empty($tier['price'])):
                        // Calculate discount based on the correct regular price
                        $discount = $helper->calculationDiscount($regular_price, $tier);
                        $price = $discount['price'];
                        $savings = $discount['savings'];
                        $savings_percent = $discount['savings_percent'];
                        $is_featured = $index == $tieredFeatured;
                        
                        // Ensure price is valid (not negative or zero)
                        if ($price <= 0) {
                            $price = $regular_price;
                            $savings = 0;
                            $savings_percent = 0;
                        }
                        
                        // Ensure price is valid (not negative or zero)
                        if ($price <= 0) {
                            $price = $regular_price;
                        }
                        
                        // Recalculate savings if price was adjusted
                        if ($price == $regular_price) {
                            $savings = 0;
                            $savings_percent = 0;
                        }
                        
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
                <div class="premium-tier <?php echo $is_featured ? 'featured-tier' : ''; ?>" 
                     data-tier-min-qty="<?php echo esc_attr($tier['min_qty']); ?>"
                     data-tier-price="<?php echo esc_attr($tier['price']); ?>"
                     data-tier-discount-type="<?php echo esc_attr(isset($tier['discount_type']) ? $tier['discount_type'] : ''); ?>"
                     data-tier-index="<?php echo esc_attr($index); ?>"
                     data-tier-variation="<?php echo esc_attr($tier_variation ? $tier_variation : 'all'); ?>">
                    <?php if ($is_featured): ?>
                        <span class="featured-badge"><?php echo esc_html__('Best', 'wholesale-tiered-pricing-for-woocommerce'); ?></span>
                    <?php endif; ?>

                    <div class="tier-content">
                        <?php if (!empty($variation_name)): ?>
                            <div class="tier-variation-name">
                                <?php echo esc_html($variation_name); ?>
                            </div>
                        <?php endif; ?>
                        <div class="tier-content-row">
                            <div class="tier-left">
                                <span class="qty-value"><?php echo esc_html(intval($tier['min_qty'])); ?><span class="qty-plus">+</span></span>
                            </div>

                            <div class="tier-center">
                                <?php if ($savings > 0 && $regular_price != $tier['price']): ?>
                                    <span class="regular-price"><?php echo wp_kses_post(wc_price($regular_price)); ?></span>
                                <?php endif; ?>
                                <span class="sale-price"><?php echo wp_kses_post(wc_price($price)); ?></span>
                            </div>

                            <div class="tier-right">
                                <?php if ($savings > 0): ?>
                                    <div class="savings-badge">
                                        <span class="save-amount"><?php echo esc_html(round($savings_percent)); ?>%</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="tier-progress">
                        <div class="progress-fill" style="width: <?php echo esc_attr( min(100, ($savings_percent / 50) * 100)); ?>%"></div>
                    </div>
                </div>
                <?php endif; endforeach; ?>
            </div>

            <div class="premium-footer">
                <svg class="check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <span><?php echo esc_html__('Applied at checkout', 'wholesale-tiered-pricing-for-woocommerce'); ?></span>
            </div>
        </div>
    <?php 
        endif;
    } 
    ?>
</div>