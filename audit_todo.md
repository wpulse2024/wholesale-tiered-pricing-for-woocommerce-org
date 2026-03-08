# Plugin Audit: Wholesale & Tiered Pricing for WooCommerce
**Version:** 1.1.0 | **Date:** 2026-03-08 | **Status:** Needs Fixes

This document is the output of a full three-pass audit (Security · Optimization · Traceability).
Each item is self-contained: file, line(s), problem, and fix. Sub-agents can pick items one at a time
and work independently.

---

## QUICK REFERENCE — SEVERITY COUNTS

| Area | Critical | High | Medium | Low | Total |
|------|----------|------|--------|-----|-------|
| Security | 3 | 8 | 5 | 5 | 21 |
| Optimization | — | 10 | 8 | 8 | 26 |
| Traceability | 4 | 9 | 4 | 2 | 19 |

Overlapping issues have been merged. **Fix CRITICAL items first — several break core functionality.**

---

# GROUP 1 — CRITICAL BUGS (Fix Immediately)

These issues either break core features entirely or create serious security holes.

---

## [CRIT-01] JSON Corruption: `sanitize_text_field()` on JSON destroys stored data
**Severity:** CRITICAL | **Affects:** Security + Traceability
**Files:**
- `includes/class-ajax.php` lines 68, 88, 116, 180

**Problem:**
`sanitize_text_field(json_encode($data))` strips the special characters that make JSON valid
(`"`, `{`, `}`, `[`, `]`, `:`). The result stored in `wp_options` is a garbled string. Every
subsequent `json_decode()` returns `null`. This means:
- All global pricing rules are unreadable after save
- All general settings are unreadable after save
- All product filter settings are unreadable after save
- The plugin's `validation()` check always fails (null is falsy → feature disabled)

**What gets stored vs what should:**
```
Input:  {"showTieredPricing":true,"defaultTemplate":"table"}
Stored: showTieredPricingtrueedefaultTemplatetable   ← BROKEN
```

**Fix:**
Replace every instance of `sanitize_text_field(json_encode(...))` with `wp_json_encode(...)`.
The individual fields should be sanitized *before* encoding, not after.

```php
// WRONG (all 4 locations):
update_option('key', sanitize_text_field(json_encode($data)));

// CORRECT:
update_option('key', wp_json_encode($data));
```

**Locations to change:**
- Line 68: `save_general_settings()` → stores `whtprole_pricing_save_general_settings`
- Line 88: `save_pricing_global_settings_on_activation()` → stores default settings
- Line 116: `save_product_settings()` → stores `whtprole_global_product_settings`
- Line 180: `save_pricing_rules()` → stores `whtprole_pricing_global_rules`

---

## [CRIT-02] Wrong option key typo — category filtering never works
**Severity:** CRITICAL | **Affects:** Traceability
**File:** `includes/helper/class-helper.php` line 32

**Problem:**
The option is saved as `whtprole_global_product_settings` (plural) everywhere, but
`isValidCategoryToAppliedTieredPricing()` reads from `whtprole_global_product_setting` (missing `s`).
It always gets an empty array, so every product passes category filtering — the feature is silently broken.

```php
// Line 32 — WRONG key:
$globalCategorySettings = get_option('whtprole_global_product_setting', []);

// Saved in class-ajax.php line 116 as:
update_option('whtprole_global_product_settings', ...);  // 's' at the end
```

**Fix:** Change line 32 to:
```php
$globalCategorySettings = get_option('whtprole_global_product_settings', []);
```

---

## [CRIT-03] Unauthenticated AJAX — non-logged-in users can modify global pricing rules
**Severity:** CRITICAL | **Affects:** Security
**File:** `includes/class-ajax.php` lines 23–24, 27–30

**Problem:**
The following write handlers are registered with **both** `wp_ajax_` (logged-in) **and**
`wp_ajax_nopriv_` (non-logged-in), meaning any anonymous visitor can POST to them:

