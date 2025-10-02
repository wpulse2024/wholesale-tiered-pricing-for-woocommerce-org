<?php
if (!defined('ABSPATH')) exit;

// -----------------------------
// 1. Add Tiered Pricing Tab
// -----------------------------
add_filter('woocommerce_settings_tabs_array', function($tabs) {
    $tabs['tiered_pricing'] = __('Tiered Pricing', 'text-domain');
    return $tabs;
}, 50);

// -----------------------------
// 2. Show Tiered Pricing Settings
// -----------------------------
add_action('woocommerce_settings_tabs_tiered_pricing', function() {
    $rules = get_option('wc_role_pricing_global_rules', []);
    $products = wc_get_products(['limit' => -1]); // Get all products
    ?>
    <h2><?php _e('Tiered Pricing Rules', 'text-domain'); ?></h2>
    <form method="post" id="tiered-pricing-form">
        <?php wp_nonce_field('save_tiered_pricing_rules', 'tiered_pricing_nonce'); ?>

        <div id="pricing-rules-container">
            <?php if (!empty($rules)) : ?>
                <?php foreach ($rules as $index => $rule) : ?>
                    <div class="pricing-rule" style="border:1px solid #ccc;padding:15px;margin-bottom:15px;background:#fff;">
                        <label>User Role:
                            <select name="pricing_rules[<?php echo $index; ?>][role]">
                                <?php
                                global $wp_roles;
                                foreach ($wp_roles->roles as $role_key => $role_data) {
                                    $selected = ($rule['role'] === $role_key) ? 'selected' : '';
                                    echo "<option value='{$role_key}' {$selected}>{$role_data['name']}</option>";
                                }
                                ?>
                            </select>
                        </label>

                        <label>Step Quantity:
                            <input type="number" name="pricing_rules[<?php echo $index; ?>][step_qty]" value="<?php echo esc_attr($rule['step_qty']); ?>">
                        </label>

                        <label>Min Quantity:
                            <input type="number" name="pricing_rules[<?php echo $index; ?>][min_qty]" value="<?php echo esc_attr($rule['min_qty']); ?>">
                        </label>

                        <label>Max Quantity:
                            <input type="text" name="pricing_rules[<?php echo $index; ?>][max_qty]" value="<?php echo esc_attr($rule['max_qty']); ?>">
                        </label>

                        <h4>Tiered Pricing</h4>
                        <div class="tiers">
                            <?php if (!empty($rule['tiered_pricing'])) : ?>
                                <?php foreach ($rule['tiered_pricing'] as $t_index => $tier) : ?>
                                    <div class="tier" style="margin-bottom:8px;">
                                        <input type="number" name="pricing_rules[<?php echo $index; ?>][tiered_pricing][<?php echo $t_index; ?>][min_qty]" value="<?php echo esc_attr($tier['min_qty']); ?>" placeholder="Min Qty">

                                        <select name="pricing_rules[<?php echo $index; ?>][tiered_pricing][<?php echo $t_index; ?>][discount_type]">
                                            <option value="percentage" <?php echo ($tier['discount_type'] === 'percentage') ? 'selected' : ''; ?>>Percentage</option>
                                            <option value="fixed" <?php echo ($tier['discount_type'] === 'fixed') ? 'selected' : ''; ?>>Fixed Amount</option>
                                        </select>

                                        <input type="number" name="pricing_rules[<?php echo $index; ?>][tiered_pricing][<?php echo $t_index; ?>][price]" value="<?php echo esc_attr($tier['price']); ?>" placeholder="Discount Value">

                                        <button type="button" class="remove-tier" style="background:#dc3545;color:#fff;border:none;padding:4px 8px;cursor:pointer;">Remove</button>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="add-tier" style="background:#0073aa;color:#fff;border:none;padding:4px 8px;cursor:pointer;">Add Tier</button>
                        
                        <button type="button" class="remove-rule" style="background:#dc3545;color:#fff;border:none;padding:4px 8px;cursor:pointer;">Remove Rule</button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <button type="button" id="add-rule" style="background:#0073aa;color:#fff;border:none;padding:6px 12px;cursor:pointer;">Add Pricing Rule</button>
        <p class="submit">
            <input type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes', 'text-domain'); ?>">
        </p>
    </form>

    <script>
    (function($){
        let ruleIndex = <?php echo !empty($rules) ? count($rules) : 0; ?>;

        $('#add-rule').on('click', function(){
            let html = `<div class="pricing-rule" style="border:1px solid #ccc;padding:15px;margin-bottom:15px;background:#fff;">
                <label>User Role:
                    <select name="pricing_rules[`+ruleIndex+`][role]">
                        <?php global $wp_roles; foreach ($wp_roles->roles as $role_key => $role_data) {
                            echo "<option value='{$role_key}'>{$role_data['name']}</option>";
                        } ?>
                    </select>
                </label>

                <label>Step Quantity:
                    <input type="number" name="pricing_rules[`+ruleIndex+`][step_qty]" value="1">
                </label>

                <label>Min Quantity:
                    <input type="number" name="pricing_rules[`+ruleIndex+`][min_qty]" value="0">
                </label>

                <label>Max Quantity:
                    <input type="text" name="pricing_rules[`+ruleIndex+`][max_qty]" value="Unlimited">
                </label>

                <h4>Tiered Pricing</h4>
                <div class="tiers"></div>
                <button type="button" class="add-tier" style="background:#0073aa;color:#fff;border:none;padding:4px 8px;cursor:pointer;">Add Tier</button>

                <label>Include Products (optional):</label>
                <select name="pricing_rules[`+ruleIndex+`][include_products][]" multiple style="width:100%;"></select>

                <label>Exclude Products (optional):</label>
                <select name="pricing_rules[`+ruleIndex+`][exclude_products][]" multiple style="width:100%;"></select>

                <button type="button" class="remove-rule" style="background:#dc3545;color:#fff;border:none;padding:4px 8px;cursor:pointer;">Remove Rule</button>
            </div>`;
            $('#pricing-rules-container').append(html);
            ruleIndex++;
        });

        $(document).on('click', '.add-tier', function(){
            let $rule = $(this).closest('.pricing-rule');
            let rIndex = $rule.index();
            let tierCount = $rule.find('.tier').length;
            let html = `<div class="tier" style="margin-bottom:8px;">
                <input type="number" name="pricing_rules[`+rIndex+`][tiered_pricing][`+tierCount+`][min_qty]" placeholder="Min Qty">

                <select name="pricing_rules[`+rIndex+`][tiered_pricing][`+tierCount+`][discount_type]">
                    <option value="percentage">Percentage</option>
                    <option value="fixed">Fixed Amount</option>
                </select>

                <input type="number" name="pricing_rules[`+rIndex+`][tiered_pricing][`+tierCount+`][value]" placeholder="Discount Value">
                <button type="button" class="remove-tier" style="background:#dc3545;color:#fff;border:none;padding:4px 8px;cursor:pointer;">Remove</button>
            </div>`;
            $rule.find('.tiers').append(html);
        });

        $(document).on('click', '.remove-tier', function(){
            $(this).closest('.tier').remove();
        });

        $(document).on('click', '.remove-rule', function(){
            $(this).closest('.pricing-rule').remove();
        });
    })(jQuery);
    </script>
    <?php
});

