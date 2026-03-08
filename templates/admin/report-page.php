<?php
/**
 * Wholesale Reports Page Template
 *
 * @package Wholesale_Tiered_Pricing_For_WooCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

$date_from = isset($_GET['date_from']) ? sanitize_text_field(wp_unslash($_GET['date_from'])) : date('Y-m-d', strtotime('-30 days'));
$date_to   = isset($_GET['date_to']) ? sanitize_text_field(wp_unslash($_GET['date_to'])) : date('Y-m-d');
$report_data = WHTPRole_Wholesale_Menu::get_report_data_static($date_from, $date_to);

$wp_roles = wp_roles()->get_names();
$settings_url = admin_url('admin.php?page=wc-settings&tab=tiered_pricing');
?>
<div class="wrap whtprole-report-wrap">
    <div class="whtprole-report-header">
        <div>
            <h1 class="whtprole-report-title">
                <?php esc_html_e('Wholesale Reports', 'wholesale-tiered-pricing-for-woocommerce'); ?>
            </h1>
            <p class="whtprole-report-desc">
                <?php esc_html_e('Overview of your wholesale and tiered pricing performance.', 'wholesale-tiered-pricing-for-woocommerce'); ?>
                <a href="<?php echo esc_url($settings_url); ?>"><?php esc_html_e('Configure Tiered Pricing', 'wholesale-tiered-pricing-for-woocommerce'); ?> &rarr;</a>
            </p>
        </div>
    </div>

    <div class="whtprole-report-filters">
        <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" id="whtprole-report-filter-form">
            <input type="hidden" name="page" value="wholesale-tiered-pricing" />
            <label>
                <span><?php esc_html_e('From', 'wholesale-tiered-pricing-for-woocommerce'); ?></span>
                <input type="date" name="date_from" value="<?php echo esc_attr($date_from); ?>" required />
            </label>
            <label>
                <span><?php esc_html_e('To', 'wholesale-tiered-pricing-for-woocommerce'); ?></span>
                <input type="date" name="date_to" value="<?php echo esc_attr($date_to); ?>" required />
            </label>
            <span class="whtprole-filter-presets">
                <a href="<?php echo esc_url(add_query_arg(array('page' => 'wholesale-tiered-pricing', 'date_from' => date('Y-m-d', strtotime('-7 days')), 'date_to' => date('Y-m-d')), admin_url('admin.php'))); ?>" class="button button-small"><?php esc_html_e('7d', 'wholesale-tiered-pricing-for-woocommerce'); ?></a>
                <a href="<?php echo esc_url(add_query_arg(array('page' => 'wholesale-tiered-pricing', 'date_from' => date('Y-m-d', strtotime('-30 days')), 'date_to' => date('Y-m-d')), admin_url('admin.php'))); ?>" class="button button-small"><?php esc_html_e('30d', 'wholesale-tiered-pricing-for-woocommerce'); ?></a>
                <a href="<?php echo esc_url(add_query_arg(array('page' => 'wholesale-tiered-pricing', 'date_from' => date('Y-m-d', strtotime('-90 days')), 'date_to' => date('Y-m-d')), admin_url('admin.php'))); ?>" class="button button-small"><?php esc_html_e('90d', 'wholesale-tiered-pricing-for-woocommerce'); ?></a>
            </span>
            <button type="submit" class="button button-primary">
                <?php esc_html_e('Apply', 'wholesale-tiered-pricing-for-woocommerce'); ?>
            </button>
        </form>
    </div>

    <div class="whtprole-summary-cards">
            <div class="whtprole-card">
                <span class="whtprole-card-label"><?php esc_html_e('Products with Pricing Rules', 'wholesale-tiered-pricing-for-woocommerce'); ?></span>
                <span class="whtprole-card-value"><?php echo esc_html((string) $report_data['summary']['products_with_rules']); ?></span>
            </div>
            <div class="whtprole-card">
                <span class="whtprole-card-label"><?php esc_html_e('Global Rules', 'wholesale-tiered-pricing-for-woocommerce'); ?></span>
                <span class="whtprole-card-value"><?php echo esc_html((string) $report_data['summary']['global_rules_count']); ?></span>
            </div>
            <div class="whtprole-card">
                <span class="whtprole-card-label"><?php esc_html_e('Wholesale Orders', 'wholesale-tiered-pricing-for-woocommerce'); ?></span>
                <span class="whtprole-card-value"><?php echo esc_html((string) $report_data['summary']['total_wholesale_orders']); ?></span>
            </div>
            <div class="whtprole-card whtprole-card-highlight">
                <span class="whtprole-card-label"><?php esc_html_e('Wholesale Revenue', 'wholesale-tiered-pricing-for-woocommerce'); ?></span>
                <span class="whtprole-card-value"><?php echo wp_kses_post(wc_price($report_data['summary']['wholesale_revenue'])); ?></span>
            </div>
        </div>

    <div class="whtprole-report-grid">
        <div class="whtprole-report-section">
            <h2><?php esc_html_e('Orders by Role', 'wholesale-tiered-pricing-for-woocommerce'); ?></h2>
            <?php if (!empty($report_data['orders_by_role'])) : ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Role', 'wholesale-tiered-pricing-for-woocommerce'); ?></th>
                            <th><?php esc_html_e('Orders', 'wholesale-tiered-pricing-for-woocommerce'); ?></th>
                            <th><?php esc_html_e('Revenue', 'wholesale-tiered-pricing-for-woocommerce'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report_data['orders_by_role'] as $role_key => $data) : ?>
                            <tr>
                                <td><?php echo esc_html(isset($wp_roles[$role_key]) ? $wp_roles[$role_key] : ($role_key === 'guest' ? __('Guest', 'wholesale-tiered-pricing-for-woocommerce') : $role_key)); ?></td>
                                <td><?php echo esc_html((string) $data['count']); ?></td>
                                <td><?php echo wp_kses_post(wc_price($data['revenue'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p class="whtprole-empty"><?php esc_html_e('No orders found for the selected period.', 'wholesale-tiered-pricing-for-woocommerce'); ?></p>
            <?php endif; ?>
        </div>

        <div class="whtprole-report-section">
            <h2><?php esc_html_e('Recent Orders', 'wholesale-tiered-pricing-for-woocommerce'); ?></h2>
            <?php if (!empty($report_data['recent_orders'])) : ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Order', 'wholesale-tiered-pricing-for-woocommerce'); ?></th>
                            <th><?php esc_html_e('Date', 'wholesale-tiered-pricing-for-woocommerce'); ?></th>
                            <th><?php esc_html_e('Role', 'wholesale-tiered-pricing-for-woocommerce'); ?></th>
                            <th><?php esc_html_e('Total', 'wholesale-tiered-pricing-for-woocommerce'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report_data['recent_orders'] as $order) : ?>
                            <tr>
                                <td><a href="<?php echo esc_url($order['edit_link']); ?>">#<?php echo esc_html((string) $order['id']); ?></a></td>
                                <td><?php echo esc_html($order['date']); ?></td>
                                <td><?php echo esc_html(isset($wp_roles[$order['role']]) ? $wp_roles[$order['role']] : ($order['role'] === 'guest' ? __('Guest', 'wholesale-tiered-pricing-for-woocommerce') : $order['role'])); ?></td>
                                <td><?php echo wp_kses_post(wc_price($order['total'], array('currency' => $order['currency']))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p class="whtprole-empty"><?php esc_html_e('No recent orders.', 'wholesale-tiered-pricing-for-woocommerce'); ?></p>
            <?php endif; ?>
        </div>

        <div class="whtprole-report-section">
            <h2><?php esc_html_e('Top Products', 'wholesale-tiered-pricing-for-woocommerce'); ?></h2>
            <?php if (!empty($report_data['top_products'])) : ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Product', 'wholesale-tiered-pricing-for-woocommerce'); ?></th>
                            <th><?php esc_html_e('Quantity Sold', 'wholesale-tiered-pricing-for-woocommerce'); ?></th>
                            <th><?php esc_html_e('Revenue', 'wholesale-tiered-pricing-for-woocommerce'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report_data['top_products'] as $product) : ?>
                            <tr>
                                <td>
                                    <?php if (!empty($product['edit_link'])) : ?>
                                        <a href="<?php echo esc_url($product['edit_link']); ?>"><?php echo esc_html($product['name']); ?></a>
                                    <?php else : ?>
                                        <?php echo esc_html($product['name']); ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html((string) $product['qty']); ?></td>
                                <td><?php echo wp_kses_post(wc_price($product['revenue'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p class="whtprole-empty"><?php esc_html_e('No product sales data for the selected period.', 'wholesale-tiered-pricing-for-woocommerce'); ?></p>
            <?php endif; ?>
        </div>

        <div class="whtprole-report-section whtprole-report-full">
            <h2><?php esc_html_e('Products with Tiered Pricing', 'wholesale-tiered-pricing-for-woocommerce'); ?></h2>
            <?php if (!empty($report_data['products_with_rules'])) : ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Product', 'wholesale-tiered-pricing-for-woocommerce'); ?></th>
                            <th><?php esc_html_e('Pricing Rules', 'wholesale-tiered-pricing-for-woocommerce'); ?></th>
                            <th><?php esc_html_e('Actions', 'wholesale-tiered-pricing-for-woocommerce'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report_data['products_with_rules'] as $product) : ?>
                            <tr>
                                <td><?php echo esc_html($product['name']); ?></td>
                                <td><?php echo esc_html((string) $product['rule_count']); ?></td>
                                <td>
                                    <?php if (!empty($product['edit_link'])) : ?>
                                        <a href="<?php echo esc_url($product['edit_link']); ?>" class="button button-small"><?php esc_html_e('Edit', 'wholesale-tiered-pricing-for-woocommerce'); ?></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p class="whtprole-empty"><?php esc_html_e('No products with tiered pricing rules. Add rules in the Role Pricing tab when editing a product.', 'wholesale-tiered-pricing-for-woocommerce'); ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>
