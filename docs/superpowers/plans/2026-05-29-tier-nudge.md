# Tier Progress Nudge Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Show a "Add N more for X/unit" nudge on product pages (live JS) and cart page (PHP) so wholesale buyers know when they're close to a better tier.

**Architecture:** New `WHTPRole_Tier_Nudge` class owns both hooks; `WHTPRole_Pricing_Helper::get_next_tier()` provides the shared tier-gap calculation. Product page uses localized tier data + vanilla JS; cart page is purely PHP rendered via WooCommerce's existing cart-fragment refresh.

**Tech Stack:** PHP 7.4+, WooCommerce hooks, vanilla JS (jQuery wrapper, no Vue), Laravel Mix (webpack.mix.js), PHPUnit + Brain\Monkey for tests.

---

## File Map

| File | Action | Responsibility |
|---|---|---|
| `includes/helper/class-helper.php` | Modify | Add `get_next_tier()` static method |
| `includes/class-tier-nudge.php` | Create | `WHTPRole_Tier_Nudge` class — both surface hooks |
| `includes/class-global-settings.php` | Modify | Add nudge enable/disable setting field |
| `resources/frontend-nudge.js` | Create | Product-page qty listener + nudge renderer |
| `wholesale-tiered-pricing-for-woocommerce.php` | Modify | `require_once` + `new WHTPRole_Tier_Nudge()` |
| `webpack.mix.js` | Modify | Add `frontend-nudge.js` entry point |
| `tests/Unit/HelperTest.php` | Modify | Add tests for `get_next_tier()` |
| `tests/Unit/TierNudgeTest.php` | Create | Tests for hook registration + cart nudge output |

---

## Task 1: Add `get_next_tier()` to Helper — tests first

**Files:**
- Modify: `tests/Unit/HelperTest.php`
- Modify: `includes/helper/class-helper.php`

- [ ] **Step 1: Write the failing tests**

Append to `tests/Unit/HelperTest.php` after the last test method, before the closing `}`:

