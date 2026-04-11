/**
 * Wholesale Applications — Admin JS
 */
(function ($) {
    'use strict';

    $(function () {

        // -----------------------------------------------------------------
        // Toggle detail row
        // -----------------------------------------------------------------
        $(document).on('click', '.whtprole-view-app', function (e) {
            e.preventDefault();
            var userId    = $(this).data('user-id');
            var $detailRow = $('#whtprole-detail-' + userId);
            $detailRow.slideToggle(150);
        });

        // -----------------------------------------------------------------
        // Approve application
        // -----------------------------------------------------------------
        $(document).on('click', '.whtprole-approve-app', function (e) {
            e.preventDefault();
            var $btn   = $(this);
            var userId = $btn.data('user-id');
            var nonce  = $btn.data('nonce');

            $btn.prop('disabled', true).text(whtproleApplications.i18n.approving);

            $.post(whtproleApplications.ajaxUrl, {
                action:  'whtprole_approve_application',
                user_id: userId,
                nonce:   nonce,
            }, function (response) {
                if (response.success) {
                    updateRowStatus(userId, 'approved');
                } else {
                    alert(response.data.message || whtproleApplications.i18n.error);
                    $btn.prop('disabled', false).text('Approve');
                }
            }).fail(function () {
                alert(whtproleApplications.i18n.error);
                $btn.prop('disabled', false).text('Approve');
            });
        });

        // -----------------------------------------------------------------
        // Open reject modal
        // -----------------------------------------------------------------
        $(document).on('click', '.whtprole-reject-app', function (e) {
            e.preventDefault();
            var userId = $(this).data('user-id');
            var nonce  = $(this).data('nonce');

            $('#whtprole-reject-user-id').val(userId);
            $('#whtprole-reject-nonce').val(nonce);
            $('#whtprole-reject-reason').val('');
            $('#whtprole-reject-modal').fadeIn(150);
        });

        // Confirm reject
        $('#whtprole-confirm-reject').on('click', function () {
            var $btn   = $(this);
            var userId = $('#whtprole-reject-user-id').val();
            var nonce  = $('#whtprole-reject-nonce').val();
            var reason = $('#whtprole-reject-reason').val();

            $btn.prop('disabled', true).text(whtproleApplications.i18n.rejecting);

            $.post(whtproleApplications.ajaxUrl, {
                action:  'whtprole_reject_application',
                user_id: userId,
                nonce:   nonce,
                reason:  reason,
            }, function (response) {
                $btn.prop('disabled', false).text('Confirm Reject');
                $('#whtprole-reject-modal').fadeOut(150);

                if (response.success) {
                    updateRowStatus(userId, 'rejected');
                } else {
                    alert(response.data.message || whtproleApplications.i18n.error);
                }
            }).fail(function () {
                $btn.prop('disabled', false).text('Confirm Reject');
                alert(whtproleApplications.i18n.error);
            });
        });

        // Cancel reject modal
        $('#whtprole-cancel-reject').on('click', function () {
            $('#whtprole-reject-modal').fadeOut(150);
        });

        // Close modal on overlay click
        $('#whtprole-reject-modal').on('click', function (e) {
            if ($(e.target).is('#whtprole-reject-modal, .whtprole-modal-overlay')) {
                $('#whtprole-reject-modal').fadeOut(150);
            }
        });

        // -----------------------------------------------------------------
        // Save settings
        // -----------------------------------------------------------------
        $('#whtprole-save-settings').on('click', function () {
            var $btn = $(this);
            var $msg = $('#whtprole-settings-msg');
            var nonce = $btn.data('nonce');

            $btn.prop('disabled', true).text(whtproleApplications.i18n.saving);
            $msg.text('');

            $.post(whtproleApplications.ajaxUrl, {
                action:        'whtprole_save_registration_settings',
                nonce:         nonce,
                approval_role: $('#whtprole-setting-approval-role').val(),
                admin_email:   $('#whtprole-setting-admin-email').val(),
                require_login: $('#whtprole-setting-require-login').is(':checked') ? 1 : 0,
                form_title:    $('#whtprole-setting-form-title').val(),
            }, function (response) {
                $btn.prop('disabled', false).text('Save Settings');
                if (response.success) {
                    $msg.css('color', 'green').text(whtproleApplications.i18n.saved);
                    setTimeout(function () { $msg.text(''); }, 3000);
                } else {
                    $msg.css('color', 'red').text(response.data.message || whtproleApplications.i18n.error);
                }
            }).fail(function () {
                $btn.prop('disabled', false).text('Save Settings');
                $msg.css('color', 'red').text(whtproleApplications.i18n.error);
            });
        });

        // -----------------------------------------------------------------
        // Helpers
        // -----------------------------------------------------------------

        function updateRowStatus(userId, newStatus) {
            var $row   = $('#whtprole-app-row-' + userId);
            var label  = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);

            // Update status badge
            $row.find('.whtprole-app-status-badge')
                .attr('class', 'whtprole-app-status-badge whtprole-app-status-badge--' + newStatus)
                .text(label);

            // Remove action buttons (no longer pending)
            $row.find('.whtprole-approve-app, .whtprole-reject-app').remove();
        }
    });
})(jQuery);