// -----------------------------
// 3. Save Tiered Pricing Rules
// -----------------------------
if (isset($_POST['tiered_pricing_nonce']) && wp_verify_nonce($_POST['tiered_pricing_nonce'], 'save_tiered_pricing_rules')) {
    if (isset($_POST['pricing_rules'])) {
        update_option('wc_role_pricing_global_rules', $_POST['pricing_rules']);
    } else {
        update_option('wc_role_pricing_global_rules', []);
    }
}

// -----------------------------
// 4. Get Tiered Price
// -----------------------------
function get_tiered_price($product_id, $product_price, $quantity, $user_role) {
    $rules = get_option('wc_role_pricing_global_rules', []);

    foreach ($rules as $rule) {
        if ($rule['role'] !== $user_role) continue;
        if ($quantity < $rule['min_qty']) continue;
        if ($rule['max_qty'] !== 'Unlimited' && $quantity > $rule['max_qty']) continue;

        if (!empty($rule['include_products']) && !in_array($product_id, $rule['include_products'])) continue;
        if (!empty($rule['exclude_products']) && in_array($product_id, $rule['exclude_products'])) continue;

        foreach ($rule['tiered_pricing'] as $tier) {
            if ($quantity >= $tier['min_qty']) {
                if ($tier['discount_type'] === 'percentage') {
                    return $product_price - ($product_price * $tier['value'] / 100);
                }
                if ($tier['discount_type'] === 'fixed') {
                    return $product_price - $tier['value'];
                }
            }
        }
    }
    return $product_price;
}