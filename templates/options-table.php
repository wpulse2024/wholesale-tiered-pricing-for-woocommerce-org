<div class="wholesale-tiered-pricing-for-woocommerce-radio">
    <?php 
    wp_enqueue_style('wholesale-tiered-pricing-for-woocommerce-radio-select', WC_ROLE_PRICING_PLUGIN_URL . 'assets/options-table.css', array(), WC_ROLE_PRICING_VERSION);
    
    $regular_price = $product->get_regular_price();
    $helper = new WC_Role_Pricing_Helper();
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
                            echo esc_html(sprintf(esc_html('Buy %d - %d pieces', 'wholesale-tiered-pricing-for-woocommerce'), $min_qty, $next_tier_qty));
                        } else {
                            echo esc_html(sprintf(esc_html('Buy %d+ pieces', 'wholesale-tiered-pricing-for-woocommerce'), $min_qty));
                        }
                        
                        if ($savings > 0) {
                            echo esc_html(' ' . sprintf(esc_html('and save %d%%', 'wholesale-tiered-pricing-for-woocommerce'), round($savings_percent)));
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

        <script>
        (function($) {
            'use strict';
            
            const productId = <?php echo esc_js($product->get_id()); ?>;
            const regularPrice = <?php echo esc_js($regular_price); ?>;
            let currentQuantity = 1;
            
            function updateTierSelection(quantity) {
                const tiers = $('.radio-tier').get().reverse(); // Start from highest tier
                let selectedTier = null;
                
                tiers.forEach(function(tier) {
                    const minQty = parseInt($(tier).data('min-qty'));
                    if (quantity >= minQty && !selectedTier) {
                        selectedTier = tier;
                    }
                });
                
                if (selectedTier) {
                    const radio = $(selectedTier).find('.tier-radio-input');
                    radio.prop('checked', true);
                    
                    // Update visual state
                    $('.radio-tier').removeClass('active');
                    $(selectedTier).addClass('active');
                    
                }
            }
            
            // Listen to quantity changes
            $(document).on('change input', '.quantity input.qty, input.qty', function() {
                currentQuantity = parseInt($(this).val()) || 1;
                updateTierSelection(currentQuantity);
            });
            
            // Manual tier selection
            $('.tier-radio-input').on('change', function() {
                if ($(this).is(':checked')) {
                    const minQty = parseInt($(this).data('min-qty'));
                    const tierPrice = parseFloat($(this).data('price'));
                    
                    // Update quantity input
                    $('.quantity input.qty, input.qty').val(minQty).trigger('change');
                    
                    // Update visual state
                    $('.radio-tier').removeClass('active');
                    $(this).closest('.radio-tier').addClass('active');
                }
            });
            
            // Click on label to select
            $('.radio-tier').on('click', function(e) {
                if (!$(e.target).is('input')) {
                    $(this).find('.tier-radio-input').prop('checked', true).trigger('change');
                }
            });
            
            // Initialize on page load
            $(document).ready(function() {
                const initialQty = parseInt($('.quantity input.qty, input.qty').val()) || 1;
                updateTierSelection(initialQty);
            });
            
            // For variable products
            $(document).on('found_variation', function(event, variation) {
                const initialQty = parseInt($('.quantity input.qty').val()) || 1;
                setTimeout(function() {
                    updateTierSelection(initialQty);
                }, 100);
            });
            
        })(jQuery);
        </script>
    <?php 
        endif;
    } 
    ?>
</div>