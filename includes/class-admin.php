<?php
if (!defined('ABSPATH')) {
    exit;
}

class WHTPRole_Pricing_Admin
{

    public function __construct()
    {
        add_action('woocommerce_product_data_panels', array($this, 'add_product_data_panel'));
        add_action('woocommerce_product_data_tabs', array($this, 'add_product_data_tab'));
        add_action('woocommerce_process_product_meta', array($this, 'save_product_data'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    public function enqueue_admin_scripts($hook)
    {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }

        global $post;
        if ($post && 'product' !== $post->post_type) {
            return;
        }

        // Enqueue Select2 (WordPress includes it for WooCommerce)
        wp_enqueue_script('select2');
        wp_enqueue_style('select2', WC()->plugin_url() . '/assets/css/select2.css', array(), WC_VERSION);
        
        wp_enqueue_style('wholesale-tiered-pricing-for-woocommerce-admin', WHTPROLE_PRICING_PLUGIN_URL . 'plugin-assets/admin.css', array(), WHTPROLE_PRICING_VERSION);
        wp_enqueue_script('wholesale-tiered-pricing-for-woocommerce-admin', WHTPROLE_PRICING_PLUGIN_URL . 'plugin-assets/admin.js', array('jquery', 'select2', 'wp-util'), WHTPROLE_PRICING_VERSION, true);
        
        // Pass user roles to JavaScript
        $roles = wp_roles()->get_names();
        wp_localize_script('wholesale-tiered-pricing-for-woocommerce-admin', 'whtproleAdminRoles', array(
            'roles' => $roles
        ));
    }

    public function add_product_data_tab($tabs)
    {
        $tabs['role_pricing'] = array(
            'label'    => __('Role Pricing', 'wholesale-tiered-pricing-for-woocommerce'),
            'target'   => 'role_pricing_data',
            'class'    => array('show_if_simple', 'show_if_variable'),
            'priority' => 80,
        );
        return $tabs;
    }

    public function add_product_data_panel()
    {
        global $post;
    ?>
        <div id="role_pricing_data" class="panel woocommerce_options_panel">
            <div class="options_group">
                <h3><?php esc_html_e('Role-Based Pricing Rules', 'wholesale-tiered-pricing-for-woocommerce'); ?></h3>

                <div id="role-pricing-rules">
                    <?php
                    $rules = get_post_meta($post->ID, '_role_pricing_rules', true);
                    if (empty($rules)) {
                        $rules = array();
                    }

                    foreach ($rules as $index => $rule) {
                        $this->render_pricing_rule($index, $rule);
                    }
                    ?>
                </div>

                <p>
                    <button type="button" class="button" id="add-pricing-rule">
                        <?php esc_html_e('Add Pricing Rule', 'wholesale-tiered-pricing-for-woocommerce'); ?>
                    </button>
                </p>
            </div>

            <div class="options_group">
                <?php

                woocommerce_wp_checkbox(array(
                    'id'    => '_show_pricing_table',
                    'label' => __('Show Pricing Table', 'wholesale-tiered-pricing-for-woocommerce'),
                    'description' => __('Display tiered pricing table on product page', 'wholesale-tiered-pricing-for-woocommerce'),
                    'desc_tip' => true,
                    'value' => get_post_meta($post->ID, '_show_pricing_table', 'yes') == 'no' ? 'no' : 'yes',
                ));
                ?>
            </div>
        </div>
    <?php
    }

    private function render_pricing_rule($index, $rule = array())
    {
        $roles = wp_roles()->get_names();
        
        // Normalize roles: support both legacy 'role' (string) and new 'roles' (array)
        $rule_roles = array();
        if (isset($rule['roles']) && is_array($rule['roles'])) {
            $rule_roles = $rule['roles'];
        } elseif (isset($rule['role']) && !empty($rule['role'])) {
            // Legacy: single role as string
            $rule_roles = array($rule['role']);
        }
        
        // Get also_for_guest value
        $also_for_guest = isset($rule['also_for_guest']) ? ($rule['also_for_guest'] === true || $rule['also_for_guest'] === 'true' || $rule['also_for_guest'] === 1 || $rule['also_for_guest'] === '1') : false;
        $has_global = in_array('guest', $rule_roles);
    ?>
        <div class="pricing-rule-row" data-index="<?php echo esc_attr($index); ?>">
            <a href="#" class="remove-pricing-rule"><?php esc_html_e('Remove', 'wholesale-tiered-pricing-for-woocommerce'); ?></a>
            <div class="pricing-rule-fields">
                <p class="form-field">
                    <label><?php esc_html_e('User Roles (Select Multiple)', 'wholesale-tiered-pricing-for-woocommerce'); ?></label>
                    <select name="role_pricing_rules[<?php echo esc_attr($index); ?>][roles][]" 
                            multiple 
                            class="role-multi-select" 
                            style="min-height: 120px; width: 100%;"
                            data-index="<?php echo esc_attr($index); ?>">
                        <option value="guest" <?php selected(in_array('guest', $rule_roles), true); ?>>
                            <?php esc_html_e('Global (All Logged-in Users)', 'wholesale-tiered-pricing-for-woocommerce'); ?>
                        </option>
                        <?php foreach ($roles as $role_key => $role_name): ?>
                            <option value="<?php echo esc_attr($role_key); ?>"
                                <?php selected(in_array($role_key, $rule_roles), true); ?>>
                                <?php echo esc_html($role_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description" style="margin-top: 5px; font-size: 12px; color: #666;">
                        <?php esc_html_e('Select one or more user roles. "Global" applies to all logged-in users. Hold Ctrl/Cmd to select multiple.', 'wholesale-tiered-pricing-for-woocommerce'); ?>
                    </p>
                    <!-- Hidden field for backward compatibility -->
                    <input type="hidden" name="role_pricing_rules[<?php echo esc_attr($index); ?>][role]" 
                           value="<?php echo esc_attr(!empty($rule_roles) ? $rule_roles[0] : ''); ?>" />
                </p>

                <p class="form-field guest-checkbox-field" style="<?php echo $has_global ? '' : 'display: none;'; ?>">
                    <label>
                        <input type="checkbox" 
                               name="role_pricing_rules[<?php echo esc_attr($index); ?>][also_for_guest]" 
                               value="1" 
                               <?php checked($also_for_guest, true); ?> />
                        <?php esc_html_e('Make it for guest user also', 'wholesale-tiered-pricing-for-woocommerce'); ?>
                    </label>
                    <span class="description" style="display: block; margin-top: 5px; font-size: 12px; color: #666;">
                        <?php esc_html_e('When enabled, this Global pricing rule will also apply to guest (non-logged-in) users', 'wholesale-tiered-pricing-for-woocommerce'); ?>
                    </span>
                </p>

                <p class="form-field">
                    <label><?php esc_html_e('Step Quantity', 'wholesale-tiered-pricing-for-woocommerce'); ?></label>
                    <input type="number" name="role_pricing_rules[<?php echo esc_attr($index); ?>][step_qty]"
                        value="<?php echo isset($rule['step_qty']) ? esc_attr($rule['step_qty']) : '1'; ?>"
                        min="1" style="width: 100%" />
                </p>

                <p class="form-field">
                    <label><?php esc_html_e('Min Quantity', 'wholesale-tiered-pricing-for-woocommerce'); ?></label>
                    <input type="number" name="role_pricing_rules[<?php echo esc_attr($index); ?>][min_qty]"
                        value="<?php echo isset($rule['min_qty']) ? esc_attr($rule['min_qty']) : '0'; ?>"
                        min="0" style="width: 100%" />
                </p>

                <p class="form-field">
                    <label><?php esc_html_e('Max Quantity', 'wholesale-tiered-pricing-for-woocommerce'); ?></label>
                    <input type="number" name="role_pricing_rules[<?php echo esc_attr($index); ?>][max_qty]"
                        value="<?php echo isset($rule['max_qty']) ? esc_attr($rule['max_qty']) : ''; ?>"
                        min="0" placeholder="<?php esc_html_e('Unlimited', 'wholesale-tiered-pricing-for-woocommerce'); ?>" style="width: 100%" />
                </p>

            </div>

            <div class="tiered-pricing-section">
                <h4><?php esc_html_e('Tiered Pricing', 'wholesale-tiered-pricing-for-woocommerce'); ?></h4>
                <div class="tiered-pricing-rules">
                    <?php
                    $tiered_rules = isset($rule['tiered_pricing']) ? $rule['tiered_pricing'] : array();
                    foreach ($tiered_rules as $tier_index => $tier_rule) {
                        $this->render_tier_rule($index, $tier_index, $tier_rule);
                    }
                    ?>
                </div>
                <button type="button" class="button add-tier-rule" data-parent="<?php echo esc_attr($index); ?>">
                    <?php esc_html_e('Add Tier', 'wholesale-tiered-pricing-for-woocommerce'); ?>
                </button>
            </div>
        </div>
    <?php
    }

    private function render_tier_rule($parent_index, $tier_index, $tier_rule = array())
    {
    ?>
        <div class="tier-rule-row" style="display: flex; gap: 10px; margin-bottom: 5px;">
            <input type="number" name="role_pricing_rules[<?php echo esc_attr($parent_index); ?>][tiered_pricing][<?php echo esc_attr($tier_index); ?>][min_qty]"
                placeholder="<?php esc_html_e('Min Qty', 'wholesale-tiered-pricing-for-woocommerce'); ?>"
                value="<?php echo isset($tier_rule['min_qty']) ? esc_attr($tier_rule['min_qty']) : ''; ?>"
                min="1" style="width: 150px;" />
            <input type="number" name="role_pricing_rules[<?php echo esc_attr($parent_index); ?>][tiered_pricing][<?php echo esc_attr($tier_index); ?>][price]"
                placeholder="<?php esc_html_e('Price', 'wholesale-tiered-pricing-for-woocommerce'); ?>"
                value="<?php echo isset($tier_rule['price']) ? esc_attr($tier_rule['price']) : ''; ?>"
                step="0.01" min="0" style="width: 150px;" />
            <select name="role_pricing_rules[<?php echo esc_attr($parent_index); ?>][tiered_pricing][<?php echo esc_attr($tier_index); ?>][discount_type]">
                <option value="fixed" <?php selected(isset($tier_rule['discount_type']) ? $tier_rule['discount_type'] : '', 'fixed'); ?>>
                    <?php esc_html_e('Fixed', 'wholesale-tiered-pricing-for-woocommerce'); ?>
                </option>
                <option value="percentage" <?php selected(isset($tier_rule['discount_type']) ? $tier_rule['discount_type'] : '', 'percentage'); ?>>
                    <?php esc_html_e('Percentage', 'wholesale-tiered-pricing-for-woocommerce'); ?>
                </option>
            </select>
            <button type="button" class="button remove-tier-rule"><?php esc_html_e('Remove', 'wholesale-tiered-pricing-for-woocommerce'); ?></button>
        </div>
<?php
    }

    public function save_product_data($post_id)
    {
        delete_post_meta($post_id, '_role_pricing_rules');
        
        if (isset($_POST['role_pricing_rules'])) {
            $rules = array();
            foreach ($_POST['role_pricing_rules'] as $rule) {
                // Normalize roles: support both new 'roles' (array) and legacy 'role' (string)
                $roles = array();
                if (isset($rule['roles']) && is_array($rule['roles'])) {
                    // New format: array of roles
                    $roles = array_map('sanitize_text_field', array_filter($rule['roles']));
                } elseif (isset($rule['role']) && !empty($rule['role'])) {
                    // Legacy format: single role string
                    $roles = array(sanitize_text_field($rule['role']));
                }
                
                // If no roles, skip this rule
                if (empty($roles)) {
                    continue;
                }
                
                // If Global is in roles, it should be the only role (wildcard behavior)
                if (in_array('guest', $roles)) {
                    $roles = array('guest');
                }
                
                // Handle also_for_guest field (only for Global/guest role)
                $also_for_guest = false;
                if (in_array('guest', $roles) && isset($rule['also_for_guest'])) {
                    $also_for_guest = ($rule['also_for_guest'] === '1' || $rule['also_for_guest'] === 1 || $rule['also_for_guest'] === true);
                }
                
                // Sanitize tiered pricing
                $tiered_pricing = array();
                if (isset($rule['tiered_pricing']) && is_array($rule['tiered_pricing'])) {
                    foreach ($rule['tiered_pricing'] as $tier) {
                        $tiered_pricing[] = array(
                            'min_qty' => isset($tier['min_qty']) ? intval($tier['min_qty']) : 0,
                            'price' => isset($tier['price']) ? floatval($tier['price']) : 0,
                            'discount_type' => isset($tier['discount_type']) ? sanitize_text_field($tier['discount_type']) : 'fixed'
                        );
                    }
                }
                
                $rules[] = array(
                    'roles' => $roles, // New: always store as array
                    'role' => !empty($roles) ? $roles[0] : 'customer', // Keep for backward compatibility
                    'min_qty' => isset($rule['min_qty']) ? intval($rule['min_qty']) : 0,
                    'max_qty' => !empty($rule['max_qty']) ? intval($rule['max_qty']) : 0,
                    'step_qty' => isset($rule['step_qty']) ? intval($rule['step_qty']) : 1,
                    'tiered_pricing' => $tiered_pricing,
                    'also_for_guest' => $also_for_guest
                );
            }
            update_post_meta($post_id, '_role_pricing_rules', $rules);
        }

        if (isset($_POST['_show_pricing_table'])) {
            update_post_meta($post_id, '_show_pricing_table', 'yes');
        } else {
            update_post_meta($post_id, '_show_pricing_table', 'no');
        }
    }
}