| Line | Action | What it changes |
|------|--------|----------------|
| 23–24 | `whtprole_pricing_save_pricing_rules` | Global pricing rules for all products |
| 27–28 | `whtprole_pricing_save_product_settings` | Which products/categories pricing applies to |
| 29–30 | `whtprole_pricing_save_general_settings` | All display settings |

An attacker can send a single POST request (no login needed) to wipe all pricing rules or
set every discount to 0%.

**Fix:** Remove the `wp_ajax_nopriv_` registration for all three pairs, AND add a capability
check at the top of each handler:

```php
// Remove these three lines:
add_action('wp_ajax_nopriv_whtprole_pricing_save_pricing_rules', ...);
add_action('wp_ajax_nopriv_whtprole_pricing_save_product_settings', ...);
add_action('wp_ajax_nopriv_whtprole_pricing_save_general_settings', ...);

// Add at top of each handler method:
if (!current_user_can('manage_woocommerce')) {
    wp_send_json_error(['message' => 'Unauthorized']);
    return;
}
```

Also remove `wp_ajax_nopriv_` for read-only admin handlers:
- `whtprole_pricing_get_pricing_rules` (lines 21–22)
- `whtprole_pricing_get_product_settings` (lines 25–26)
- `whtprole_pricing_get_general_settings` (lines 31–32)

---

## [CRIT-04] Role format mismatch — new multi-role rules are never matched in two handlers
**Severity:** CRITICAL | **Affects:** Traceability
**File:** `includes/class-ajax.php` lines 375, 415

**Problem:**
The plugin stores rules in **new format** with `roles` (array), but two handlers still use the
**old format** check `$rule['role']` (single string). Any rule saved through the admin UI with
the new multi-role selector is invisible to these handlers.

Affected handlers:
- `whtprole_get_variation_pricing_rules()` — line 375: variation tier tables never show
- `whtprole_validate_quantity_rules()` — line 415: quantity validation always passes silently

```php
// WRONG (line 375 and 415):
if ($rule['role'] === $current_user_role || empty($current_user_role)) {

// CORRECT — use the helper that handles both formats:
$rule_roles = isset($rule['roles']) ? $rule['roles'] : (isset($rule['role']) ? $rule['role'] : []);
$also_for_guest = isset($rule['also_for_guest']) ? $rule['also_for_guest'] : false;
if (WHTPRole_Pricing_Helper::rule_applies_to_user($rule_roles, $current_user_role, $is_guest, $also_for_guest)) {
```

Same bug in `includes/class-shows-message.php` line 68 (`show_discount_message()`).

---

## [CRIT-05] Missing `return` after `wp_send_json_error()` — code runs after security failure
**Severity:** CRITICAL | **Affects:** Security
**File:** `includes/class-ajax.php` lines 576–579 (`calculate_savings()`)

**Problem:**
After a failed nonce check, `wp_send_json_error()` is called but there is no `return`.
PHP continues executing — the function processes `$_POST` data and sends a second JSON response.
This can produce incorrect pricing data even when the request is invalid.

```php
// WRONG:
if (!wp_verify_nonce($nonce, '...')) {
    wp_send_json_error(['message' => 'Security check failed']);
    // ← no return here — code keeps running!
}

// CORRECT:
if (!wp_verify_nonce($nonce, '...')) {
    wp_send_json_error(['message' => 'Security check failed']);
    return;
}
```

**Audit all `wp_send_json_error()` calls** across the codebase and ensure every one is
immediately followed by `return;`.

---

# GROUP 2 — HIGH SEVERITY

---

## [HIGH-01] Nonce check can be bypassed by simply not sending the nonce
**Severity:** HIGH | **Affects:** Security
**File:** `includes/class-ajax.php` lines 244–251 (`whtprole_get_role_based_price`) and lines 575–579 (`calculate_savings`)

**Problem:**
Nonce verification is wrapped in `if (isset($_POST['nonce']))`. If a caller omits the `nonce`
field entirely, the check is skipped and the function runs with no authentication.

