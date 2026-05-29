<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wholesale-tiered-pricing-for-woocommerce-table">
    <?php 
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
    $quantityLabel = !empty($general_settings['quantityLabel']) ? $general_settings['quantityLabel'] : __('Quantity', 'wholesale-tiered-pricing-for-woocommerce');
    $discountLabel = !empty($general_settings['discountLabel']) ? $general_settings['discountLabel'] : __('Price Per Unit', 'wholesale-tiered-pricing-for-woocommerce');
    $priceLabel = !empty($general_settings['priceLabel']) ? $general_settings['priceLabel'] : __('You Save', 'wholesale-tiered-pricing-for-woocommerce');
    
    foreach ($applicable_rules as $rule) :
        if (empty($rule['tiered_pricing'])) {
            continue;
        }

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
        <table class="pricing-table" style="--wtp-primary-color: <?php echo esc_attr($activePricingColor); ?>;" data-base-regular-price="<?php echo esc_attr($base_price); ?>" data-product-id="<?php echo esc_attr($product->get_id()); ?>">
            <thead>
                <tr>
                    <th><?php echo esc_html($quantityLabel); ?></th>
                    <th><?php echo esc_html( $discountLabel); ?></th>
                    <th><?php echo esc_html($priceLabel); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rule['tiered_pricing'] as $tier) :
                    if (empty($tier['min_qty']) || empty($tier['price'])) {
                        continue;
                    }
                    $discount = $helper->calculation_discount($regular_price, $tier);
                    $price = $discount['price'];
                    $savings = $discount['savings'];
                    $savings_percent = $discount['savings_percent'];
                    
                    // Store tier data for JavaScript updates
                    $tier_price = floatval($tier['price']);
                    $discount_type = isset($tier['discount_type']) ? $tier['discount_type'] : 'fixed';
                ?>
                <tr data-tier-min-qty="<?php echo esc_attr($tier['min_qty']); ?>" data-tier-price="<?php echo esc_attr($tier_price); ?>" data-tier-discount-type="<?php echo esc_attr($discount_type); ?>">
                    <td class="quantity-badge">
                        <span><?php echo intval($tier['min_qty']); ?>+</span>
                        <div class="items-text"><?php esc_html_e('items', 'wholesale-tiered-pricing-for-woocommerce'); ?></div>
                    </td>
                    <td class="price-unit">
                        <del class="tier-regular-price"><?php echo wp_kses_post(wc_price($regular_price)); ?></del>
                        <ins class="tier-sale-price"><?php echo wp_kses_post(wc_price($price)); ?></ins>
                    </td>
                    <td class="savings-info">
                        <?php if ($savings > 0): ?>
                            <span class="savings-badge">
                                <span class="save-amount"><?php echo esc_html__('Save', 'wholesale-tiered-pricing-for-woocommerce') . ' ' . esc_html(round($savings_percent)) . '%'; ?></span>
                            </span>
                            <div class="savings-detail">(<?php echo wp_kses_post(wc_price($savings)); ?> <?php esc_html_e('off', 'wholesale-tiered-pricing-for-woocommerce'); ?>)</div>
                        <?php else: ?>
                            &mdash;
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endforeach; ?>
</div>