```php
    // --- get_next_tier ---

    public function test_get_next_tier_returns_null_when_no_rules(): void
    {
        Functions\when('is_user_logged_in')->justReturn(true);
        $user = new \stdClass();
        $user->roles = ['wholesale'];
        Functions\when('wp_get_current_user')->justReturn($user);
        Functions\when('get_post_meta')->justReturn([]);
        Functions\when('get_option')->justReturn([]);
        Functions\when('gmdate')->justReturn('2026-05-29');

        $this->assertNull(WHTPRole_Pricing_Helper::get_next_tier(1, 5));
    }

    public function test_get_next_tier_returns_null_when_role_does_not_match(): void
    {
        Functions\when('is_user_logged_in')->justReturn(true);
        $user = new \stdClass();
        $user->roles = ['customer'];
        Functions\when('wp_get_current_user')->justReturn($user);
        Functions\when('get_post_meta')->justReturn([
            [
                'roles' => ['wholesale'],
                'tiered_pricing' => [
                    ['min_qty' => 10, 'price' => '5.00', 'discount_type' => 'fixed'],
                ],
            ],
        ]);
        Functions\when('get_option')->justReturn([]);
        Functions\when('gmdate')->justReturn('2026-05-29');

        $this->assertNull(WHTPRole_Pricing_Helper::get_next_tier(1, 5));
    }

    public function test_get_next_tier_returns_next_tier_data(): void
    {
        Functions\when('is_user_logged_in')->justReturn(true);
        $user = new \stdClass();
        $user->roles = ['wholesale'];
        Functions\when('wp_get_current_user')->justReturn($user);
        Functions\when('get_post_meta')->justReturn([
            [
                'roles' => ['wholesale'],
                'tiered_pricing' => [
                    ['min_qty' => 10, 'price' => '5.00', 'discount_type' => 'fixed'],
                    ['min_qty' => 20, 'price' => '8.00', 'discount_type' => 'fixed'],
                ],
            ],
        ]);
        Functions\when('get_option')->justReturn([]);
        Functions\when('gmdate')->justReturn('2026-05-29');

        $result = WHTPRole_Pricing_Helper::get_next_tier(1, 5);

        $this->assertNotNull($result);
        $this->assertSame(5, $result['qty_needed']);   // 10 - 5
        $this->assertSame('5.00', $result['tier_price']);
        $this->assertSame('fixed', $result['discount_type']);
    }

    public function test_get_next_tier_returns_null_when_at_max_tier(): void
    {
        Functions\when('is_user_logged_in')->justReturn(true);
        $user = new \stdClass();
        $user->roles = ['wholesale'];
        Functions\when('wp_get_current_user')->justReturn($user);
        Functions\when('get_post_meta')->justReturn([
            [
                'roles' => ['wholesale'],
                'tiered_pricing' => [
                    ['min_qty' => 10, 'price' => '5.00', 'discount_type' => 'fixed'],
                ],
            ],
        ]);
        Functions\when('get_option')->justReturn([]);
        Functions\when('gmdate')->justReturn('2026-05-29');

        // qty=20 is above min_qty=10 — already at max tier
        $this->assertNull(WHTPRole_Pricing_Helper::get_next_tier(1, 20));
    }

    public function test_get_next_tier_skips_tiers_for_wrong_variation(): void
    {
        Functions\when('is_user_logged_in')->justReturn(true);
        $user = new \stdClass();
        $user->roles = ['wholesale'];
        Functions\when('wp_get_current_user')->justReturn($user);
        Functions\when('get_post_meta')->justReturn([
            [
                'roles' => ['wholesale'],
                'tiered_pricing' => [
                    ['min_qty' => 10, 'price' => '5.00', 'discount_type' => 'fixed', 'variation' => 99],
                ],
            ],
        ]);
        Functions\when('get_option')->justReturn([]);
        Functions\when('gmdate')->justReturn('2026-05-29');

        // variation_id=42 does not match tier variation=99
        $this->assertNull(WHTPRole_Pricing_Helper::get_next_tier(1, 5, 42));
    }

    public function test_get_next_tier_percentage_discount_type_preserved(): void
    {
        Functions\when('is_user_logged_in')->justReturn(true);
        $user = new \stdClass();
        $user->roles = ['wholesale'];
        Functions\when('wp_get_current_user')->justReturn($user);
        Functions\when('get_post_meta')->justReturn([
            [
                'roles' => ['wholesale'],
                'tiered_pricing' => [
                    ['min_qty' => 10, 'price' => '15', 'discount_type' => 'percentage'],
                ],
            ],
        ]);
        Functions\when('get_option')->justReturn([]);
        Functions\when('gmdate')->justReturn('2026-05-29');

        $result = WHTPRole_Pricing_Helper::get_next_tier(1, 3);

        $this->assertNotNull($result);
        $this->assertSame('percentage', $result['discount_type']);
        $this->assertSame('15', $result['tier_price']);
    }
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
./vendor/bin/phpunit tests/Unit/HelperTest.php --filter get_next_tier -v
```

Expected: `Error: Call to undefined method WHTPRole_Pricing_Helper::get_next_tier()`

- [ ] **Step 3: Implement `get_next_tier()` in Helper**

Open `includes/helper/class-helper.php`. Add this method just before the final closing `}` of the class (after the `calculate_price()` method, around line 409):

```php
	/**
	 * Return the next tier above the given quantity for the current user.
	 *
	 * @param  int      $product_id
	 * @param  int      $current_qty
	 * @param  int|null $variation_id
	 * @return array{qty_needed: int, tier_price: string, discount_type: string}|null
	 */
	public static function get_next_tier( int $product_id, int $current_qty, ?int $variation_id = null ): ?array {
		$current_user_role = self::get_current_user_role();
		$is_guest          = ( $current_user_role === 'guest' );
		$rules             = self::get_rules_for_product( $product_id );

		foreach ( $rules as $rule ) {
			$rule_roles     = isset( $rule['roles'] ) ? $rule['roles'] : ( isset( $rule['role'] ) ? $rule['role'] : array() );
			$also_for_guest = ! empty( $rule['also_for_guest'] );

			if ( ! self::rule_applies_to_user( $rule_roles, $current_user_role, $is_guest, $also_for_guest ) ) {
				continue;
			}

			if ( empty( $rule['tiered_pricing'] ) || ! is_array( $rule['tiered_pricing'] ) ) {
				continue;
			}

			$tiers = $rule['tiered_pricing'];
			usort(
				$tiers,
				function ( $a, $b ) {
					return intval( $a['min_qty'] ?? 0 ) - intval( $b['min_qty'] ?? 0 );
				}
			);

			foreach ( $tiers as $tier ) {
				if ( $variation_id !== null && ! self::tier_applies_to_variation( $tier, $variation_id ) ) {
					continue;
				}
				$tier_min = intval( $tier['min_qty'] ?? 0 );
				if ( $tier_min > $current_qty ) {
					return array(
						'qty_needed'    => $tier_min - $current_qty,
						'tier_price'    => strval( $tier['price'] ?? '0' ),
						'discount_type' => $tier['discount_type'] ?? 'fixed',
					);
				}
			}

			// First matching rule wins — stop looking.
			break;
		}

		return null;
	}
```

