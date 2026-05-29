<?php
declare(strict_types=1);

namespace Tests\Unit;

use Brain\Monkey;
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
}
