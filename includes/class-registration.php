<?php
/**
 * Wholesale Registration
 *
 * Provides a shortcode-based application form for customers to request wholesale
 * access. Admins review, approve, or reject applications from Wholesale > Applications.
 *
 * @package Wholesale_Tiered_Pricing_For_WooCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

class WHTPRole_Registration {

    public function __construct() {
        add_shortcode('whtprole_registration_form', array($this, 'render_shortcode'));
        add_action('wp_enqueue_scripts',            array($this, 'enqueue_frontend_assets'));
        add_action('admin_menu',                    array($this, 'add_applications_submenu'));
        add_action('admin_enqueue_scripts',         array($this, 'enqueue_admin_assets'));

        // Public — guests can submit applications
        add_action('wp_ajax_whtprole_submit_application',        array($this, 'ajax_submit_application'));
        add_action('wp_ajax_nopriv_whtprole_submit_application', array($this, 'ajax_submit_application'));

        // Admin-only
        add_action('wp_ajax_whtprole_approve_application',            array($this, 'ajax_approve_application'));
        add_action('wp_ajax_whtprole_reject_application',             array($this, 'ajax_reject_application'));
        add_action('wp_ajax_whtprole_save_registration_settings',     array($this, 'ajax_save_registration_settings'));
    }

    // -------------------------------------------------------------------------
    // Shortcode
    // -------------------------------------------------------------------------

    public function render_shortcode($atts) {
        $atts = shortcode_atts(array('title' => ''), $atts, 'whtprole_registration_form');

        // Enqueue assets regardless of wp_enqueue_scripts timing
        $this->enqueue_frontend_assets();

        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            $status  = get_user_meta($user_id, '_whtprole_application_status', true);

            if ($status === 'approved') {
                ob_start();
                ?>
                <div class="whtprole-registration-wrap">
                    <div class="whtprole-reg-status whtprole-reg-status--approved">
                        <p><?php esc_html_e('Your wholesale access is active. You can start shopping at wholesale prices.', 'wholesale-tiered-pricing-for-woocommerce'); ?></p>
                    </div>
                </div>
                <?php
                return ob_get_clean();
            }

            if ($status === 'pending') {
                ob_start();
                ?>
                <div class="whtprole-registration-wrap">
                    <div class="whtprole-reg-status whtprole-reg-status--pending">
                        <p><?php esc_html_e('Your application is currently under review. We will notify you by email once a decision has been made.', 'wholesale-tiered-pricing-for-woocommerce'); ?></p>
                    </div>
                </div>
                <?php
                return ob_get_clean();
            }

            if ($status === 'rejected') {
                ob_start();
                ?>
                <div class="whtprole-registration-wrap">
                    <div class="whtprole-reg-status whtprole-reg-status--rejected">
                        <p><?php esc_html_e('Your previous application was not approved. You may submit a new application below.', 'wholesale-tiered-pricing-for-woocommerce'); ?></p>
                    </div>
                </div>
                <?php
                $rejected_notice = ob_get_clean();
            }
        }

        $settings   = $this->get_settings();
        $form_title = !empty($atts['title']) ? $atts['title'] : ($settings['form_title'] ?: __('Apply for Wholesale Access', 'wholesale-tiered-pricing-for-woocommerce'));

        ob_start();
        if (!empty($rejected_notice)) {
            echo $rejected_notice; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
        include WHTPROLE_PRICING_PLUGIN_PATH . 'templates/frontend/registration-form.php';
        return ob_get_clean();
    }

    // -------------------------------------------------------------------------
    // Asset enqueue
    // -------------------------------------------------------------------------

    public function enqueue_frontend_assets() {
        if (!wp_script_is('whtprole-registration', 'registered')) {
            wp_register_script(
                'whtprole-registration',
                WHTPROLE_PRICING_PLUGIN_URL . 'plugin-assets/registration.js',
                array('jquery'),
                WHTPROLE_PRICING_VERSION,
                true
            );
            wp_localize_script('whtprole-registration', 'whtproleRegistration', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('whtprole_registration_nonce'),
                'i18n'    => array(
                    'submit'     => __('Submit Application', 'wholesale-tiered-pricing-for-woocommerce'),
                    'submitting' => __('Submitting...', 'wholesale-tiered-pricing-for-woocommerce'),
                    'error'      => __('An unexpected error occurred. Please try again.', 'wholesale-tiered-pricing-for-woocommerce'),
                ),
            ));
            wp_register_style(
                'whtprole-registration',
                WHTPROLE_PRICING_PLUGIN_URL . 'plugin-assets/registration.css',
                array(),
                WHTPROLE_PRICING_VERSION
            );
        }
        wp_enqueue_script('whtprole-registration');
        wp_enqueue_style('whtprole-registration');
    }

    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'wholesale-applications') === false) {
            return;
        }
        wp_enqueue_script(
            'whtprole-applications',
            WHTPROLE_PRICING_PLUGIN_URL . 'plugin-assets/applications.js',
            array('jquery'),
            WHTPROLE_PRICING_VERSION,
            true
        );
        wp_localize_script('whtprole-applications', 'whtproleApplications', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('whtprole_applications_admin_nonce'),
            'i18n'    => array(
                'approving'  => __('Approving...', 'wholesale-tiered-pricing-for-woocommerce'),
                'rejecting'  => __('Rejecting...', 'wholesale-tiered-pricing-for-woocommerce'),
                'saving'     => __('Saving...', 'wholesale-tiered-pricing-for-woocommerce'),
                'saved'      => __('Saved!', 'wholesale-tiered-pricing-for-woocommerce'),
                'confirm_approve' => __('Approve this application?', 'wholesale-tiered-pricing-for-woocommerce'),
                'error'      => __('An error occurred. Please try again.', 'wholesale-tiered-pricing-for-woocommerce'),
            ),
        ));
        wp_enqueue_style(
            'whtprole-applications',
            WHTPROLE_PRICING_PLUGIN_URL . 'plugin-assets/applications.css',
            array(),
            WHTPROLE_PRICING_VERSION
        );
    }

    // -------------------------------------------------------------------------
    // Admin menu
    // -------------------------------------------------------------------------

    public function add_applications_submenu() {
        add_submenu_page(
            'wholesale-tiered-pricing',
            __('Applications', 'wholesale-tiered-pricing-for-woocommerce'),
            __('Applications', 'wholesale-tiered-pricing-for-woocommerce'),
            'manage_woocommerce',
            'wholesale-applications',
            array($this, 'render_applications_page')
        );
    }

    public function render_applications_page() {
        include WHTPROLE_PRICING_PLUGIN_PATH . 'templates/admin/applications-page.php';
    }

    // -------------------------------------------------------------------------
    // AJAX — public: submit application
    // -------------------------------------------------------------------------

    public function ajax_submit_application() {
        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'whtprole_registration_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'wholesale-tiered-pricing-for-woocommerce')));
            return;
        }

        // Sanitize inputs
        $first_name    = sanitize_text_field(wp_unslash($_POST['first_name']    ?? ''));
        $last_name     = sanitize_text_field(wp_unslash($_POST['last_name']     ?? ''));
        $email         = sanitize_email(wp_unslash($_POST['email']              ?? ''));
        $company       = sanitize_text_field(wp_unslash($_POST['company']       ?? ''));
        $business_type = sanitize_text_field(wp_unslash($_POST['business_type'] ?? ''));
        $vat_number    = sanitize_text_field(wp_unslash($_POST['vat_number']    ?? ''));
        $phone         = sanitize_text_field(wp_unslash($_POST['phone']         ?? ''));
        $message       = sanitize_textarea_field(wp_unslash($_POST['message']   ?? ''));

        // Whitelist business_type
        $allowed_types = array('retailer', 'distributor', 'manufacturer', 'other');
        if (!in_array($business_type, $allowed_types, true)) {
            wp_send_json_error(array('message' => __('Invalid business type selected.', 'wholesale-tiered-pricing-for-woocommerce')));
            return;
        }

        // Required field validation
        $errors = array();
        if (empty($company))       $errors[] = __('Company name is required.', 'wholesale-tiered-pricing-for-woocommerce');
        if (empty($phone))         $errors[] = __('Phone number is required.', 'wholesale-tiered-pricing-for-woocommerce');
        if (empty($business_type)) $errors[] = __('Business type is required.', 'wholesale-tiered-pricing-for-woocommerce');

        // Determine user
        $settings = $this->get_settings();

        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
        } else {
            if (!empty($settings['require_login'])) {
                wp_send_json_error(array('message' => __('Please log in before applying for wholesale access.', 'wholesale-tiered-pricing-for-woocommerce')));
                return;
            }
            if (empty($first_name)) $errors[] = __('First name is required.', 'wholesale-tiered-pricing-for-woocommerce');
            if (empty($last_name))  $errors[] = __('Last name is required.', 'wholesale-tiered-pricing-for-woocommerce');
            if (empty($email) || !is_email($email)) $errors[] = __('A valid email address is required.', 'wholesale-tiered-pricing-for-woocommerce');

            if (!empty($errors)) {
                wp_send_json_error(array('message' => implode('<br>', $errors)));
                return;
            }

            $result = $this->create_or_get_user($email, $first_name, $last_name);
            if (is_wp_error($result)) {
                wp_send_json_error(array('message' => $result->get_error_message()));
                return;
            }
            $user_id = $result;
        }

        if (!empty($errors)) {
            wp_send_json_error(array('message' => implode('<br>', $errors)));
            return;
        }

        // Check if already approved
        $existing_status = get_user_meta($user_id, '_whtprole_application_status', true);
        if ($existing_status === 'approved') {
            wp_send_json_error(array('message' => __('Your account already has wholesale access.', 'wholesale-tiered-pricing-for-woocommerce')));
            return;
        }

        // Duplicate pending check
        if ($this->is_application_duplicate($user_id)) {
            wp_send_json_error(array('message' => __('You already have a pending application under review.', 'wholesale-tiered-pricing-for-woocommerce')));
            return;
        }

        // Get user display name for logged-in users
        if (is_user_logged_in() && (empty($first_name) || empty($last_name))) {
            $user      = get_userdata($user_id);
            $first_name = $first_name ?: $user->first_name;
            $last_name  = $last_name  ?: $user->last_name;
        }

        // Save to user meta
        $app_data = wp_json_encode(array(
            'first_name'    => $first_name,
            'last_name'     => $last_name,
            'company'       => $company,
            'business_type' => $business_type,
            'vat_number'    => $vat_number,
            'phone'         => $phone,
            'message'       => $message,
        ));

        update_user_meta($user_id, '_whtprole_application_status',  'pending');
        update_user_meta($user_id, '_whtprole_application_data',    $app_data);
        update_user_meta($user_id, '_whtprole_application_date',    current_time('c'));
        delete_user_meta($user_id, '_whtprole_application_reject_reason');
        delete_user_meta($user_id, '_whtprole_application_reviewed_date');

        $this->send_admin_notification($user_id);

        wp_send_json_success(array(
            'message' => __('Thank you! Your application is under review. We will contact you by email once a decision has been made.', 'wholesale-tiered-pricing-for-woocommerce'),
        ));
    }

    // -------------------------------------------------------------------------
    // AJAX — admin: approve / reject / save settings
    // -------------------------------------------------------------------------

    public function ajax_approve_application() {
        $this->require_admin_capability();

        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'whtprole_applications_admin_nonce')) {
            wp_send_json_error(array('message' => 'Invalid nonce'), 403);
            return;
        }

        $user_id = intval($_POST['user_id'] ?? 0);
        if (!$user_id || !get_userdata($user_id)) {
            wp_send_json_error(array('message' => __('User not found.', 'wholesale-tiered-pricing-for-woocommerce')));
            return;
        }

        $settings      = $this->get_settings();
        $approval_role = $settings['approval_role'] ?: $this->get_default_approval_role();

        $user = new WP_User($user_id);
        $user->set_role($approval_role);

        update_user_meta($user_id, '_whtprole_application_status',        'approved');
        update_user_meta($user_id, '_whtprole_application_reviewed_date', current_time('c'));

        $this->send_user_notification_approved($user_id);

        wp_send_json_success(array('message' => __('Application approved.', 'wholesale-tiered-pricing-for-woocommerce')));
    }

    public function ajax_reject_application() {
        $this->require_admin_capability();

        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'whtprole_applications_admin_nonce')) {
            wp_send_json_error(array('message' => 'Invalid nonce'), 403);
            return;
        }

        $user_id = intval($_POST['user_id'] ?? 0);
        if (!$user_id || !get_userdata($user_id)) {
            wp_send_json_error(array('message' => __('User not found.', 'wholesale-tiered-pricing-for-woocommerce')));
            return;
        }

        $reason = sanitize_textarea_field(wp_unslash($_POST['reason'] ?? ''));

        update_user_meta($user_id, '_whtprole_application_status',        'rejected');
        update_user_meta($user_id, '_whtprole_application_reviewed_date', current_time('c'));
        if ($reason) {
            update_user_meta($user_id, '_whtprole_application_reject_reason', $reason);
        }

        $this->send_user_notification_rejected($user_id, $reason);

        wp_send_json_success(array('message' => __('Application rejected.', 'wholesale-tiered-pricing-for-woocommerce')));
    }

    public function ajax_save_registration_settings() {
        $this->require_admin_capability();

        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (!wp_verify_nonce($nonce, 'whtprole_applications_admin_nonce')) {
            wp_send_json_error(array('message' => 'Invalid nonce'), 403);
            return;
        }

        $approval_role = sanitize_text_field(wp_unslash($_POST['approval_role'] ?? ''));
        $admin_email   = sanitize_email(wp_unslash($_POST['admin_email']        ?? ''));
        $require_login = !empty($_POST['require_login']);
        $form_title    = sanitize_text_field(wp_unslash($_POST['form_title']    ?? ''));

        // Validate role exists
        if ($approval_role && !array_key_exists($approval_role, wp_roles()->roles)) {
            wp_send_json_error(array('message' => __('Invalid role selected.', 'wholesale-tiered-pricing-for-woocommerce')));
            return;
        }

        $settings = array(
            'approval_role' => $approval_role,
            'admin_email'   => $admin_email ?: get_option('admin_email'),
            'require_login' => $require_login,
            'form_title'    => $form_title,
        );

        update_option('whtprole_registration_settings', wp_json_encode($settings));

        wp_send_json_success(array('message' => __('Settings saved.', 'wholesale-tiered-pricing-for-woocommerce')));
    }

    // -------------------------------------------------------------------------
    // Settings helpers
    // -------------------------------------------------------------------------

    private function get_settings() {
        $raw = get_option('whtprole_registration_settings', '');
        if (is_string($raw) && !empty($raw)) {
            $decoded = json_decode($raw, true);
        } elseif (is_array($raw)) {
            $decoded = $raw;
        } else {
            $decoded = array();
        }

        return wp_parse_args($decoded, array(
            'approval_role' => $this->get_default_approval_role(),
            'admin_email'   => get_option('admin_email'),
            'require_login' => false,
            'form_title'    => '',
        ));
    }

    public static function get_settings_static() {
        return (new self())->get_settings();
    }

    private function get_default_approval_role() {
        foreach (wp_roles()->roles as $slug => $role) {
            if (stripos($slug, 'wholesale') !== false) {
                return $slug;
            }
        }
        return 'customer';
    }

    // -------------------------------------------------------------------------
    // Applicants query
    // -------------------------------------------------------------------------

    private function get_applicants($status_filter = '', $paged = 1, $per_page = 20) {
        $meta_query = array(
            array(
                'key'     => '_whtprole_application_status',
                'compare' => 'EXISTS',
            ),
        );

        if (!empty($status_filter) && in_array($status_filter, array('pending', 'approved', 'rejected'), true)) {
            $meta_query = array(
                array(
                    'key'   => '_whtprole_application_status',
                    'value' => $status_filter,
                ),
            );
        }

        $query = new WP_User_Query(array(
            'meta_query'  => $meta_query,
            'number'      => $per_page,
            'offset'      => ($paged - 1) * $per_page,
            'orderby'     => 'ID',
            'order'       => 'DESC',
            'count_total' => true,
        ));

        return array(
            'users' => $query->get_results(),
            'total' => $query->get_total(),
        );
    }

    public static function get_applicants_static($status_filter = '', $paged = 1, $per_page = 20) {
        return (new self())->get_applicants($status_filter, $paged, $per_page);
    }

    // -------------------------------------------------------------------------
    // User creation
    // -------------------------------------------------------------------------

    private function create_or_get_user($email, $first_name, $last_name) {
        $existing_id = email_exists($email);
        if ($existing_id) {
            return $existing_id;
        }

        $username = wc_create_new_customer_username($email, array(
            'first_name' => $first_name,
            'last_name'  => $last_name,
        ));
        $password = wp_generate_password();

        $customer_id = wc_create_new_customer($email, $username, $password, array(
            'first_name' => $first_name,
            'last_name'  => $last_name,
        ));

        if (is_wp_error($customer_id)) {
            return $customer_id;
        }

        // Send the standard WooCommerce new account email
        do_action('woocommerce_created_customer', $customer_id, array(
            'user_login' => $username,
            'user_pass'  => $password,
        ), true);

        return $customer_id;
    }

    private function is_application_duplicate($user_id) {
        $status = get_user_meta($user_id, '_whtprole_application_status', true);
        return $status === 'pending';
    }

    // -------------------------------------------------------------------------
    // Email notifications
    // -------------------------------------------------------------------------

    private function send_admin_notification($user_id) {
        $settings  = $this->get_settings();
        $to        = $settings['admin_email'] ?: get_option('admin_email');
        $user      = get_userdata($user_id);
        $app_data  = json_decode(get_user_meta($user_id, '_whtprole_application_data', true), true) ?: array();
        $site_name = get_bloginfo('name');

        $subject = sprintf(
            /* translators: 1: site name, 2: applicant name */
            __('[%1$s] New Wholesale Application from %2$s', 'wholesale-tiered-pricing-for-woocommerce'),
            $site_name,
            $user ? $user->display_name : ($app_data['first_name'] . ' ' . $app_data['last_name'])
        );

        $apps_url = admin_url('admin.php?page=wholesale-applications&status=pending');
        $body  = __('A new wholesale application has been submitted.', 'wholesale-tiered-pricing-for-woocommerce') . "\n\n";
        $body .= __('Name:', 'wholesale-tiered-pricing-for-woocommerce') . ' ' . ($app_data['first_name'] ?? '') . ' ' . ($app_data['last_name'] ?? '') . "\n";
        $body .= __('Email:', 'wholesale-tiered-pricing-for-woocommerce') . ' ' . ($user ? $user->user_email : '') . "\n";
        $body .= __('Company:', 'wholesale-tiered-pricing-for-woocommerce') . ' ' . ($app_data['company'] ?? '') . "\n";
        $body .= __('Business Type:', 'wholesale-tiered-pricing-for-woocommerce') . ' ' . ucfirst($app_data['business_type'] ?? '') . "\n\n";
        $body .= __('Review application:', 'wholesale-tiered-pricing-for-woocommerce') . ' ' . $apps_url;

        wp_mail($to, $subject, $body);
    }

    private function send_user_notification_approved($user_id) {
        $user      = get_userdata($user_id);
        if (!$user) return;

        $site_name = get_bloginfo('name');
        $shop_url  = wc_get_page_permalink('shop');

        $subject = sprintf(
            /* translators: %s: site name */
            __('[%s] Your Wholesale Application Has Been Approved', 'wholesale-tiered-pricing-for-woocommerce'),
            $site_name
        );

        $body = $this->wrap_email_html(
            __('Application Approved', 'wholesale-tiered-pricing-for-woocommerce'),
            sprintf(
                /* translators: %s: customer display name */
                '<p>' . __('Hi %s,', 'wholesale-tiered-pricing-for-woocommerce') . '</p>
                <p>' . __('Great news! Your wholesale application has been approved. You now have access to wholesale pricing on our store.', 'wholesale-tiered-pricing-for-woocommerce') . '</p>
                <p><a href="%s" style="background:#333;color:#fff;padding:10px 20px;text-decoration:none;border-radius:4px;">' . __('Start Shopping', 'wholesale-tiered-pricing-for-woocommerce') . '</a></p>',
                esc_html($user->display_name),
                esc_url($shop_url)
            )
        );

        wp_mail($user->user_email, $subject, $body, array('Content-Type: text/html; charset=UTF-8'));
    }

    private function send_user_notification_rejected($user_id, $reason = '') {
        $user = get_userdata($user_id);
        if (!$user) return;

        $site_name = get_bloginfo('name');

        $subject = sprintf(
            /* translators: %s: site name */
            __('[%s] Update on Your Wholesale Application', 'wholesale-tiered-pricing-for-woocommerce'),
            $site_name
        );

        $reason_html = '';
        if ($reason) {
            $reason_html = '<p><strong>' . __('Reason:', 'wholesale-tiered-pricing-for-woocommerce') . '</strong> ' . esc_html($reason) . '</p>';
        }

        $body = $this->wrap_email_html(
            __('Application Update', 'wholesale-tiered-pricing-for-woocommerce'),
            sprintf(
                /* translators: %s: customer display name */
                '<p>' . __('Hi %s,', 'wholesale-tiered-pricing-for-woocommerce') . '</p>
                <p>' . __('Thank you for your interest in our wholesale program. After reviewing your application, we are unable to approve your request at this time.', 'wholesale-tiered-pricing-for-woocommerce') . '</p>',
                esc_html($user->display_name)
            ) . $reason_html . '<p>' . __('If you have any questions, please contact us.', 'wholesale-tiered-pricing-for-woocommerce') . '</p>'
        );

        wp_mail($user->user_email, $subject, $body, array('Content-Type: text/html; charset=UTF-8'));
    }

    /**
     * Wrap email content in basic HTML with WooCommerce styling if available.
     */
    private function wrap_email_html($heading, $content) {
        ob_start();
        $email_heading = $heading;
        wc_get_template('emails/email-header.php', array('email_heading' => $email_heading));
        echo wp_kses_post($content);
        wc_get_template('emails/email-footer.php');
        $html = ob_get_clean();

        // Fallback if WC templates produced nothing
        if (empty(trim($html))) {
            $html = '<!DOCTYPE html><html><body>' . wp_kses_post($content) . '</body></html>';
        }

        return $html;
    }

    // -------------------------------------------------------------------------
    // Security helper
    // -------------------------------------------------------------------------

    private function require_admin_capability() {
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Unauthorized'), 403);
            exit;
        }
    }
}
