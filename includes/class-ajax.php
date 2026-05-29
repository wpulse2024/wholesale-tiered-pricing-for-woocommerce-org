<?php
/**
 * AJAX Handlers for Role-Based Pricing
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WHTPRole_Pricing_Ajax {

	public function __construct() {
		add_action( 'admin_init', array( $this, 'save_pricing_global_settings_on_activation' ) );

		// --- Frontend (public) handlers — nopriv is intentional ---
		add_action( 'wp_ajax_whtprole_get_role_based_price', array( $this, 'whtprole_get_role_based_price' ) );
		add_action( 'wp_ajax_nopriv_whtprole_get_role_based_price', array( $this, 'whtprole_get_role_based_price' ) );

		add_action( 'wp_ajax_whtprole_get_variation_pricing_rules', array( $this, 'whtprole_get_variation_pricing_rules' ) );
		add_action( 'wp_ajax_nopriv_whtprole_get_variation_pricing_rules', array( $this, 'whtprole_get_variation_pricing_rules' ) );

		add_action( 'wp_ajax_whtprole_validate_quantity_rules', array( $this, 'whtprole_validate_quantity_rules' ) );
		add_action( 'wp_ajax_nopriv_whtprole_validate_quantity_rules', array( $this, 'whtprole_validate_quantity_rules' ) );

		add_action( 'wp_ajax_whtprole_calculate_savings', array( $this, 'calculate_savings' ) );
		add_action( 'wp_ajax_nopriv_whtprole_calculate_savings', array( $this, 'calculate_savings' ) );

		add_action( 'wp_ajax_whtprole_get_variation_price', array( $this, 'whtprole_get_variation_price' ) );
		add_action( 'wp_ajax_nopriv_whtprole_get_variation_price', array( $this, 'whtprole_get_variation_price' ) );

		// --- Admin-only handlers — FIX [CRIT-03]: nopriv registrations removed ---
		add_action( 'wp_ajax_whtprole_pricing_get_pricing_rules', array( $this, 'get_pricing_rules' ) );
		add_action( 'wp_ajax_whtprole_pricing_save_pricing_rules', array( $this, 'save_pricing_rules' ) );
		add_action( 'wp_ajax_whtprole_pricing_get_product_settings', array( $this, 'get_product_settings' ) );
		add_action( 'wp_ajax_whtprole_pricing_save_product_settings', array( $this, 'save_product_settings' ) );
		add_action( 'wp_ajax_whtprole_pricing_save_general_settings', array( $this, 'save_general_settings' ) );
		add_action( 'wp_ajax_whtprole_pricing_get_general_settings', array( $this, 'get_general_settings' ) );
	}

	// -------------------------------------------------------------------------
	// Capability guard — shared by all admin handlers  FIX [CRIT-03]
	// -------------------------------------------------------------------------
	private function require_admin_capability(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
			exit;
		}
	}

	// -------------------------------------------------------------------------
	// General settings
	// -------------------------------------------------------------------------

	public function get_general_settings() {
		$this->require_admin_capability();

		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'whtprole_pricing_get_pricing_rules' ) ) {
			wp_send_json_error( array( 'message' => 'Invalid nonce' ) );
			return;
		}

		$settings = get_option( 'whtprole_pricing_save_general_settings', array() );
		if ( ! is_array( $settings ) ) {
			$settings = json_decode( $settings, true ) ?? array();
		}
		wp_send_json_success( $settings );
	}

	public function save_general_settings() {
		$this->require_admin_capability();

		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'whtprole_pricing_get_pricing_rules' ) ) {
			wp_send_json_error( array( 'message' => 'Invalid nonce' ) );
			return;
		}

		$raw      = isset( $_POST['settings'] ) ? $_POST['settings'] : array();
		$settings = is_array( $raw ) ? $raw : json_decode( stripslashes( $raw ), true );
		if ( ! is_array( $settings ) ) {
			wp_send_json_error( array( 'message' => 'Invalid settings data' ) );
			return;
		}

		$clean = array(
			'showTieredPricing'  => ! empty( $settings['showTieredPricing'] ),
			'defaultTemplate'    => sanitize_text_field( $settings['defaultTemplate'] ?? 'table' ),
			'pricingTitle'       => sanitize_text_field( $settings['pricingTitle'] ?? '' ),
			'position'           => sanitize_text_field( $settings['position'] ?? 'above_add_to_cart' ),
			'showQuantityColumn' => ! empty( $settings['showQuantityColumn'] ),
			'showDiscountColumn' => ! empty( $settings['showDiscountColumn'] ),
			'responsiveTable'    => ! empty( $settings['responsiveTable'] ),
			'activePricingColor' => sanitize_text_field( $settings['activePricingColor'] ?? '#ff9a00' ),
			'quantityLabel'      => sanitize_text_field( $settings['quantityLabel'] ?? '' ),
			'discountLabel'      => sanitize_text_field( $settings['discountLabel'] ?? '' ),
			'priceLabel'         => sanitize_text_field( $settings['priceLabel'] ?? '' ),
		);

		// FIX [CRIT-01]: wp_json_encode replaces sanitize_text_field(json_encode()) which
		// was stripping JSON structural characters and corrupting stored data.
		update_option( 'whtprole_pricing_save_general_settings', wp_json_encode( $clean ) );
		wp_send_json_success( array( 'message' => 'General settings saved successfully' ) );
	}

	public function save_pricing_global_settings_on_activation() {
		$existing = get_option( 'whtprole_pricing_save_general_settings', array() );
		if ( ! empty( $existing ) ) {
			return;
		}
		$default = array(
			'showTieredPricing'  => true,
			'defaultTemplate'    => 'table',
			'pricingTitle'       => __( 'Buy more save more!', 'wholesale-tiered-pricing-for-woocommerce' ),
			'position'           => 'above_add_to_cart',
			'showQuantityColumn' => true,
			'showDiscountColumn' => true,
			'responsiveTable'    => true,
			'activePricingColor' => '#ff9a00',
			'quantityLabel'      => __( 'Quantity', 'wholesale-tiered-pricing-for-woocommerce' ),
			'discountLabel'      => __( 'Discount', 'wholesale-tiered-pricing-for-woocommerce' ),
			'priceLabel'         => __( 'Price', 'wholesale-tiered-pricing-for-woocommerce' ),
		);
		update_option( 'whtprole_pricing_save_general_settings', wp_json_encode( $default ) ); // FIX [CRIT-01]
	}

	// -------------------------------------------------------------------------
	// Product settings
	// -------------------------------------------------------------------------

	public function get_product_settings() {
		$this->require_admin_capability();

		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'whtprole_pricing_get_pricing_rules' ) ) {
			wp_send_json_error( array( 'message' => 'Invalid nonce' ) );
			return;
		}

		$settings = get_option( 'whtprole_global_product_settings', array() );
		if ( ! is_array( $settings ) ) {
			$settings = json_decode( $settings, true ) ?? array();
		}
		wp_send_json_success( $settings );
	}

	public function save_product_settings() {
		$this->require_admin_capability();

		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'whtprole_pricing_get_pricing_rules' ) ) {
			wp_send_json_error( array( 'message' => 'Invalid nonce' ) );
			return;
		}

		$raw      = isset( $_POST['settings'] ) ? $_POST['settings'] : array();
		$settings = is_array( $raw ) ? $raw : json_decode( stripslashes( $raw ), true );
		if ( ! is_array( $settings ) ) {
			wp_send_json_error( array( 'message' => 'Invalid settings data' ) );
			return;
		}

		$clean = array(
			'include_products'   => ! empty( $settings['include_products'] ) ? array_map( 'intval', $settings['include_products'] ) : array(),
			'include_categories' => ! empty( $settings['include_categories'] ) ? array_map( 'intval', $settings['include_categories'] ) : array(),
			'exclude_products'   => ! empty( $settings['exclude_products'] ) ? array_map( 'intval', $settings['exclude_products'] ) : array(),
			'exclude_categories' => ! empty( $settings['exclude_categories'] ) ? array_map( 'intval', $settings['exclude_categories'] ) : array(),
			'apply_type'         => sanitize_text_field( $settings['apply_type'] ?? 'include' ),
		);

		update_option( 'whtprole_global_product_settings', wp_json_encode( $clean ) ); // FIX [CRIT-01]
		wp_send_json_success( array( 'message' => 'Product settings saved successfully' ) );
	}

	// -------------------------------------------------------------------------
	// Global pricing rules
	// -------------------------------------------------------------------------

	public function get_pricing_rules() {
		$this->require_admin_capability();

		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'whtprole_pricing_get_pricing_rules' ) ) {
			wp_send_json_error( array( 'message' => 'Invalid nonce' ) );
			return;
		}

		$rules = get_option( 'whtprole_pricing_global_rules', array() );
		if ( ! is_array( $rules ) ) {
			$rules = json_decode( $rules, true ) ?? array();
		}
		wp_send_json_success( $rules );
	}

	public function save_pricing_rules() {
		$this->require_admin_capability();

		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'whtprole_pricing_get_pricing_rules' ) ) {
			wp_send_json_error( array( 'message' => 'Invalid nonce' ) );
			return;
		}

		$raw   = isset( $_POST['rules'] ) ? $_POST['rules'] : array();
		$input = is_array( $raw ) ? $raw : json_decode( stripslashes( $raw ), true );
		if ( ! is_array( $input ) ) {
			wp_send_json_error( array( 'message' => 'Invalid rules data' ) );
			return;
		}

		// FIX [DUP-07]: sanitize_rules_data result was previously discarded and the
		// identical loop was repeated inline. Now we call it once and use the result.
		$sanitized_rules = $this->sanitize_rules_data( $input );

		update_option( 'whtprole_pricing_global_rules', wp_json_encode( $sanitized_rules ) ); // FIX [CRIT-01]
		wp_send_json_success( array( 'message' => 'Pricing rules saved successfully' ) );
	}

	private function sanitize_rules_data( array $rules ): array {
		$sanitized = array();

		foreach ( $rules as $rule ) {
			$roles = array();
			if ( isset( $rule['roles'] ) && is_array( $rule['roles'] ) ) {
				$roles = array_map( 'sanitize_text_field', array_filter( $rule['roles'] ) );
			} elseif ( ! empty( $rule['role'] ) ) {
				$roles = array( sanitize_text_field( $rule['role'] ) );
			}

			if ( empty( $roles ) ) {
				continue;
			}

			if ( in_array( 'guest', $roles, true ) ) {
				$roles = array( 'guest' );
			}

			$also_for_guest = false;
			if ( in_array( 'guest', $roles, true ) && isset( $rule['also_for_guest'] ) ) {
				$also_for_guest = in_array( $rule['also_for_guest'], array( true, 'true', 1, '1' ), true );
			}

			// Sanitize schedule dates (YYYY-MM-DD or empty string)
			$date_from = '';
			$date_to   = '';
			if ( ! empty( $rule['date_from'] ) ) {
				$candidate = sanitize_text_field( $rule['date_from'] );
				if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $candidate ) ) {
					$date_from = $candidate;
				}
			}
			if ( ! empty( $rule['date_to'] ) ) {
				$candidate = sanitize_text_field( $rule['date_to'] );
				if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $candidate ) ) {
					$date_to = $candidate;
				}
			}

			$sanitized[] = array(
				'id'              => sanitize_text_field( $rule['id'] ?? '' ),
				'roles'           => $roles,
				'role'            => $roles[0],
				'min_qty'         => intval( $rule['min_qty'] ?? 0 ),
				'max_qty'         => ! empty( $rule['max_qty'] ) ? intval( $rule['max_qty'] ) : 0,
				'step_qty'        => intval( $rule['step_qty'] ?? 1 ),
				'min_order_value' => ! empty( $rule['min_order_value'] ) ? floatval( $rule['min_order_value'] ) : 0,
				'tiered_pricing'  => $this->sanitize_tiered_pricing_data( $rule['tiered_pricing'] ?? array() ),
				'also_for_guest'  => $also_for_guest,
				'date_from'       => $date_from,
				'date_to'         => $date_to,
			);
		}

		return $sanitized;
	}

	private function sanitize_tiered_pricing_data( array $tiers ): array {
		// FIX [HIGH-10]: whitelist discount_type.
		$valid_types = array( 'fixed', 'percentage' );
		$sanitized   = array();

		foreach ( $tiers as $tier ) {
			$discount_type = sanitize_text_field( $tier['discount_type'] ?? 'fixed' );
			if ( ! in_array( $discount_type, $valid_types, true ) ) {
				$discount_type = 'fixed';
			}

			$sanitized[] = array(
				'id'            => sanitize_text_field( $tier['id'] ?? '' ),
				'min_qty'       => intval( $tier['min_qty'] ?? 0 ),
				'discount_type' => $discount_type,
				'price'         => floatval( $tier['price'] ?? 0 ),
			);
		}

		return $sanitized;
	}

	// -------------------------------------------------------------------------
	// Frontend: role-based price lookup
	// -------------------------------------------------------------------------

	public function whtprole_get_role_based_price() {
		// FIX [HIGH-01]: nonce is now always required — removed the isset() bypass that
		// allowed callers to skip nonce verification by simply omitting the field.
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'wholesale-tiered-pricing-for-woocommerce-ajax' ) &&
			! wp_verify_nonce( $nonce, 'wc_add_to_cart_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Invalid nonce' ) );
			return;
		}

		$product_id   = intval( $_POST['product_id'] ?? 0 );
		$variation_id = isset( $_POST['variation_id'] ) ? intval( $_POST['variation_id'] ) : null;
		$quantity     = intval( $_POST['quantity'] ?? 0 );

		if ( ! $product_id || ! $quantity ) {
			wp_send_json_error( array( 'message' => 'Invalid parameters' ) );
			return;
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			wp_send_json_error( array( 'message' => 'Product not found' ) );
			return;
		}

		$variation_product = null;
		if ( $variation_id ) {
			$variation_product = wc_get_product( $variation_id );
			if ( $variation_product && $variation_product->is_type( 'variation' ) ) {
				$product = $variation_product;
			}
		}

		$parent_id = WHTPRole_Pricing_Helper::get_parent_product_id( $product );

		if ( $product->is_type( 'variation' ) && $variation_id === null ) {
			$variation_id = $product->get_id();
		} elseif ( $variation_id && $variation_product ) {
			$parent_id = $variation_product->get_parent_id();
		}

		$rules = WHTPRole_Pricing_Helper::get_rules_for_product( $parent_id );
		if ( empty( $rules ) ) {
			wp_send_json_success( array( 'price_html' => $product->get_price_html() ) );
			return;
		}

		$current_user_role = WHTPRole_Pricing_Helper::get_current_user_role();
		$is_guest          = ( $current_user_role === 'guest' );
		$base_price        = $variation_product ? floatval( $variation_product->get_price() ) : floatval( $product->get_price() );
		$new_price         = $base_price;

		foreach ( $rules as $rule ) {
			if ( $variation_id ) {
				$rule_variations = isset( $rule['variations'] ) && is_array( $rule['variations'] ) ? $rule['variations'] : array();
				if ( ! empty( $rule_variations ) && ! in_array( $variation_id, $rule_variations, true ) ) {
					continue;
				}
			}

			$rule_roles     = isset( $rule['roles'] ) ? $rule['roles'] : ( $rule['role'] ?? array() );
			$also_for_guest = $rule['also_for_guest'] ?? false;

			if ( WHTPRole_Pricing_Helper::rule_applies_to_user( $rule_roles, $current_user_role, $is_guest, $also_for_guest ) ) {
				$new_price = WHTPRole_Pricing_Helper::calculate_price( $base_price, $rule, $quantity, $variation_id );
				break;
			}
		}

		$temp_product = ( $variation_product && $variation_product->is_type( 'variation' ) ) ? clone $variation_product : clone $product;
		$temp_product->set_price( $new_price );

		wp_send_json_success(
			array(
				'price_html' => $temp_product->get_price_html(),
				'price'      => $new_price,
			)
		);
	}

	// -------------------------------------------------------------------------
	// Frontend: variation pricing rules table
	// -------------------------------------------------------------------------

	public function whtprole_get_variation_pricing_rules() {
		check_ajax_referer( 'wc_add_to_cart_nonce', 'nonce' );

		$variation_id = intval( $_POST['variation_id'] ?? 0 );
		if ( ! $variation_id ) {
			wp_send_json_error( array( 'message' => 'Invalid variation ID' ) );
			return;
		}

		$variation = wc_get_product( $variation_id );
		if ( ! $variation ) {
			wp_send_json_error( array( 'message' => 'Variation not found' ) );
			return;
		}

		$parent_id = $variation->get_parent_id();
		$rules     = WHTPRole_Pricing_Helper::get_rules_for_product( $parent_id );

		if ( empty( $rules ) ) {
			wp_send_json_success( array( 'pricing_table' => '' ) );
			return;
		}

		$current_user_role = WHTPRole_Pricing_Helper::get_current_user_role();
		$is_guest          = ( $current_user_role === 'guest' );
		$applicable_rules  = array();

		foreach ( $rules as $rule ) {
			// FIX [CRIT-04]: was `$rule['role'] === $current_user_role` — old single-role
			// format only. Now uses helper that handles both formats.
			$rule_roles     = isset( $rule['roles'] ) ? $rule['roles'] : ( $rule['role'] ?? array() );
			$also_for_guest = $rule['also_for_guest'] ?? false;

			if ( WHTPRole_Pricing_Helper::rule_applies_to_user( $rule_roles, $current_user_role, $is_guest, $also_for_guest ) ) {
				$applicable_rules[] = $rule;
			}
		}

		if ( empty( $applicable_rules ) ) {
			wp_send_json_success( array( 'pricing_table' => '' ) );
			return;
		}

		ob_start();
		$this->render_pricing_table( $applicable_rules );
		$pricing_table = ob_get_clean();

		wp_send_json_success( array( 'pricing_table' => $pricing_table ) );
	}

	// -------------------------------------------------------------------------
	// Frontend: quantity validation
	// -------------------------------------------------------------------------

	public function whtprole_validate_quantity_rules() {
		check_ajax_referer( 'wc_add_to_cart_nonce', 'nonce' );

		$product_id = intval( $_POST['product_id'] ?? 0 );
		$quantity   = intval( $_POST['quantity'] ?? 0 );

		if ( ! $product_id || ! $quantity ) {
			wp_send_json_error( array( 'message' => 'Invalid parameters' ) );
			return;
		}

		// FIX [HIGH-06]: now uses get_rules_for_product which includes global-rule fallback.
		$product   = wc_get_product( $product_id );
		$parent_id = $product ? WHTPRole_Pricing_Helper::get_parent_product_id( $product ) : $product_id;
		$rules     = WHTPRole_Pricing_Helper::get_rules_for_product( $parent_id );

		if ( empty( $rules ) ) {
			wp_send_json_success( array( 'valid' => true ) );
			return;
		}

		$current_user_role = WHTPRole_Pricing_Helper::get_current_user_role();
		$is_guest          = ( $current_user_role === 'guest' );

		foreach ( $rules as $rule ) {
			// FIX [CRIT-04]: same old-format bug as whtprole_get_variation_pricing_rules.
			$rule_roles     = isset( $rule['roles'] ) ? $rule['roles'] : ( $rule['role'] ?? array() );
			$also_for_guest = $rule['also_for_guest'] ?? false;

			if ( WHTPRole_Pricing_Helper::rule_applies_to_user( $rule_roles, $current_user_role, $is_guest, $also_for_guest ) ) {
				$validation = $this->validate_quantity( $quantity, $rule );
				if ( ! $validation['valid'] ) {
					wp_send_json_error( $validation );
					return;
				}
				break;
			}
		}

		wp_send_json_success( array( 'valid' => true ) );
	}

	private function validate_quantity( $quantity, $rule ) {
		if ( $rule['min_qty'] > 0 && $quantity < $rule['min_qty'] ) {
			return array(
				'valid'   => false,
				/* translators: %d = minimum quantity */
				'message' => sprintf( __( 'Minimum quantity required: %d', 'wholesale-tiered-pricing-for-woocommerce' ), $rule['min_qty'] ),
			);
		}

		if ( $rule['max_qty'] > 0 && $quantity > $rule['max_qty'] ) {
			return array(
				'valid'   => false,
				/* translators: %d = maximum quantity */
				'message' => sprintf( __( 'Maximum quantity allowed: %d', 'wholesale-tiered-pricing-for-woocommerce' ), $rule['max_qty'] ),
			);
		}

		if ( $rule['step_qty'] > 1 && ( $quantity - $rule['min_qty'] ) % $rule['step_qty'] !== 0 ) {
			return array(
				'valid'   => false,
				/* translators: %d = step quantity */
				'message' => sprintf( __( 'Quantity must be in multiples of %d', 'wholesale-tiered-pricing-for-woocommerce' ), $rule['step_qty'] ),
			);
		}

		return array( 'valid' => true );
	}

	private function render_pricing_table( $rules ) {
		echo '<h3>' . esc_html__( 'Pricing Information', 'wholesale-tiered-pricing-for-woocommerce' ) . '</h3>';

		foreach ( $rules as $rule ) {
			if ( empty( $rule['tiered_pricing'] ) ) {
				continue;
			}

			usort(
				$rule['tiered_pricing'],
				function ( $a, $b ) {
					return intval( $a['min_qty'] ?? 0 ) - intval( $b['min_qty'] ?? 0 );
				}
			);

			echo '<table class="pricing-table">';
			echo '<thead><tr><th>' . esc_html__( 'Quantity', 'wholesale-tiered-pricing-for-woocommerce' ) . '</th><th>' . esc_html__( 'Price', 'wholesale-tiered-pricing-for-woocommerce' ) . '</th></tr></thead>';
			echo '<tbody>';

			foreach ( $rule['tiered_pricing'] as $tier ) {
				if ( empty( $tier['min_qty'] ) || empty( $tier['price'] ) ) {
					continue;
				}
				echo '<tr>';
				echo '<td>' . esc_html( $tier['min_qty'] ) . '+</td>';
				echo '<td>' . wp_kses_post( wc_price( $tier['price'] ) ) . '</td>';
				echo '</tr>';
			}

			echo '</tbody></table>';
		}
	}

	// -------------------------------------------------------------------------
	// Frontend: savings calculator
	// -------------------------------------------------------------------------

	public function calculate_savings() {
		// FIX [HIGH-01 + CRIT-05]: nonce always required; return after every error.
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'wholesale-tiered-pricing-for-woocommerce-ajax' ) &&
			! wp_verify_nonce( $nonce, 'wc_add_to_cart_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed', 'wholesale-tiered-pricing-for-woocommerce' ) ) );
			return; // FIX [CRIT-05]: was missing — execution continued after security failure
		}

		$product_id   = intval( $_POST['product_id'] ?? 0 );
		$variation_id = isset( $_POST['variation_id'] ) ? intval( $_POST['variation_id'] ) : null;
		$quantity     = intval( $_POST['quantity'] ?? 0 );

		if ( ! $product_id || ! $quantity ) {
			wp_send_json_error( array( 'message' => 'Invalid parameters' ) );
			return;
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			wp_send_json_error( array( 'message' => 'Product not found' ) );
			return;
		}

		$variation_product = null;
		if ( $variation_id ) {
			$variation_product = wc_get_product( $variation_id );
			if ( $variation_product && $variation_product->is_type( 'variation' ) ) {
				$product = $variation_product;
			}
		}

		$parent_id = WHTPRole_Pricing_Helper::get_parent_product_id( $product );

		if ( $product->is_type( 'variation' ) && $variation_id === null ) {
			$variation_id = $product->get_id();
		} elseif ( $variation_id && $variation_product ) {
			$parent_id = $variation_product->get_parent_id();
		}

		$rules      = WHTPRole_Pricing_Helper::get_rules_for_product( $parent_id );
		$base_price = $variation_product ? floatval( $variation_product->get_price() ) : floatval( $product->get_price() );

		if ( empty( $rules ) ) {
			wp_send_json_success(
				array(
					'has_discount'               => false,
					'regular_price'              => $base_price,
					'discounted_price'           => $base_price,
					'savings'                    => 0,
					'savings_percent'            => 0,
					'total_regular'              => $base_price * $quantity,
					'total_discounted'           => $base_price * $quantity,
					'total_savings'              => 0,
					'formatted_regular_total'    => wc_price( $base_price * $quantity ),
					'formatted_discounted_total' => wc_price( $base_price * $quantity ),
					'formatted_total_savings'    => wc_price( 0 ),
				)
			);
			return;
		}

		$current_user_role = WHTPRole_Pricing_Helper::get_current_user_role();
		$is_guest          = ( $current_user_role === 'guest' );
		$regular_price     = $base_price;
		$discounted_price  = $regular_price;

		foreach ( $rules as $rule ) {
			if ( $variation_id ) {
				$rule_variations = isset( $rule['variations'] ) && is_array( $rule['variations'] ) ? $rule['variations'] : array();
				if ( ! empty( $rule_variations ) && ! in_array( $variation_id, $rule_variations, true ) ) {
					continue;
				}
			}

			$rule_roles     = isset( $rule['roles'] ) ? $rule['roles'] : ( $rule['role'] ?? array() );
			$also_for_guest = $rule['also_for_guest'] ?? false;

			if ( WHTPRole_Pricing_Helper::rule_applies_to_user( $rule_roles, $current_user_role, $is_guest, $also_for_guest ) ) {
				$discounted_price = WHTPRole_Pricing_Helper::calculate_price( $regular_price, $rule, $quantity, $variation_id );
				break;
			}
		}

		$savings          = $regular_price - $discounted_price;
		$savings_percent  = $regular_price > 0 ? ( $savings / $regular_price ) * 100 : 0;
		$total_regular    = $regular_price * $quantity;
		$total_discounted = $discounted_price * $quantity;
		$total_savings    = $total_regular - $total_discounted;

		wp_send_json_success(
			array(
				'has_discount'               => $savings > 0,
				'regular_price'              => $regular_price,
				'discounted_price'           => $discounted_price,
				'savings'                    => $savings,
				'savings_percent'            => round( $savings_percent, 2 ),
				'total_regular'              => $total_regular,
				'total_discounted'           => $total_discounted,
				'total_savings'              => $total_savings,
				'quantity'                   => $quantity,
				'formatted_regular_total'    => wc_price( $total_regular ),
				'formatted_discounted_total' => wc_price( $total_discounted ),
				'formatted_total_savings'    => wc_price( $total_savings ),
			)
		);
	}

	// -------------------------------------------------------------------------
	// Frontend: variation base price lookup
	// -------------------------------------------------------------------------

	public function whtprole_get_variation_price() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'wholesale-tiered-pricing-for-woocommerce-ajax' ) &&
			! wp_verify_nonce( $nonce, 'wc_add_to_cart_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed', 'wholesale-tiered-pricing-for-woocommerce' ) ) );
			return;
		}

		$variation_id = isset( $_POST['variation_id'] ) ? intval( $_POST['variation_id'] ) : 0;
		if ( ! $variation_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid variation ID', 'wholesale-tiered-pricing-for-woocommerce' ) ) );
			return;
		}

		$variation = wc_get_product( $variation_id );
		if ( ! $variation || ! $variation->is_type( 'variation' ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid variation', 'wholesale-tiered-pricing-for-woocommerce' ) ) );
			return;
		}

		$sale_price    = $variation->get_sale_price();
		$regular_price = $variation->get_regular_price();
		$base_price    = ( $sale_price && floatval( $sale_price ) > 0 ) ? $sale_price : $regular_price;

		if ( ! $base_price || floatval( $base_price ) <= 0 ) {
			$base_price = $variation->get_price();
		}

		wp_send_json_success(
			array(
				'base_price'      => floatval( $base_price ),
				'sale_price'      => $sale_price ? floatval( $sale_price ) : null,
				'regular_price'   => $regular_price ? floatval( $regular_price ) : null,
				'formatted_price' => wc_price( $base_price ),
			)
		);
	}
}

// FIX [MED-01]: removed `new WHTPRole_Pricing_Ajax()` that was here — it caused every
// AJAX hook to be registered twice. Single instantiation is in WHTPRole_Based_Pricing::hooks().
