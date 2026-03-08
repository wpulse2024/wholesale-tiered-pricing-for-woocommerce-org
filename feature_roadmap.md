# Feature Roadmap: Wholesale & Tiered Pricing for WooCommerce
**Version:** 1.1.1 | **Date:** 2026-03-08 | **Status:** Planning

This document lists potential features grouped by theme and priority.
Each item includes: what it is, why it fits the plugin, implementation notes, and effort estimate.

---

## QUICK REFERENCE — FEATURE COUNTS

| Group | Features | Priority |
|-------|----------|----------|
| Pricing & Discounts | 7 | High |
| Catalog & Visibility | 4 | High |
| Checkout & Orders | 5 | High |
| Admin & UX | 6 | Medium |
| Reporting & Analytics | 4 | Medium |
| Integrations | 6 | Low–Medium |
| Developer & Headless | 3 | Low |

---

# GROUP 1 — PRICING & DISCOUNTS

These directly extend the core value proposition of the plugin.

---

## [FEAT-01] Scheduled / Flash Pricing
**Priority:** HIGH | **Effort:** Medium

**What:**
Allow admins to set a start and end date on any pricing rule. The rule only applies
within that window, enabling time-limited wholesale deals or flash sales for specific roles.

**Why it fits:**
Wholesale buyers expect seasonal volume deals (end-of-quarter clearances, Black Friday wholesale
windows). This is a top request in competitor plugins (Wholesale Suite, Discount Rules for WC).

**Implementation notes:**
- Add `date_from` / `date_to` fields to the rule object in the product editor and global rules Vue app.
- Filter out rules where `current time < date_from || current time > date_to` inside
  `WHTPRole_Pricing_Helper::get_rules_for_product()` before returning.
- Add a WP-Cron job or use transients to invalidate cached rules when a schedule starts/ends.

---

## [FEAT-02] Minimum Order Value (MOV) per Role
**Priority:** HIGH | **Effort:** Small

**What:**
Set a minimum cart total (in addition to, or instead of, minimum quantity) before a role's
pricing rule activates. E.g. "Wholesale price only if order is at least $200".

**Why it fits:**
Protects margin. Many B2B stores use MOV, not just MOQ. Currently the plugin only supports
minimum quantity.

**Implementation notes:**
- Add `min_order_value` field to the rule in the product editor.
- Check it in `validate_add_to_cart()` and in `update_cart_prices()` using `WC()->cart->get_subtotal()`.
- Show a notice to the customer ("Add $X more to unlock wholesale pricing") via `woocommerce_before_cart`.

---

## [FEAT-03] Cart-Level Bulk Discount
**Priority:** HIGH | **Effort:** Medium

**What:**
Apply a discount to the entire cart (not per-product) when the cart total or total quantity
crosses a threshold for a given role. E.g. "Buy 50 items total, get 10% off everything".

**Why it fits:**
Wholesale stores often want cross-product bulk incentives, not just per-product tiers.
WooCommerce has no native hook for this — it's a clear gap this plugin can fill.

**Implementation notes:**
- Add a "Cart Rules" section in the global settings Vue app (separate from product rules).
- Apply the discount as a negative fee using `woocommerce_cart_calculate_fees`.
- Store cart rules in a new `whtprole_cart_rules` option.

---

## [FEAT-04] Buy X Get Y (BOGO) Pricing Tiers
**Priority:** MEDIUM | **Effort:** Large

**What:**
Instead of (or alongside) percentage/fixed discounts, allow a tier rule to give free units.
E.g. "Buy 10, get 2 free". The free units are added to the cart automatically.

**Why it fits:**
Physical wholesale stores use BOGO deals heavily. WooCommerce Smart Coupons charges extra for this.
Bundling it here differentiates the plugin.

**Implementation notes:**
- Add `discount_type: 'bogo'` and `free_qty` fields to the tier data model.
- Handle in `woocommerce_add_to_cart` / `woocommerce_before_calculate_totals`.
- Track free items with a cart item meta flag so they can't be removed independently.

