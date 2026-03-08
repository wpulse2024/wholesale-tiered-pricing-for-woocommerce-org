<?php
if (!defined('ABSPATH')) exit;

class WHTPRole_Pricing_Helper
{
    // ---------------------------------------------------------------------------
    // Static cache — avoids re-fetching the same option on every method call
    // ---------------------------------------------------------------------------
    private static ?array $global_rules_cache    = null;
    private static ?array $general_settings_cache = null;

    // ---------------------------------------------------------------------------
    // Product / category validation
    // ---------------------------------------------------------------------------

    public function isValidProductToAppliedTieredPricing($product_id)
    {
        $globalProductSettings = get_option('whtprole_global_product_settings', []);
        $globalProductSettings = is_array($globalProductSettings) ? $globalProductSettings : json_decode($globalProductSettings, true);
        $include_products = !empty($globalProductSettings['include_products']) ? $globalProductSettings['include_products'] : [];
        $exclude_products = !empty($globalProductSettings['exclude_products']) ? $globalProductSettings['exclude_products'] : [];
        $apply_type       = !empty($globalProductSettings['apply_type']) ? $globalProductSettings['apply_type'] : 'include';

        if ($apply_type === 'include') {
            if (!empty($include_products) && !in_array($product_id, $include_products)) {
                return false;
            }
        } else {
            if (!empty($exclude_products) && in_array($product_id, $exclude_products)) {
                return false;
            }
        }
        return true;
    }

    public function isValidCategoryToAppliedTieredPricing($productId)
    {
        $productCategories   = wp_get_post_terms($productId, 'product_cat');
        $productCategoryIds  = array_map(function ($category) {
            return $category->term_id;
        }, $productCategories);

        // FIX [CRIT-02]: was 'whtprole_global_product_setting' (missing 's') — category
        // filtering was silently reading from the wrong option key and always returning true.
        $globalCategorySettings = get_option('whtprole_global_product_settings', []);
        $globalCategorySettings = is_array($globalCategorySettings) ? $globalCategorySettings : json_decode($globalCategorySettings, true);

        $include_categories = !empty($globalCategorySettings['include_categories']) ? $globalCategorySettings['include_categories'] : [];
        $exclude_categories = !empty($globalCategorySettings['exclude_categories']) ? $globalCategorySettings['exclude_categories'] : [];
        $apply_type         = !empty($globalCategorySettings['apply_type']) ? $globalCategorySettings['apply_type'] : 'include';

        if ($apply_type === 'include') {
            if (!empty($include_categories) && !array_intersect($include_categories, $productCategoryIds)) {
                return false;
            }
        } else {
            if (!empty($exclude_categories) && array_intersect($exclude_categories, $productCategoryIds)) {
                return false;
            }
        }
        return true;
    }

    // FIX [MED-03]: default was string 'yes'; json_decode('yes') → null which made
    // validation() always fail on fresh installs. Default is now [] (empty array).
    public function getGeneralSettings()
    {
        if (self::$general_settings_cache !== null) {
            return self::$general_settings_cache;
        }
        $raw = get_option('whtprole_pricing_save_general_settings', []);
        if (is_array($raw)) {
            self::$general_settings_cache = $raw;
        } else {
            $decoded = json_decode($raw, true);
            self::$general_settings_cache = is_array($decoded) ? $decoded : [];
        }
        return self::$general_settings_cache;
    }

    public function validation($product_id)
    {
        if (!$this->isValidProductToAppliedTieredPricing($product_id)) {
            return false;
        }
        if (!$this->isValidCategoryToAppliedTieredPricing($product_id)) {
            return false;
        }
        // FIX [MED-03]: only block when showTieredPricing is explicitly false.
        // Previously `if (!$this->getGeneralSettings())` blocked every fresh install
        // because an unconfigured option returned null.
        $settings = $this->getGeneralSettings();
        if (is_array($settings) && isset($settings['showTieredPricing']) && $settings['showTieredPricing'] === false) {
            return false;
        }
        return true;
    }

    public function enableToShowsTable($product_id)
    {
        $show_table     = get_post_meta($product_id, '_show_pricing_table', true);
        $globalSettings = $this->getGeneralSettings();

        if (!empty($show_table) && $show_table === 'no') {
            return false;
        }

        if (empty($show_table) && is_array($globalSettings) && isset($globalSettings['showTieredPricing']) && $globalSettings['showTieredPricing'] === false) {
            return false;
        }

        return true;
    }

