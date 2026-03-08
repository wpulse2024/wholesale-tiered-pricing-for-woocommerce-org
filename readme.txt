=== Wholesale & Tiered Pricing for WooCommerce ===
Contributors: wpulse, dasnitesh780, chadni54  
License: GPLv2 or later  
License URI: https://www.gnu.org/licenses/gpl-2.0.html  
Tags: woocommerce, wholesale, pricing, role-based, tiered pricing 
Requires at least: 4.5  
Tested up to: 6.9.1 
Stable tag: 1.1.1
Requires PHP: 7.1  

**Beautiful volume pricing tables with role-based discounts for wholesale, B2B & bulk sellers offering tiered pricing by quantity and user role.**

---

## Description

**Wholesale & Tiered Pricing for WooCommerce** is the ultimate solution for **B2B stores, wholesale shops, and bulk sellers**. This plugin lets you define **custom prices and quantity rules** for different user roles.

Show attractive **tiered pricing tables** on product pages and encourage bulk purchases by offering discounts based on role and quantity.

🎥 **Watch the Plugin Overview Video:** 

[youtube https://www.youtube.com/watch?v=Id6WGI4wKkY]

👉 *See how to create dynamic pricing rules in a minute!*

---

### 🔑 Key Benefits

* Increase conversions with clear, role-based discounts.  
* Reward wholesale customers with better pricing and bulk deals.  
* Set purchase limits per role to manage stock efficiently.  
* Fully customizable pricing tables to match your store’s design.

---

## Features

### ✅ Role-Based Pricing
* Different product prices per user role (e.g., Customer, Wholesale, Vendor).  
* Define **tiered prices** based on quantity ranges (e.g., Buy 2 for $20, Buy 4 for $40).  
* Supports custom roles created by membership or role management plugins.

### ✅ Role-Based Quantity Rules
* Minimum quantity per role (e.g., Wholesale must buy at least 10).  
* Maximum quantity per role (e.g., Customers can buy up to 5).  
* Step quantity rules (buy in multiples of 2, 5, etc.).

### ✅ Tiered Pricing Table Display
* Show a **pricing table** on product pages.  
* Role-specific table visibility (different tables for different roles).  
* Multiple pricing tables supported.  
* Customizable table design/layout.

---

## Upcoming Features 🚀

* Role-based shipping & payment method restrictions.  
* Shortcodes & Gutenberg blocks for pricing tables.  
* Import/export rules with CSV.  
* Elementor & Divi widget compatibility.

---

## Installation

1. Upload the plugin files to `/wp-content/plugins/`, or install via **WordPress → Plugins → Add New**.  
2. Activate through the **Plugins** screen.  
3. Go to **WooCommerce → Settings → Role Pricing** to configure.  
4. Edit a product and set **role-based pricing and quantity rules**.

---

## Frequently Asked Questions

**Q: Can I create different pricing for wholesale vs retail?**  
Yes, you can assign unique prices and discounts per user role.

**Q: Will this work with variable products?**  
Yes, it supports both simple and variable products.

**Q: Can I restrict purchases by quantity?**  
Yes, you can set **minimum, maximum, and step quantities per role**.

**Q: Does it support custom user roles?**  
Yes, any role created by a membership or user role plugin is supported.

---

## Screenshots

1. Role-based pricing setup in the product editor.  
2. Tiered pricing table displayed on product page.  
3. Quantity restriction messages in the cart.  
4. Global settings for pricing table display and customization.  
5. Options table template for pricing display.  
6. Plain text template for pricing display.  
7. Minimal table template for pricing display.  
8. Compact list template for pricing display.  
9. Dropdown template for pricing display.  
10. Horizontal table template for pricing display.

---

## Changelog

= 1.1.1 – 2026-03-08 =
- Fixed critical data corruption bug where JSON settings were mangled on save, silently breaking all pricing rules on every product page.
- Fixed admin-only AJAX handlers that were incorrectly registered as publicly accessible, exposing pricing rule reads and writes to unauthenticated users.
- Fixed pricing table not appearing for users matched by global rules due to role format mismatch between old single-role and new multi-role rule formats.
- Fixed variable product pricing table throwing a fatal `foreach` error when rules were stored as a JSON string instead of a decoded array.
- Fixed early-return bug in cart validation and price HTML filter that loaded global rules but then discarded them and returned without applying them.
- Fixed missing `return` after security error in savings calculation handler, allowing code execution to continue after an auth failure.
- Added nonce verification and capability check to product data save handler.
- Added `Show savings calculator` toggle in Template Options settings — admins can now enable or disable the savings calculator widget per store.
- Improved performance: added transient caching to the wholesale reports page (5-minute TTL) and pre-warm post meta cache to eliminate N+1 queries.
- Replaced unbounded `wc_get_products(limit: -1)` and `get_terms` calls in global settings with capped queries (limit: 200) to prevent timeouts on large stores.
- Moved activation hook registration to plugin load time (outside `plugins_loaded`) for correct WordPress lifecycle behavior.
- Renamed global `get_wp_user_roles` function to prefixed `whtprole_get_wp_user_roles` to avoid namespace collision with other plugins.
- Removed dead code: unused `find_applicable_tier()` and `getPrice()` methods, stray second class instantiation that caused all hooks to register twice, and commented-out `admin_head` block.

= 1.1.0 - 2026-02-10 =
- Adds Report Page
- Enhanced user experience with real-time price recalculation based on variation selection

= 1.0.8 – 2026-01-24 =
- Enhanced variable product support across all pricing table templates.
- Added dynamic variation filtering - pricing tables now show only tiers applicable to the selected variation.
- Improved pricing-table-view.php template to fully support variable products with variation-specific pricing.
- Enhanced pricing-table-view-compact-list.php template with variation name display and variable product compatibility.
- Added automatic price updates when customers select different variations on variable products.
- Improved tier filtering logic to show only relevant pricing tiers for the currently selected variation.
- Enhanced user experience with real-time price recalculation based on variation selection.

= 1.0.7 – 2026-01-23 =
- Added support for assigning tiered pricing rules to multiple user roles using a normalized roles array.
- Introduced a Global pricing option that applies to all user roles as a wildcard.
- Added an “Apply to Guest Users” option for Global rules to control pricing for non-logged-in users.
- Improved admin UI with a multi-select role selector for better flexibility and usability.
- Updated backend logic to maintain full backward compatibility with existing single-role pricing rules.
- Centralized rule applicability logic to ensure consistent pricing behavior across all roles.

= 1.0.6 – 2025-01-23 =
- Added Dynamic Savings Calculator - Real-time savings display that updates as customers change quantity, showing total savings and discount percentage.
- Improved pricing calculation logic with better edge case handling (negative price prevention, max_qty validation, improved tier matching).
- Enhanced performance and reliability of tiered pricing calculations.
- Fixed pricing calculation issues when max_qty constraints are set.

= 1.0.5 – 2025-11-10 =
- Added pricing rule for guest users.
- Added Option to show/hide pricing table on product page.

= 1.0.4 – 2025-11-07 =
- Added Multi language support for frontend.
- Fix some minor issues.

= 1.0.3 – 2025-11-01 =
- Added Admin order details now display the applied tier pricing rule beside each product line for better clarity.
- Added Discount notice now shows in the cart, checkout, and order details (frontend) pages.
- Improved Discount calculation now uses the actual/selling price instead of the regular price for more accurate savings.

= 1.0.2 – 22 October, 2025 =  
- Fixed undefined `price_type` key on cart and checkout page.  
- Fixed single product tiered pricing settings not saving properly.  
- Fixed pricing table not displaying on product page until general settings are saved.  

= 1.0.1 – 22 October, 2025 =  
- Bug fixes and minor improvements.  

= 1.0.0 – 22 October, 2025 =  
- Initial release with role-based pricing, tiered discounts, quantity rules, and customizable pricing tables.

---

## Upgrade Notice

### 1.0.0
First stable release of **Wholesale & Tiered Pricing for WooCommerce**.