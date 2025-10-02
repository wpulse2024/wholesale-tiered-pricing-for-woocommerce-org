<div class="wholesale-tiered-pricing-for-woocommerce-table">
    <?php 
    wp_enqueue_style(
        'wholesale-tiered-pricing-for-woocommerce', 
        WC_ROLE_PRICING_PLUGIN_URL . 'assets/frontend.css', 
        array(), 
        WC_ROLE_PRICING_VERSION
    );

    $regular_price = floatval($product->get_regular_price());

    foreach ($applicable_rules as $rule) :
        if (empty($rule['tiered_pricing'])) {
            continue;
        }

        usort($rule['tiered_pricing'], function($a, $b) {
            return intval($a['min_qty']) - intval($b['min_qty']);
        });
    ?>
        <table class="pricing-table">
            <thead>
                <tr>
                    <th><?php echo esc_html__('Quantity', 'wholesale-tiered-pricing-for-woocommerce'); ?></th>
                    <th><?php echo esc_html__('Price Per Unit', 'wholesale-tiered-pricing-for-woocommerce'); ?></th>
                    <th><?php echo esc_html__('You Save', 'wholesale-tiered-pricing-for-woocommerce'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rule['tiered_pricing'] as $tier) :
                    if (empty($tier['min_qty']) || empty($tier['price'])) {
                        continue;
                    }

                    $discount_type = $tier['discount_type'] ?? 'fixed';
                    $tier_price = floatval($tier['price']);

                    if ($discount_type === 'fixed') {
                        $price = $tier_price;
                        $savings = $regular_price - $price;
                        $savings_percent = $regular_price > 0 ? ($savings / $regular_price) * 100 : 0;
                    } else { // percentage
                        $price = $regular_price - ($regular_price * $tier_price / 100);
                        $savings = $regular_price - $price;
                        $savings_percent = $tier_price;
                    }
                ?>
                <tr>
                    <td class="quantity-badge">
                        <span><?php echo intval($tier['min_qty']); ?>+</span>
                        <div class="items-text"><?php esc_html_e('items', 'wholesale-tiered-pricing-for-woocommerce'); ?></div>
                    </td>
                    <td class="price-unit">
                        <?php if ($discount_type === 'fixed'): ?>
                            <strong><?php echo wc_price($price); ?></strong>
                        <?php else: ?>
                            <del><?php echo wc_price($regular_price); ?></del>
                            <ins><?php echo wc_price($price); ?></ins>
                        <?php endif; ?>
                    </td>
                    <td class="savings-info">
                        <?php if ($savings > 0): ?>
                            <span class="savings-badge">
                                <?php echo esc_html__('Save', 'wholesale-tiered-pricing-for-woocommerce') . ' ' . esc_html(round($savings_percent)) . '%'; ?>
                            </span>
                            <div class="savings-detail">(<?php echo wc_price($savings); ?> off)</div>
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