    public function getTemplatePath()
    {
        $globalSettings = $this->getGeneralSettings();
        $template       = !empty($globalSettings['defaultTemplate']) ? $globalSettings['defaultTemplate'] : 'table';

        wp_enqueue_style(
            'wholesale-tiered-pricing-for-woocommerce',
            WHTPROLE_PRICING_PLUGIN_URL . 'plugin-assets/frontend.css',
            array(),
            WHTPROLE_PRICING_VERSION
        );

        $templates = apply_filters('whtprole_pricing_templates', [
            'table'        => WHTPROLE_PRICING_PLUGIN_PATH . 'templates/pricing-table-view.php',
            'compact_list' => WHTPROLE_PRICING_PLUGIN_PATH . 'templates/pricing-table-view-compact-list.php',
            'minimal_table' => WHTPROLE_PRICING_PLUGIN_PATH . 'templates/minimal-template.php',
            'plain_text'   => WHTPROLE_PRICING_PLUGIN_PATH . 'templates/plain-text-template.php',
            'options'      => WHTPROLE_PRICING_PLUGIN_PATH . 'templates/options-table.php',
        ]);

        // FIX [MED-04]: guard against unknown template key → PHP fatal on include_once(null).
        if (!isset($templates[$template])) {
            $template = 'table';
        }

        return $templates[$template];
    }

    public function calculationDiscount($regular_price, $tier)
    {
        $regular_price = floatval($regular_price);
        $discount_type = $tier['discount_type'] ?? '';
        $tier_price    = floatval($tier['price'] ?? 0);

        if ($discount_type === 'fixed') {
            $price           = $regular_price - $tier_price;
            $savings         = $regular_price - $price;
            $savings_percent = $regular_price > 0 ? ($savings / $regular_price) * 100 : 0;
        } elseif ($discount_type === 'percentage') {
            $price           = $regular_price - ($regular_price * $tier_price / 100);
            $savings         = $regular_price - $price;
            $savings_percent = $tier_price;
        } else {
            $price           = $tier_price;
            $savings         = $regular_price - $tier_price;
            $savings_percent = $regular_price > 0 ? ($savings / $regular_price) * 100 : 0;
        }

        $price   = max(0, $price);
        $savings = max(0, $savings);

        return [
            'price'           => $price,
            'savings'         => $savings,
            'savings_percent' => $savings_percent,
        ];
    }

    public function getTieredFeatured($tiered_pricing, $regular_price)
    {
        $discounted_tier = 0;
        // FIX [LOW-05]: was 1 — any tier with <1% discount was never selected.
        $discountAmount  = 0;
        foreach ($tiered_pricing as $index => $tier) {
            $discount        = $this->calculationDiscount($regular_price, $tier);
            $savings_percent = $discount['savings_percent'];
            if ($savings_percent > $discountAmount) {
                $discounted_tier = $index;
                $discountAmount  = $savings_percent;
            }
        }
        return $discounted_tier;
    }

    // ---------------------------------------------------------------------------
    // Role normalisation helpers
    // ---------------------------------------------------------------------------

    /**
     * Normalise rule roles to always return a plain array of role slugs.
     * Supports both legacy string format and new array format.
     *
     * @param  array|string $rule_roles
     * @return array
     */
    public static function normalize_rule_roles($rule_roles)
    {
        if (empty($rule_roles)) {
            return [];
        }
        if (is_array($rule_roles)) {
            return array_values(array_filter($rule_roles));
        }
        if (is_string($rule_roles)) {
            return [$rule_roles];
        }
        return [];
    }

    /**
     * Check whether a rule applies to the current user.
     *
     * @param  array|string $rule_roles
     * @param  string       $current_user_role
     * @param  bool         $is_guest
     * @param  bool         $also_for_guest
     * @return bool
     */
    public static function rule_applies_to_user($rule_roles, $current_user_role, $is_guest = false, $also_for_guest = false)
    {
        $roles = self::normalize_rule_roles($rule_roles);

        if (empty($roles)) {
            return false;
        }

        // 'guest' or 'global' in roles means wildcard for all logged-in users
        if (in_array('guest', $roles, true) || in_array('global', $roles, true)) {
            if (!$is_guest) {
                return true;
            }
            return $is_guest && $also_for_guest;
        }

        if ($is_guest) {
            return in_array('guest', $roles, true);
        }

        return in_array($current_user_role, $roles, true);
    }

    // ---------------------------------------------------------------------------
    // Static utility methods (DUP-01 … DUP-05, PERF-01)
    // These replace the four identical private copies scattered across classes.
    // ---------------------------------------------------------------------------

