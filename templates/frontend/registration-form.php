<?php
/**
 * Wholesale Registration Form Template
 *
 * Variables available:
 *   $form_title  (string) — heading text
 *   $settings    (array)  — plugin registration settings
 *
 * @package Wholesale_Tiered_Pricing_For_WooCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_user  = wp_get_current_user();
$is_logged_in  = is_user_logged_in();
$first_name    = $is_logged_in ? esc_attr($current_user->first_name)    : '';
$last_name     = $is_logged_in ? esc_attr($current_user->last_name)     : '';
$email         = $is_logged_in ? esc_attr($current_user->user_email)    : '';
$nonce         = wp_create_nonce('whtprole_registration_nonce');
?>
<div class="whtprole-registration-wrap">

    <?php if ($form_title) : ?>
        <h2 class="whtprole-reg-title"><?php echo esc_html($form_title); ?></h2>
    <?php endif; ?>

    <div class="whtprole-registration-response" style="display:none;" role="alert" aria-live="polite"></div>

    <form id="whtprole-registration-form" novalidate>

        <?php if (!$is_logged_in) : ?>
        <div class="whtprole-reg-row">
            <div class="whtprole-reg-field">
                <label for="whtprole_first_name">
                    <?php esc_html_e('First Name', 'wholesale-tiered-pricing-for-woocommerce'); ?>
                    <span class="whtprole-required" aria-hidden="true">*</span>
                </label>
                <input type="text"
                       id="whtprole_first_name"
                       name="first_name"
                       value="<?php echo $first_name; ?>"
                       autocomplete="given-name"
                       required>
            </div>
            <div class="whtprole-reg-field">
                <label for="whtprole_last_name">
                    <?php esc_html_e('Last Name', 'wholesale-tiered-pricing-for-woocommerce'); ?>
                    <span class="whtprole-required" aria-hidden="true">*</span>
                </label>
                <input type="text"
                       id="whtprole_last_name"
                       name="last_name"
                       value="<?php echo $last_name; ?>"
                       autocomplete="family-name"
                       required>
            </div>
        </div>

        <div class="whtprole-reg-row">
            <div class="whtprole-reg-field whtprole-reg-full">
                <label for="whtprole_email">
                    <?php esc_html_e('Email Address', 'wholesale-tiered-pricing-for-woocommerce'); ?>
                    <span class="whtprole-required" aria-hidden="true">*</span>
                </label>
                <input type="email"
                       id="whtprole_email"
                       name="email"
                       value="<?php echo $email; ?>"
                       autocomplete="email"
                       required>
            </div>
        </div>
        <?php else : ?>
        <!-- Pre-fill hidden name fields for logged-in users so they are sent in the POST -->
        <input type="hidden" name="first_name" value="<?php echo $first_name; ?>">
        <input type="hidden" name="last_name"  value="<?php echo $last_name; ?>">
        <?php endif; ?>

        <div class="whtprole-reg-row">
            <div class="whtprole-reg-field">
                <label for="whtprole_company">
                    <?php esc_html_e('Company Name', 'wholesale-tiered-pricing-for-woocommerce'); ?>
                    <span class="whtprole-required" aria-hidden="true">*</span>
                </label>
                <input type="text"
                       id="whtprole_company"
                       name="company"
                       autocomplete="organization"
                       required>
            </div>
            <div class="whtprole-reg-field">
                <label for="whtprole_business_type">
                    <?php esc_html_e('Business Type', 'wholesale-tiered-pricing-for-woocommerce'); ?>
                    <span class="whtprole-required" aria-hidden="true">*</span>
                </label>
                <select id="whtprole_business_type" name="business_type" required>
                    <option value=""><?php esc_html_e('Select...', 'wholesale-tiered-pricing-for-woocommerce'); ?></option>
                    <option value="retailer"><?php esc_html_e('Retailer', 'wholesale-tiered-pricing-for-woocommerce'); ?></option>
                    <option value="distributor"><?php esc_html_e('Distributor', 'wholesale-tiered-pricing-for-woocommerce'); ?></option>
                    <option value="manufacturer"><?php esc_html_e('Manufacturer', 'wholesale-tiered-pricing-for-woocommerce'); ?></option>
                    <option value="other"><?php esc_html_e('Other', 'wholesale-tiered-pricing-for-woocommerce'); ?></option>
                </select>
            </div>
        </div>

        <div class="whtprole-reg-row">
            <div class="whtprole-reg-field">
                <label for="whtprole_phone">
                    <?php esc_html_e('Phone Number', 'wholesale-tiered-pricing-for-woocommerce'); ?>
                    <span class="whtprole-required" aria-hidden="true">*</span>
                </label>
                <input type="tel"
                       id="whtprole_phone"
                       name="phone"
                       autocomplete="tel"
                       required>
            </div>
            <div class="whtprole-reg-field">
                <label for="whtprole_vat_number">
                    <?php esc_html_e('VAT / Tax Number', 'wholesale-tiered-pricing-for-woocommerce'); ?>
                    <span class="whtprole-optional"><?php esc_html_e('(optional)', 'wholesale-tiered-pricing-for-woocommerce'); ?></span>
                </label>
                <input type="text"
                       id="whtprole_vat_number"
                       name="vat_number"
                       autocomplete="off">
            </div>
        </div>

        <div class="whtprole-reg-row">
            <div class="whtprole-reg-field whtprole-reg-full">
                <label for="whtprole_message">
                    <?php esc_html_e('Message / Notes', 'wholesale-tiered-pricing-for-woocommerce'); ?>
                    <span class="whtprole-optional"><?php esc_html_e('(optional)', 'wholesale-tiered-pricing-for-woocommerce'); ?></span>
                </label>
                <textarea id="whtprole_message"
                          name="message"
                          rows="4"></textarea>
            </div>
        </div>

        <p class="whtprole-reg-required-note">
            <span class="whtprole-required" aria-hidden="true">*</span>
            <?php esc_html_e('Required fields', 'wholesale-tiered-pricing-for-woocommerce'); ?>
        </p>

        <input type="hidden" name="action" value="whtprole_submit_application">
        <input type="hidden" name="nonce"  value="<?php echo esc_attr($nonce); ?>">

        <div class="whtprole-reg-submit-row">
            <button type="submit" class="whtprole-reg-submit button">
                <?php esc_html_e('Submit Application', 'wholesale-tiered-pricing-for-woocommerce'); ?>
            </button>
        </div>

    </form>
</div>