- [ ] **Step 4: Run tests to verify they pass**

```bash
./vendor/bin/phpunit tests/Unit/HelperTest.php --filter get_next_tier -v
```

Expected: All 6 tests PASS.

- [ ] **Step 5: Run full test suite to check no regressions**

```bash
./vendor/bin/phpunit --testdox
```

Expected: All tests pass.

- [ ] **Step 6: Commit**

```bash
git add includes/helper/class-helper.php tests/Unit/HelperTest.php
git commit -m "feat: add get_next_tier() to WHTPRole_Pricing_Helper"
```

---

## Task 2: Add settings toggle

**Files:**
- Modify: `includes/class-global-settings.php`

- [ ] **Step 1: Add WC settings field for nudge toggle**

Open `includes/class-global-settings.php`. After the closing `}` of the `woocommerce_settings_tabs_tiered_pricing` action callback (after line 65), add:

```php
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
```

- [ ] **Step 2: Verify setting saves correctly**

No automated test needed — this is pure WC settings API. Manual verify in Task 6 (browser test).

- [ ] **Step 3: Commit**

```bash
git add includes/class-global-settings.php
git commit -m "feat: add nudge enable/disable setting to Tiered Pricing tab"
```

---

## Task 3: Create `WHTPRole_Tier_Nudge` class — tests first

**Files:**
- Create: `tests/Unit/TierNudgeTest.php`
- Create: `includes/class-tier-nudge.php`

- [ ] **Step 1: Create test file**

Create `tests/Unit/TierNudgeTest.php`:

```php
<?php
declare(strict_types=1);

namespace Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use WHTPRole_Tier_Nudge;

class TierNudgeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function test_constructor_skips_hooks_when_disabled(): void
    {
        Functions\when('get_option')->justReturn('no');

        $nudge = new WHTPRole_Tier_Nudge();

        $this->assertFalse(
            has_action('woocommerce_before_add_to_cart_button', [$nudge, 'render_product_nudge'])
        );
        $this->assertFalse(
            has_action('woocommerce_after_cart_item_name', [$nudge, 'render_cart_nudge'])
        );
    }

    public function test_render_cart_nudge_outputs_nothing_when_at_max_tier(): void
    {
        Functions\when('get_option')->alias(function ($key, $default = null) {
            if ($key === 'whtprole_nudge_enabled') return 'yes';
            return [];
        });
        Functions\when('is_user_logged_in')->justReturn(true);
        $user = new \stdClass();
        $user->roles = ['wholesale'];
        Functions\when('wp_get_current_user')->justReturn($user);
        // No rules → get_next_tier returns null
        Functions\when('get_post_meta')->justReturn([]);
        Functions\when('gmdate')->justReturn('2026-05-29');

        $nudge = new WHTPRole_Tier_Nudge();
        $cart_item = [
            'product_id'   => 42,
            'variation_id' => 0,
            'quantity'     => 10,
        ];

        ob_start();
        $nudge->render_cart_nudge($cart_item, 'abc123');
        $output = ob_get_clean();

        $this->assertSame('', $output);
    }

    public function test_render_cart_nudge_outputs_fixed_message(): void
    {
        Functions\when('get_option')->alias(function ($key, $default = null) {
            if ($key === 'whtprole_nudge_enabled') return 'yes';
            return [
                [
                    'roles' => ['wholesale'],
                    'tiered_pricing' => [
                        ['min_qty' => 10, 'price' => '5.00', 'discount_type' => 'fixed'],
                    ],
                ],
            ];
        });
        Functions\when('get_post_meta')->justReturn([]);
        Functions\when('is_user_logged_in')->justReturn(true);
        $user = new \stdClass();
        $user->roles = ['wholesale'];
        Functions\when('wp_get_current_user')->justReturn($user);
        Functions\when('gmdate')->justReturn('2026-05-29');
        Functions\when('get_woocommerce_currency_symbol')->justReturn('$');
        Functions\when('esc_html')->returnArg();
        Functions\when('__')->returnArg();

        $nudge = new WHTPRole_Tier_Nudge();
        $cart_item = [
            'product_id'   => 42,
            'variation_id' => 0,
            'quantity'     => 5,
        ];

        ob_start();
        $nudge->render_cart_nudge($cart_item, 'abc123');
        $output = ob_get_clean();

        $this->assertStringContainsString('whtprole-cart-nudge', $output);
        $this->assertStringContainsString('5', $output); // qty_needed = 10 - 5
        $this->assertStringContainsString('$', $output);
        $this->assertStringContainsString('5.00', $output);
    }

    public function test_render_cart_nudge_outputs_percentage_message(): void
    {
        Functions\when('get_option')->alias(function ($key, $default = null) {
            if ($key === 'whtprole_nudge_enabled') return 'yes';
            return [
                [
                    'roles' => ['wholesale'],
                    'tiered_pricing' => [
                        ['min_qty' => 10, 'price' => '15', 'discount_type' => 'percentage'],
                    ],
                ],
            ];
        });
        Functions\when('get_post_meta')->justReturn([]);
        Functions\when('is_user_logged_in')->justReturn(true);
        $user = new \stdClass();
        $user->roles = ['wholesale'];
        Functions\when('wp_get_current_user')->justReturn($user);
        Functions\when('gmdate')->justReturn('2026-05-29');
        Functions\when('get_woocommerce_currency_symbol')->justReturn('$');
        Functions\when('esc_html')->returnArg();
        Functions\when('__')->returnArg();

        $nudge = new WHTPRole_Tier_Nudge();
        $cart_item = [
            'product_id'   => 42,
            'variation_id' => 0,
            'quantity'     => 3,
        ];

        ob_start();
        $nudge->render_cart_nudge($cart_item, 'abc123');
        $output = ob_get_clean();

        $this->assertStringContainsString('whtprole-cart-nudge', $output);
        $this->assertStringContainsString('15', $output); // percentage value
        $this->assertStringContainsString('%', $output);
    }
}
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
./vendor/bin/phpunit tests/Unit/TierNudgeTest.php -v
```

