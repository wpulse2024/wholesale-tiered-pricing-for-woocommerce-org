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