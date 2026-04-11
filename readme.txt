=== Wholesale & Tiered Pricing for WooCommerce ===
Contributors: wpulse, dasnitesh780, chadni54
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Tags: wholesale pricing, bulk discount, tiered pricing, role based pricing, b2b
Requires at least: 5.0
Tested up to: 6.9.1
Stable tag: 1.2.3
Requires PHP: 7.4
Requires Plugins: woocommerce

Set role-based wholesale prices, volume discounts, and quantity rules in WooCommerce. Display beautiful tiered pricing tables for B2B, wholesale, and bulk buyers.

== Description ==

**Wholesale & Tiered Pricing for WooCommerce** is the most flexible **wholesale pricing plugin** for WooCommerce stores. Designed for **B2B shops, wholesale distributors, and bulk sellers**, it lets you define custom prices, tiered volume discounts, and quantity purchase rules for any user role — including custom roles created by membership or access-control plugins.

Show eye-catching **tiered pricing tables** on your product pages, let customers see exactly how much they save as they buy more, and automate bulk discount logic without writing a single line of code.

🎥 **Watch the Plugin Overview Video:**

[youtube https://www.youtube.com/watch?v=Id6WGI4wKkY]

👉 *See how to set up wholesale pricing rules in under a minute!*

---

Whether you run a **wholesale WooCommerce store**, sell to **B2B customers**, or simply want to reward bulk buyers with **volume discounts**, this plugin covers every scenario:

* Charge different prices per user role (wholesale, retailer, VIP, distributor, etc.)
* Offer quantity-based tiered discounts (buy 5 save 10%, buy 10 save 20%)
* Set minimum, maximum, and step purchase quantities per role
* Display professional pricing tables directly on the product page
* Schedule flash sales and time-limited pricing rules with start and end dates
* Show a live savings calculator so customers see their discount in real time

---

== Key Features ==

= Role-Based Wholesale Pricing =
* Assign **different product prices per user role** — wholesale, retailer, B2B customer, VIP, vendor, or any custom role.
* Full backward compatibility with both single-role and multi-role rule formats.
* **Global pricing rules** act as a wildcard and apply to all logged-in users or guest users.
* Works with any custom role created by plugins like WooCommerce Memberships, Ultimate Member, or User Role Editor.

= Tiered / Volume Discount Pricing =
* Create **quantity-based pricing tiers**: the more a customer buys, the lower the price per unit.
* Set tiers as a **fixed discount** (e.g., $5 off per item) or a **percentage discount** (e.g., 15% off).
* Apply tiers to all variations or target a specific variation of a variable product.
* Multiple tier levels per rule — unlimited pricing bands.

= Scheduled & Flash Pricing =
* Set an **Active From** and **Active Until** date on any pricing rule.
* Rules outside their date window are automatically excluded from pricing, tables, cart validation, and discount messages — no manual toggling needed.
* Perfect for **flash sales**, seasonal wholesale pricing, and time-limited B2B promotions.

= Quantity Rules per Role =
* **Minimum quantity** — require wholesale buyers to purchase at least N units.
* **Maximum quantity** — limit retail customers to a maximum order size.
* **Step/increment quantity** — force orders in multiples of 2, 5, 10, etc.
* Quantity enforcement applies on the product page, in the cart, and at checkout.

= Beautiful Pricing Table Templates =
* Six ready-made table layouts: **Table**, **Options**, **Minimal Table**, **Compact List**, **Plain Text**, **Horizontal**.
* Choose the default template globally and override it per product.
* Control which columns appear: Quantity, Price, Discount.
* Set a custom table title and active-tier highlight color.
* Responsive layout option for mobile shoppers.
* Choose where the table appears: above/below add-to-cart, before/after product meta, or after product summary.

= Live Savings Calculator =
* Displays a **real-time savings widget** on the product page.
* Updates automatically as the customer changes the quantity input.
* Shows regular price, discounted price, total savings amount, and discount percentage.
* Can be enabled or disabled from Template Options settings.

= Discount Badges in Cart, Checkout & Orders =
* Applied wholesale tier is shown beside each line item in the **cart**, **checkout**, **customer order details**, and **admin order screen**.
* Customers see exactly which discount tier was applied and how much they saved.

= Variable Product Support =
* Full support for **WooCommerce variable products** — rules and tiers can target all variations or a specific variation.
* Pricing tables update dynamically when the customer switches between variations.

= Performance & Security =
* Transient caching on the wholesale reports page (5-minute TTL).
* Pre-warmed post meta cache to eliminate N+1 database queries.
* Capped product and category queries (limit: 200) to prevent timeouts on large stores.
* All AJAX handlers protected with nonce verification and capability checks.
* Admin-only handlers are never registered as publicly accessible.

= Wholesale Registration Form =
* Add the shortcode `[whtprole_registration_form]` to any page to let customers **apply for wholesale access**.
* Collects company name, business type, VAT/tax number, phone, and a free-text message.
* Guest applicants get a WooCommerce customer account created automatically — no extra step required.
* Logged-in users who already submitted see their **application status** (pending / approved / rejected) instead of the form.
* Admin receives an **email notification** for every new application.
* Approved and rejected applicants are notified by email with WooCommerce-branded messages.
* Manage all applications from **Wholesale → Applications** in the WordPress admin.
* One-click Approve (assigns the configured role) or Reject (with optional reason).
* Configurable: choose which WordPress role is assigned on approval, set the notification email, and optionally require users to be logged in before applying.

= Wholesale Reports =
* Dedicated **Wholesale Reports** page inside WooCommerce showing revenue, order counts, and top wholesale buyers.

---

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`, or install directly from **WordPress → Plugins → Add New**.
2. Activate the plugin through the **Plugins** screen.
3. Navigate to **WooCommerce → Tiered Pricing → Template Options** to configure global display settings.
4. Navigate to **WooCommerce → Tiered Pricing → Pricing Rules** to set global role-based pricing rules.
5. Edit any product and open the **Tiered Pricing** tab in the product data panel to set per-product rules.

---

== Frequently Asked Questions ==

= Can I set different prices for wholesale customers vs retail customers? =
Yes. Create separate pricing rules — one for your wholesale role (e.g., "Wholesale Customer") and one for standard customers. Each role sees only its own price.

= Does it support WooCommerce variable products? =
Yes, fully. You can apply pricing rules to all variations or to a specific variation. The pricing table on the product page updates in real time when the customer selects a variation.

= Can I offer volume discounts that increase with quantity? =
Yes. Each rule supports unlimited pricing tiers. You can define as many quantity breakpoints as you need, using either a fixed dollar discount or a percentage discount per tier.

= Can I set minimum or maximum order quantities per role? =
Yes. Each rule has optional Minimum Quantity, Maximum Quantity, and Step Quantity fields. These are enforced on the product page, in the cart, and at checkout.

= Does it work with custom user roles from membership plugins? =
Yes. Any role registered in WordPress — including those created by WooCommerce Memberships, Paid Memberships Pro, Ultimate Member, or User Role Editor — is automatically available in the role selector.

= Can I schedule pricing rules to run during a specific date range? =
Yes. Every pricing rule has optional "Active From" and "Active Until" date fields. Rules outside their scheduled window are automatically skipped — no manual toggling needed. Use this for flash sales, seasonal wholesale rates, or limited-time B2B promotions.

= Can guest (non-logged-in) users see wholesale prices? =
Yes. Create a Global rule and enable the "Make it for guest user also" option. Guest users will see those prices and discounts without needing to log in.

= Can I show the pricing table on some products but hide it on others? =
Yes. There is a "Show Pricing Table" toggle on every product's Tiered Pricing panel. You can also control the default behavior globally from the Template Options settings.

= Will this plugin slow down my store? =
No. The plugin uses transient caching for reports, pre-warms meta cache to eliminate N+1 queries, and uses bounded database queries to avoid timeouts on large catalogs.

= Does it support multiple languages? =
Yes, all frontend strings are fully translatable. The plugin ships with a .pot file and is compatible with WPML, Polylang, and Loco Translate.

= Will this work with my theme? =
Yes. The pricing table templates use minimal HTML/CSS and inherit your theme's base styles. You can also override templates in your child theme.

= Can customers apply for wholesale access themselves? =
Yes. Add the shortcode `[whtprole_registration_form]` to any page. Customers fill in their company details and submit an application. You review it under **Wholesale → Applications** and approve or reject it with one click. Approved applicants are automatically assigned the wholesale role you configure and are notified by email.

= Can I choose which role is assigned when I approve an application? =
Yes. Go to **Wholesale → Applications → Registration Settings** and pick any WordPress role from the dropdown. The default is the first role whose slug contains "wholesale", or "Customer" if none is found.

= Do applicants need an account before they can apply? =
No (by default). If a guest submits the form, a WooCommerce customer account is created for them automatically and the standard "New Account" email is sent. You can require login first by enabling the option in Registration Settings.

= Where can I get support? =
Please open a support thread in the WordPress.org plugin support forum. We typically respond within 1–2 business days.

---

== Screenshots ==

1. Role-based and tiered pricing rules setup in the product editor.
2. Tiered pricing table (Table template) displayed on the product page.
3. Live savings calculator widget on the product page.
4. Quantity restriction messages on the product page and in the cart.
5. Template Options — choose template, position, columns, and color.
6. Global pricing rules panel (multi-role selector with scheduled dates).
7. Options table template for pricing display.
8. Minimal table template for pricing display.
9. Compact list template for pricing display.
10. Wholesale Reports page inside WooCommerce.
11. Wholesale Registration Form displayed on the frontend via shortcode.
12. Wholesale Applications admin page with approve / reject workflow.

---

== Changelog ==

= 1.2.3 – 2026-04-11 =
- Added Wholesale Registration Form — customers can now apply for wholesale access directly on the frontend using the `[whtprole_registration_form]` shortcode.
- Added Wholesale → Applications admin page: lists all pending, approved, and rejected applications with status filter tabs, an inline details panel, and one-click Approve / Reject actions.
- Approve action automatically assigns the configured WordPress role to the applicant and sends an approval email.
- Reject action stores an optional reason and sends a rejection email to the applicant.
- Admin receives an email notification for every new application submitted.
- Guest applicants get a WooCommerce customer account created automatically (configurable: can require login instead).
- Logged-in users who already submitted an application see their current status (pending / approved / rejected) instead of the form.
- Added Registration Settings panel (Wholesale → Applications): configure the approval role, admin notification email, login requirement, and form heading.
- All AJAX handlers are nonce-verified; admin-only actions require the `manage_woocommerce` capability and are never registered as publicly accessible.

= 1.2.2 – 2026-03-25 =
- Added Minimum Order Value (MOV) per pricing rule — set a minimum cart subtotal that must be reached before a role's pricing rule activates (e.g. "wholesale price only if order is at least $200").

= 1.2.1 – 2026-03-15 =
- Redesigned Wholesale Reports admin page: summary cards now show a coloured icon badge (blue / purple / green / orange) for each metric; section headings include contextual SVG icons (cart, trend, box, gear).
- Reports filter bar restructured: date labels stacked above inputs, calendar icon overlaid on each date field, preset buttons (7d / 30d / 90d) highlight the active selection with a dark fill, Apply and Edit buttons use a high-contrast dark style.
- Report tables now use uppercase column headers and subtle row-hover styling instead of the WP striped class.
- Revamped Volume Pricing card (minimal template): replaced old premium wrapper markup with `wtp-volume-card` / `wtp-tier-row` structure; savings shown as a plain "Save N% / $X off" label; removed "Best" featured-tier badge and progress bar.
- Pricing table: column headers changed to muted uppercase on a transparent background, removing the coloured primary-colour header fill; `.quantity-badge`, `.price-unit`, and `.savings-info` cell classes introduced for cleaner markup; table border changed from box-shadow to a solid border with rounded corners.
- Radio tier selector: active tier now uses an inset box-shadow instead of background fill; colour palette updated to neutral tones; responsive breakpoint tightened from 768 px to 480 px.
- Added Dashicons lightbulb icon before the "See Your Savings" savings-calculator heading.
- Quantity field now defaults to the first tier's minimum quantity on page load (when greater than 1), so the eligible tier price is immediately visible without the customer adjusting quantity manually.

= 1.2.0 – 2026-03-12 =
- Added Scheduled / Flash Pricing — pricing rules now support `Active From` and `Active Until` date fields; rules outside their scheduled window are automatically excluded everywhere (product page, cart, checkout, quantity validation, discount messages).
- Added date pickers to the product editor (PHP) and the global pricing rules form (Vue) for per-rule scheduling.
- Fixed date fields not being saved when clicking "Save Changes" in the global pricing rules admin — `date_from`/`date_to` were silently stripped by the AJAX sanitize function.
- Fixed expired or future-dated rules still applying prices, showing pricing tables, enforcing quantity limits, and displaying discount badges — all rule lookups now route through the central date filter in `WHTPRole_Pricing_Helper::get_rules_for_product()`.
- Fixed cart and order discount messages using the variation ID instead of the parent product ID, causing rules to not be found for variable products.

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

= 1.1.0 – 2026-02-10 =
- Added Wholesale Reports page with revenue and order analytics.
- Enhanced user experience with real-time price recalculation based on variation selection.

= 1.0.8 – 2026-01-24 =
- Enhanced variable product support across all pricing table templates.
- Added dynamic variation filtering — pricing tables now show only tiers applicable to the selected variation.
- Added automatic price updates when customers select different variations on variable products.

= 1.0.7 – 2026-01-23 =
- Added support for assigning tiered pricing rules to multiple user roles using a normalized roles array.
- Introduced a Global pricing option that applies to all user roles as a wildcard.
- Added an "Apply to Guest Users" option for Global rules to control pricing for non-logged-in users.
- Improved admin UI with a multi-select role selector for better flexibility and usability.

= 1.0.6 – 2025-01-23 =
- Added Dynamic Savings Calculator — real-time savings display that updates as customers change quantity, showing total savings and discount percentage.
- Improved pricing calculation logic with better edge case handling.
- Fixed pricing calculation issues when max_qty constraints are set.

= 1.0.5 – 2025-11-10 =
- Added pricing rule support for guest (non-logged-in) users.
- Added option to show or hide the pricing table per product.

= 1.0.4 – 2025-11-07 =
- Added multilingual support for all frontend strings.
- Minor bug fixes.

= 1.0.3 – 2025-11-01 =
- Admin order details now display the applied tier pricing rule beside each product line item.
- Discount notice now shows in the cart, checkout, and order details pages (frontend).
- Discount calculation now uses the actual selling price instead of the regular price for more accurate savings.

= 1.0.2 – 2025-10-22 =
- Fixed undefined `price_type` key on cart and checkout page.
- Fixed single product tiered pricing settings not saving properly.
- Fixed pricing table not displaying on product page until general settings are saved.

= 1.0.1 – 2025-10-22 =
- Bug fixes and minor improvements.

= 1.0.0 – 2025-10-22 =
- Initial release with role-based pricing, tiered volume discounts, quantity rules, and customizable pricing tables.

---

== Upgrade Notice ==

= 1.2.3 =
Adds a self-service Wholesale Registration Form (shortcode `[whtprole_registration_form]`) and an Applications admin page with one-click approve / reject, automatic role assignment, and email notifications for both the admin and the applicant.

= 1.2.2 =
Adds Minimum Order Value (MOV) per pricing rule. Wholesale pricing is withheld until the cart subtotal meets the threshold. Product and cart pages show a clear "Add $X more to unlock wholesale pricing" notice.

= 1.2.1 =
UI overhaul of the Wholesale Reports admin page and all frontend pricing templates (volume pricing card, pricing table, radio tier selector). Quantity field now pre-selects the first tier on page load.

= 1.2.0 =
Adds Scheduled / Flash Pricing — set start and end dates on any pricing rule. Also fixes expired rules being applied to prices, tables, and cart validation, and fixes discount messages on variable products.

= 1.1.1 =
Critical security and stability release. Fixes unauthenticated access to pricing rule AJAX handlers, a JSON corruption bug that silently broke all pricing rules on save, and a fatal error on variable product pages. Upgrade immediately.
