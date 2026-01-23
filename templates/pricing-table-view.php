<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wholesale-tiered-pricing-for-woocommerce-table">
    <?php 
    $regular_price = floatval($product->get_price());
    $helper = new WHTPRole_Pricing_Helper();
    $generalSettings = $helper->getGeneralSettings();
    $activePricingColor = !empty($generalSettings['activePricingColor']) ? $generalSettings['activePricingColor'] : '#7f54b3';
    $quantityLabel = !empty($generalSettings['quantityLabel']) ? $generalSettings['quantityLabel'] : __('Quantity', 'wholesale-tiered-pricing-for-woocommerce');
    $discountLabel = !empty($generalSettings['discountLabel']) ? $generalSettings['discountLabel'] : __('Price Per Unit', 'wholesale-tiered-pricing-for-woocommerce');
    $priceLabel = !empty($generalSettings['priceLabel']) ? $generalSettings['priceLabel'] : __('You Save', 'wholesale-tiered-pricing-for-woocommerce');
    foreach ($applicable_rules as $rule) :
        if (empty($rule['tiered_pricing'])) {
            continue;
        }

        usort($rule['tiered_pricing'], function($a, $b) {
            return intval($a['min_qty']) - intval($b['min_qty']);
        });
    ?>
        <table class="pricing-table" style="--wtp-primary-color: <?php echo esc_attr($activePricingColor); ?>;">
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
                    $discount = $helper->calculationDiscount($regular_price, $tier);
                    $price = $discount['price'];
                    $savings = $discount['savings'];
                    $savings_percent = $discount['savings_percent'];
                ?>
                <tr>
                    <td class="quantity-badge">
                        <span><?php echo intval($tier['min_qty']); ?>+</span>
                        <div class="items-text"><?php esc_html_e('items', 'wholesale-tiered-pricing-for-woocommerce'); ?></div>
                    </td>
                    <td class="price-unit">
                        <del><?php echo wp_kses_post(wc_price($regular_price)); ?></del>
                        <ins><?php echo wp_kses_post(wc_price($price)); ?></ins>
                    </td>
                    <td class="savings-info">
                        <?php if ($savings > 0): ?>
                            <span class="savings-badge">
                                <?php echo esc_html__('Save', 'wholesale-tiered-pricing-for-woocommerce') . ' ' . esc_html(round($savings_percent)) . '%'; ?>
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
