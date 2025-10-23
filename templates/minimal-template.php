<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wholesale-tiered-pricing-for-woocommerce-premium">
    <?php 
    wp_enqueue_style('wholesale-tiered-pricing-for-woocommerce-premium', WHTPROLE_PRICING_PLUGIN_URL . 'plugin-assets/minimal-template.css', array(), WHTPROLE_PRICING_VERSION);
    $regular_price = $product->get_regular_price();
    $helper = new WHTPRole_Pricing_Helper();
    foreach ($applicable_rules as $rule) {
        if (!empty($rule['tiered_pricing'])):
            usort($rule['tiered_pricing'], function($a, $b) {
                return intval($a['min_qty']) - intval($b['min_qty']);
            });
            $tier_count = count($rule['tiered_pricing']);
    ?>
        <div class="premium-pricing-wrapper">
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
                $tieredFeatured = $helper->getTieredFeatured($rule['tiered_pricing'], $regular_price);
                foreach ($rule['tiered_pricing'] as $index => $tier):
                    if (!empty($tier['min_qty']) && !empty($tier['price'])):
                        $discount = $helper->calculationDiscount($regular_price, $tier);
                        $price = $discount['price'];
                        $savings = $discount['savings'];
                        $savings_percent = $discount['savings_percent'];
                        $is_featured = $index == $tieredFeatured;
                ?>
                <div class="premium-tier <?php echo $is_featured ? 'featured-tier' : ''; ?>">
                    <?php if ($is_featured): ?>
                        <span class="featured-badge"><?php echo esc_html__('Best', 'wholesale-tiered-pricing-for-woocommerce'); ?></span>
                    <?php endif; ?>

                    <div class="tier-content">
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