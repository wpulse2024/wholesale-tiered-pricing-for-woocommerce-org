<?php

/**
 * Plugin Name: Wholesale & Tiered Pricing for WooCommerce
 * Description: Set role-based prices and quantity rules in WooCommerce. Show tiered pricing tables for wholesale, B2B, and bulk discounts.
 * Version: 1.0.0
 * Author: WPulse
 * Author URI: https://profiles.wordpress.org/wpulse/
 * Text Domain: wholesale-tiered-pricing-for-woocommerce
 * Domain Path: /languages
 * License: GPLv2 or later
 * Requires at least: 5.0
 * Tested up to: 6.8
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WHTPROLE_PRICING_VERSION', '1.0.0');
define('WHTPROLE_PRICING_PLUGIN_FILE', __FILE__);
define('WHTPROLE_PRICING_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('WHTPROLE_PRICING_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('WHTPROLE_PRICING_PLUGIN_URL', plugin_dir_url(__FILE__));

add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
});

// add_action('admin_head', function () {
//     $screen = get_current_screen();
//     if ($screen && $screen->id === 'woocommerce_page_wc-settings') {
//         echo '<style>.woocommerce-save-button {display:none !important;}</style>';
//     }
// });

class WHTPRole_Based_Pricing
{

    private static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        add_action('plugins_loaded', array($this, 'init'));
    }

    public function init()
    {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }

        $this->includes();
        $this->hooks();
    }

    private function includes()
    {
        require_once WHTPROLE_PRICING_PLUGIN_PATH . 'includes/class-admin.php';
        require_once WHTPROLE_PRICING_PLUGIN_PATH . 'includes/class-frontend.php';
        require_once WHTPROLE_PRICING_PLUGIN_PATH . 'includes/class-pricing.php';
        require_once WHTPROLE_PRICING_PLUGIN_PATH . 'includes/class-ajax.php';
        require_once WHTPROLE_PRICING_PLUGIN_PATH . 'includes/class-global-settings.php';
        require_once WHTPROLE_PRICING_PLUGIN_PATH . 'includes/helper/class-helper.php';
    }

    private function hooks()
    {
        new WHTPRole_Pricing_Admin();
        new WHTPRole_Pricing_Frontend();
        new WHTPRole_Pricing_Engine();
        new WHTPRole_Pricing_Ajax();

        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function activate()
    {
        $this->create_tables();
        flush_rewrite_rules();
    }

    public function deactivate()
    {
        flush_rewrite_rules();
    }

    private function create_tables()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wc_role_pricing';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            product_id bigint(20) NOT NULL,
            role_name varchar(100) NOT NULL,
            min_qty int(11) DEFAULT 0,
            max_qty int(11) DEFAULT 0,
            step_qty int(11) DEFAULT 1,
            price_type varchar(20) DEFAULT 'fixed',
            price_value decimal(10,2) DEFAULT 0.00,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY product_id (product_id),
            KEY role_name (role_name)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        update_option('whtprole_role_pricing_db_version', WHTPROLE_PRICING_VERSION);
    }

    public function woocommerce_missing_notice()
    {
        echo '<div class="error"><p><strong>' . esc_html__('WooCommerce Role-Based Pricing', 'wholesale-tiered-pricing-for-woocommerce') . '</strong> '
            . esc_html__('requires WooCommerce to be installed and active.', 'wholesale-tiered-pricing-for-woocommerce') . '</p></div>';
    }
}

function wc_role_pricing_init()
{
    return WHTPRole_Based_Pricing::get_instance();
}

//get role=================================
add_action('wp_ajax_get_user_roles', 'get_wp_user_roles');
function get_wp_user_roles()
{
    global $wp_roles;

    if (!isset($wp_roles)) {
        $wp_roles = new WP_Roles();
    }

    $roles = $wp_roles->get_names();

    wp_send_json_success($roles);
}

wc_role_pricing_init();
