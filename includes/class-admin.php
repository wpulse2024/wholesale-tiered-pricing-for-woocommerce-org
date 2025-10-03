<?php
if (!defined('ABSPATH')) {
    exit;
}

class WC_Role_Pricing_Admin
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

        wp_enqueue_style('wholesale-tiered-pricing-for-woocommerce-admin', WC_ROLE_PRICING_PLUGIN_URL . 'assets/admin.css', array(), WC_ROLE_PRICING_VERSION);
        wp_enqueue_script('wholesale-tiered-pricing-for-woocommerce-admin', WC_ROLE_PRICING_PLUGIN_URL . 'assets/admin.js', array('jquery', 'wp-util'), WC_ROLE_PRICING_VERSION, true);
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
                ));
                ?>
            </div>
        </div>
    <?php
    }

    private function render_pricing_rule($index, $rule = array())
    {
        $roles = wp_roles()->get_names();
    ?>
        <div class="pricing-rule-row" data-index="<?php echo esc_attr($index); ?>">
            <a href="#" class="remove-pricing-rule"><?php esc_html_e('Remove', 'wholesale-tiered-pricing-for-woocommerce'); ?></a>
            <div class="pricing-rule-fields">
                <p class="form-field">
                    <label><?php esc_html_e('User Role', 'wholesale-tiered-pricing-for-woocommerce'); ?></label>
                    <select name="role_pricing_rules[<?php echo esc_attr($index); ?>][role]">
                        <option value=""><?php esc_html_e('Select Role', 'wholesale-tiered-pricing-for-woocommerce'); ?></option>
                        <?php foreach ($roles as $role_key => $role_name): ?>
                            <option value="<?php echo esc_attr($role_key); ?>"
                                <?php selected(isset($rule['role']) ? $rule['role'] : '', $role_key); ?>>
                                <?php echo esc_html($role_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
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
                    $tiered_rules = isset($rule['tiered_pricing']) ? esc_attr($rule['tiered_pricing']) : array();
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
            <button type="button" class="button remove-tier-rule"><?php esc_html_e('Remove', 'wholesale-tiered-pricing-for-woocommerce'); ?></button>
        </div>
<?php
    }

    public function save_product_data($post_id)
    {
        if (isset($_POST['role_pricing_rules'])) {
            $rules = array();
            foreach ($_POST['role_pricing_rules'] as $rule) {
                if (!empty($rule['role'])) {
                    $rules[] = array(
                        'role' => sanitize_text_field($rule['role']),
                        'min_qty' => intval($rule['min_qty']),
                        'max_qty' => !empty($rule['max_qty']) ? intval($rule['max_qty']) : 0,
                        'step_qty' => intval($rule['step_qty']),
                        'price_type' => sanitize_text_field($rule['price_type']),
                        'price_value' => floatval($rule['price_value']),
                        'tiered_pricing' => isset($rule['tiered_pricing']) ? $rule['tiered_pricing'] : array()
                    );
                }
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