```php
// WRONG:
if (isset($_POST['nonce'])) {
    // only verified if caller bothers to send nonce
}
$product_id = intval($_POST['product_id']); // always runs

// CORRECT:
$nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
if (!wp_verify_nonce($nonce, 'wholesale-tiered-pricing-for-woocommerce-ajax')) {
    wp_send_json_error(['message' => 'Invalid nonce']);
    return;
}
```

---

## [HIGH-02] CSRF: product-level pricing rules can be saved without nonce verification
**Severity:** HIGH | **Affects:** Security
**File:** `includes/class-admin.php` line 327 (`save_product_data()`)

**Problem:**
`save_product_data()` is hooked to `woocommerce_process_product_meta` and reads
`$_POST['role_pricing_rules']` with no explicit nonce check. WooCommerce provides a nonce
in the product edit form, but the plugin never verifies it. A CSRF attack could modify pricing rules.

**Fix:** Add at the top of `save_product_data()`:
```php
if (!isset($_POST['woocommerce_meta_nonce']) ||
    !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['woocommerce_meta_nonce'])), 'woocommerce_save_data')) {
    return;
}
```
Or use `check_admin_referer('woocommerce_save_data')` (which WooCommerce already sets in the form).

---

## [HIGH-03] `get_price_html()` fetches global rules but immediately returns before using them
**Severity:** HIGH | **Affects:** Optimization / Logic Bug
**File:** `includes/class-pricing.php` lines 76–84

**Problem:**
The function fetches global rules (line 81) but then hits `return $price_html` on line 83
before the rules are ever applied. Price HTML for products using global rules is never updated.

```php
// WRONG (current):
if (empty($rules)) {
    $globalRules = get_option('whtprole_pricing_global_rules', []);
    if (empty($globalRules)) {
        return $price_html;
    } else {
        $rules = $globalRules;  // set here...
    }
    return $price_html;  // ← ...but immediately returned here, rules never used
}

// CORRECT:
if (empty($rules)) {
    $rules = get_option('whtprole_pricing_global_rules', []);
}
if (empty($rules)) {
    return $price_html;
}
// now process $rules below...
```

---

## [HIGH-04] `validate_add_to_cart()` ignores global rules — quantity limits not enforced
**Severity:** HIGH | **Affects:** Optimization / Logic Bug
**File:** `includes/class-frontend.php` lines 253–262

**Problem:**
Same early-return bug as HIGH-03. Global rules are fetched into `$rules` on line 258 but
`return $passed` on line 261 exits before validation runs. Products using only global rules
have no quantity enforcement at add-to-cart.

```php
// WRONG:
if (empty($rules)) {
    $globalRules = get_option(...);
    if (empty($globalRules)) {
        return $passed;
    } else {
        $rules = $globalRules;
    }
    return $passed;  // ← exits without validating global rules
}

// CORRECT: remove the final `return $passed;` inside the else block
```

---

## [HIGH-05] `show_discount_message()` uses old role format — discount badges never show
**Severity:** HIGH | **Affects:** Security / Traceability
**File:** `includes/class-shows-message.php` line 68

**Problem:**
Cart and order discount messages check `$rule['role'] === $current_user_role` (old format).
All rules saved through the current UI use the `roles` array format, so no discount messages
ever appear for any customer.

**Fix:** Replace line 68's check with `WHTPRole_Pricing_Helper::rule_applies_to_user()` (same
fix as CRIT-04 — they are the same root cause in different files).

---

## [HIGH-06] `whtprole_validate_quantity_rules()` ignores global rules
**Severity:** HIGH | **Affects:** Traceability
**File:** `includes/class-ajax.php` lines 406–410

**Problem:**
Quantity validation via AJAX only checks `get_post_meta()` rules and returns `valid: true` if no
product-specific rules exist — without checking global rules. Products using only global rules
with min/max qty constraints bypass validation.

**Fix:** After the early return for empty product rules, add a global rules fallback (same
pattern used in other methods like `whtprole_get_role_based_price`).

---

