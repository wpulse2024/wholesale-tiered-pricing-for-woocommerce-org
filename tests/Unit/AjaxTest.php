<?php
declare(strict_types=1);

namespace Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use WHTPRole_Pricing_Ajax;

class AjaxTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        // Constructor calls add_action/add_filter — stub them out.
        Functions\when('add_action')->justReturn(null);
        Functions\when('add_filter')->justReturn(null);
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function test_require_admin_capability_calls_current_user_can(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('manage_woocommerce')
            ->andReturn(true);

        $ajax = new WHTPRole_Pricing_Ajax();
        $ref  = new \ReflectionMethod($ajax, 'require_admin_capability');
        $ref->setAccessible(true);
        $ref->invoke($ajax); // No exception — capability granted.

        $this->addToAssertionCount(1); // Explicit assertion: no exception thrown.
    }

    public function test_require_admin_capability_blocks_unauthorized_user(): void
    {
        Functions\expect('current_user_can')
            ->once()
            ->with('manage_woocommerce')
            ->andReturn(false);

        // Mock wp_send_json_error to throw instead of echoing JSON + exit.
        Functions\when('wp_send_json_error')->alias(function () {
            throw new \RuntimeException('Unauthorized');
        });

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unauthorized');

        $ajax = new WHTPRole_Pricing_Ajax();
        $ref  = new \ReflectionMethod($ajax, 'require_admin_capability');
        $ref->setAccessible(true);
        $ref->invoke($ajax);
    }
}
