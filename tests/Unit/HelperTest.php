<?php
declare(strict_types=1);

namespace Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use WHTPRole_Pricing_Helper;

class HelperTest extends TestCase
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

    // --- normalize_rule_roles ---

    public function test_normalize_empty_array_returns_empty(): void
    {
        $this->assertSame([], WHTPRole_Pricing_Helper::normalize_rule_roles([]));
    }

    public function test_normalize_string_wraps_in_array(): void
    {
        $this->assertSame(['wholesale'], WHTPRole_Pricing_Helper::normalize_rule_roles('wholesale'));
    }

    public function test_normalize_array_returns_values(): void
    {
        $this->assertSame(
            ['wholesale', 'retailer'],
            WHTPRole_Pricing_Helper::normalize_rule_roles(['wholesale', 'retailer'])
        );
    }

    public function test_normalize_strips_empty_values_from_array(): void
    {
        $this->assertSame(
            ['wholesale'],
            WHTPRole_Pricing_Helper::normalize_rule_roles(['wholesale', ''])
        );
    }

    // --- rule_applies_to_user ---

    public function test_matching_role_returns_true(): void
    {
        $this->assertTrue(
            WHTPRole_Pricing_Helper::rule_applies_to_user(['wholesale'], 'wholesale')
        );
    }

    public function test_non_matching_role_returns_false(): void
    {
        $this->assertFalse(
            WHTPRole_Pricing_Helper::rule_applies_to_user(['wholesale'], 'retailer')
        );
    }

    public function test_global_role_applies_to_any_logged_in_user(): void
    {
        $this->assertTrue(
            WHTPRole_Pricing_Helper::rule_applies_to_user(['global'], 'customer', false, false)
        );
    }

    public function test_global_role_excludes_guest_when_also_for_guest_false(): void
    {
        $this->assertFalse(
            WHTPRole_Pricing_Helper::rule_applies_to_user(['global'], 'customer', true, false)
        );
    }

    public function test_global_role_includes_guest_when_also_for_guest_true(): void
    {
        $this->assertTrue(
            WHTPRole_Pricing_Helper::rule_applies_to_user(['global'], 'customer', true, true)
        );
    }

    public function test_guest_role_in_rules_requires_also_for_guest_true(): void
    {
        // When a rule has 'guest' in it, we need also_for_guest=true for it to apply to guests
        $this->assertFalse(
            WHTPRole_Pricing_Helper::rule_applies_to_user(['guest'], 'guest', true, false)
        );
    }

    public function test_guest_role_in_rules_applies_with_also_for_guest_true(): void
    {
        // When a rule has 'guest' in it and also_for_guest=true, it applies to guests
        $this->assertTrue(
            WHTPRole_Pricing_Helper::rule_applies_to_user(['guest'], 'guest', true, true)
        );
    }

    public function test_legacy_string_role_matches_user_role(): void
    {
        $this->assertTrue(
            WHTPRole_Pricing_Helper::rule_applies_to_user('wholesale', 'wholesale')
        );
    }

    public function test_empty_roles_always_returns_false(): void
    {
        $this->assertFalse(
            WHTPRole_Pricing_Helper::rule_applies_to_user([], 'wholesale')
        );
    }

    public function test_multiple_roles_matches_any(): void
    {
        $this->assertTrue(
            WHTPRole_Pricing_Helper::rule_applies_to_user(['wholesale', 'retailer'], 'retailer')
        );
    }

    // --- calculate_price ---

    public function test_fixed_discount_subtracts_tier_price_from_base(): void
    {
        $rule = ['tiered_pricing' => [
            ['min_qty' => 1, 'max_qty' => '', 'price' => '10', 'discount_type' => 'fixed'],
        ]];
        $this->assertSame(90.0, WHTPRole_Pricing_Helper::calculate_price(100.0, $rule, 1));
    }

    public function test_percentage_discount_applies_percent_off_base(): void
    {
        $rule = ['tiered_pricing' => [
            ['min_qty' => 1, 'max_qty' => '', 'price' => '20', 'discount_type' => 'percentage'],
        ]];
        $this->assertSame(80.0, WHTPRole_Pricing_Helper::calculate_price(100.0, $rule, 1));
    }

    public function test_quantity_below_min_qty_gets_no_discount(): void
    {
        $rule = ['tiered_pricing' => [
            ['min_qty' => 5, 'max_qty' => '', 'price' => '10', 'discount_type' => 'fixed'],
        ]];
        $this->assertSame(100.0, WHTPRole_Pricing_Helper::calculate_price(100.0, $rule, 3));
    }

    public function test_highest_matching_tier_is_selected(): void
    {
        $rule = ['tiered_pricing' => [
            ['min_qty' => 1,  'max_qty' => '4',  'price' => '5',  'discount_type' => 'fixed'],
            ['min_qty' => 5,  'max_qty' => '9',  'price' => '10', 'discount_type' => 'fixed'],
            ['min_qty' => 10, 'max_qty' => '',   'price' => '20', 'discount_type' => 'fixed'],
        ]];
        $this->assertSame(80.0, WHTPRole_Pricing_Helper::calculate_price(100.0, $rule, 10));
    }

    public function test_quantity_exceeding_max_qty_falls_through_to_no_match(): void
    {
        $rule = ['tiered_pricing' => [
            ['min_qty' => 1, 'max_qty' => '4', 'price' => '5',  'discount_type' => 'fixed'],
            ['min_qty' => 5, 'max_qty' => '9', 'price' => '15', 'discount_type' => 'fixed'],
        ]];
        $this->assertSame(100.0, WHTPRole_Pricing_Helper::calculate_price(100.0, $rule, 10));
    }

    public function test_zero_base_price_returns_unchanged(): void
    {
        $rule = ['tiered_pricing' => [
            ['min_qty' => 1, 'max_qty' => '', 'price' => '10', 'discount_type' => 'fixed'],
        ]];
        $this->assertSame(0.0, WHTPRole_Pricing_Helper::calculate_price(0.0, $rule, 5));
    }

    public function test_fixed_discount_cannot_produce_negative_price(): void
    {
        $rule = ['tiered_pricing' => [
            ['min_qty' => 1, 'max_qty' => '', 'price' => '200', 'discount_type' => 'fixed'],
        ]];
        $this->assertSame(0.0, WHTPRole_Pricing_Helper::calculate_price(100.0, $rule, 1));
    }

    public function test_empty_tiered_pricing_returns_base_price(): void
    {
        $rule = ['tiered_pricing' => []];
        $this->assertSame(50.0, WHTPRole_Pricing_Helper::calculate_price(50.0, $rule, 5));
    }

    // --- calculationDiscount ---

    public function test_calculation_discount_fixed_type(): void
    {
        $helper = new WHTPRole_Pricing_Helper();
        $result = $helper->calculation_discount(100.0, ['discount_type' => 'fixed', 'price' => '20']);
        $this->assertSame(80.0, $result['price']);
        $this->assertSame(20.0, $result['savings']);
        $this->assertSame(20.0, $result['savings_percent']);
    }

    public function test_calculation_discount_percentage_type(): void
    {
        $helper = new WHTPRole_Pricing_Helper();
        $result = $helper->calculation_discount(100.0, ['discount_type' => 'percentage', 'price' => '25']);
        $this->assertSame(75.0, $result['price']);
        $this->assertSame(25.0, $result['savings']);
        $this->assertSame(25.0, $result['savings_percent']);
    }

    public function test_calculation_discount_unknown_type_uses_direct_price(): void
    {
        $helper = new WHTPRole_Pricing_Helper();
        $result = $helper->calculation_discount(100.0, ['discount_type' => 'direct', 'price' => '60']);
        $this->assertSame(60.0, $result['price']);
        $this->assertSame(40.0, $result['savings']);
    }

    public function test_calculation_discount_price_cannot_go_negative(): void
    {
        $helper = new WHTPRole_Pricing_Helper();
        $result = $helper->calculation_discount(10.0, ['discount_type' => 'fixed', 'price' => '50']);
        $this->assertEquals(0, $result['price']);
        $this->assertSame(50.0, $result['savings']);
    }

    // --- tier_applies_to_variation ---

    public function test_null_variation_field_applies_to_all(): void
    {
        $this->assertTrue(
            WHTPRole_Pricing_Helper::tier_applies_to_variation(['variation' => null], 42)
        );
    }

    public function test_empty_string_variation_field_applies_to_all(): void
    {
        $this->assertTrue(
            WHTPRole_Pricing_Helper::tier_applies_to_variation(['variation' => ''], 42)
        );
    }

    public function test_specific_variation_id_matches(): void
    {
        $this->assertTrue(
            WHTPRole_Pricing_Helper::tier_applies_to_variation(['variation' => '42'], 42)
        );
    }

    public function test_specific_variation_id_no_match(): void
    {
        $this->assertFalse(
            WHTPRole_Pricing_Helper::tier_applies_to_variation(['variation' => '99'], 42)
        );
    }

    public function test_legacy_empty_variations_array_applies_to_all(): void
    {
        $this->assertTrue(
            WHTPRole_Pricing_Helper::tier_applies_to_variation(['variations' => []], 42)
        );
    }

    public function test_legacy_all_keyword_applies_to_all(): void
    {
        $this->assertTrue(
            WHTPRole_Pricing_Helper::tier_applies_to_variation(['variations' => ['all']], 42)
        );
    }

    public function test_no_variation_key_at_all_applies_to_all(): void
    {
        $this->assertTrue(
            WHTPRole_Pricing_Helper::tier_applies_to_variation(['min_qty' => 1, 'price' => '10'], 42)
        );
    }

    // --- get_rules_for_product ---

    public function test_returns_product_rules_when_present(): void
    {
        $rules = [
            ['roles' => ['wholesale'], 'tiered_pricing' => [
                ['min_qty' => 1, 'price' => '10', 'discount_type' => 'fixed'],
            ]],
        ];
        Functions\when('get_post_meta')->justReturn($rules);

        $result = WHTPRole_Pricing_Helper::get_rules_for_product(42);

        $this->assertCount(1, $result);
        $this->assertSame(['wholesale'], $result[0]['roles']);
    }

    public function test_falls_back_to_global_rules_when_product_has_none(): void
    {
        $global_rules = [
            ['roles' => ['global'], 'tiered_pricing' => [
                ['min_qty' => 1, 'price' => '5', 'discount_type' => 'fixed'],
            ]],
        ];
        Functions\when('get_post_meta')->justReturn([]);
        Functions\when('get_option')->justReturn($global_rules);

        $result = WHTPRole_Pricing_Helper::get_rules_for_product(42);

        $this->assertCount(1, $result);
        $this->assertSame(['global'], $result[0]['roles']);
    }

    public function test_returns_empty_array_when_no_rules_anywhere(): void
    {
        Functions\when('get_post_meta')->justReturn([]);
        Functions\when('get_option')->justReturn([]);

        $result = WHTPRole_Pricing_Helper::get_rules_for_product(42);

        $this->assertSame([], $result);
    }

    public function test_filters_out_expired_rules(): void
    {
        $rules = [
            ['roles' => ['wholesale'], 'date_to' => '2020-01-01', 'tiered_pricing' => []],
            ['roles' => ['retail'],   'date_to' => '2099-12-31', 'tiered_pricing' => []],
        ];
        Functions\when('get_post_meta')->justReturn($rules);

        $result = WHTPRole_Pricing_Helper::get_rules_for_product(42);

        $this->assertCount(1, $result);
        $this->assertSame(['retail'], $result[0]['roles']);
    }

    public function test_filters_out_rules_not_yet_started(): void
    {
        $rules = [
            ['roles' => ['wholesale'], 'date_from' => '2099-01-01', 'tiered_pricing' => []],
            ['roles' => ['retail'],   'date_from' => '2020-01-01', 'tiered_pricing' => []],
        ];
        Functions\when('get_post_meta')->justReturn($rules);

        $result = WHTPRole_Pricing_Helper::get_rules_for_product(42);

        $this->assertCount(1, $result);
        $this->assertSame(['retail'], $result[0]['roles']);
    }

    public function test_decodes_json_string_rules(): void
    {
        $rules = json_encode([
            ['roles' => ['wholesale'], 'tiered_pricing' => []],
        ]);
        Functions\when('get_post_meta')->justReturn($rules);

        $result = WHTPRole_Pricing_Helper::get_rules_for_product(42);

        $this->assertCount(1, $result);
    }

    // --- get_next_tier ---

    public function test_get_next_tier_returns_null_when_no_rules(): void
    {
        Functions\when('is_user_logged_in')->justReturn(true);
        $user = new \stdClass();
        $user->roles = ['wholesale'];
        Functions\when('wp_get_current_user')->justReturn($user);
        Functions\when('get_post_meta')->justReturn([]);
        Functions\when('get_option')->justReturn([]);

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

        $result = WHTPRole_Pricing_Helper::get_next_tier(1, 3);

        $this->assertNotNull($result);
        $this->assertSame('percentage', $result['discount_type']);
        $this->assertSame('15', $result['tier_price']);
    }
}
