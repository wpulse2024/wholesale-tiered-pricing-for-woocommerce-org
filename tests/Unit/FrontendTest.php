<?php
declare(strict_types=1);

namespace Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use WHTPRole_Pricing_Frontend;

class FrontendTest extends TestCase
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

    public function test_constructor_registers_product_page_hooks(): void
    {
        Functions\expect('add_action')
            ->atLeast()->once()
            ->andReturn(true);
        Functions\expect('add_filter')
            ->atLeast()->once()
            ->andReturn(true);

        new WHTPRole_Pricing_Frontend();

        $this->addToAssertionCount(1);
    }
}
