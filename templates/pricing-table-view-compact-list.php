<div class="wholesale-tiered-pricing-for-woocommerce-compact">
    <?php 
    wp_enqueue_style('wholesale-tiered-pricing-for-woocommerce-grid', WC_ROLE_PRICING_PLUGIN_URL . 'includes/compact-list-template.css', array(), WC_ROLE_PRICING_VERSION);
    foreach ($applicable_rules as $rule) {
        if (!empty($rule['tiered_pricing'])):
            usort($rule['tiered_pricing'], function($a, $b) {
                return intval($a['min_qty']) - intval($b['min_qty']);
            });

            $regular_price = $product->get_regular_price();
    ?>
        <div class="woocommerce-bulk-pricing">
            <div class="bulk-pricing-header">
                <span class="dashicons dashicons-tag"></span>
                <strong><?php esc_html__('Bulk Pricing', 'wholesale-tiered-pricing-for-woocommerce'); ?></strong>
            </div>
            <ul class="bulk-pricing-list">
                <?php
                foreach ($rule['tiered_pricing'] as $tier):
                    if (!empty($tier['min_qty']) && !empty($tier['price'])):
                        $savings = $regular_price - floatval($tier['price']);
                        $savings_percent = $regular_price > 0 ? ($savings / $regular_price) * 100 : 0;
                ?>
                <li class="pricing-tier">
                    <span class="tier-qty"><?php echo esc_html(intval($tier['min_qty'])); ?>+</span>
                    <span class="tier-price"><?php echo esc_html(wc_price($tier['price'])); ?></span>
                    <?php if ($savings > 0): ?>
                        <span class="tier-save woocommerce-Price-amount"><?php echo esc_html(round($savings_percent)); ?>% <?php esc_html__('off', 'wholesale-tiered-pricing-for-woocommerce'); ?></span>
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
