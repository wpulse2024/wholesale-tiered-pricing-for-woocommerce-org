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

    public function test_render_cart_nudge_outputs_nothing_when_no_rules(): void
    {
        Functions\when('get_option')->alias(function ($key, $default = null) {
            if ($key === 'whtprole_nudge_enabled') return 'yes';
            return [];
        });
        Functions\when('is_user_logged_in')->justReturn(true);
        $user = new \stdClass();
        $user->roles = ['wholesale'];
        Functions\when('wp_get_current_user')->justReturn($user);
        Functions\when('get_post_meta')->justReturn([]);

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
        $this->assertStringContainsString('15', $output);
        $this->assertStringContainsString('%', $output);
    }
}