## [HIGH-07] `calculate_price()` missing return — returns `null` instead of base price
**Severity:** HIGH | **Affects:** Optimization / Logic Bug
**File:** `includes/class-shows-message.php` lines 96–124

**Problem:**
When `$rule['tiered_pricing']` is empty, the function exits the `if` block with no return
statement, implicitly returning `null`. The caller compares `$new_price < $original_price`
where `null < float` evaluates unpredictably.

**Fix:** Add `return $base_price;` as the last line of the method.

---

## [HIGH-08] `wc_get_products(['limit' => -1])` loads all products into memory on every page load
**Severity:** HIGH | **Affects:** Optimization / Performance
**File:** `includes/class-global-settings.php` line 17

**Problem:**
Every time the WooCommerce Settings › Tiered Pricing tab loads, ALL products are loaded into memory
and serialized into the page as JSON via `wp_localize_script`. On stores with thousands of products
this causes timeouts and memory exhaustion.

**Fix:** Replace with a lightweight ID+name query and implement AJAX-based search:
```php
// Replace lines 17-24 with:
$product_ids = get_posts([
    'post_type'      => 'product',
    'post_status'    => 'publish',
    'posts_per_page' => 100,
    'fields'         => 'ids',
]);
$products_array = array_map(function($id) {
    return ['id' => $id, 'name' => get_the_title($id)];
}, $product_ids);
```

---

## [HIGH-09] `wc_get_orders(['limit' => -1])` loads all orders on every report page view
**Severity:** HIGH | **Affects:** Optimization / Performance
**File:** `includes/class-wholesale-menu.php` line 277

**Problem:**
The reports page fetches every order in the store (no limit) on every page load, with no caching.
On stores with 10,000+ orders this causes memory exhaustion and multi-second timeouts.

**Fix:**
1. Add a reasonable limit (e.g., 1000) and pagination support
2. Wrap report generation in a transient cache:
```php
$cache_key = 'whtprole_report_' . md5($date_from . $date_to);
$data = get_transient($cache_key);
if ($data === false) {
    $data = $this->fetch_report_data($date_from, $date_to);
    set_transient($cache_key, $data, HOUR_IN_SECONDS);
}
```

---

## [HIGH-10] Unvalidated `discount_type` field accepts arbitrary strings
**Severity:** HIGH | **Affects:** Security
**File:** `includes/class-ajax.php` line 232 (`sanitizeTieredPricingData()`)

**Problem:**
`discount_type` is passed through `sanitize_text_field()` but is never validated against
a whitelist. The `switch()` statement in `calculate_price()` has a `default` branch that treats
any unknown type as a direct price override, which could be exploited.

**Fix:**
```php
$valid_types = ['fixed', 'percentage'];
$discount_type = in_array($tier['discount_type'], $valid_types, true)
    ? $tier['discount_type']
    : 'fixed';
```

---

# GROUP 3 — MEDIUM SEVERITY

---

## [MED-01] `WHTPRole_Pricing_Ajax` instantiated twice — all hooks registered twice
**Severity:** MEDIUM | **Affects:** Security + Optimization
**Files:**
- `wholesale-tiered-pricing-for-woocommerce.php` line 89
- `includes/class-ajax.php` line 758

**Problem:**
Both files end with `new WHTPRole_Pricing_Ajax()`. Every `add_action()` inside the constructor
runs twice, registering all 15+ AJAX handlers as duplicate callbacks. WordPress fires both on
each request.

**Fix:** Delete line 758 from `class-ajax.php` (the stray instantiation at the bottom of the file).
The main plugin's `hooks()` method (line 89) is the correct single instantiation point.

---

## [MED-02] `global $helper` is never set — show-message validation is silently skipped
**Severity:** MEDIUM | **Affects:** Optimization / Logic Bug
**File:** `includes/class-shows-message.php` line 47

**Problem:**
`global $helper;` declares a dependency on a global `$helper` variable, but that variable is
never set anywhere in the plugin. The subsequent `isset($helper) && !$helper->enableToShowsTable()`
always evaluates the `isset` to false, so the pricing table visibility check is never enforced
on order pages.

