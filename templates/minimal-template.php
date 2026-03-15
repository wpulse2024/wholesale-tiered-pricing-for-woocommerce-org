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
                $sale_price    = $variation->get_sale_price();
                $regular_price = $variation->get_regular_price();
                $base_price    = ($sale_price && $sale_price > 0) ? $sale_price : $regular_price;

                if ($base_price <= 0) {
                    $base_price = $variation->get_price();
                }
            }
        }

        if ($base_price <= 0) {
            $sale_price    = $product->get_sale_price();
            $regular_price = $product->get_regular_price();
            $base_price    = ($sale_price && $sale_price > 0) ? $sale_price : $regular_price;

            if ($base_price <= 0) {
                $base_price = $product->get_price();
            }
        }
    } elseif ($product->is_type('variation')) {
        $sale_price    = $product->get_sale_price();
        $regular_price = $product->get_regular_price();
        $base_price    = ($sale_price && $sale_price > 0) ? $sale_price : $regular_price;

        if ($base_price <= 0) {
            $base_price = $product->get_price();
        }
    } else {
        $sale_price    = $product->get_sale_price();
        $regular_price = $product->get_regular_price();
        $base_price    = ($sale_price && $sale_price > 0) ? $sale_price : $regular_price;

        if ($base_price <= 0) {
            $base_price = $product->get_price();
        }
    }

    $base_price = floatval($base_price);
    if ($base_price <= 0) {
        return;
    }

    $regular_price = $base_price;

    $helper = new WHTPRole_Pricing_Helper();
    foreach ($applicable_rules as $rule) {
        if (!empty($rule['tiered_pricing'])):
            $rule['tiered_pricing'] = array_filter($rule['tiered_pricing'], function ($tier) {
                return !empty($tier['min_qty']) && !empty($tier['price']);
            });

            if (empty($rule['tiered_pricing'])) {
                continue;
            }

            usort($rule['tiered_pricing'], function ($a, $b) {
                return intval($a['min_qty']) - intval($b['min_qty']);
            });
    ?>
        <div class="wtp-volume-card"
             data-base-regular-price="<?php echo esc_attr($base_price); ?>"
             data-product-id="<?php echo esc_attr($product->get_id()); ?>">

            <div class="wtp-volume-header">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                    <line x1="12" y1="7" x2="12" y2="3"></line>
                    <path d="M12 3C12 3 9 1 7 3"></path>
                    <path d="M12 3C12 3 15 1 17 3"></path>
                </svg>
                <p class="wtp-volume-title"><?php echo esc_html__('Volume Pricing', 'wholesale-tiered-pricing-for-woocommerce'); ?></p>
            </div>

            <div class="wtp-tiers-list">
                <?php foreach ($rule['tiered_pricing'] as $index => $tier):
                    if (!empty($tier['min_qty']) && !empty($tier['price'])):
                        $discount        = $helper->calculationDiscount($regular_price, $tier);
                        $price           = $discount['price'];
                        $savings         = $discount['savings'];
                        $savings_percent = $discount['savings_percent'];

                        if ($price <= 0) {
                            $price           = $regular_price;
                            $savings         = 0;
                            $savings_percent = 0;
                        }

                        if ($price == $regular_price) {
                            $savings         = 0;
                            $savings_percent = 0;
                        }
                ?>
                <div class="wtp-tier-row"
                     data-tier-min-qty="<?php echo esc_attr($tier['min_qty']); ?>"
                     data-tier-price="<?php echo esc_attr($tier['price']); ?>"
                     data-tier-discount-type="<?php echo esc_attr(isset($tier['discount_type']) ? $tier['discount_type'] : ''); ?>"
                     data-tier-index="<?php echo esc_attr($index); ?>">

                    <div class="wtp-tier-qty">
                        <?php echo esc_html(intval($tier['min_qty'])); ?><span class="wtp-plus">+</span>
                    </div>

                    <div class="wtp-tier-prices">
                        <?php if ($savings > 0 && $regular_price != $tier['price']): ?>
                            <span class="wtp-orig-price"><?php echo wp_kses_post(wc_price($regular_price)); ?></span>
                        <?php endif; ?>
                        <span class="wtp-disc-price"><?php echo wp_kses_post(wc_price($price)); ?></span>
                    </div>

                    <?php if ($savings > 0): ?>
                    <div class="wtp-tier-savings">
                        <span class="wtp-save-pct"><?php printf(esc_html__('Save %d%%', 'wholesale-tiered-pricing-for-woocommerce'), round($savings_percent)); ?></span>
                        <span class="wtp-save-amt"><?php echo wp_kses_post(wc_price($savings)); ?> <?php echo esc_html__('off', 'wholesale-tiered-pricing-for-woocommerce'); ?></span>
                    </div>
                    <?php endif; ?>

                </div>
                <?php endif; endforeach; ?>
            </div>

        </div>
    <?php
        endif;
    }
    ?>
</div>