Expected: `Error: Class "WHTPRole_Tier_Nudge" not found`

- [ ] **Step 3: Create `includes/class-tier-nudge.php`**

After creating the file, run `composer dump-autoload` so the classmap picks it up:

```bash
composer dump-autoload
```

```php
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

	/**
	 * Return tiers from the first applicable rule for the given role, sorted ascending.
	 */
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
```

- [ ] **Step 4: Run tests to verify they pass**

```bash
./vendor/bin/phpunit tests/Unit/TierNudgeTest.php -v
```

Expected: All 4 tests PASS.

- [ ] **Step 5: Run full suite**

```bash
./vendor/bin/phpunit --testdox
```

Expected: All tests pass.

- [ ] **Step 6: Commit**

```bash
git add includes/class-tier-nudge.php tests/Unit/TierNudgeTest.php
git commit -m "feat: add WHTPRole_Tier_Nudge class with product and cart hooks"
```

---

## Task 4: Register class in main plugin file

**Files:**
- Modify: `wholesale-tiered-pricing-for-woocommerce.php`

- [ ] **Step 1: Add require_once**

In `wholesale-tiered-pricing-for-woocommerce.php`, find the `private function includes()` block (around line 79). After the line:

```php
		require_once WHTPROLE_PRICING_PLUGIN_PATH . 'includes/class-registration.php';
```

Add:

```php
		require_once WHTPROLE_PRICING_PLUGIN_PATH . 'includes/class-tier-nudge.php';
```

- [ ] **Step 2: Instantiate the class**

In the same file, find `private function hooks()` (around line 91). After:

```php
		new WHTPRole_Registration();
```

Add:

```php
		new WHTPRole_Tier_Nudge();
```

- [ ] **Step 3: Run full suite**

```bash
./vendor/bin/phpunit --testdox
```

Expected: All tests pass.

- [ ] **Step 4: Commit**

```bash
git add wholesale-tiered-pricing-for-woocommerce.php
git commit -m "feat: register WHTPRole_Tier_Nudge in plugin bootstrap"
```

---

## Task 5: Add JS entry point to build config

**Files:**
- Modify: `webpack.mix.js`
- Create: `resources/frontend-nudge.js`

- [ ] **Step 1: Add entry point to webpack.mix.js**

Open `webpack.mix.js`. After the line:

```js
   .js('resources/frontend.js', 'plugin-assets/frontend.js')
```

Add:

```js
   .js('resources/frontend-nudge.js', 'plugin-assets/frontend-nudge.js')
```