**Fix:** Replace `global $helper;` with a local instantiation:
```php
$helper = new WHTPRole_Pricing_Helper();
```
Then update the condition to always call it (remove the `isset` guard).

---

## [MED-03] `getGeneralSettings()` has wrong default — returns null on fresh install
**Severity:** MEDIUM | **Affects:** Traceability
**File:** `includes/helper/class-helper.php` line 50

**Problem:**
The default for `get_option('whtprole_pricing_save_general_settings', 'yes')` is the string
`'yes'`. `json_decode('yes', true)` returns `null`. The `validation()` method then does
`if (!$this->getGeneralSettings())` — null is falsy, so validation always fails on fresh installs
before any settings are saved (or after CRIT-01 corrupts the stored value).

**Fix:** Change the default to an empty array:
```php
$enableGlobalRules = get_option('whtprole_pricing_save_general_settings', []);
```

---

## [MED-04] `getTemplatePath()` — undefined key causes PHP fatal on bad template name
**Severity:** MEDIUM | **Affects:** Optimization / Code Smell
**File:** `includes/helper/class-helper.php` lines 92–99

**Problem:**
If `$globalSettings['defaultTemplate']` contains a value not in the `$templates` array
(could happen after JSON corruption or future settings changes), `$templates[$template]`
throws an undefined index notice and returns `null`. The calling `include_once(null)` fails fatally.

**Fix:**
```php
if (!isset($templates[$template])) {
    $template = 'table'; // safe fallback
}
return $templates[$template];
```

---

## [MED-05] `get_all_wholesale_role_keys()` — N+1 query pattern (100 products × meta query each)
**Severity:** MEDIUM | **Affects:** Optimization / Performance
**File:** `includes/class-wholesale-menu.php` lines 407–426

**Problem:**
100 product IDs are fetched, then `get_post_meta()` is called inside a `foreach` loop — one DB
query per product.

**Fix:** Replace with a single `$wpdb` query:
```php
global $wpdb;
$results = $wpdb->get_results(
    "SELECT meta_value FROM {$wpdb->postmeta}
     WHERE meta_key = '_role_pricing_rules'
     AND meta_value != ''
     LIMIT 200"
);
```

---

## [MED-06] JSON attribute output not escaped in HTML (`data-variations`)
**Severity:** MEDIUM | **Affects:** Security (XSS)
**File:** `includes/class-admin.php` lines 117, 233

**Problem:**
`json_encode($variations)` is echoed directly into an HTML attribute without `esc_attr()`. If any
variation name contains a single quote (the attribute delimiter), it breaks the HTML and creates
an XSS vector.

**Fix:**
```php
// WRONG:
data-variations='<?php echo json_encode($variations); ?>'

// CORRECT:
data-variations='<?php echo esc_attr(wp_json_encode($variations)); ?>'
```

---

## [MED-07] `$userRoles` defined AFTER template is included
**Severity:** MEDIUM | **Affects:** Optimization / Code Smell
**File:** `includes/class-global-settings.php` lines 29–30

**Problem:**
Line 29 includes the template; line 30 defines `$userRoles`. Any reference to `$userRoles`
inside the template gets an undefined variable.

**Fix:** Swap the order — define all variables before the `include_once`.

---

## [MED-08] No nonce check in `get_wp_user_roles()` — any logged-in user can enumerate roles
**Severity:** MEDIUM | **Affects:** Security
**File:** `wholesale-tiered-pricing-for-woocommerce.php` lines 149–161

**Problem:**
The global function `get_wp_user_roles()` is an AJAX handler with zero authentication beyond
being logged in. No nonce, no capability check. Any subscriber-level user can enumerate all
WordPress roles.

**Fix:** Add a nonce check and restrict to admin/shop-manager:
```php
check_ajax_referer('whtprole_get_user_roles', 'nonce');
if (!current_user_can('manage_woocommerce')) {
    wp_send_json_error(['message' => 'Unauthorized']);
    return;
}
```
Update `wp_localize_script` to pass the nonce to the JS that calls this action.

