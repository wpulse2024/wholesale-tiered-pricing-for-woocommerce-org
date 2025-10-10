<div class="wholesale-tiered-pricing-for-woocommerce-table">
    <?php 
    wp_enqueue_style(
        'wholesale-tiered-pricing-for-woocommerce', 
        WC_ROLE_PRICING_PLUGIN_URL . 'assets/frontend.css', 
        array(), 
        WC_ROLE_PRICING_VERSION
    );

    $regular_price = floatval($product->get_regular_price());
    $helper = new WC_Role_Pricing_Helper();
    $generalSettings = $helper->getGeneralSettings();
    $activePricingColor = !empty($generalSettings['activePricingColor']) ? $generalSettings['activePricingColor'] : '#7f54b3';
    $quantityLabel = !empty($generalSettings['quantityLabel']) ? $generalSettings['quantityLabel'] : 'Quantity';
    $discountLabel = !empty($generalSettings['discountLabel']) ? $generalSettings['discountLabel'] : 'Price Per Unit';
    $priceLabel = !empty($generalSettings['priceLabel']) ? $generalSettings['priceLabel'] : 'You Save';
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
                            <div class="savings-detail">(<?php echo wp_kses_post(wc_price($savings)); ?> off)</div>
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
