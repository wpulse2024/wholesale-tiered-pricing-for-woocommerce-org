<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// -----------------------------
// 1. Add Tiered Pricing Tab
// -----------------------------
add_filter(
	'woocommerce_settings_tabs_array',
	function ( $tabs ) {
		$tabs['tiered_pricing'] = __( 'Tiered Pricing', 'wholesale-tiered-pricing-for-woocommerce' );
		return $tabs;
	},
	50
);

// -----------------------------
// 2. Show Tiered Pricing Settings
// -----------------------------
add_action(
	'woocommerce_settings_tabs_tiered_pricing',
	function () {
		$rules          = get_option( 'whtprole_pricing_global_rules', array() );
		$products       = wc_get_products(
			array(
				'limit'  => 200,
				'status' => 'publish',
			)
		);
		$products_array = array();
		foreach ( $products as $product ) {
			$products_array[] = array(
				'id'   => $product->get_id(),
				'name' => $product->get_name(),
			);
		}

		$categories = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'number'     => 200,
			)
		);
		$user_roles = wp_roles()->roles;

		include_once WHTPROLE_PRICING_PLUGIN_PATH . 'templates/admin/global-settings.php';

		wp_enqueue_script( 'wholesale-tiered-pricing-for-woocommerce-admin', WHTPROLE_PRICING_PLUGIN_URL . 'plugin-assets/global-settings.js', array( 'jquery' ), WHTPROLE_PRICING_VERSION, true );
		wp_enqueue_script( 'wholesale-tiered-pricing-for-woocommerce-admin-vua-app', WHTPROLE_PRICING_PLUGIN_URL . 'plugin-assets/admin/app.js', array( 'jquery' ), WHTPROLE_PRICING_VERSION, true );
		wp_enqueue_style( 'wholesale-tiered-pricing-for-woocommerce-admin', WHTPROLE_PRICING_PLUGIN_URL . 'plugin-assets/global-setting.css', array(), WHTPROLE_PRICING_VERSION );
		wp_localize_script(
			'wholesale-tiered-pricing-for-woocommerce-admin',
			'whtproleTieredPricingVar',
			array(
				'userRoles'  => $user_roles,
				'nonce'      => wp_create_nonce( 'whtprole_pricing_get_pricing_rules' ),
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'products'   => $products_array,
				'categories' => $categories,
			)
		);
	}
);

// -----------------------------
// 3. Save Tiered Pricing Settings
// -----------------------------
add_action(
	'woocommerce_update_options_tiered_pricing',
	function () {
		woocommerce_update_options( whtprole_nudge_settings_fields() );
	}
);

// -----------------------------
// 4. Show nudge toggle below Vue app
// -----------------------------
add_action(
	'woocommerce_settings_tabs_tiered_pricing',
	function () {
		woocommerce_admin_fields( whtprole_nudge_settings_fields() );
	},
	20
);

function whtprole_nudge_settings_fields(): array {
	return array(
		array(
			'title' => __( 'Tier Progress Nudge', 'wholesale-tiered-pricing-for-woocommerce' ),
			'type'  => 'title',
			'id'    => 'whtprole_nudge_section',
		),
		array(
			'title'   => __( 'Enable tier progress nudge', 'wholesale-tiered-pricing-for-woocommerce' ),
			'desc'    => __( 'Show "Add N more for X/unit" messages on product and cart pages.', 'wholesale-tiered-pricing-for-woocommerce' ),
			'id'      => 'whtprole_nudge_enabled',
			'type'    => 'checkbox',
			'default' => 'yes',
		),
		array(
			'type' => 'sectionend',
			'id'   => 'whtprole_nudge_section',
		),
	);
}