---

## [FEAT-05] Customer-Specific Price Overrides
**Priority:** MEDIUM | **Effort:** Medium

**What:**
Let admins set a custom price for a specific WordPress user ID, overriding role-based pricing.
Useful for VIP accounts or negotiated contracts.

**Why it fits:**
Role-based pricing works for groups; this handles exceptions. Common in B2B CRMs.

**Implementation notes:**
- Add a "User Overrides" meta box on the WP User edit screen.
- Store overrides in user meta: `_whtprole_price_overrides` as `[ product_id => price ]`.
- In price resolution, check user meta first before falling back to role rules.

---

## [FEAT-06] Tiered Shipping Rates by Role
**Priority:** MEDIUM | **Effort:** Large

**What:**
Define shipping cost rules per role and quantity tier. E.g. "Wholesale customers get free shipping
on 20+ items; flat $5 on fewer".

**Why it fits:**
Shipping is a major negotiation point in B2B. The plugin already controls price and quantity —
shipping is the natural next step. Listed in the existing "Upcoming Features" section of readme.

**Implementation notes:**
- Register a custom WooCommerce shipping method class (`WC_Shipping_Method`).
- Rate calculation reads the active role's shipping tiers from global settings.
- Add a shipping tiers section to the global settings Vue app.

---

## [FEAT-07] Rule Priority / Conflict Resolution
**Priority:** HIGH | **Effort:** Small

**What:**
When a user matches multiple rules (e.g. a role rule AND a global rule), let the admin control
which one wins — highest priority, lowest price, or first match.

**Why it fits:**
Currently the plugin uses "first match wins" implicitly. This becomes unpredictable as rule
count grows. Admins need explicit control.

**Implementation notes:**
- Add a `priority` integer field to each rule.
- Sort rules by priority descending in `get_rules_for_product()` before iteration.
- Add a conflict mode setting in general settings: "Highest priority", "Lowest price", "Highest price".

---

# GROUP 2 — CATALOG & VISIBILITY

Control what wholesale customers see, not just what they pay.

---

## [FEAT-08] Role-Based Product Visibility
**Priority:** HIGH | **Effort:** Medium

**What:**
Hide specific products or entire categories from certain roles. E.g. retail-only products
invisible to wholesale buyers, or wholesale-only products hidden from guests.

**Why it fits:**
B2B stores frequently segment catalogs by role. This is requested alongside pricing rules
by almost every wholesale plugin user.

**Implementation notes:**
- Add a "Visibility" tab on the product data panel (alongside the existing "Role Pricing" tab).
- Filter `pre_get_posts` and `woocommerce_product_is_visible` to exclude hidden products.
- Store visibility rules in post meta: `_whtprole_visibility_rules`.

---

## [FEAT-09] Role-Based Payment Method Restrictions
**Priority:** MEDIUM | **Effort:** Small

**What:**
Show or hide specific payment gateways based on the current user's role. E.g. wholesale
customers can pay by invoice (BACS), while guests are card-only.

**Why it fits:**
Listed in the existing "Upcoming Features" section of the readme.

**Implementation notes:**
- Filter `woocommerce_available_payment_gateways`.
- Add a "Payment Methods" section in global settings — a matrix of role × gateway checkboxes.

---

## [FEAT-10] Role-Based Catalog Pricing Display
**Priority:** MEDIUM | **Effort:** Small

**What:**
Option to hide prices entirely from guests or specific roles, replacing the price with a
"Login to see pricing" or "Contact us for a quote" message.

**Why it fits:**
Common pattern for wholesale stores that don't want retail pricing visible publicly.

**Implementation notes:**
- Filter `woocommerce_get_price_html` to return a custom message string for configured roles.
- Add per-role toggle in global settings: "Show price" / "Hide price" / "Show login prompt".
- Also hide the Add to Cart button with `woocommerce_is_purchasable` for hidden-price roles.

