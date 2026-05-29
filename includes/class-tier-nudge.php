<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WHTPRole_Tier_Nudge {

	public function __construct() {
		if ( get_option( 'whtprole_nudge_enabled', 'yes' ) !== 'yes' ) {
			return;
		}
		add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'render_product_nudge' ) );
		add_action( 'woocommerce_after_cart_item_name', array( $this, 'render_cart_nudge' ), 10, 2 );
	}

	public function render_product_nudge(): void {
		global $product;
		if ( ! $product ) {
			return;
		}

		$parent_id         = WHTPRole_Pricing_Helper::get_parent_product_id( $product );
		$rules             = WHTPRole_Pricing_Helper::get_rules_for_product( $parent_id );
		$current_user_role = WHTPRole_Pricing_Helper::get_current_user_role();
		$is_guest          = ( $current_user_role === 'guest' );

		if ( $product->is_type( 'variable' ) ) {
			$variation_tiers = array();
			foreach ( $product->get_children() as $vid ) {
				$tiers = $this->get_tiers_for_role( $rules, $current_user_role, $is_guest, $vid );
				if ( ! empty( $tiers ) ) {
					$variation_tiers[ $vid ] = $tiers;
				}
			}
			if ( empty( $variation_tiers ) ) {
				return;
			}
			wp_enqueue_script( 'whtprole-frontend-nudge', WHTPROLE_PRICING_PLUGIN_URL . 'plugin-assets/frontend-nudge.js', array( 'jquery' ), WHTPROLE_PRICING_VERSION, true );
			wp_localize_script(
				'whtprole-frontend-nudge',
				'whtproleNudge',
				array(
					'tiers'           => array(),
					'variation_tiers' => $variation_tiers,
					'currency_symbol' => get_woocommerce_currency_symbol(),
					'is_variable'     => true,
				)
			);
		} else {
			$tiers = $this->get_tiers_for_role( $rules, $current_user_role, $is_guest, null );
			if ( empty( $tiers ) ) {
				return;
			}
			wp_enqueue_script( 'whtprole-frontend-nudge', WHTPROLE_PRICING_PLUGIN_URL . 'plugin-assets/frontend-nudge.js', array( 'jquery' ), WHTPROLE_PRICING_VERSION, true );
			wp_localize_script(
				'whtprole-frontend-nudge',
				'whtproleNudge',
				array(
					'tiers'           => $tiers,
					'variation_tiers' => array(),
					'currency_symbol' => get_woocommerce_currency_symbol(),
					'is_variable'     => false,
				)
			);
		}

		echo '<div class="whtprole-nudge"></div>';
	}

	public function render_cart_nudge( array $cart_item, string $cart_item_key ): void {
		$product_id   = $cart_item['product_id'];
		$variation_id = ! empty( $cart_item['variation_id'] ) ? (int) $cart_item['variation_id'] : null;
		$quantity     = (int) $cart_item['quantity'];

		$next_tier = WHTPRole_Pricing_Helper::get_next_tier( $product_id, $quantity, $variation_id );
		if ( ! $next_tier ) {
			return;
		}

		$symbol = get_woocommerce_currency_symbol();

		if ( $next_tier['discount_type'] === 'percentage' ) {
			$message = sprintf(
				/* translators: 1: quantity needed 2: percentage discount */
				__( 'Add %1$d more to save %2$s%% per unit', 'wholesale-tiered-pricing-for-woocommerce' ),
				$next_tier['qty_needed'],
				$next_tier['tier_price']
			);
		} else {
			$message = sprintf(
				/* translators: 1: quantity needed 2: currency symbol 3: price per unit */
				__( 'Add %1$d more for %2$s%3$s/unit', 'wholesale-tiered-pricing-for-woocommerce' ),
				$next_tier['qty_needed'],
				$symbol,
				$next_tier['tier_price']
			);
		}

		echo '<br><small class="whtprole-cart-nudge">' . esc_html( $message ) . '</small>';
	}

	private function get_tiers_for_role( array $rules, string $role, bool $is_guest, ?int $variation_id ): array {
		foreach ( $rules as $rule ) {
			$rule_roles     = isset( $rule['roles'] ) ? $rule['roles'] : ( isset( $rule['role'] ) ? $rule['role'] : array() );
			$also_for_guest = ! empty( $rule['also_for_guest'] );

			if ( ! WHTPRole_Pricing_Helper::rule_applies_to_user( $rule_roles, $role, $is_guest, $also_for_guest ) ) {
				continue;
			}

			if ( empty( $rule['tiered_pricing'] ) || ! is_array( $rule['tiered_pricing'] ) ) {
				continue;
			}

			$tiers = $rule['tiered_pricing'];

			if ( $variation_id !== null ) {
				$tiers = array_values(
					array_filter(
						$tiers,
						function ( $tier ) use ( $variation_id ) {
							return WHTPRole_Pricing_Helper::tier_applies_to_variation( $tier, $variation_id );
						}
					)
				);
			}

			usort(
				$tiers,
				function ( $a, $b ) {
					return intval( $a['min_qty'] ?? 0 ) - intval( $b['min_qty'] ?? 0 );
				}
			);

			return array_map(
				function ( $tier ) {
					return array(
						'min_qty'       => intval( $tier['min_qty'] ?? 0 ),
						'price'         => strval( $tier['price'] ?? '0' ),
						'discount_type' => $tier['discount_type'] ?? 'fixed',
					);
				},
				$tiers
			);
		}

		return array();
	}
}
