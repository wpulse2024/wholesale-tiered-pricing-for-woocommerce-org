# Tier Progress Nudge — Design Spec

**Date:** 2026-05-29  
**Status:** Approved  
**Target users:** Small B2B store owners (DIY, 1-5 products, simple wholesale)

---

## Problem

Wholesale buyers don't know when they're close to unlocking a better price tier. They leave money (and savings) on the table because there's no signal prompting them to add a few more units.

---

## Solution

Show a "next tier" nudge in two places:

1. **Product page** — static callout below Add to Cart, updated live by JS as qty changes
2. **Cart page** — per-line-item inline notice, refreshed by WooCommerce's native cart fragment mechanism

---

## Architecture

### New class: `WHTPRole_Tier_Nudge`

Registered in the main plugin file alongside existing class instances. Owns all hooks for both surfaces.

**Hooks:**
- `woocommerce_before_add_to_cart_button` → `render_product_nudge($product_id)`
- `woocommerce_after_cart_item_name` → `render_cart_nudge($cart_item, $cart_item_key)`

Only hooks register if "Enable tier progress nudge" setting is on.

### New helper method: `WHTPRole_Helper::get_next_tier()`

```php
public static function get_next_tier(
    int $product_id,
    int $current_qty,
    ?int $variation_id = null
): ?array
```

Returns `['qty_needed' => int, 'tier_price' => string, 'discount_type' => string]` or `null` if user is already at max tier or no rules apply.

Logic:
1. Get current user role via `get_current_user_role()`
2. Fetch rules via `get_rules_for_product()` (falls back to global rules)
3. Filter by `rule_applies_to_user()` and `tier_applies_to_variation()`
4. Sort tiers by `min_qty` ascending
5. Find first tier where `min_qty > current_qty` — that's the next tier
6. Return `qty_needed = tier.min_qty - current_qty`

### New JS: `resources/frontend-nudge.js`

Compiled to `plugin-assets/frontend-nudge.js`, enqueued on single product pages only.

Reads `window.whtproleNudge`:
```js
{
  // Simple product: tiers array at top level
  tiers: [{ min_qty: 10, price: "12.00", discount_type: "fixed" }, ...],
  // Variable product: tiers keyed by variation_id (all variations pre-loaded)
  variation_tiers: { "123": [...], "124": [...] },
  currency_symbol: "$",
  is_variable: false
}
```

Behavior:
- Listens to `input`/`change` on `.qty` input
- For variable products: listens to WC `found_variation` event, switches active tiers to `variation_tiers[variation_id]`
- Finds next tier where `min_qty > currentQty`
- Updates `.whtprole-nudge` inner text
- Clears nudge if already at max tier or no variation selected (variable product)

---

## Component Details

### `render_product_nudge($product_id)`

1. Resolve applicable rule(s) for current user
2. If no rules → return early (no output)
3. For simple products: localize active tiers array
4. For variable products: localize `variation_tiers` map (all variation IDs → their tiers) so JS can switch on `found_variation` without a server round-trip
5. Call `wp_localize_script()` with tier data + `is_variable` flag
6. Output: `<div class="whtprole-nudge"></div>` (JS populates content)

### `render_cart_nudge($cart_item, $cart_item_key)`

1. Extract `product_id`, `variation_id`, `quantity` from `$cart_item`
2. Call `get_next_tier($product_id, $quantity, $variation_id)`
3. If null → return early
4. Output inline `<small class="whtprole-cart-nudge">Add {N} more for {price}/unit</small>`

### Nudge message formats

| Discount type | Product page JS | Cart PHP |
|---|---|---|
| `fixed` | "Add {N} more for {symbol}{price}/unit" | "Add {N} more for {symbol}{price}/unit" |
| `percentage` | "Add {N} more to save {price}% per unit" | "Add {N} more to save {price}% per unit" |

All strings wrapped in `__()` for translation.

---

## Settings

One new field in existing global settings (WooCommerce → Settings → Tiered Pricing):

- **Label:** "Enable tier progress nudge"
- **Type:** checkbox
- **Default:** checked (on)
- **Storage key:** `whtprole_general_settings['enable_tier_nudge']`

No message template customization — text is hardcoded but translatable.

---

## Edge Cases

| Scenario | Behavior |
|---|---|
| User at max tier | No nudge rendered |
| No rules for user's role | No nudge rendered |
| Guest + `also_for_guest: true` | Nudge shown |
| Variable product, no variation selected | Empty container output; JS populates on `found_variation` |
| Variable product, variation selected | JS uses variation-specific tier data |
| Product rules + global rules both exist | Product rules win (existing helper precedence) |
| Scheduled rules outside date range | Filtered out by existing `get_rules_for_product()` logic |

---

## Performance

No new DB reads on product page — tier data comes from the same rule fetch already done for the pricing table render. Cart nudge does one `get_rules_for_product()` call per line item on cart load/refresh (acceptable; same cost as existing cart price recalculation).

---

## Files Changed

| File | Change |
|---|---|
| `includes/class-tier-nudge.php` | New class |
| `includes/helper/class-helper.php` | Add `get_next_tier()` static method |
| `includes/class-global-settings.php` | Add "enable_tier_nudge" setting field |
| `resources/frontend-nudge.js` | New JS file |
| `wholesale-tiered-pricing-for-woocommerce.php` | Register `WHTPRole_Tier_Nudge` instance |
| `package.json` / build config | Add new JS entry point |

---

## Out of Scope

- Customizable nudge message templates
- Nudge on mini-cart widget
- Animated progress bar UI
- Email nudges / abandoned cart integration