---

## [FEAT-11] Role-Based Tax Exemption
**Priority:** MEDIUM | **Effort:** Small

**What:**
Mark certain roles as tax-exempt. When a tax-exempt user shops, WooCommerce removes tax
from their cart automatically.

**Why it fits:**
Many wholesale and B2B buyers are registered businesses that legally should not pay VAT/GST.
This is a manual workaround for most stores currently.

**Implementation notes:**
- Filter `woocommerce_customer_taxable_address` or set `WC()->customer->set_is_vat_exempt(true)`.
- Add "Tax exempt roles" multi-select in global settings.

---

# GROUP 3 — CHECKOUT & ORDERS

Features that affect the purchasing and order flow.

---

## [FEAT-12] Wholesale Account Application Form
**Priority:** HIGH | **Effort:** Large

**What:**
A front-end form where customers can apply for a wholesale account. The admin reviews and
approves/rejects applications, automatically assigning the wholesale role on approval.

**Why it fits:**
The plugin manages wholesale pricing but has no way to onboard wholesale buyers. Admins
currently have to manually assign roles. Automating this closes the loop.

**Implementation notes:**
- Register a shortcode `[whtprole_apply_form]` that renders an application form.
- Store applications as a custom post type `whtprole_application`.
- On approval, use `wp_update_user()` to assign the wholesale role.
- Send approval/rejection email notifications using `wp_mail()`.

---

## [FEAT-13] CSV Import / Export of Pricing Rules
**Priority:** HIGH | **Effort:** Medium

**What:**
Export all product pricing rules and global rules to a CSV file. Import from CSV to bulk-set
rules across many products at once.

**Why it fits:**
Listed in the existing "Upcoming Features" section of the readme. Stores with hundreds of
products cannot set rules one by one.

**Implementation notes:**
- Add an "Import / Export" sub-page under the Wholesale admin menu.
- Export: `WP_Query` all products with `_role_pricing_rules`, map to CSV rows.
- Import: Parse uploaded CSV, validate, and call `update_post_meta()` per row.
- Use WordPress's built-in `WP_Background_Process` (or Action Scheduler) for large imports.

---

## [FEAT-14] Quote Request / Request for Pricing
**Priority:** MEDIUM | **Effort:** Large

**What:**
Let users add products to a "quote cart" instead of buying directly. The admin receives
the quote request and can reply with custom pricing.

**Why it fits:**
High-volume B2B deals are often negotiated, not bought at listed price. This bridges the
plugin from self-service wholesale to fully managed B2B.

**Implementation notes:**
- Add a "Request Quote" button alongside Add to Cart (conditional on role or product setting).
- Store quote carts as a custom post type `whtprole_quote`.
- Admin can convert a quote to an order via a "Create Order" button on the quote edit screen.

---

## [FEAT-15] Role-Based Free Shipping Threshold
**Priority:** MEDIUM | **Effort:** Small

**What:**
Set a different free-shipping minimum order value per role. E.g. "Wholesale: free shipping
over $100; Retail: free shipping over $50".

**Why it fits:**
WooCommerce's built-in free shipping has a single global threshold. This makes it role-aware.

**Implementation notes:**
- Filter `woocommerce_shipping_free_shipping_is_available`.
- Read the role's threshold from global settings and compare with `WC()->cart->get_subtotal()`.

---

## [FEAT-16] Reorder from Previous Wholesale Order
**Priority:** LOW | **Effort:** Small

**What:**
On the My Account → Orders page, add a "Reorder" button that re-adds all items from a
previous wholesale order to the cart at the same wholesale price.

**Why it fits:**
Wholesale buyers often reorder the same products. One-click reorder reduces friction and
increases repeat purchase rate.

**Implementation notes:**
- Hook into `woocommerce_my_account_my_orders_actions`.
- On reorder, add items via `WC()->cart->add_to_cart()` — pricing rules apply automatically.

