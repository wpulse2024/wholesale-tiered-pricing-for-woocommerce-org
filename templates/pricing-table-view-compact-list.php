<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wholesale-tiered-pricing-for-woocommerce-compact">
    <?php 
    $helper = new WHTPRole_Pricing_Helper();
    $regular_price = $product->get_price();
    wp_enqueue_style('wholesale-tiered-pricing-for-woocommerce-grid', WHTPROLE_PRICING_PLUGIN_URL . 'includes/compact-list-template.css', array(), WHTPROLE_PRICING_VERSION);
    foreach ($applicable_rules as $rule) {
        if (!empty($rule['tiered_pricing'])):
            usort($rule['tiered_pricing'], function($a, $b) {
                return intval($a['min_qty']) - intval($b['min_qty']);
            });

            $regular_price = $product->get_price();
    ?>
        <div class="woocommerce-bulk-pricing">
            <div class="bulk-pricing-header">
                <span class="dashicons dashicons-tag"></span>
                <strong><?php echo esc_html__('Bulk Pricing', 'wholesale-tiered-pricing-for-woocommerce'); ?></strong>
            </div>
            <ul class="bulk-pricing-list">
                <?php
                foreach ($rule['tiered_pricing'] as $tier):
                    if (!empty($tier['min_qty']) && !empty($tier['price'])):
                        $discount = $helper->calculationDiscount($regular_price, $tier);
                        $savings = $discount['savings'];
                        $savings_percent = $discount['savings_percent'];
                        $price = $discount['price'];
                        
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
                                
                                // If no attributes found, use variation name
                                if (empty($variation_name)) {
                                    $variation_name = $variation_product->get_name();
                                }
                            }
                        }
                ?>
                <li class="pricing-tier">
                    <?php if (!empty($variation_name)): ?>
                        <span class="tier-variation-name"><?php echo esc_html($variation_name); ?></span>
                    <?php endif; ?>
                    <span class="tier-qty"><?php echo esc_attr(intval($tier['min_qty'])); ?>+</span>
                    <span class="tier-price"><?php echo wp_kses_post(wc_price($tier['price'])); ?></span>
                    <?php if ($savings > 0): ?>
                        <span class="tier-save woocommerce-Price-amount"><?php echo esc_html(round($savings_percent)); ?>% <?php echo esc_html__('off', 'wholesale-tiered-pricing-for-woocommerce'); ?></span>
                    <?php endif; ?>
                </li>
                <?php endif; endforeach; ?>
            </ul>
        </div>
    <?php 
        endif;
    } 
    ?>
</div>
