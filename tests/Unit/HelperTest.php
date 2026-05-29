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
}