---

# GROUP 4 — ADMIN & UX

Improvements to the admin editing experience.

---

## [FEAT-17] Bulk Rule Assignment via Product List
**Priority:** HIGH | **Effort:** Medium

**What:**
Add a bulk action to the WooCommerce Products list: "Apply pricing rule". Admin selects
multiple products, picks a rule template, and applies it to all selected products at once.

**Why it fits:**
Currently rules can only be set one product at a time. Stores with many products need bulk tooling.

**Implementation notes:**
- Register a custom bulk action via `bulk_actions-edit-product`.
- Show a modal (using WP admin modal or inline JS) to select or create a rule template.
- Apply using `update_post_meta()` in a loop; for large sets use Action Scheduler.

---

## [FEAT-18] Rule Templates (Saved Rule Presets)
**Priority:** MEDIUM | **Effort:** Medium

**What:**
Let admins save a pricing rule configuration as a named template (e.g. "Standard Wholesale",
"VIP Tier"). When creating a rule on a product, they can load from a template instead of
configuring tiers from scratch.

**Why it fits:**
Reduces repetitive configuration. Pairs naturally with FEAT-17 (bulk assignment).

**Implementation notes:**
- Store templates in a `whtprole_rule_templates` option as a JSON array.
- Add a "Templates" tab in the global settings Vue app.
- On the product editor, add a "Load from template" dropdown above the Add Rule button.

---

## [FEAT-19] Pricing Rule Preview (Live Price Simulator)
**Priority:** MEDIUM | **Effort:** Medium

**What:**
In the product editor, after setting up tiers, show a live preview table of what each role
will pay at each quantity level, without leaving the page.

**Why it fits:**
Admins currently have to save, then view the product as a wholesale customer, to verify
their rules look right. A live preview reduces this friction.

**Implementation notes:**
- Add a "Preview" panel below the tier rows using Alpine.js or vanilla JS (no Vue build needed).
- Read tier data directly from the form fields in real time.
- Display a matrix: rows = roles, columns = quantity breakpoints, cells = calculated price.

---

## [FEAT-20] Wholesale Customer Dashboard (My Account Tab)
**Priority:** MEDIUM | **Effort:** Medium

**What:**
Add a "Wholesale" tab to WooCommerce My Account for users with wholesale roles. Shows their
current role tier, available discounts, and a link to apply for a higher tier.

**Why it fits:**
Gives wholesale buyers visibility into their pricing benefits, increasing loyalty and engagement.

**Implementation notes:**
- Register a custom My Account endpoint via `woocommerce_account_menu_items` and
  `woocommerce_account_{endpoint}_endpoint`.
- Template file at `templates/my-account/wholesale-dashboard.php`.

---

## [FEAT-21] Duplicate Rule across Products
**Priority:** LOW | **Effort:** Small

**What:**
On the product editor "Role Pricing" tab, add a "Copy rule to other products" button.
Admin selects target products from a searchable list and the current rule is copied to each.

**Why it fits:**
Quicker than bulk assignment when copying a specific existing rule from one product to several others.

**Implementation notes:**
- Add a button that opens a modal with a Select2 product search.
- On confirm, AJAX call that reads the source product's `_role_pricing_rules` and writes to targets.

---

## [FEAT-22] Dark Mode / Accessible Color Theming for Pricing Tables
**Priority:** LOW | **Effort:** Small

**What:**
Honour the user's OS dark-mode preference (`prefers-color-scheme: dark`) in the front-end
pricing table CSS. Also add a contrast-ratio check on the active pricing colour setting.

**Why it fits:**
Accessibility compliance (WCAG 2.1 AA) is increasingly expected. The current table uses
inline colour variables that do not adapt to dark mode.

**Implementation notes:**
- Add `@media (prefers-color-scheme: dark)` overrides in `resources/scss/` files.
- In `Settings.vue`, warn if the selected `activePricingColor` has insufficient contrast against white.

