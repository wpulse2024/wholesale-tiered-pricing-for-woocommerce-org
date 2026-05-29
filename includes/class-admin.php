<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WHTPRole_Pricing_Admin {


	public function __construct() {
		add_action( 'woocommerce_product_data_panels', array( $this, 'add_product_data_panel' ) );
		add_action( 'woocommerce_product_data_tabs', array( $this, 'add_product_data_tab' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_product_data' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	public function enqueue_admin_scripts( $hook ) {
		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}

		global $post;
		if ( $post && 'product' !== $post->post_type ) {
			return;
		}

		// Enqueue Select2 (WordPress includes it for WooCommerce)
		wp_enqueue_script( 'select2' );
		wp_enqueue_style( 'select2', WC()->plugin_url() . '/assets/css/select2.css', array(), WC_VERSION );

		wp_enqueue_style( 'wholesale-tiered-pricing-for-woocommerce-admin', WHTPROLE_PRICING_PLUGIN_URL . 'plugin-assets/admin.css', array(), WHTPROLE_PRICING_VERSION );
		wp_enqueue_script( 'wholesale-tiered-pricing-for-woocommerce-admin', WHTPROLE_PRICING_PLUGIN_URL . 'plugin-assets/admin.js', array( 'jquery', 'select2', 'wp-util' ), WHTPROLE_PRICING_VERSION, true );

		// Pass user roles to JavaScript
		$roles = wp_roles()->get_names();
		wp_localize_script(
			'wholesale-tiered-pricing-for-woocommerce-admin',
			'whtproleAdminRoles',
			array(
				'roles' => $roles,
			)
		);
	}

	public function add_product_data_tab( $tabs ) {
		$tabs['role_pricing'] = array(
			'label'    => __( 'Role Pricing', 'wholesale-tiered-pricing-for-woocommerce' ),
			'target'   => 'role_pricing_data',
			'class'    => array( 'show_if_simple', 'show_if_variable' ),
			'priority' => 80,
		);
		return $tabs;
	}

	public function add_product_data_panel() {
		global $post;
		$product     = wc_get_product( $post->ID );
		$is_variable = $product && $product->is_type( 'variable' );
		$variations  = array();

		if ( $is_variable ) {
			$variation_ids = $product->get_children();
			foreach ( $variation_ids as $variation_id ) {
				$variation = wc_get_product( $variation_id );
				if ( $variation ) {
					// Get variation name - build clean name without HTML
					$variation_name = $variation->get_name();

					// Get variation attributes as plain text
					$attributes = $variation->get_variation_attributes();
					if ( ! empty( $attributes ) ) {
						$attribute_parts = array();
						foreach ( $attributes as $attr_name => $attr_value ) {
							$taxonomy = str_replace( 'attribute_', '', $attr_name );
							$term     = get_term_by( 'slug', $attr_value, $taxonomy );
							if ( $term ) {
								$attribute_parts[] = wc_attribute_label( $taxonomy ) . ': ' . $term->name;
							} else {
								$attribute_parts[] = wc_attribute_label( $taxonomy ) . ': ' . $attr_value;
							}
						}
						if ( ! empty( $attribute_parts ) ) {
							$variation_name .= ' - ' . implode( ', ', $attribute_parts );
						}
					}

					// Add variation ID for clarity
					$variations[ $variation_id ] = $variation_name . ' (#' . $variation_id . ')';
				}
			}
		}
		?>
		<div id="role_pricing_data" class="panel woocommerce_options_panel">
			<?php wp_nonce_field( 'whtprole_save_product_data', 'whtprole_product_nonce' ); ?>
			<div class="options_group">
				<h3><?php esc_html_e( 'Role-Based Pricing Rules', 'wholesale-tiered-pricing-for-woocommerce' ); ?></h3>
				
				<?php if ( $is_variable && ! empty( $variations ) ) : ?>
					<p class="description" style="margin-bottom: 15px; padding: 10px; background: #f0f6fc; border-left: 4px solid #2271b1;">
						<strong><?php esc_html_e( 'Variable Product Detected', 'wholesale-tiered-pricing-for-woocommerce' ); ?></strong><br>
						<?php esc_html_e( 'You can set pricing rules for specific variations. If no variation is selected, the rule will apply to all variations.', 'wholesale-tiered-pricing-for-woocommerce' ); ?>
					</p>
				<?php endif; ?>

				<div id="role-pricing-rules">
					<?php
					$rules = get_post_meta( $post->ID, '_role_pricing_rules', true );
					if ( empty( $rules ) ) {
						$rules = array();
					}

					foreach ( $rules as $index => $rule ) {
						$this->render_pricing_rule( $index, $rule, $is_variable, $variations );
					}
					?>
				</div>

				<p>
					<button type="button" class="button" id="add-pricing-rule" data-is-variable="<?php echo $is_variable ? '1' : '0'; ?>" data-variations='<?php echo esc_attr( wp_json_encode( $variations ) ); ?>'>
						<?php esc_html_e( 'Add Pricing Rule', 'wholesale-tiered-pricing-for-woocommerce' ); ?>
					</button>
				</p>
			</div>

			<div class="options_group">
				<?php

				woocommerce_wp_checkbox(
					array(
						'id'          => '_show_pricing_table',
						'label'       => __( 'Show Pricing Table', 'wholesale-tiered-pricing-for-woocommerce' ),
						'description' => __( 'Display tiered pricing table on product page', 'wholesale-tiered-pricing-for-woocommerce' ),
						'desc_tip'    => true,
						'value'       => get_post_meta( $post->ID, '_show_pricing_table', 'yes' ) === 'no' ? 'no' : 'yes',
					)
				);
				?>
			</div>
		</div>
		<?php
	}

	private function render_pricing_rule( $index, $rule = array(), $is_variable = false, $variations = array() ) {
		$roles = wp_roles()->get_names();

		// Normalize roles: support both legacy 'role' (string) and new 'roles' (array)
		$rule_roles = array();
		if ( isset( $rule['roles'] ) && is_array( $rule['roles'] ) ) {
			$rule_roles = $rule['roles'];
		} elseif ( isset( $rule['role'] ) && ! empty( $rule['role'] ) ) {
			// Legacy: single role as string
			$rule_roles = array( $rule['role'] );
		}

		// Get also_for_guest value
		$also_for_guest = isset( $rule['also_for_guest'] ) ? ( $rule['also_for_guest'] === true || $rule['also_for_guest'] === 'true' || $rule['also_for_guest'] === 1 || $rule['also_for_guest'] === '1' ) : false;
		$has_global     = in_array( 'guest', $rule_roles, true );

		// Note: Rule-level variations are stored for backward compatibility only
		// New logic uses tier-level variations (handled in render_tier_rule)
		?>
		<div class="pricing-rule-row" data-index="<?php echo esc_attr( $index ); ?>">
			<a href="#" class="remove-pricing-rule"><?php esc_html_e( 'Remove', 'wholesale-tiered-pricing-for-woocommerce' ); ?></a>
			<div class="pricing-rule-fields">
				<p class="form-field">
					<label><?php esc_html_e( 'User Roles (Select Multiple)', 'wholesale-tiered-pricing-for-woocommerce' ); ?></label>
					<select name="role_pricing_rules[<?php echo esc_attr( $index ); ?>][roles][]" 
							multiple 
							class="role-multi-select" 
							style="min-height: 120px; width: 100%;"
							data-index="<?php echo esc_attr( $index ); ?>">
						<option value="guest" <?php selected( in_array( 'guest', $rule_roles, true ), true ); ?>>
							<?php esc_html_e( 'Global (All Logged-in Users)', 'wholesale-tiered-pricing-for-woocommerce' ); ?>
						</option>
						<?php foreach ( $roles as $role_key => $role_name ) : ?>
							<option value="<?php echo esc_attr( $role_key ); ?>"
								<?php selected( in_array( $role_key, $rule_roles, true ), true ); ?>>
								<?php echo esc_html( $role_name ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<p class="description" style="margin-top: 5px; font-size: 12px; color: #666;">
						<?php esc_html_e( 'Select one or more user roles. "Global" applies to all logged-in users. Hold Ctrl/Cmd to select multiple.', 'wholesale-tiered-pricing-for-woocommerce' ); ?>
					</p>
					<!-- Hidden field for backward compatibility -->
					<input type="hidden" name="role_pricing_rules[<?php echo esc_attr( $index ); ?>][role]" 
							value="<?php echo esc_attr( ! empty( $rule_roles ) ? $rule_roles[0] : '' ); ?>" />
				</p>

				<p class="form-field guest-checkbox-field" style="<?php echo $has_global ? '' : 'display: none;'; ?>">
					<label>
						<input type="checkbox" 
								name="role_pricing_rules[<?php echo esc_attr( $index ); ?>][also_for_guest]" 
								value="1" 
								<?php checked( $also_for_guest, true ); ?> />
						<?php esc_html_e( 'Make it for guest user also', 'wholesale-tiered-pricing-for-woocommerce' ); ?>
					</label>
					<span class="description" style="display: block; margin-top: 5px; font-size: 12px; color: #666;">
						<?php esc_html_e( 'When enabled, this Global pricing rule will also apply to guest (non-logged-in) users', 'wholesale-tiered-pricing-for-woocommerce' ); ?>
					</span>
				</p>

				<p class="form-field">
					<label><?php esc_html_e( 'Step Quantity', 'wholesale-tiered-pricing-for-woocommerce' ); ?></label>
					<input type="number" name="role_pricing_rules[<?php echo esc_attr( $index ); ?>][step_qty]"
						value="<?php echo isset( $rule['step_qty'] ) ? esc_attr( $rule['step_qty'] ) : '1'; ?>"
						min="1" style="width: 100%" />
				</p>

				<p class="form-field">
					<label><?php esc_html_e( 'Min Quantity', 'wholesale-tiered-pricing-for-woocommerce' ); ?></label>
					<input type="number" name="role_pricing_rules[<?php echo esc_attr( $index ); ?>][min_qty]"
						value="<?php echo isset( $rule['min_qty'] ) ? esc_attr( $rule['min_qty'] ) : '0'; ?>"
						min="0" style="width: 100%" />
				</p>

				<p class="form-field">
					<label><?php esc_html_e( 'Max Quantity', 'wholesale-tiered-pricing-for-woocommerce' ); ?></label>
					<input type="number" name="role_pricing_rules[<?php echo esc_attr( $index ); ?>][max_qty]"
						value="<?php echo isset( $rule['max_qty'] ) ? esc_attr( $rule['max_qty'] ) : ''; ?>"
						min="0" placeholder="<?php esc_html_e( 'Unlimited', 'wholesale-tiered-pricing-for-woocommerce' ); ?>" style="width: 100%" />
				</p>

				<p class="form-field">
					<label><?php esc_html_e( 'Min Order Value', 'wholesale-tiered-pricing-for-woocommerce' ); ?></label>
					<input type="number" name="role_pricing_rules[<?php echo esc_attr( $index ); ?>][min_order_value]"
						value="<?php echo isset( $rule['min_order_value'] ) ? esc_attr( $rule['min_order_value'] ) : ''; ?>"
						min="0" step="0.01" placeholder="<?php esc_html_e( 'No minimum', 'wholesale-tiered-pricing-for-woocommerce' ); ?>" style="width: 100%" />
					<span class="description" style="display: block; margin-top: 5px; font-size: 12px; color: #666;">
						<?php esc_html_e( 'Minimum cart subtotal required before this pricing rule activates. Leave blank for no minimum.', 'wholesale-tiered-pricing-for-woocommerce' ); ?>
					</span>
				</p>

				<p class="form-field">
					<label><?php esc_html_e( 'Schedule: Active From', 'wholesale-tiered-pricing-for-woocommerce' ); ?></label>
					<input type="date" name="role_pricing_rules[<?php echo esc_attr( $index ); ?>][date_from]"
						value="<?php echo isset( $rule['date_from'] ) ? esc_attr( $rule['date_from'] ) : ''; ?>"
						style="width: 100%" />
					<span class="description" style="display: block; margin-top: 5px; font-size: 12px; color: #666;">
						<?php esc_html_e( 'Leave blank for no start restriction.', 'wholesale-tiered-pricing-for-woocommerce' ); ?>
					</span>
				</p>

				<p class="form-field">
					<label><?php esc_html_e( 'Schedule: Active Until', 'wholesale-tiered-pricing-for-woocommerce' ); ?></label>
					<input type="date" name="role_pricing_rules[<?php echo esc_attr( $index ); ?>][date_to]"
						value="<?php echo isset( $rule['date_to'] ) ? esc_attr( $rule['date_to'] ) : ''; ?>"
						style="width: 100%" />
					<span class="description" style="display: block; margin-top: 5px; font-size: 12px; color: #666;">
						<?php esc_html_e( 'Leave blank for no end restriction.', 'wholesale-tiered-pricing-for-woocommerce' ); ?>
					</span>
				</p>

			</div>

			<div class="tiered-pricing-section">
				<h4><?php esc_html_e( 'Tiered Pricing', 'wholesale-tiered-pricing-for-woocommerce' ); ?></h4>
				<div class="tiered-pricing-rules">
					<?php
					$tiered_rules = isset( $rule['tiered_pricing'] ) ? $rule['tiered_pricing'] : array();
					foreach ( $tiered_rules as $tier_index => $tier_rule ) {
						$this->render_tier_rule( $index, $tier_index, $tier_rule, $is_variable, $variations );
					}
					?>
				</div>
				<button type="button" class="button add-tier-rule" data-parent="<?php echo esc_attr( $index ); ?>" data-is-variable="<?php echo $is_variable ? '1' : '0'; ?>" data-variations='<?php echo esc_attr( wp_json_encode( $variations ) ); ?>'>
					<?php esc_html_e( 'Add Tier', 'wholesale-tiered-pricing-for-woocommerce' ); ?>
				</button>
			</div>
		</div>
		<?php
	}

	private function render_tier_rule( $parent_index, $tier_index, $tier_rule = array(), $is_variable = false, $variations = array() ) {
		// Get selected variation for this tier (single select)
		// Priority: new 'variation' field > old 'variations' array > default to 'all'
		$selected_variation = 'all';

		// Check new single variation format first
		if ( isset( $tier_rule['variation'] ) ) {
			$tier_variation = $tier_rule['variation'];
			// If it's null, empty, or 'all', use 'all'
			if ( $tier_variation === null || $tier_variation === '' || $tier_variation === 'all' ) {
				$selected_variation = 'all';
			} else {
				// Convert to integer for consistent comparison
				$selected_variation = intval( $tier_variation );
			}
		} elseif ( isset( $tier_rule['variations'] ) && is_array( $tier_rule['variations'] ) && ! empty( $tier_rule['variations'] ) ) {
			// Backward compatibility: check old variations array format
			// If 'all' is in the array or array is empty, use 'all'
			if ( in_array( 'all', $tier_rule['variations'], true ) || empty( $tier_rule['variations'] ) ) {
				$selected_variation = 'all';
			} else {
				// Use first variation from array
				$selected_variation = intval( $tier_rule['variations'][0] );
			}
		}

		// Normalize selected_variation for comparison (ensure it's either 'all' or an integer)
		$is_all_variations = ( $selected_variation === 'all' || $selected_variation === null || $selected_variation === '' );
		if ( ! $is_all_variations ) {
			$selected_variation = intval( $selected_variation );
		} else {
			$selected_variation = 'all';
		}
		?>
		<div class="tier-rule-row" style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 15px; padding: 15px; background: #fafafa; border: 1px solid #ddd; border-radius: 4px;">
			<div style="display: flex; gap: 10px; width: 100%;">
				<input type="number" name="role_pricing_rules[<?php echo esc_attr( $parent_index ); ?>][tiered_pricing][<?php echo esc_attr( $tier_index ); ?>][min_qty]"
					placeholder="<?php esc_html_e( 'Min Qty', 'wholesale-tiered-pricing-for-woocommerce' ); ?>"
					value="<?php echo isset( $tier_rule['min_qty'] ) ? esc_attr( $tier_rule['min_qty'] ) : ''; ?>"
					min="1" style="width: 150px;" />
				<input type="number" name="role_pricing_rules[<?php echo esc_attr( $parent_index ); ?>][tiered_pricing][<?php echo esc_attr( $tier_index ); ?>][price]"
					placeholder="<?php esc_html_e( 'Price', 'wholesale-tiered-pricing-for-woocommerce' ); ?>"
					value="<?php echo isset( $tier_rule['price'] ) ? esc_attr( $tier_rule['price'] ) : ''; ?>"
					step="0.01" min="0" style="width: 150px;" />
				<select name="role_pricing_rules[<?php echo esc_attr( $parent_index ); ?>][tiered_pricing][<?php echo esc_attr( $tier_index ); ?>][discount_type]" style="width: 150px;">
					<option value="fixed" <?php selected( isset( $tier_rule['discount_type'] ) ? $tier_rule['discount_type'] : '', 'fixed' ); ?>>
						<?php esc_html_e( 'Fixed', 'wholesale-tiered-pricing-for-woocommerce' ); ?>
					</option>
					<option value="percentage" <?php selected( isset( $tier_rule['discount_type'] ) ? $tier_rule['discount_type'] : '', 'percentage' ); ?>>
						<?php esc_html_e( 'Percentage', 'wholesale-tiered-pricing-for-woocommerce' ); ?>
					</option>
				</select>
				<button type="button" class="button remove-tier-rule"><?php esc_html_e( 'Remove', 'wholesale-tiered-pricing-for-woocommerce' ); ?></button>
			</div>
			
			<?php if ( $is_variable && ! empty( $variations ) ) : ?>
			<div style="width: 100%; margin-top: 10px;">
				<label style="display: block; margin-bottom: 5px; font-weight: 600; font-size: 12px;">
					<?php esc_html_e( 'Apply to Variation (Optional)', 'wholesale-tiered-pricing-for-woocommerce' ); ?>
				</label>
				<select name="role_pricing_rules[<?php echo esc_attr( $parent_index ); ?>][tiered_pricing][<?php echo esc_attr( $tier_index ); ?>][variation]" 
						class="tier-variation-select" 
						style="width: 100%;"
						data-parent-index="<?php echo esc_attr( $parent_index ); ?>"
						data-tier-index="<?php echo esc_attr( $tier_index ); ?>">
					<option value="all" <?php selected( $is_all_variations, true ); ?>>
						<?php esc_html_e( 'All Variations', 'wholesale-tiered-pricing-for-woocommerce' ); ?>
					</option>
					<?php foreach ( $variations as $variation_id => $variation_name ) : ?>
						<option value="<?php echo esc_attr( $variation_id ); ?>"
							<?php selected( $selected_variation === intval( $variation_id ), true ); ?>>
							<?php echo esc_html( $variation_name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<p class="description" style="margin-top: 5px; font-size: 11px; color: #666;">
					<?php esc_html_e( 'Select a specific variation for this tier. If "All Variations" is selected, applies to all variations.', 'wholesale-tiered-pricing-for-woocommerce' ); ?>
				</p>
			</div>
			<?php endif; ?>
		</div>
		<?php
	}

	public function save_product_data( $post_id ) {
		if ( ! isset( $_POST['whtprole_product_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['whtprole_product_nonce'] ) ), 'whtprole_save_product_data' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		delete_post_meta( $post_id, '_role_pricing_rules' );

		if ( isset( $_POST['role_pricing_rules'] ) ) {
			$rules = array();
			foreach ( $_POST['role_pricing_rules'] as $rule ) {
				// Normalize roles: support both new 'roles' (array) and legacy 'role' (string)
				$roles = array();
				if ( isset( $rule['roles'] ) && is_array( $rule['roles'] ) ) {
					// New format: array of roles
					$roles = array_map( 'sanitize_text_field', array_filter( $rule['roles'] ) );
				} elseif ( isset( $rule['role'] ) && ! empty( $rule['role'] ) ) {
					// Legacy format: single role string
					$roles = array( sanitize_text_field( $rule['role'] ) );
				}

				// If no roles, skip this rule
				if ( empty( $roles ) ) {
					continue;
				}

				// If Global is in roles, it should be the only role (wildcard behavior)
				if ( in_array( 'guest', $roles, true ) ) {
					$roles = array( 'guest' );
				}

				// Handle also_for_guest field (only for Global/guest role)
				$also_for_guest = false;
				if ( in_array( 'guest', $roles, true ) && isset( $rule['also_for_guest'] ) ) {
					$also_for_guest = ( $rule['also_for_guest'] === '1' || $rule['also_for_guest'] === 1 || $rule['also_for_guest'] === true );
				}

				// Sanitize tiered pricing
				$tiered_pricing = array();
				if ( isset( $rule['tiered_pricing'] ) && is_array( $rule['tiered_pricing'] ) ) {
					foreach ( $rule['tiered_pricing'] as $tier ) {
						// Handle variation for each tier (single select)
						// Priority: new 'variation' field > old 'variations' array > default to null (all variations)
						$tier_variation = null;

						// Check new single variation format first
						if ( isset( $tier['variation'] ) ) {
							$variation_value = $tier['variation'];
							// If it's 'all', empty, or null, store as null (means all variations)
							if ( $variation_value !== 'all' && $variation_value !== '' && $variation_value !== null ) {
								$tier_variation = intval( $variation_value );
							}
						} elseif ( isset( $tier['variations'] ) && is_array( $tier['variations'] ) && ! empty( $tier['variations'] ) ) {
							// Backward compatibility: check for old 'variations' array format
							// If 'all' is in the array, store as null (means all variations)
							if ( ! in_array( 'all', $tier['variations'], true ) ) {
								// Use first variation from array
								$tier_variation = intval( $tier['variations'][0] );
							}
						}

						$tiered_pricing[] = array(
							'min_qty'       => isset( $tier['min_qty'] ) ? intval( $tier['min_qty'] ) : 0,
							'price'         => isset( $tier['price'] ) ? floatval( $tier['price'] ) : 0,
							'discount_type' => isset( $tier['discount_type'] ) ? sanitize_text_field( $tier['discount_type'] ) : 'fixed',
							'variation'     => $tier_variation, // Store single variation ID for this tier (null = all variations)
						);
					}
				}

				// Handle variations (for variable products)
				$variations = array();
				if ( isset( $rule['variations'] ) && is_array( $rule['variations'] ) ) {
					$variations = array_map( 'intval', array_filter( $rule['variations'] ) );
				}

				// Sanitize schedule dates (YYYY-MM-DD or empty)
				$date_from = '';
				$date_to   = '';
				if ( ! empty( $rule['date_from'] ) ) {
					$date_from = sanitize_text_field( $rule['date_from'] );
					// Validate format
					if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_from ) ) {
						$date_from = '';
					}
				}
				if ( ! empty( $rule['date_to'] ) ) {
					$date_to = sanitize_text_field( $rule['date_to'] );
					if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_to ) ) {
						$date_to = '';
					}
				}

				$rules[] = array(
					'roles'           => $roles, // New: always store as array
					'role'            => ! empty( $roles ) ? $roles[0] : 'customer', // Keep for backward compatibility
					'min_qty'         => isset( $rule['min_qty'] ) ? intval( $rule['min_qty'] ) : 0,
					'max_qty'         => ! empty( $rule['max_qty'] ) ? intval( $rule['max_qty'] ) : 0,
					'step_qty'        => isset( $rule['step_qty'] ) ? intval( $rule['step_qty'] ) : 1,
					'min_order_value' => ! empty( $rule['min_order_value'] ) ? floatval( $rule['min_order_value'] ) : 0,
					'tiered_pricing'  => $tiered_pricing,
					'also_for_guest'  => $also_for_guest,
					'variations'      => $variations, // Store selected variation IDs
					'date_from'       => $date_from,
					'date_to'         => $date_to,
				);
			}
			update_post_meta( $post_id, '_role_pricing_rules', $rules );
		}

		if ( isset( $_POST['_show_pricing_table'] ) ) {
			update_post_meta( $post_id, '_show_pricing_table', 'yes' );
		} else {
			update_post_meta( $post_id, '_show_pricing_table', 'no' );
		}
	}
}


