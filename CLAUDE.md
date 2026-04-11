# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Build Commands

```bash
npm run dev          # Development build (single run)
npm run watch        # Development build with file watching
npm run prod         # Production build (minified)
```

Built assets go to `plugin-assets/`. Always run a build after modifying files in `resources/`.

## Architecture Overview

**WordPress/WooCommerce plugin** implementing role-based wholesale pricing and volume tiered discounts for B2B stores.

### PHP Class Responsibilities

| Class | File | Purpose |
|---|---|---|
| `WHTPRole_Based_Pricing` | Main plugin file | Singleton entry point, activation hook (creates DB table), registers all class instances |
| `WHTPRole_Admin` | `includes/class-admin.php` | Adds "Role Pricing" tab to WooCommerce product editor |
| `WHTPRole_Frontend` | `includes/class-frontend.php` | Pricing table display, quantity validation, MOV notices, savings calculator |
| `WHTPRole_Pricing_Engine` | `includes/class-pricing.php` | Core: hooks `woocommerce_get_price_html`, `woocommerce_product_variation_get_price`, `woocommerce_before_calculate_totals` |
| `WHTPRole_Ajax` | `includes/class-ajax.php` | All `wp_ajax_*` handlers (admin and public) |
| `WHTPRole_Global_Settings` | `includes/class-global-settings.php` | Adds "Tiered Pricing" tab to WooCommerce → Settings |
| `WHTPRole_Wholesale_Menu` | `includes/class-wholesale-menu.php` | Admin menu, reports page, stores customer role on order creation |
| `WHTPRole_Shows_Message` | `includes/class-shows-message.php` | Shows "Applied Tier" discount badge in cart, order details, admin order |
| `WHTPRole_Helper` | `includes/helper/class-helper.php` | Shared utilities: `calculate_price()`, `rule_applies_to_user()`, `get_pricing_rules()`, template resolution |

### Data Storage

- **Per-product rules:** `wp_postmeta` key `_whtprole_pricing_rules` (JSON array of rule objects)
- **Global rules:** `wp_options` key `whtprole_global_pricing_rules`
- **Plugin settings:** `wp_options` key `whtprole_general_settings`
- Custom table `wp_whtprole_pricing` was created on activation but is legacy/unused

### Pricing Rule Structure

Each rule object contains:
```json
{
  "roles": ["wholesale", "retailer"],   // array (new) or string (legacy single role)
  "also_for_guest": false,
  "variation": "attribute_pa_size:large",
  "tiered_pricing": [
    { "min_qty": 1, "max_qty": 10, "price": "15.00", "discount_type": "fixed" },
    { "min_qty": 11, "max_qty": "", "price": "20", "discount_type": "percentage" }
  ],
  "min_order_value": 100,
  "date_from": "2025-01-01",
  "date_to": "2025-12-31"
}
```

Special role values: `"guest"` (non-logged-in users), `"global"` (all logged-in users).

### Frontend (Vue 3 Admin App)

- Entry: `resources/admin/js/app.js` → compiled to `plugin-assets/admin/app.js`
- Pages: Settings, Pricing (global rules), Product (per-product rules), Documentation
- Uses Element Plus UI, Vuex 4 for state, Axios for AJAX calls to `wp_ajax_*` handlers
- Product editor JS (non-Vue): `resources/admin.js`
- Frontend product page JS: `resources/frontend.js`

### Pricing Templates

Six display templates in `templates/`:
- `pricing-table-view.php` (Table)
- `options-table.php` (Options)
- `minimal-template.php` (Minimal Table)
- `pricing-table-view-compact-list.php` (Compact List)
- `plain-text-template.php` (Plain Text)
- Horizontal (inline variant of table)

Custom templates can be registered via the `whtprole_pricing_templates` filter.

### Key Patterns

**Role format backward compatibility:** Helper normalizes both legacy single-role string `"wholesale"` and new multi-role array `["wholesale", "retailer"]` formats. Always use `WHTPRole_Helper::get_user_roles()` and `rule_applies_to_user()` rather than checking roles directly.

**Price calculation flow:**
1. `WHTPRole_Pricing_Engine` hooks WooCommerce price filters
2. Delegates to `WHTPRole_Helper::calculate_price()` with product, quantity, and user role
3. Helper fetches rules (product-level first, falls back to global), filters by schedule/variation, applies tier matching

**AJAX security:** Admin handlers require `manage_woocommerce` capability and nonce. Public handlers require nonce only. Admin-only handlers must NOT be registered with `wp_ajax_nopriv_`.

**JSON storage:** Always use `wp_json_encode()` (not `json_encode()`) when storing rules to avoid corruption.

**Performance:** Reports page uses 5-minute transient caching. Product catalog queries are capped at 200 results.