---

# GROUP 5 — REPORTING & ANALYTICS

Extend the wholesale reports page.

---

## [FEAT-23] CSV Export from Reports Page
**Priority:** HIGH | **Effort:** Small

**What:**
Add an "Export CSV" button on the Wholesale → Reports page that downloads the currently
displayed data (orders by role, top products, recent orders) as a CSV file.

**Why it fits:**
Admins need to share wholesale reports with finance teams who work in spreadsheets, not WP admin.

**Implementation notes:**
- Add an AJAX handler `whtprole_export_report_csv` that calls `get_report_data()` and outputs
  headers + `fputcsv()` rows with `Content-Disposition: attachment`.
- Add the button to the `report-page.php` template, linking to the AJAX export URL with nonce.

---

## [FEAT-24] Charts on Reports Page
**Priority:** MEDIUM | **Effort:** Medium

**What:**
Add line/bar charts to the reports page: wholesale revenue over time, top roles by order count,
top products by revenue. Rendered client-side using Chart.js (already available in WP admin via
WooCommerce's existing bundle or a small CDN include).

**Why it fits:**
The current reports page is tables only. Visual charts make trends immediately obvious.

**Implementation notes:**
- Extend `get_report_data()` to return daily revenue data series for the date range.
- Render charts in `report-page.php` using `<canvas>` and inline Chart.js initialization.
- Keep Chart.js optional — degrade gracefully to tables if JS is disabled.

---

## [FEAT-25] Wholesale Revenue Goal / Target Tracking
**Priority:** LOW | **Effort:** Small

**What:**
Let the admin set a monthly wholesale revenue target. The reports page shows a progress bar:
"$X of $Y target reached this month".

**Why it fits:**
Gives store owners at-a-glance performance context without needing to export to a spreadsheet.

**Implementation notes:**
- Add a "Monthly Target" field in general settings.
- Calculate current-month revenue in `get_report_data()`.
- Render the progress bar in the summary cards section of `report-page.php`.

---

## [FEAT-26] Email Digest: Weekly Wholesale Summary
**Priority:** LOW | **Effort:** Medium

**What:**
Optional weekly email to the admin summarising the past 7 days of wholesale activity: total
orders, revenue, top-selling products, and any new wholesale account applications (FEAT-12).

**Why it fits:**
Store owners who don't check the dashboard daily would still stay informed.

**Implementation notes:**
- Schedule with `wp_schedule_event()` on plugin activation (weekly recurrence).
- Use `WC_Email` subclass for consistent WooCommerce email styling.
- Add opt-in toggle in general settings.

---

# GROUP 6 — INTEGRATIONS

Connect with other popular WooCommerce plugins and platforms.

---

## [FEAT-27] WooCommerce Memberships Integration
**Priority:** HIGH | **Effort:** Medium

**What:**
When WooCommerce Memberships is active, sync membership plans to pricing rules. Members of a
plan automatically get the corresponding wholesale pricing without needing a separate WP role.

**Why it fits:**
WooCommerce Memberships is the most popular membership plugin. Many stores use it to manage
wholesale access. Currently users have to manually maintain both memberships and WP roles.

**Implementation notes:**
- Check for `class_exists('WC_Memberships')` on init.
- Use `wc_memberships_get_user_active_memberships()` to resolve the current user's membership plan.
- Map plan → role in a settings UI; use the mapped role for pricing rule resolution.

---

## [FEAT-28] WooCommerce Subscriptions Integration
**Priority:** MEDIUM | **Effort:** Medium

**What:**
Apply tiered pricing to subscription products. Renewal orders should re-calculate at the
current tiered price for the subscriber's role (not the price at sign-up).

**Why it fits:**
Wholesale subscriptions (e.g. monthly stock orders) are a common B2B model. WooCommerce
Subscriptions doesn't natively support role-based renewal pricing.

**Implementation notes:**
- Hook into `wcs_renewal_order_created` to re-apply role-based pricing.
- Filter subscription product price display with variation-aware logic.

---

## [FEAT-29] REST API Endpoints
**Priority:** MEDIUM | **Effort:** Medium

**What:**
Expose pricing rules and wholesale settings via authenticated WooCommerce REST API endpoints.
Allows headless storefronts, mobile apps, and external ERP/CRM systems to read and write rules.

**Why it fits:**
Headless WooCommerce is growing rapidly. Without REST API support, this plugin is unusable
in headless setups.

**Implementation notes:**
- Register custom REST routes under `/wc/v3/whtprole/` using `register_rest_route()`.
- Endpoints: `GET /rules/{product_id}`, `PUT /rules/{product_id}`, `GET /global-rules`,
  `PUT /global-rules`, `GET /settings`, `PUT /settings`.
- Authentication uses WooCommerce's existing consumer key / secret mechanism.

**Endpoints:**
```
GET    /wc/v3/whtprole/rules/{product_id}    → get product rules
PUT    /wc/v3/whtprole/rules/{product_id}    → update product rules
GET    /wc/v3/whtprole/global-rules           → get global rules
PUT    /wc/v3/whtprole/global-rules           → update global rules
GET    /wc/v3/whtprole/settings               → get general settings
PUT    /wc/v3/whtprole/settings               → update settings
GET    /wc/v3/whtprole/report                 → get report summary
```

---

## [FEAT-30] Elementor Widget & Gutenberg Block
**Priority:** MEDIUM | **Effort:** Large

**What:**
Native Elementor widget and Gutenberg block that renders the pricing table for a specified
product, usable anywhere on the site — not just on product pages.

**Why it fits:**
Listed in the existing "Upcoming Features" section of the readme. Many stores use custom
landing pages built with page builders where the standard WooCommerce single product layout
doesn't apply.

**Implementation notes:**
- **Gutenberg block:** Register with `register_block_type()`. Block has a Product ID attribute
  (searchable picker). `render_callback` calls the same template logic as `display_pricing_table()`.
- **Elementor:** Register with `\Elementor\Plugin::instance()->widgets_manager->register()`.
  Widget controls include Product ID and template selection.
- Also register a shortcode `[whtprole_pricing_table product_id="123"]` as the simplest entry point.

---

## [FEAT-31] Zapier / Webhook Integration
**Priority:** LOW | **Effort:** Medium

**What:**
Fire webhooks on key wholesale events: new wholesale order placed, wholesale rule created/updated,
account application approved/rejected (FEAT-12). Enables Zapier, Make.com, and custom integrations.

**Why it fits:**
B2B stores connect their WooCommerce to ERP, CRM, or accounting systems. Webhooks let those
systems react to wholesale events without polling.

**Implementation notes:**
- Use WooCommerce's existing webhook system (`WC_Webhook`) and register custom topics.
- Or add a lightweight webhook manager that POSTs JSON payloads to admin-configured URLs.
- Fire via `do_action('whtprole_order_placed', $order_id, $role)` etc. — clean hook surface.

---

## [FEAT-32] Multi-Currency Support (WooCommerce Currency Switcher)
**Priority:** LOW | **Effort:** Medium

**What:**
When a multi-currency plugin is active (WooCommerce Payments multi-currency, WOOCS, or Aelia),
tiered prices should be calculated in the customer's selected currency, not the base currency.

**Why it fits:**
International wholesale buyers often pay in their local currency. Without this, tiered amounts
defined in USD would be applied as-is to EUR transactions.

**Implementation notes:**
- Check for active currency switcher on init using `class_exists()` guards.
- Wrap price calculations with the active currency's exchange rate before returning.
- Provide an integration filter `whtprole_convert_price` so third-party currency plugins can hook in.

---

# GROUP 7 — DEVELOPER & HEADLESS

Features for developers building on top of the plugin.

---

## [FEAT-33] Public Filter & Action Hook Library
**Priority:** MEDIUM | **Effort:** Small

**What:**
Document and formalise all existing internal filters into a stable, versioned public API.
Add missing hooks that developers commonly need: before/after rule applies, filter the
resolved price, filter the pricing table HTML, filter applicable rules list.

**Why it fits:**
Third-party developers cannot safely extend the plugin without stable hooks. This is a
prerequisite for a healthy ecosystem of add-ons.

**Key hooks to formalise:**
```php
// Filter the rules array before pricing is applied
apply_filters('whtprole_applicable_rules', $rules, $product_id, $user_role);

// Filter the final calculated price
apply_filters('whtprole_calculated_price', $price, $base_price, $rule, $quantity);

// Filter the pricing table HTML
apply_filters('whtprole_pricing_table_html', $html, $product, $rules);

// Action before/after pricing table renders
do_action('whtprole_before_pricing_table', $product, $rules);
do_action('whtprole_after_pricing_table', $product, $rules);
```

---

## [FEAT-34] WP-CLI Commands
**Priority:** LOW | **Effort:** Small

**What:**
WP-CLI commands for managing wholesale pricing rules from the command line.
Useful for DevOps workflows, automated testing, and bulk migrations.

**Why it fits:**
Developers and site administrators who manage multiple WooCommerce installations prefer
CLI tools for scripted operations.

**Commands:**
```bash
wp whtprole rules list [--product=<id>] [--role=<role>] [--format=json]
wp whtprole rules set <product_id> --role=<role> --tiers='[{"min_qty":10,"price":5,"discount_type":"fixed"}]'
wp whtprole rules delete <product_id> [--role=<role>]
wp whtprole global-rules get [--format=json]
wp whtprole global-rules import <file.json>
wp whtprole report [--date-from=<date>] [--date-to=<date>] [--format=table]
```

---

## [FEAT-35] PHPUnit Test Suite
**Priority:** LOW | **Effort:** Large

**What:**
A WP_UnitTestCase-based test suite covering: price calculation logic, rule matching for all
role formats, JSON storage round-trips, nonce verification, cart price updates.

**Why it fits:**
The audit identified that multiple calculation bugs survived into production because there
were no tests to catch regressions. A test suite prevents this going forward.

**Implementation notes:**
- Set up with `wp scaffold plugin-tests wholesale-tiered-pricing-for-woocommerce`.
- Priority test files:
  - `tests/test-helper.php` — `calculationDiscount()`, `rule_applies_to_user()`, `calculate_price()`
  - `tests/test-ajax.php` — nonce checks, capability guards, JSON round-trip
  - `tests/test-pricing.php` — `update_cart_prices()`, `get_price_html()` with mocked products
  - `tests/test-frontend.php` — `validate_add_to_cart()` with qty edge cases
- Use a GitHub Actions workflow to run the suite on every push.

---

## IMPLEMENTATION ORDER SUGGESTION

For the next release cycle, tackle in this order:

1. **FEAT-13** — CSV Import/Export (high value, medium effort, frequently requested)
2. **FEAT-23** — CSV Export from Reports (small effort, high value for existing users)
3. **FEAT-07** — Rule Priority / Conflict Resolution (small effort, prevents user confusion)
4. **FEAT-08** — Role-Based Product Visibility (high priority, frequently requested)
5. **FEAT-17** — Bulk Rule Assignment (high priority for stores with many products)
6. **FEAT-02** — Minimum Order Value per Role (small effort, high commercial value)
7. **FEAT-18** — Rule Templates (medium effort, pairs with bulk assignment)
8. **FEAT-30** — Shortcode first, then Gutenberg block (listed in readme promises)
9. **FEAT-33** — Public hook library (developer ecosystem, enables paid add-ons)
10. **FEAT-35** — Test suite (quality of life, prevents future regressions)
