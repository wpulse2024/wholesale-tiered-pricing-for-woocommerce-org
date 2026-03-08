# Support Ticket Reply

**Subject:** Re: Multiple roles & Support buttons in Documentation

---

Hi,

Thank you for your kind words and for using Wholesale & Tiered Pricing for WooCommerce! We're glad to help clarify both points:

## 1. How Pricing Rules Work with Multiple Roles

When a user has multiple roles (e.g., Administrator and Shop Manager), the plugin uses the **primary role** — that is, the first role in the user's role list as defined by WordPress.

- Rules are matched against this primary role only
- The first matching rule in the rules list is applied
- For example: If a user has both Administrator and Shop Manager, and Administrator is their primary role, only rules targeting Administrator will apply. Rules targeting only Shop Manager would not apply in that case.

**Practical tip:** If you want a rule to apply to users who have either Administrator OR Shop Manager, create a rule that includes both roles in the "User Role" selection. The plugin supports rules targeting multiple roles.

## 2. Support and Contact Support Buttons

You're correct — the Support and Contact Support buttons in the Documentation sidebar currently don't have any links attached, so they don't do anything when clicked. This is a bug on our end, and we apologize for the confusion.

In the meantime, you can reach support through:
- **WordPress.org support forum:** https://wordpress.org/support/plugin/wholesale-tiered-pricing-for-woocommerce/
- Or through your purchase/support channel if you have a premium version

We'll add proper links to these buttons in an upcoming release so they correctly direct users to our support page.

---

If you have any other questions, feel free to ask!

Best regards,
[Your name]
