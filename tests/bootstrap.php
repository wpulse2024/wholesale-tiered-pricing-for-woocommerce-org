<?php
declare(strict_types=1);

// Required by every plugin file before any class is autoloaded.
define('ABSPATH', dirname(__DIR__) . '/');
define('WPINC', 'wp-includes');

// WooCommerce class stubs — WHTPRole_Pricing_Helper type-hints WC_Product.
// These stubs satisfy PHP's type system without requiring a WooCommerce install.
if (!class_exists('WC_Product')) {
    class WC_Product {
        public function is_type(string $type): bool { return false; }
        public function get_parent_id(): int { return 0; }
        public function get_id(): int { return 0; }
    }
}

require_once dirname(__DIR__) . '/vendor/autoload.php';