    /**
     * [DUP-01] Return the current user's primary role.
     * Returns 'guest' for non-logged-in visitors.
     */
    public static function get_current_user_role(): string
    {
        if (!is_user_logged_in()) {
            return 'guest';
        }
        $user = wp_get_current_user();
        return !empty($user->roles) ? $user->roles[0] : 'customer';
    }

    /**
     * [DUP-03] Return the parent product ID for variations, or the product's own ID
     * for simple / variable products.
     */
    public static function get_parent_product_id(WC_Product $product): int
    {
        return $product->is_type('variation') ? $product->get_parent_id() : $product->get_id();
    }

    /**
     * [DUP-04 / PERF-01] Return pricing rules for a product, with automatic global-rule
     * fallback. Results are NOT cached here because post_meta can change per-request;
     * caller is responsible for caching if needed.
     *
     * @return array  Always an array (empty when no rules found).
     */
    public static function get_rules_for_product(int $parent_id): array
    {
        $rules = get_post_meta($parent_id, '_role_pricing_rules', true);

        if (empty($rules)) {
            $rules = get_option('whtprole_pricing_global_rules', []);
        }

        if (empty($rules)) {
            return [];
        }

        if (!is_array($rules)) {
            $rules = json_decode($rules, true);
        }

        if (!is_array($rules)) {
            return [];
        }

        // Filter out rules that are outside their scheduled date window.
        $today = gmdate('Y-m-d');
        $rules = array_values(array_filter($rules, function ($rule) use ($today) {
            if (!empty($rule['date_from']) && $today < $rule['date_from']) {
                return false; // Rule hasn't started yet
            }
            if (!empty($rule['date_to']) && $today > $rule['date_to']) {
                return false; // Rule has expired
            }
            return true;
        }));

        return $rules;
    }

    /**
     * [DUP-05] Return true when a tier should be applied to the given variation.
     * Handles both the new single-variation field and the legacy variations-array field.
     */
    public static function tier_applies_to_variation(array $tier, int $variation_id): bool
    {
        // New format: single 'variation' field
        if (array_key_exists('variation', $tier)) {
            $v = $tier['variation'];
            if ($v === null || $v === '' || $v === 'all') {
                return true; // applies to all variations
            }
            return intval($v) === $variation_id;
        }

        // Legacy format: 'variations' array
        if (isset($tier['variations']) && is_array($tier['variations'])) {
            if (empty($tier['variations']) || in_array('all', $tier['variations'], true)) {
                return true;
            }
            return in_array($variation_id, $tier['variations'], true);
        }

        // No filter set — applies to all
        return true;
    }

    /**
     * [DUP-02] Canonical price calculation used by all classes.
     * Replaces three near-identical private calculate_price() implementations.
     *
     * @param  float    $base_price
     * @param  array    $rule
     * @param  int      $quantity
     * @param  int|null $variation_id  Pass null for non-variation products.
     * @return float
     */
    public static function calculate_price(float $base_price, array $rule, int $quantity, ?int $variation_id = null): float
    {
        if ($base_price <= 0) {
            return $base_price;
        }
        if ($quantity <= 0) {
            $quantity = 1;
        }

        if (empty($rule['tiered_pricing']) || !is_array($rule['tiered_pricing'])) {
            return $base_price;
        }

        // Sort descending by min_qty to find the highest applicable tier first
        $tiers = $rule['tiered_pricing'];
        usort($tiers, function ($a, $b) {
            return intval($b['min_qty'] ?? 0) - intval($a['min_qty'] ?? 0);
        });

        $valid_discount_types = ['fixed', 'percentage'];

        foreach ($tiers as $tier) {
            // Skip tiers that don't apply to this variation
            if ($variation_id !== null && !self::tier_applies_to_variation($tier, $variation_id)) {
                continue;
            }

            $tier_min_qty = intval($tier['min_qty'] ?? 0);
            $tier_price   = floatval($tier['price'] ?? 0);

            if ($tier_min_qty <= 0 || $tier_price <= 0) {
                continue;
            }

            if ($quantity < $tier_min_qty) {
                continue;
            }

            // Respect optional max_qty cap
            if (!empty($tier['max_qty']) && $quantity > intval($tier['max_qty'])) {
                continue;
            }

            $discount_type = isset($tier['discount_type']) && in_array($tier['discount_type'], $valid_discount_types, true)
                ? $tier['discount_type']
                : 'fixed';

            switch ($discount_type) {
                case 'percentage':
                    return max(0.0, $base_price - ($base_price * $tier_price / 100));
                case 'fixed':
                    return max(0.0, $base_price - $tier_price);
            }
        }

        return $base_price;
    }
}