---

# GROUP 4 — DUPLICATE CODE (Refactoring Targets)

These don't cause bugs today but create maintenance risk and are the source of several bugs above
(a fix in one copy doesn't propagate to others).

---

## [DUP-01] `get_current_user_role()` exists in 4 classes
**File:** class-ajax.php, class-pricing.php, class-frontend.php, class-shows-message.php
**Fix:** Move to `WHTPRole_Pricing_Helper` as a `public static function get_current_user_role()`.
Remove the private copy from all four classes and call the static method instead.

---

## [DUP-02] `calculate_price()` exists in 3 classes with near-identical logic
**File:** class-ajax.php (lines 497–568), class-pricing.php (lines 187–258), class-shows-message.php (lines 96–124)
**Fix:** Extract to `WHTPRole_Pricing_Helper::calculate_price($base_price, $rule, $quantity, $variation_id = null)`.
The class-shows-message.php version is a simplified (and buggy — see HIGH-07) subset.

---

## [DUP-03] "Get parent_id for variation" pattern repeated 10+ times
**Files:** class-ajax.php, class-pricing.php, class-frontend.php (5 methods)
**Fix:** Extract to `WHTPRole_Pricing_Helper::get_parent_product_id(WC_Product $product): int`.

---

## [DUP-04] "Fetch rules + fall back to global rules" pattern repeated 6+ times
**Files:** class-ajax.php, class-pricing.php, class-frontend.php, class-shows-message.php
**Fix:** Extract to `WHTPRole_Pricing_Helper::get_rules_for_product(int $parent_id): array`.

---

## [DUP-05] Variation tier filtering logic repeated 5+ times
**Files:** class-ajax.php, class-frontend.php, templates/pricing-table-view.php, templates/options-table.php
**Fix:** Extract to `WHTPRole_Pricing_Helper::tier_applies_to_variation(array $tier, int $variation_id): bool`.

---

## [DUP-06] Price-resolution block (60+ lines) duplicated verbatim in two templates
**Files:** `templates/pricing-table-view.php` lines 8–76, `templates/options-table.php` lines 7–77
**Fix:** Extract into a shared function `whtprole_resolve_base_price(WC_Product $product, ?int $variation_id): float`
and call it at the top of each template.

---

## [DUP-07] `sanitizeRulesData()` result is immediately discarded in `save_pricing_rules()`
**File:** `includes/class-ajax.php` lines 134–178
**Problem:** Line 140 calls `$this->sanitizeRulesData()`, but lines 141–178 repeat the
identical loop. The method's output is never used.
**Fix:** Delete lines 141–178. Use the return value of `sanitizeRulesData()` directly:
```php
$sanitized_rules = $this->sanitizeRulesData(
    is_array($_POST['rules']) ? $_POST['rules'] : json_decode(stripslashes($_POST['rules']), true)
);
update_option('whtprole_pricing_global_rules', wp_json_encode($sanitized_rules));
```

---

## [DUP-08] Role extraction ternary repeated everywhere
Pattern: `isset($rule['roles']) ? $rule['roles'] : (isset($rule['role']) ? $rule['role'] : [])`
appears in 10+ places. Already wrapped by `normalize_rule_roles()` in the helper — just not used
consistently.
**Fix:** Replace every inline occurrence with `WHTPRole_Pricing_Helper::normalize_rule_roles($rule)`.

---

# GROUP 5 — DEAD CODE (Remove)

---

## [DEAD-01] `find_applicable_tier()` — private method, never called
**File:** `includes/class-pricing.php` lines 260–277
**Fix:** Delete the method.

---

## [DEAD-02] `getPrice()` static method — never called
**File:** `includes/class-pricing.php` lines 177–185
**Fix:** Delete the method.

---

## [DEAD-03] Commented-out `admin_head` style injection
**File:** `wholesale-tiered-pricing-for-woocommerce.php` lines 36–41
**Fix:** Delete the commented block.

---

## [DEAD-04] Commented-out `woocommerce_product_get_price` filter
**File:** `includes/class-pricing.php` line 9
**Problem:** Simple products never get the role-based price applied via the standard filter hook.
Either this was intentional (needs a comment explaining why) or it is a bug.
**Fix:** Uncomment the filter OR add a code comment explaining why only variation prices are filtered.

---

## [DEAD-05] `global $helper` line that does nothing
**File:** `includes/class-shows-message.php` line 47
**Fix:** Delete this line (also see MED-02 for replacing it with a real instantiation).

---

# GROUP 6 — PERFORMANCE

---

## [PERF-01] `get_option('whtprole_pricing_global_rules')` called 15+ times per request
**Files:** All classes
**Fix:** Cache in a static property on `WHTPRole_Pricing_Helper`:
```php
private static ?array $global_rules_cache = null;

public static function get_global_rules(): array {
    if (self::$global_rules_cache === null) {
        $raw = get_option('whtprole_pricing_global_rules', []);
        self::$global_rules_cache = is_array($raw) ? $raw : (json_decode($raw, true) ?? []);
    }
    return self::$global_rules_cache;
}
```

---

## [PERF-02] `get_post_meta('_role_pricing_rules')` called 5 times per product page
**File:** `includes/class-frontend.php` (5 separate methods)
**Fix:** Cache per product ID in a static array inside `WHTPRole_Pricing_Helper::get_rules_for_product()` (see DUP-04).

---

## [PERF-03] `get_terms(['hide_empty' => false])` loads all categories on settings page
**File:** `includes/class-global-settings.php` line 27
**Fix:** Add `'number' => 200` to the query args.

---

## [PERF-04] Report loads all order items in a loop (N+1)
**File:** `includes/class-wholesale-menu.php` `get_top_wholesale_products()` lines 356–367
**Fix:** Replace with a direct SQL aggregate query on `wp_woocommerce_order_items` and
`wp_woocommerce_order_itemmeta` rather than loading full order objects.

---

## [PERF-05] `new WHTPRole_Pricing_Helper()` instantiated multiple times per page request
**File:** `includes/class-frontend.php` lines 33, 328
**Fix:** Instantiate once in the constructor and store as `$this->helper`.

---

# GROUP 7 — LOW SEVERITY / CODE QUALITY

---

## [LOW-01] `register_activation_hook()` called inside `plugins_loaded` — fires too late
**File:** `wholesale-tiered-pricing-for-woocommerce.php` lines 93–94
**Problem:** Activation hooks must be registered at file load time, not inside a `plugins_loaded`
callback. The `create_tables()` method may not run correctly on activation.
**Fix:** Move `register_activation_hook()` to the top level of the main plugin file, outside any
action hook.

---

## [LOW-02] Global functions pollute namespace
**File:** `wholesale-tiered-pricing-for-woocommerce.php` lines 143–163
Functions `whtprole_pricing_init()` and `get_wp_user_roles()` are in the global namespace.
`get_wp_user_roles` in particular is a dangerously generic name.
**Fix:** Rename `get_wp_user_roles` to `whtprole_get_wp_user_roles` and move the AJAX handler
into `WHTPRole_Pricing_Ajax` or a dedicated class.

---

## [LOW-03] Inconsistent `is_admin()` + AJAX check pattern
**File:** `includes/class-pricing.php` lines 16, 121
Line 16 uses `!wp_doing_ajax()` (correct); line 121 uses `!defined('DOING_AJAX')` (older pattern).
**Fix:** Standardize all to `is_admin() && !wp_doing_ajax()`.

---

## [LOW-04] Inconsistent method naming (camelCase vs snake_case)
**File:** `includes/helper/class-helper.php`
Methods mix `getTemplatePath()` (camelCase) with `rule_applies_to_user()` (snake_case).
WordPress convention is snake_case.
**Fix:** When refactoring, use snake_case for all new/renamed methods.

---

## [LOW-05] `getTieredFeatured()` initializes discount threshold at 1%, not 0%
**File:** `includes/helper/class-helper.php` line 136
`$discountAmount = 1` means any tier with < 1% discount is never selected as "featured".
**Fix:** Change to `$discountAmount = 0;`.

---

## [LOW-06] Script enqueuing happens inside a settings action, not `admin_enqueue_scripts`
**File:** `includes/class-global-settings.php` lines 33–35
Non-standard pattern. Scripts are conditionally enqueued inside a tab callback instead of the
proper hook.
**Fix:** Move to `admin_enqueue_scripts` with a conditional check for the current page/tab.

---

## [LOW-07] `edit_link` in reports exposes raw admin URLs to template without further capability check
**File:** `includes/class-wholesale-menu.php` line 268 (`get_edit_post_link($pid, 'raw')`)
Low risk since the report page requires `manage_woocommerce`, but explicitly passing `'raw'`
disables nonce-addition for the edit link.
**Fix:** Change `'raw'` to `''` to let WordPress add the appropriate nonce/referer.

---

# TRACEABILITY QUICK REFERENCE

Status of every AJAX action end-to-end:

| Action | UI Source | Handler | Storage | Status |
|--------|-----------|---------|---------|--------|
| `whtprole_pricing_save_pricing_rules` | global-settings.js | `save_pricing_rules()` | `wp_options` | ❌ JSON corrupted (CRIT-01) |
| `whtprole_pricing_get_pricing_rules` | global-settings.js | `get_pricing_rules()` | `wp_options` | ❌ Returns null (CRIT-01) |
| `whtprole_pricing_save_general_settings` | global-settings.js | `save_general_settings()` | `wp_options` | ❌ JSON corrupted (CRIT-01) |
| `whtprole_pricing_get_general_settings` | (unused) | `get_general_settings()` | `wp_options` | ❌ Returns null (CRIT-01) |
| `whtprole_pricing_save_product_settings` | global-settings.js | `save_product_settings()` | `wp_options` | ❌ JSON corrupted + wrong key (CRIT-01, CRIT-02) |
| `whtprole_pricing_get_product_settings` | global-settings.js | `get_product_settings()` | `wp_options` | ❌ Returns null |
| `whtprole_get_role_based_price` | frontend.js | `whtprole_get_role_based_price()` | `wp_postmeta` | ⚠️ Nonce bypassable (HIGH-01) |
| `whtprole_calculate_savings` | frontend.js | `calculate_savings()` | `wp_postmeta` | ⚠️ Missing return + nonce bypass (CRIT-05, HIGH-01) |
| `whtprole_get_variation_pricing_rules` | (unused) | `whtprole_get_variation_pricing_rules()` | `wp_postmeta` | ❌ Old role format (CRIT-04) |
| `whtprole_validate_quantity_rules` | (unused) | `whtprole_validate_quantity_rules()` | `wp_postmeta` | ❌ Old role format + ignores global (CRIT-04, HIGH-06) |
| `whtprole_get_variation_price` | frontend.js | `whtprole_get_variation_price()` | `wp_postmeta` | ✅ Working |
| `whtprole_get_report_data` | report.js | `ajax_get_report_data()` | Orders | ✅ Working (but slow — PERF) |
| `whtprole_get_user_roles` | admin.js | `get_wp_user_roles()` | WP roles | ⚠️ No nonce (MED-08) |
| Product save (form POST) | WC product form | `save_product_data()` | `wp_postmeta` | ⚠️ No explicit nonce (HIGH-02) |

Legend: ✅ Working · ⚠️ Works but has issue · ❌ Broken

---

## HOW TO USE THIS FILE

1. Pick any item by its ID (e.g. `CRIT-01`)
2. Read the **Problem** and **Fix** sections
3. Make the change, test, mark done
4. Items in GROUP 1 and GROUP 2 should be done before anything else
5. GROUP 4 (Duplicates) should be done after the logic bugs are fixed — refactoring before fixing
   bugs makes the bugs harder to find

Items are independent unless noted. The DUP-* items depend on CRIT-* and HIGH-* being fixed first
since the deduplicated helper methods need to contain the corrected logic.
