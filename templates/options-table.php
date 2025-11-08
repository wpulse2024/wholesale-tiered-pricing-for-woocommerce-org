<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wholesale-tiered-pricing-for-woocommerce-radio">
    <?php 
    wp_enqueue_style('wholesale-tiered-pricing-for-woocommerce-radio-select', WHTPROLE_PRICING_PLUGIN_URL . 'plugin-assets/options-table.css', array(), WHTPROLE_PRICING_VERSION);
    
    $regular_price = $product->get_price();
    $helper = new WHTPRole_Pricing_Helper();
    $generalSettings = $helper->getGeneralSettings();
    $activePricingColor = !empty($generalSettings['activePricingColor']) ? $generalSettings['activePricingColor'] : '#7f54b3';
    
    foreach ($applicable_rules as $rule) {
        if (!empty($rule['tiered_pricing'])):
            usort($rule['tiered_pricing'], function($a, $b) {
                return intval($a['min_qty']) - intval($b['min_qty']);
            });
    ?>
        <div class="radio-pricing-wrapper" style="--wtp-primary-color: <?php echo esc_attr($activePricingColor); ?>;">
            <?php
            foreach ($rule['tiered_pricing'] as $index => $tier):
                if (!empty($tier['min_qty']) && !empty($tier['price'])):
                    $min_qty = intval($tier['min_qty']);
                    $discount = $helper->calculationDiscount($regular_price, $tier);
                    $tier_price = $discount['price'];
                    $savings = $discount['savings'];
                    $savings_percent = $discount['savings_percent'];
                    
                    // Determine max quantity for range display
                    $next_tier_qty = isset($rule['tiered_pricing'][$index + 1]) ? intval($rule['tiered_pricing'][$index + 1]['min_qty']) - 1 : null;
                    $is_last_tier = ($index === count($rule['tiered_pricing']) - 1);
            ?>
            <label class="radio-tier" data-min-qty="<?php echo esc_attr($min_qty); ?>" data-price="<?php echo esc_attr($tier_price); ?>">
                <input type="radio" 
                       name="tier_selection_<?php echo esc_attr($product->get_id()); ?>" 
                       value="<?php echo esc_attr($min_qty); ?>" 
                       class="tier-radio-input"
                       data-min-qty="<?php echo esc_attr($min_qty); ?>"
                       data-price="<?php echo esc_attr($tier_price); ?>">
                
                <span class="radio-custom"></span>
                
                <div class="tier-info">
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
                    
                    <?php if ($is_last_tier): ?>
                        <div class="tier-total">
                            <span class="total-label"><?php echo esc_html__('Total:', 'wholesale-tiered-pricing-for-woocommerce'); ?></span>
                            <span class="total-regular" data-regular-total=""></span>
                            <span class="total-sale" data-sale-total=""></span>
                        </div>
                    <?php endif; ?>
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