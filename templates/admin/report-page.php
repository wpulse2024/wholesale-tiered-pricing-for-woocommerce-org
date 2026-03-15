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

$today        = date('Y-m-d');
$preset_7d    = date('Y-m-d', strtotime('-7 days'));
$preset_30d   = date('Y-m-d', strtotime('-30 days'));
$preset_90d   = date('Y-m-d', strtotime('-90 days'));

$active_preset = '';
if ($date_from === $preset_7d && $date_to === $today)   $active_preset = '7d';
elseif ($date_from === $preset_30d && $date_to === $today) $active_preset = '30d';
elseif ($date_from === $preset_90d && $date_to === $today) $active_preset = '90d';
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
            <div class="whtprole-filter-dates">
                <label>
                    <span><?php esc_html_e('From', 'wholesale-tiered-pricing-for-woocommerce'); ?></span>
                    <div class="whtprole-date-input-wrap">
                        <input type="date" name="date_from" value="<?php echo esc_attr($date_from); ?>" required />
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </div>
                </label>
                <label>
                    <span><?php esc_html_e('To', 'wholesale-tiered-pricing-for-woocommerce'); ?></span>
                    <div class="whtprole-date-input-wrap">
                        <input type="date" name="date_to" value="<?php echo esc_attr($date_to); ?>" required />
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </div>
                </label>
            </div>
            <div class="whtprole-filter-actions">
                <span class="whtprole-filter-presets">
                    <a href="<?php echo esc_url(add_query_arg(array('page' => 'wholesale-tiered-pricing', 'date_from' => $preset_7d, 'date_to' => $today), admin_url('admin.php'))); ?>" class="button button-small<?php echo $active_preset === '7d' ? ' is-active' : ''; ?>"><?php esc_html_e('7d', 'wholesale-tiered-pricing-for-woocommerce'); ?></a>
                    <a href="<?php echo esc_url(add_query_arg(array('page' => 'wholesale-tiered-pricing', 'date_from' => $preset_30d, 'date_to' => $today), admin_url('admin.php'))); ?>" class="button button-small<?php echo $active_preset === '30d' ? ' is-active' : ''; ?>"><?php esc_html_e('30d', 'wholesale-tiered-pricing-for-woocommerce'); ?></a>
                    <a href="<?php echo esc_url(add_query_arg(array('page' => 'wholesale-tiered-pricing', 'date_from' => $preset_90d, 'date_to' => $today), admin_url('admin.php'))); ?>" class="button button-small<?php echo $active_preset === '90d' ? ' is-active' : ''; ?>"><?php esc_html_e('90d', 'wholesale-tiered-pricing-for-woocommerce'); ?></a>
                </span>
                <button type="submit" class="button whtprole-btn-dark">
                    <?php esc_html_e('Apply', 'wholesale-tiered-pricing-for-woocommerce'); ?>
                </button>
            </div>
        </form>
    </div>

    <div class="whtprole-summary-cards">
        <div class="whtprole-card">
            <div class="whtprole-card-body">
                <span class="whtprole-card-label"><?php esc_html_e('Products with Pricing Rules', 'wholesale-tiered-pricing-for-woocommerce'); ?></span>
                <span class="whtprole-card-value"><?php echo esc_html((string) $report_data['summary']['products_with_rules']); ?></span>
            </div>
            <div class="whtprole-card-icon whtprole-card-icon--blue" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
            </div>
        </div>
        <div class="whtprole-card">
            <div class="whtprole-card-body">
                <span class="whtprole-card-label"><?php esc_html_e('Global Rules', 'wholesale-tiered-pricing-for-woocommerce'); ?></span>
                <span class="whtprole-card-value"><?php echo esc_html((string) $report_data['summary']['global_rules_count']); ?></span>
            </div>
            <div class="whtprole-card-icon whtprole-card-icon--purple" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            </div>
        </div>
        <div class="whtprole-card">
            <div class="whtprole-card-body">
                <span class="whtprole-card-label"><?php esc_html_e('Wholesale Orders', 'wholesale-tiered-pricing-for-woocommerce'); ?></span>
                <span class="whtprole-card-value"><?php echo esc_html((string) $report_data['summary']['total_wholesale_orders']); ?></span>
            </div>
            <div class="whtprole-card-icon whtprole-card-icon--green" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            </div>
        </div>
        <div class="whtprole-card">
            <div class="whtprole-card-body">
                <span class="whtprole-card-label"><?php esc_html_e('Wholesale Revenue', 'wholesale-tiered-pricing-for-woocommerce'); ?></span>
                <span class="whtprole-card-value"><?php echo wp_kses_post(wc_price($report_data['summary']['wholesale_revenue'])); ?></span>
            </div>
            <div class="whtprole-card-icon whtprole-card-icon--orange" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            </div>
        </div>
    </div>

    <div class="whtprole-report-grid">
        <div class="whtprole-report-section">
            <h2>
                <span class="whtprole-section-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                </span>
                <?php esc_html_e('Orders by Role', 'wholesale-tiered-pricing-for-woocommerce'); ?>
            </h2>
            <?php if (!empty($report_data['orders_by_role'])) : ?>
                <table class="widefat">
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
            <h2>
                <span class="whtprole-section-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                </span>
                <?php esc_html_e('Recent Orders', 'wholesale-tiered-pricing-for-woocommerce'); ?>
            </h2>
            <?php if (!empty($report_data['recent_orders'])) : ?>
                <table class="widefat">
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

        <div class="whtprole-report-section whtprole-report-full">
            <h2>
                <span class="whtprole-section-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                </span>
                <?php esc_html_e('Top Products', 'wholesale-tiered-pricing-for-woocommerce'); ?>
            </h2>
            <?php if (!empty($report_data['top_products'])) : ?>
                <table class="widefat">
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
            <h2>
                <span class="whtprole-section-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                </span>
                <?php esc_html_e('Products with Tiered Pricing', 'wholesale-tiered-pricing-for-woocommerce'); ?>
            </h2>
            <?php if (!empty($report_data['products_with_rules'])) : ?>
                <table class="widefat">
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
                                        <a href="<?php echo esc_url($product['edit_link']); ?>" class="button button-small whtprole-btn-dark"><?php esc_html_e('Edit', 'wholesale-tiered-pricing-for-woocommerce'); ?></a>
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
