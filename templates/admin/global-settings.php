<div class="wtpfw_admin_settings_wrapper">
<h2><?php _e('Tiered Pricing Rules', 'text-domain'); ?></h2>
<form method="post" id="tiered-pricing-form">
    <?php wp_nonce_field('save_tiered_pricing_rules', 'tiered_pricing_nonce'); ?>

    <div id="pricing-rules-container">
        <?php if (!empty($rules)) : ?>
            <?php foreach ($rules as $index => $rule) : ?>
                <div class="pricing-rule" style="border:1px solid #ccc;padding:15px;margin-bottom:15px;background:#fff;">
                    <div class="pricing-rule-header_wrapper">
                        <div class="pricing-rule-header">
                            <label>User Role:</label>
                            <select name="pricing_rules[<?php echo $index; ?>][role]">
                                <?php
                                global $wp_roles;
                                foreach ($wp_roles->roles as $role_key => $role_data) {
                                    $selected = ($rule['role'] === $role_key) ? 'selected' : '';
                                    echo "<option value='{$role_key}' {$selected}>{$role_data['name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="pricing-rule-header">
                            <label>Step Quantity:</label>
                            <input type="number" name="pricing_rules[<?php echo $index; ?>][step_qty]" value="<?php echo esc_attr($rule['step_qty']); ?>">
                        </div>
                        <div class="pricing-rule-header">
                            <label>Min Quantity:</label>
                            <input type="number" name="pricing_rules[<?php echo $index; ?>][min_qty]" value="<?php echo esc_attr($rule['min_qty']); ?>">
                        </div>
                        <div class="pricing-rule-header">
                            <label>Max Quantity:</label>
                            <input type="text" name="pricing_rules[<?php echo $index; ?>][max_qty]" value="<?php echo esc_attr($rule['max_qty']); ?>">
                        </div>
                    </div>

                    <h4>Tiered Pricing</h4>
                    <div class="tiers" style="margin-bottom:15px;">
                        <?php if (!empty($rule['tiered_pricing'])) : ?>
                            <?php foreach ($rule['tiered_pricing'] as $t_index => $tier) : ?>
                                <div class="tier" style="margin-bottom:8px;">
                                    <input type="number" name="pricing_rules[<?php echo $index; ?>][tiered_pricing][<?php echo $t_index; ?>][min_qty]" value="<?php echo esc_attr($tier['min_qty']); ?>" placeholder="Min Qty">

                                    <select name="pricing_rules[<?php echo $index; ?>][tiered_pricing][<?php echo $t_index; ?>][discount_type]">
                                        <option value="percentage" <?php echo ($tier['discount_type'] === 'percentage') ? 'selected' : ''; ?>>Percentage</option>
                                        <option value="fixed" <?php echo ($tier['discount_type'] === 'fixed') ? 'selected' : ''; ?>>Fixed Amount</option>
                                    </select>

                                    <input type="number" name="pricing_rules[<?php echo $index; ?>][tiered_pricing][<?php echo $t_index; ?>][price]" value="<?php echo esc_attr($tier['price']); ?>" placeholder="Discount Value">

                                    <button type="button" class="remove-tier" style="background:#868A98;color:#fff;border:none;padding:10px; border-radius:12px;cursor:pointer;">
                                        <span class="dashicons dashicons-no-alt"></span>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="remove-rule" style="color:#000;border:none;padding:6px 12px;cursor:pointer; border-radius:12px; background: #E8EAF1;">Remove Rule</button>
                    <button type="button" class="add-tier" style="color:#000;border:none; padding:6px 12px;cursor:pointer; border-radius:12px; background:#E8EAF1;">Add Tier</button>
                    </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <div class="wtpfw_admin_settings_buttons" style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px;">
        <button type="button" id="add-rule" style="background:#666978; color:#fff; border:none; padding:12px 12px;cursor:pointer; border-radius:12px;">Add Pricing Rule</button>
        <p class="submit">
            <input style="background: #253241; border-color: #253241; padding: 4px 12px; border-radius: 12px; font-size: 14px; font-weight: 500;" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes', 'text-domain'); ?>">
        </p>
    </div>
</form>
</div>