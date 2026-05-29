<?php
class WHTPRole_Pricing_Show_Message {
	public function __construct() {
		// Cart items
		add_filter( 'woocommerce_cart_item_name', array( $this, 'show_discount_in_cart' ), 20, 3 );

		// Customer Order details (frontend)
		add_action( 'woocommerce_order_item_meta_end', array( $this, 'show_discount_in_order_details' ), 10, 3 );

		// Admin Order details (backend)
		add_action( 'woocommerce_before_order_itemmeta', array( $this, 'show_discount_in_admin_order_details' ), 10, 3 );
	}

	/* ----------------- CART ------------------ */
	public function show_discount_in_cart( $product_name, $cart_item, $cart_item_key ) {
		if ( isset( $cart_item['data'] ) && is_object( $cart_item['data'] ) ) {
			$product  = $cart_item['data'];
			$quantity = $cart_item['quantity'];
			ob_start();
			$this->show_discount_message( $product, true, false, $quantity );
			$product_name .= ob_get_clean();
		}
		return $product_name;
	}

	/* ----------------- CUSTOMER ORDER DETAILS ------------------ */
	public function show_discount_in_order_details( $item_id, $item, $order ) {
		$product = $item->get_product();
		if ( $product ) {
			$this->show_discount_message( $product );
		}
	}

	/* ----------------- ADMIN ORDER DETAILS ------------------ */
	public function show_discount_in_admin_order_details( $item_id, $item, $product ) {
		if ( ! is_admin() || ! $product instanceof WC_Product ) {
			return;
		}
		$quantity = $item->get_quantity();
		echo '<div style="margin-top:4px;font-size:12px;color:#555;">';
		$this->show_discount_message( $product, true, true, $quantity );
		echo '</div>';
	}

	/* ----------------- CORE MESSAGE ------------------ */
	private function show_discount_message( $product, $do_echo = true, $for_admin = false, $quantity = 1 ) {
		if ( ! $product ) {
			return;
		}

		$parent_id = WHTPRole_Pricing_Helper::get_parent_product_id( $product );
		$rules     = WHTPRole_Pricing_Helper::get_rules_for_product( $parent_id );
		if ( empty( $rules ) ) {
			return;
		}

		$current_user_role = WHTPRole_Pricing_Helper::get_current_user_role();
		$is_guest          = ( $current_user_role === 'guest' );
		$original_price    = (float) $product->get_regular_price();

		foreach ( $rules as $rule ) {
			$rule_roles     = isset( $rule['roles'] ) ? $rule['roles'] : ( isset( $rule['role'] ) ? $rule['role'] : array() );
			$also_for_guest = isset( $rule['also_for_guest'] ) ? $rule['also_for_guest'] : false;

			if ( WHTPRole_Pricing_Helper::rule_applies_to_user( $rule_roles, $current_user_role, $is_guest, $also_for_guest ) ) {
				$new_price = $this->calculate_price( $original_price, $rule, $quantity );
				if ( $new_price < $original_price ) {
					$savings         = ( $original_price - $new_price );
					$savings_percent = ( $savings / $original_price ) * 100;

					$role_label    = ucfirst( is_array( $rule_roles ) ? implode( ', ', $rule_roles ) : $rule_roles );
					$discount_text = sprintf(
						/* translators: 1: role label, 2: savings amount, 3: savings percentage */
						esc_html__( 'Applied Tier: %1$s – Save %2$s (%3$.1f%%)', 'wholesale-tiered-pricing-for-woocommerce' ),
						esc_html( $role_label ),
						wc_price( $savings ),
						$savings_percent
					);

					$html = '<div class="role-pricing-savings" style="font-size:12px;color:#128219;margin-top:3px;">' . $discount_text . '</div>';

					if ( $do_echo ) {
						echo wp_kses_post( $html );
					} else {
						return $html;
					}
				}
				break;
			}
		}
	}

	/* ----------------- PRICE CALCULATION ------------------ */
	private function calculate_price( $base_price, $rule, $quantity ) {
		// Check tiered pricing first
		if ( ! empty( $rule['tiered_pricing'] ) ) {
			$applicable_tier = null;
			// Sort tiers by quantity descending to find the highest applicable tier
			usort(
				$rule['tiered_pricing'],
				function ( $a, $b ) {
					return $b['min_qty'] - $a['min_qty'];
				}
			);

			foreach ( $rule['tiered_pricing'] as $tier ) {
				if ( ! empty( $tier['min_qty'] ) && ! empty( $tier['price'] ) && $quantity >= $tier['min_qty'] ) {
					$applicable_tier = $tier;
					break;
				}
			}
			if ( $applicable_tier ) {
				switch ( $applicable_tier['discount_type'] ) {
					case 'percentage':
						return $base_price - ( $base_price * floatval( $applicable_tier['price'] ) / 100 );
					case 'fixed':
						return $base_price - floatval( $applicable_tier['price'] );
					default:
						return floatval( $applicable_tier['price'] );
				}
			} else {
				return $base_price;
			}
		}
		return $base_price;
	}

	/* ----------------- USER ROLE ------------------ */
	private function get_current_user_role() {
		return WHTPRole_Pricing_Helper::get_current_user_role();
	}
}
