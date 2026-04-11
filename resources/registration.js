/**
 * Wholesale Registration Form — Frontend JS
 */
(function ($) {
    'use strict';

    $(function () {
        var $form = $('#whtprole-registration-form');
        if (!$form.length) return;

        var $wrap = $form.closest('.whtprole-registration-wrap');
        var $resp = $wrap.find('.whtprole-registration-response');
        var $btn  = $form.find('.whtprole-reg-submit');

        $form.on('submit', function (e) {
            e.preventDefault();

            $btn.prop('disabled', true).text(whtproleRegistration.i18n.submitting);
            $resp.hide().removeClass('whtprole-reg-success whtprole-reg-error').empty();

            $.post(
                whtproleRegistration.ajaxUrl,
                $form.serialize(),
                function (response) {
                    $btn.prop('disabled', false).text(whtproleRegistration.i18n.submit);

                    if (response.success) {
                        $form.hide();
                        $resp
                            .addClass('whtprole-reg-success')
                            .html(response.data.message)
                            .show();
                    } else {
                        $resp
                            .addClass('whtprole-reg-error')
                            .html(response.data.message || whtproleRegistration.i18n.error)
                            .show();
                    }
                }
            ).fail(function () {
                $btn.prop('disabled', false).text(whtproleRegistration.i18n.submit);
                $resp
                    .addClass('whtprole-reg-error')
                    .html(whtproleRegistration.i18n.error)
                    .show();
            });
        });
    });
})(jQuery);
