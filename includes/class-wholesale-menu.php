<?php
/**
 * Wholesale Admin Menu & Reports
 *
 * Adds a top-level Wholesale menu in WordPress admin with a Reports page.
 */

if (!defined('ABSPATH')) {
    exit;
}

class WHTPRole_Wholesale_Menu {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_wholesale_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_report_assets'));
        add_action('woocommerce_checkout_order_created', array($this, 'store_customer_role_on_order'));
        add_action('woocommerce_new_order', array($this, 'store_customer_role_on_order_legacy'), 10, 2);
        add_action('wp_ajax_whtprole_get_report_data', array($this, 'ajax_get_report_data'));
    }

    /**
     * Add Wholesale menu to WordPress admin sidebar
     */
    public function add_wholesale_menu() {
        add_menu_page(
            __('Wholesale', 'wholesale-tiered-pricing-for-woocommerce'),
            __('Wholesale', 'wholesale-tiered-pricing-for-woocommerce'),
            'manage_woocommerce',
            'wholesale-tiered-pricing',
            array($this, 'render_reports_page'),
            'dashicons-store',
            56
        );

        add_submenu_page(
            'wholesale-tiered-pricing',
            __('Reports', 'wholesale-tiered-pricing-for-woocommerce'),
            __('Reports', 'wholesale-tiered-pricing-for-woocommerce'),
            'manage_woocommerce',
            'wholesale-tiered-pricing',
            array($this, 'render_reports_page')
        );

        add_submenu_page(
            'wholesale-tiered-pricing',
            __('Tiered Pricing Settings', 'wholesale-tiered-pricing-for-woocommerce'),
            __('Settings', 'wholesale-tiered-pricing-for-woocommerce'),
            'manage_woocommerce',
            'wholesale-tiered-pricing-settings',
            array($this, 'redirect_to_tiered_settings')
        );
    }

    /**
     * Store customer role on order for reporting
     */
    public function store_customer_role_on_order($order) {
        if (!$order || !is_callable(array($order, 'get_customer_id'))) {
            return;
        }
        $customer_id = $order->get_customer_id();
        $role = $this->get_user_role($customer_id);
        if ($role) {
            $order->update_meta_data('_whtprole_customer_role', $role);
            $order->save();
        }
    }

    /**
     * Legacy hook for WooCommerce < 8.2
     */
    public function store_customer_role_on_order_legacy($order_id, $order = null) {
        if ($order === null) {
            $order = wc_get_order($order_id);
        }
        if ($order) {
            $this->store_customer_role_on_order($order);
        }
    }

    private function get_user_role($user_id) {
        if (!$user_id) {
            return 'guest';
        }
        $user = get_userdata($user_id);
        if (!$user || empty($user->roles)) {
            return 'guest';
        }
        return $user->roles[0];
    }

    /**
     * Enqueue report page assets
     */
    public function enqueue_report_assets($hook) {
        if (strpos($hook, 'wholesale-tiered-pricing') === false) {
            return;
        }

        wp_enqueue_style(
            'whtprole-report',
            WHTPROLE_PRICING_PLUGIN_URL . 'plugin-assets/report.css',
            array(),
            WHTPROLE_PRICING_VERSION
        );

        wp_enqueue_script(
            'whtprole-report',
            WHTPROLE_PRICING_PLUGIN_URL . 'plugin-assets/report.js',
            array('jquery'),
            WHTPROLE_PRICING_VERSION,
            true
        );

        wp_localize_script('whtprole-report', 'whtproleReport', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('whtprole_report'),
            'i18n'    => array(
                'loading'     => __('Loading...', 'wholesale-tiered-pricing-for-woocommerce'),
                'export'     => __('Export CSV', 'wholesale-tiered-pricing-for-woocommerce'),
                'dateFrom'   => __('Date From', 'wholesale-tiered-pricing-for-woocommerce'),
                'dateTo'     => __('Date To', 'wholesale-tiered-pricing-for-woocommerce'),
                'apply'      => __('Apply', 'wholesale-tiered-pricing-for-woocommerce'),
                'noData'     => __('No data found', 'wholesale-tiered-pricing-for-woocommerce'),
            ),
        ));
    }

    /**
     * Redirect to Tiered Pricing settings tab
     */
    public function redirect_to_tiered_settings() {
        wp_safe_redirect(admin_url('admin.php?page=wc-settings&tab=tiered_pricing'));
        exit;
    }

    /**
     * Render reports page
     */
    public function render_reports_page() {
        include WHTPROLE_PRICING_PLUGIN_PATH . 'templates/admin/report-page.php';
    }

    /**
     * AJAX: Get report data
     */
    public function ajax_get_report_data() {
        check_ajax_referer('whtprole_report', 'nonce');

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }

        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
        $date_to   = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';

        $data = $this->get_report_data($date_from, $date_to);
        wp_send_json_success($data);
    }

    /**
     * Static helper to get report data (for template)
     */
    public static function get_report_data_static($date_from = '', $date_to = '') {
        $instance = new self();
        return $instance->get_report_data($date_from, $date_to);
    }

    /**
     * Get report data
     */
    public function get_report_data($date_from = '', $date_to = '') {
        $cache_key = 'whtprole_report_' . md5($date_from . '_' . $date_to);
        $cached = get_transient($cache_key);
        if ($cached !== false) {
            return $cached;
        }

        $wholesale_roles = $this->get_wholesale_roles();
        $products_with_rules = $this->get_products_with_pricing_rules();
        $global_rules = get_option('whtprole_pricing_global_rules', array());
        $global_rules = is_array($global_rules) ? $global_rules : json_decode($global_rules, true);

        // Orders with wholesale/tiered pricing roles
        $orders_data = $this->get_wholesale_orders($date_from, $date_to);

        // Top products by wholesale sales
        $top_products = $this->get_top_wholesale_products($date_from, $date_to);

        $result = array(
            'summary' => array(
                'products_with_rules' => count($products_with_rules),
                'global_rules_count'  => count($global_rules),
                'wholesale_roles'     => count($wholesale_roles),
                'total_wholesale_orders' => $orders_data['total_orders'],
                'wholesale_revenue'   => $orders_data['total_revenue'],
                'wholesale_discount_given' => $orders_data['total_discount'],
            ),
            'wholesale_roles' => $wholesale_roles,
            'products_with_rules' => $products_with_rules,
            'orders_by_role' => $orders_data['by_role'],
            'recent_orders'  => $orders_data['recent_orders'],
            'top_products'   => $top_products,
            'global_rules'   => $global_rules,
        );

        set_transient($cache_key, $result, 5 * MINUTE_IN_SECONDS);
        return $result;
    }

    private function get_wholesale_roles() {
        $roles = array();
        $global_rules = get_option('whtprole_pricing_global_rules', array());
        $global_rules = is_array($global_rules) ? $global_rules : json_decode($global_rules, true);

        if (!empty($global_rules)) {
            foreach ($global_rules as $rule) {
                $rule_roles = isset($rule['roles']) ? $rule['roles'] : (isset($rule['role']) ? array($rule['role']) : array());
                foreach ($rule_roles as $r) {
                    if ($r !== 'guest' && !in_array($r, $roles)) {
                        $roles[] = $r;
                    }
                }
            }
        }

        $wp_roles = wp_roles()->get_names();
        $result = array();
        foreach ($roles as $role_key) {
            $result[] = array(
                'key'  => $role_key,
                'name' => isset($wp_roles[$role_key]) ? $wp_roles[$role_key] : $role_key,
            );
        }
        return $result;
    }

    private function get_products_with_pricing_rules() {
        $args = array(
            'post_type'      => 'product',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => array(
                array(
                    'key'     => '_role_pricing_rules',
                    'compare' => 'EXISTS',
                ),
            ),
            'fields'         => 'ids',
        );
        $product_ids = get_posts($args);

        $global_rules = get_option('whtprole_pricing_global_rules', array());
        $has_global = !empty($global_rules);

        $products = array();
        foreach ($product_ids as $pid) {
            $rules = get_post_meta($pid, '_role_pricing_rules', true);
            if (empty($rules) && !$has_global) {
                continue;
            }
            $product = wc_get_product($pid);
            if ($product) {
                $rule_count = 0;
                if (!empty($rules) && is_array($rules)) {
                    $rule_count = count($rules);
                } elseif ($has_global) {
                    $rule_count = 1;
                }
                $products[] = array(
                    'id'         => $pid,
                    'name'       => $product->get_name(),
                    'edit_link'  => get_edit_post_link($pid, 'raw'),
                    'rule_count' => $rule_count,
                );
            }
        }

        return $products;
    }

    private function get_wholesale_orders($date_from, $date_to) {
        $orders = wc_get_orders(array(
            'limit'    => -1,
            'status'   => array('wc-completed', 'wc-processing', 'wc-on-hold'),
            'date_created' => $this->get_date_range($date_from, $date_to),
            'return'   => 'objects',
        ));

        $wholesale_roles = $this->get_all_wholesale_role_keys();
        $by_role = array();
        $total_revenue = 0;
        $total_discount = 0;
        $recent_orders = array();
        $count = 0;
        $wholesale_order_count = 0;

        foreach ($orders as $order) {
            $role = $order->get_meta('_whtprole_customer_role');
            if (empty($role)) {
                $role = $this->get_user_role($order->get_customer_id());
            }

            $is_wholesale = in_array($role, $wholesale_roles) || $role === 'guest';
            if (!$is_wholesale && !empty($wholesale_roles)) {
                continue;
            }

            $wholesale_order_count++;
            $total = $order->get_total();
            $total_revenue += floatval($total);

            if (!isset($by_role[$role])) {
                $by_role[$role] = array('count' => 0, 'revenue' => 0);
            }
            $by_role[$role]['count']++;
            $by_role[$role]['revenue'] += floatval($total);

            if ($count < 20) {
                $recent_orders[] = array(
                    'id'        => $order->get_id(),
                    'date'      => $order->get_date_created() ? $order->get_date_created()->format('Y-m-d H:i') : '',
                    'total'     => $total,
                    'currency'  => $order->get_currency(),
                    'role'      => $role,
                    'edit_link' => $order->get_edit_order_url(),
                );
                $count++;
            }
        }

        return array(
            'total_orders'   => $wholesale_order_count,
            'total_revenue'  => $total_revenue,
            'total_discount' => $total_discount,
            'by_role'        => $by_role,
            'recent_orders'  => $recent_orders,
        );
    }

    private function get_top_wholesale_products($date_from, $date_to) {
        $orders = wc_get_orders(array(
            'limit'    => 500,
            'status'   => array('wc-completed', 'wc-processing'),
            'date_created' => $this->get_date_range($date_from, $date_to),
            'return'   => 'objects',
        ));

        $wholesale_roles = $this->get_all_wholesale_role_keys();
        $product_sales = array();

        foreach ($orders as $order) {
            $role = $order->get_meta('_whtprole_customer_role');
            if (empty($role)) {
                $role = $this->get_user_role($order->get_customer_id());
            }
            $is_wholesale = in_array($role, $wholesale_roles) || $role === 'guest';
            if (!$is_wholesale && !empty($wholesale_roles)) {
                continue;
            }

            foreach ($order->get_items() as $item) {
                $product_id = $item->get_product_id();
                if (!$product_id) {
                    continue;
                }
                if (!isset($product_sales[$product_id])) {
                    $product_sales[$product_id] = array('qty' => 0, 'revenue' => 0);
                }
                $product_sales[$product_id]['qty'] += $item->get_quantity();
                $product_sales[$product_id]['revenue'] += $item->get_total();
            }
        }

        uasort($product_sales, function ($a, $b) {
            return $b['revenue'] <=> $a['revenue'];
        });
        $top = array();
        $i = 0;
        foreach ($product_sales as $pid => $data) {
            if ($i++ >= 10) {
                break;
            }
            $product = wc_get_product($pid);
            $top[] = array(
                'id'      => $pid,
                'name'    => $product ? $product->get_name() : '#' . $pid,
                'qty'     => $data['qty'],
                'revenue' => $data['revenue'],
                'edit_link' => get_edit_post_link($pid, 'raw'),
            );
        }

        return $top;
    }

    private function get_all_wholesale_role_keys() {
        $roles = array();
        $global_rules = get_option('whtprole_pricing_global_rules', array());
        $global_rules = is_array($global_rules) ? $global_rules : json_decode($global_rules, true);

        if (!empty($global_rules)) {
            foreach ($global_rules as $rule) {
                $rule_roles = isset($rule['roles']) ? $rule['roles'] : (isset($rule['role']) ? array($rule['role']) : array());
                foreach ($rule_roles as $r) {
                    if (!in_array($r, $roles)) {
                        $roles[] = $r;
                    }
                }
            }
        }

        $post_ids = get_posts(array(
            'post_type'      => 'product',
            'posts_per_page' => 100,
            'meta_key'       => '_role_pricing_rules',
            'fields'         => 'ids',
        ));

        // Pre-warm the meta cache to avoid N+1 queries
        if (!empty($post_ids)) {
            update_meta_cache('post', $post_ids);
        }

        foreach ($post_ids as $pid) {
            $rules = get_post_meta($pid, '_role_pricing_rules', true);
            if (!empty($rules) && is_array($rules)) {
                foreach ($rules as $rule) {
                    $rule_roles = isset($rule['roles']) ? $rule['roles'] : (isset($rule['role']) ? array($rule['role']) : array());
                    foreach ($rule_roles as $r) {
                        if (!in_array($r, $roles)) {
                            $roles[] = $r;
                        }
                    }
                }
            }
        }

        return $roles;
    }

    private function get_date_range($from, $to) {
        $default_from = date('Y-m-d', strtotime('-30 days'));
        $default_to   = date('Y-m-d');
        if (empty($from) && empty($to)) {
            return $default_from . '...' . $default_to;
        }
        $from = !empty($from) ? sanitize_text_field($from) : $default_from;
        $to   = !empty($to) ? sanitize_text_field($to) : $default_to;
        // WooCommerce expects Y-m-d format for date range
        return $from . '...' . $to;
    }
}
