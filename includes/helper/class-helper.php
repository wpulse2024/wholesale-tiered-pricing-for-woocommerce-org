<?php
if (!defined('ABSPATH')) exit;

class WHTPRole_Pricing_Helper
{
    public function isValidProductToAppliedTieredPricing($product_id)
    {
        $globalProductSettings = get_option('whtprole_global_product_settings', []);
        $globalProductSettings = is_array($globalProductSettings) ? $globalProductSettings : json_decode($globalProductSettings, true);
        $include_products = !empty($globalProductSettings['include_products']) ? $globalProductSettings['include_products'] : [];
        $exclude_products = !empty($globalProductSettings['exclude_products']) ? $globalProductSettings['exclude_products'] : [];
        $apply_type = !empty($globalProductSettings['apply_type']) ? $globalProductSettings['apply_type'] : 'include';
        
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
        $productCategories = wp_get_post_terms($productId, 'product_cat');
        $productCategoryIds = array_map(function($category) {
            return $category->term_id;
        }, $productCategories);
        $globalCategorySettings = get_option('whtprole_global_product_setting', []);
        $globalCategorySettings = is_array($globalCategorySettings) ? $globalCategorySettings : json_decode($globalCategorySettings, true);
        $include_categories = !empty($globalCategorySettings['include_categories']) ? $globalCategorySettings['include_categories'] : [];
        $exclude_categories = !empty($globalCategorySettings['exclude_categories']) ? $globalCategorySettings['exclude_categories'] : [];
        $apply_type = !empty($globalCategorySettings['apply_type']) ? $globalCategorySettings['apply_type'] : 'include';
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

    public function getGeneralSettings() {
        $enableGlobalRules = get_option('whtprole_pricing_save_general_settings', 'yes');
        $generalSettings = is_array($enableGlobalRules) ? $enableGlobalRules : json_decode($enableGlobalRules, true);
        return $generalSettings;
    }

    public function validation($product_id) {
        if (!$this->isValidProductToAppliedTieredPricing($product_id)) {
            return false;
        }
        if (!$this->isValidCategoryToAppliedTieredPricing($product_id)) {
            return false;
        }
        if (!$this->getGeneralSettings()) {
            return false;
        }
        return true;
    }

    public function enableToShowsTable($product_id) {
        $show_table = get_post_meta($product_id, '_show_pricing_table', true);
        $globalSettings = $this->getGeneralSettings();
        if(!empty($show_table) && $show_table == 'no') {
            return false;
        }

        if(empty($show_table) && !empty($globalSettings) && $globalSettings['showTieredPricing'] == false) {
            return false;
        }

        return true;
    }

    public function getTemplatePath() {
        $globalSettings = $this->getGeneralSettings();
        $template = !empty($globalSettings['defaultTemplate']) ? $globalSettings['defaultTemplate'] : 'table';
        wp_enqueue_style(
            'wholesale-tiered-pricing-for-woocommerce', 
            WHTPROLE_PRICING_PLUGIN_URL . 'plugin-assets/frontend.css', 
            array(), 
            WHTPROLE_PRICING_VERSION
        );
        
        $templates = apply_filters('whtprole_pricing_templates', [
            'table' => WHTPROLE_PRICING_PLUGIN_PATH . 'templates/pricing-table-view.php',
            'compact_list' => WHTPROLE_PRICING_PLUGIN_PATH . 'templates/pricing-table-view-compact-list.php',
            'minimal_table' => WHTPROLE_PRICING_PLUGIN_PATH . 'templates/minimal-template.php',
            'plain_text' => WHTPROLE_PRICING_PLUGIN_PATH . 'templates/plain-text-template.php',
            'options' => WHTPROLE_PRICING_PLUGIN_PATH . 'templates/options-table.php',
        ]);
        return $templates[$template];
    }

    public function calculationDiscount($regular_price, $tier) {
        // Ensure both values are floats to prevent type errors
        $regular_price = floatval($regular_price);
        $discount_type = $tier['discount_type'] ?? '';
        $tier_price = floatval($tier['price'] ?? 0);

        if ($discount_type === 'fixed') {
            $price = $regular_price - $tier_price;
            $savings = $regular_price - $price;
            $savings_percent = $regular_price > 0 ? ($savings / $regular_price) * 100 : 0;
        } else if($discount_type === 'percentage') {
            $price = $regular_price - ($regular_price * $tier_price / 100);
            $savings = $regular_price - $price;
            $savings_percent = $tier_price;
        } else {
            $price = $tier_price;
            $savings = $regular_price - $tier_price;
            $savings_percent = $regular_price > 0 ? ($savings / $regular_price) * 100 : 0;
        }

        // Ensure we don't return negative values
        $price = max(0, $price);
        $savings = max(0, $savings);

        return [
            'price' => $price,
            'savings' => $savings,
            'savings_percent' => $savings_percent,
        ];
    }

    public function getTieredFeatured($tiered_pricing, $regular_price) {
        // return the most discounted tier and return index
        $discounted_tier = 0;
        $discountAmount = 1;
        foreach ($tiered_pricing as $index => $tier) {
            $discount = $this->calculationDiscount($regular_price, $tier);
            $savings_percent = $discount['savings_percent'];
            if ($savings_percent > $discountAmount) {
                $discounted_tier = $index;
                $discountAmount = $savings_percent;
            }
        }
        return $discounted_tier;
    }
}