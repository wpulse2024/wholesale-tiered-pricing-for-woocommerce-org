<?php
/**
 * Wholesale Applications Admin Page
 *
 * @package Wholesale_Tiered_Pricing_For_WooCommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

$settings      = WHTPRole_Registration::get_settings_static();
$status_filter = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '';
$paged         = max(1, intval($_GET['paged'] ?? 1));
$per_page      = 20;
$data          = WHTPRole_Registration::get_applicants_static($status_filter, $paged, $per_page);
$applicants    = $data['users'];
$total         = $data['total'];
$wp_roles      = wp_roles()->get_names();
$admin_nonce   = wp_create_nonce('whtprole_applications_admin_nonce');
$base_url      = admin_url('admin.php?page=wholesale-applications');

$status_counts = array(
    ''         => (int) WHTPRole_Registration::get_applicants_static('', 1, 9999)['total'],
    'pending'  => (int) WHTPRole_Registration::get_applicants_static('pending', 1, 9999)['total'],
    'approved' => (int) WHTPRole_Registration::get_applicants_static('approved', 1, 9999)['total'],
    'rejected' => (int) WHTPRole_Registration::get_applicants_static('rejected', 1, 9999)['total'],
);
?>
<div class="wrap whtprole-report-wrap">

    <div class="whtprole-report-header">
        <div>
            <h1 class="whtprole-report-title">
                <?php esc_html_e('Wholesale Applications', 'wholesale-tiered-pricing-for-woocommerce'); ?>
            </h1>
            <p class="whtprole-report-desc">
                <?php esc_html_e('Review and manage wholesale access requests from customers.', 'wholesale-tiered-pricing-for-woocommerce'); ?>
                <?php if ($settings['approval_role']) : ?>
                    <?php
                    $role_label = isset($wp_roles[$settings['approval_role']]) ? translate_user_role($wp_roles[$settings['approval_role']]) : $settings['approval_role'];
                    printf(
                        /* translators: %s: role name */
                        esc_html__('Approved applicants are assigned the "%s" role.', 'wholesale-tiered-pricing-for-woocommerce'),
                        esc_html($role_label)
                    );
                    ?>
                <?php endif; ?>
            </p>
        </div>
    </div>

    <!-- Status filter tabs -->
    <ul class="subsubsub whtprole-app-tabs">
        <?php
        $tab_labels = array(
            ''         => __('All', 'wholesale-tiered-pricing-for-woocommerce'),
            'pending'  => __('Pending', 'wholesale-tiered-pricing-for-woocommerce'),
            'approved' => __('Approved', 'wholesale-tiered-pricing-for-woocommerce'),
            'rejected' => __('Rejected', 'wholesale-tiered-pricing-for-woocommerce'),
        );
        $tabs = array();
        foreach ($tab_labels as $slug => $label) {
            $url     = $slug ? add_query_arg('status', $slug, $base_url) : $base_url;
            $active  = ($status_filter === $slug) ? ' class="current"' : '';
            $count   = $status_counts[$slug] ?? 0;
            $tabs[]  = '<li><a href="' . esc_url($url) . '"' . $active . '>' . esc_html($label) . ' <span class="count">(' . $count . ')</span></a></li>';
        }
        echo implode(' | ', $tabs); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        ?>
    </ul>

    <div class="whtprole-report-section" style="margin-top: 12px;">

        <?php if (empty($applicants)) : ?>
            <p class="whtprole-no-results">
                <?php
                if ($status_filter) {
                    printf(
                        /* translators: %s: status filter name */
                        esc_html__('No %s applications found.', 'wholesale-tiered-pricing-for-woocommerce'),
                        esc_html($status_filter)
                    );
                } else {
                    esc_html_e('No applications yet. Share the registration shortcode [whtprole_registration_form] on a page to start receiving applications.', 'wholesale-tiered-pricing-for-woocommerce');
                }
                ?>
            </p>
        <?php else : ?>
            <table class="widefat striped whtprole-applications-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Name', 'wholesale-tiered-pricing-for-woocommerce'); ?></th>
                        <th><?php esc_html_e('Email', 'wholesale-tiered-pricing-for-woocommerce'); ?></th>
                        <th><?php esc_html_e('Company', 'wholesale-tiered-pricing-for-woocommerce'); ?></th>
                        <th><?php esc_html_e('Business Type', 'wholesale-tiered-pricing-for-woocommerce'); ?></th>
                        <th><?php esc_html_e('Status', 'wholesale-tiered-pricing-for-woocommerce'); ?></th>
                        <th><?php esc_html_e('Date Submitted', 'wholesale-tiered-pricing-for-woocommerce'); ?></th>
                        <th><?php esc_html_e('Actions', 'wholesale-tiered-pricing-for-woocommerce'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applicants as $applicant) :
                        $app_data   = json_decode(get_user_meta($applicant->ID, '_whtprole_application_data', true), true) ?: array();
                        $status     = get_user_meta($applicant->ID, '_whtprole_application_status', true);
                        $date       = get_user_meta($applicant->ID, '_whtprole_application_date', true);
                        $reason     = get_user_meta($applicant->ID, '_whtprole_application_reject_reason', true);
                        $edit_url   = get_edit_user_link($applicant->ID);
                        $status_label = array(
                            'pending'  => __('Pending', 'wholesale-tiered-pricing-for-woocommerce'),
                            'approved' => __('Approved', 'wholesale-tiered-pricing-for-woocommerce'),
                            'rejected' => __('Rejected', 'wholesale-tiered-pricing-for-woocommerce'),
                        );
                    ?>
                        <tr id="whtprole-app-row-<?php echo esc_attr($applicant->ID); ?>">
                            <td>
                                <a href="<?php echo esc_url($edit_url); ?>" target="_blank">
                                    <?php echo esc_html($applicant->display_name); ?>
                                </a>
                            </td>
                            <td><?php echo esc_html($applicant->user_email); ?></td>
                            <td><?php echo esc_html($app_data['company'] ?? '—'); ?></td>
                            <td><?php echo esc_html(ucfirst($app_data['business_type'] ?? '—')); ?></td>
                            <td>
                                <span class="whtprole-app-status-badge whtprole-app-status-badge--<?php echo esc_attr($status); ?>">
                                    <?php echo esc_html($status_label[$status] ?? ucfirst($status)); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo $date ? esc_html(date_i18n(get_option('date_format'), strtotime($date))) : '—'; ?>
                            </td>
                            <td class="whtprole-app-actions">
                                <a href="#"
                                   class="whtprole-view-app button button-small"
                                   data-user-id="<?php echo esc_attr($applicant->ID); ?>">
                                    <?php esc_html_e('Details', 'wholesale-tiered-pricing-for-woocommerce'); ?>
                                </a>
                                <?php if ($status === 'pending') : ?>
                                    <a href="#"
                                       class="whtprole-approve-app button button-small button-primary"
                                       data-user-id="<?php echo esc_attr($applicant->ID); ?>"
                                       data-nonce="<?php echo esc_attr($admin_nonce); ?>">
                                        <?php esc_html_e('Approve', 'wholesale-tiered-pricing-for-woocommerce'); ?>
                                    </a>
                                    <a href="#"
                                       class="whtprole-reject-app button button-small"
                                       data-user-id="<?php echo esc_attr($applicant->ID); ?>"
                                       data-nonce="<?php echo esc_attr($admin_nonce); ?>">
                                        <?php esc_html_e('Reject', 'wholesale-tiered-pricing-for-woocommerce'); ?>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <!-- Detail row (hidden by default) -->
                        <tr class="whtprole-app-detail-row" id="whtprole-detail-<?php echo esc_attr($applicant->ID); ?>" style="display:none;">
                            <td colspan="7">
                                <div class="whtprole-app-detail-content">
                                    <div class="whtprole-app-detail-grid">
                                        <div>
                                            <strong><?php esc_html_e('Phone:', 'wholesale-tiered-pricing-for-woocommerce'); ?></strong>
                                            <?php echo esc_html($app_data['phone'] ?? '—'); ?>
                                        </div>
                                        <div>
                                            <strong><?php esc_html_e('VAT / Tax Number:', 'wholesale-tiered-pricing-for-woocommerce'); ?></strong>
                                            <?php echo esc_html($app_data['vat_number'] ?? '—'); ?>
                                        </div>
                                        <?php if (!empty($app_data['message'])) : ?>
                                        <div class="whtprole-app-detail-full">
                                            <strong><?php esc_html_e('Message:', 'wholesale-tiered-pricing-for-woocommerce'); ?></strong><br>
                                            <?php echo nl2br(esc_html($app_data['message'])); ?>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($status === 'rejected' && $reason) : ?>
                                        <div class="whtprole-app-detail-full">
                                            <strong><?php esc_html_e('Rejection Reason:', 'wholesale-tiered-pricing-for-woocommerce'); ?></strong><br>
                                            <?php echo nl2br(esc_html($reason)); ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($total > $per_page) : ?>
                <div class="whtprole-pagination">
                    <?php
                    echo paginate_links(array( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        'total'   => ceil($total / $per_page),
                        'current' => $paged,
                        'base'    => add_query_arg('paged', '%#%'),
                        'format'  => '',
                    ));
                    ?>
                </div>
            <?php endif; ?>

        <?php endif; ?>

    </div><!-- /.whtprole-report-section (applications) -->

    <!-- Reject modal -->
    <div id="whtprole-reject-modal" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="whtprole-reject-modal-title">
        <div class="whtprole-modal-overlay">
            <div class="whtprole-modal-box">
                <h3 id="whtprole-reject-modal-title">
                    <?php esc_html_e('Reject Application', 'wholesale-tiered-pricing-for-woocommerce'); ?>
                </h3>
                <p><?php esc_html_e('Optionally provide a reason for the applicant.', 'wholesale-tiered-pricing-for-woocommerce'); ?></p>
                <input type="hidden" id="whtprole-reject-user-id" value="">
                <input type="hidden" id="whtprole-reject-nonce" value="">
                <label for="whtprole-reject-reason">
                    <?php esc_html_e('Reason (optional):', 'wholesale-tiered-pricing-for-woocommerce'); ?>
                </label>
                <textarea id="whtprole-reject-reason" rows="3" style="width:100%; margin-top:4px;"></textarea>
                <div class="whtprole-modal-actions">
                    <button id="whtprole-confirm-reject" class="button button-primary">
                        <?php esc_html_e('Confirm Reject', 'wholesale-tiered-pricing-for-woocommerce'); ?>
                    </button>
                    <button id="whtprole-cancel-reject" class="button">
                        <?php esc_html_e('Cancel', 'wholesale-tiered-pricing-for-woocommerce'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Registration Settings -->
    <div class="whtprole-report-section whtprole-reg-settings-section">
        <h2><?php esc_html_e('Registration Settings', 'wholesale-tiered-pricing-for-woocommerce'); ?></h2>
        <p class="description">
            <?php esc_html_e('Add the shortcode [whtprole_registration_form] to any page to display the wholesale application form.', 'wholesale-tiered-pricing-for-woocommerce'); ?>
        </p>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="whtprole-setting-approval-role">
                        <?php esc_html_e('Role to Assign on Approval', 'wholesale-tiered-pricing-for-woocommerce'); ?>
                    </label>
                </th>
                <td>
                    <select id="whtprole-setting-approval-role" name="approval_role">
                        <?php foreach ($wp_roles as $slug => $label) : ?>
                            <option value="<?php echo esc_attr($slug); ?>"
                                <?php selected($settings['approval_role'] ?? '', $slug); ?>>
                                <?php echo esc_html(translate_user_role($label)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        <?php esc_html_e('WordPress role assigned to applicants when you click Approve.', 'wholesale-tiered-pricing-for-woocommerce'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="whtprole-setting-admin-email">
                        <?php esc_html_e('Admin Notification Email', 'wholesale-tiered-pricing-for-woocommerce'); ?>
                    </label>
                </th>
                <td>
                    <input type="email"
                           id="whtprole-setting-admin-email"
                           name="admin_email"
                           class="regular-text"
                           value="<?php echo esc_attr($settings['admin_email'] ?? get_option('admin_email')); ?>">
                    <p class="description">
                        <?php esc_html_e('Receive an email notification when a new application is submitted.', 'wholesale-tiered-pricing-for-woocommerce'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="whtprole-setting-form-title">
                        <?php esc_html_e('Form Title', 'wholesale-tiered-pricing-for-woocommerce'); ?>
                    </label>
                </th>
                <td>
                    <input type="text"
                           id="whtprole-setting-form-title"
                           name="form_title"
                           class="regular-text"
                           placeholder="<?php esc_attr_e('Apply for Wholesale Access', 'wholesale-tiered-pricing-for-woocommerce'); ?>"
                           value="<?php echo esc_attr($settings['form_title'] ?? ''); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <?php esc_html_e('Require Login to Apply', 'wholesale-tiered-pricing-for-woocommerce'); ?>
                </th>
                <td>
                    <label>
                        <input type="checkbox"
                               id="whtprole-setting-require-login"
                               name="require_login"
                               value="1"
                               <?php checked(!empty($settings['require_login'])); ?>>
                        <?php esc_html_e('Users must be logged in before submitting an application', 'wholesale-tiered-pricing-for-woocommerce'); ?>
                    </label>
                    <p class="description">
                        <?php esc_html_e('When unchecked, a new customer account is automatically created for guest applicants.', 'wholesale-tiered-pricing-for-woocommerce'); ?>
                    </p>
                </td>
            </tr>
        </table>
        <p>
            <button id="whtprole-save-settings"
                    class="button button-primary"
                    data-nonce="<?php echo esc_attr($admin_nonce); ?>">
                <?php esc_html_e('Save Settings', 'wholesale-tiered-pricing-for-woocommerce'); ?>
            </button>
            <span id="whtprole-settings-msg" style="margin-left: 8px; line-height: 30px;"></span>
        </p>
    </div>

</div><!-- /.wrap -->