- [ ] **Step 2: Create `resources/frontend-nudge.js`**

```js
/* global whtproleNudge, jQuery */
(function ($) {
    'use strict';

    if (!window.whtproleNudge) {
        return;
    }

    var nudge = window.whtproleNudge;
    var activeTiers = nudge.is_variable ? [] : nudge.tiers;
    var $nudgeEl = $('.whtprole-nudge');

    function findNextTier(qty) {
        for (var i = 0; i < activeTiers.length; i++) {
            if (activeTiers[i].min_qty > qty) {
                return activeTiers[i];
            }
        }
        return null;
    }

    function updateNudge() {
        var qty = parseInt($('form.cart input.qty').val(), 10);
        if (!qty || qty < 1) {
            $nudgeEl.text('');
            return;
        }

        var next = findNextTier(qty);
        if (!next) {
            $nudgeEl.text('');
            return;
        }

        var need = next.min_qty - qty;
        var msg;

        if (next.discount_type === 'percentage') {
            msg = 'Add ' + need + ' more to save ' + next.price + '% per unit';
        } else {
            msg = 'Add ' + need + ' more for ' + nudge.currency_symbol + next.price + '/unit';
        }

        $nudgeEl.text(msg);
    }

    $('form.cart').on('change input', 'input.qty', updateNudge);

    if (nudge.is_variable) {
        $(document).on('found_variation', function (e, variation) {
            var vid = String(variation.variation_id);
            activeTiers = nudge.variation_tiers[vid] || [];
            updateNudge();
        });

        $(document).on('reset_data', function () {
            activeTiers = [];
            $nudgeEl.text('');
        });
    } else {
        updateNudge();
    }

}(jQuery));
```

- [ ] **Step 3: Build assets**

```bash
npm run dev
```

Expected: `plugin-assets/frontend-nudge.js` created with no build errors.

- [ ] **Step 4: Commit**

```bash
git add webpack.mix.js resources/frontend-nudge.js plugin-assets/frontend-nudge.js
git commit -m "feat: add frontend-nudge.js build entry and source file"
```

---

## Task 6: Manual browser verification

- [ ] **Step 1: Activate plugin on a local WP + WooCommerce install**

Ensure the plugin is active. Confirm no PHP fatal errors on the admin dashboard.

- [ ] **Step 2: Configure a wholesale rule**

Go to WooCommerce → Settings → Tiered Pricing. Add a global rule:
- Role: `wholesale` (or any role your test user has)
- Tiers: `min_qty=5 → $3 fixed discount`, `min_qty=10 → $6 fixed discount`

- [ ] **Step 3: Verify product page nudge**

Visit a simple product page as the wholesale user with qty=1. Confirm:
- Nudge div renders below "Add to Cart" button
- Message reads "Add 4 more for $X/unit"
- Changing qty to 5 updates message to "Add 5 more for $X/unit" (next tier)
- Changing qty to 10+ clears the nudge

- [ ] **Step 4: Verify variable product nudge**

Visit a variable product page. Confirm:
- No nudge before selecting a variation
- After selecting a variation, nudge appears and updates on qty change
- Resetting variation clears nudge

- [ ] **Step 5: Verify cart nudge**

Add a wholesale product with qty=3 to cart. Visit cart. Confirm:
- "Add 2 more for $X/unit" appears below the product name in the cart line
- Changing cart qty to 5 and updating cart refreshes nudge to next tier
- Changing cart qty to 10 removes the nudge

- [ ] **Step 6: Verify disable toggle works**

Go to WooCommerce → Settings → Tiered Pricing. Uncheck "Enable tier progress nudge". Save. Revisit product and cart pages. Confirm no nudge renders.

- [ ] **Step 7: Final commit**

```bash
git add -p  # stage any tweaks made during manual testing
git commit -m "feat: tier progress nudge complete"
```

---

## Checklist — Spec Coverage

| Spec requirement | Task |
|---|---|
| `get_next_tier()` helper method | Task 1 |
| Setting toggle with default=on | Task 2 |
| Product page nudge + JS qty listener | Tasks 3 + 5 |
| Cart page PHP nudge | Task 3 |
| Variable product variation_tiers pre-load | Task 3 |
| `found_variation` / `reset_data` JS events | Task 5 |
| All edge cases (max tier, no rules, guest) | Task 1 tests |
| Register in main plugin | Task 4 |
| Build config entry | Task 5 |
