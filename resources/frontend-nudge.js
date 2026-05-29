/* global whtproleNudge, jQuery */
(function ($) {
    'use strict';

    if (!window.whtproleNudge) {
        return;
    }

    var nudge = window.whtproleNudge;
    var activeTiers = nudge.is_variable ? [] : nudge.tiers;
    var $nudgeEl = $('.whtprole-nudge');

    function findNextTier(qty) {
        for (var i = 0; i < activeTiers.length; i++) {
            if (activeTiers[i].min_qty > qty) {
                return activeTiers[i];
            }
        }
        return null;
    }

    function updateNudge() {
        var qty = parseInt($('form.cart input.qty').val(), 10);
        if (!qty || qty < 1) {
            $nudgeEl.text('');
            return;
        }

        var next = findNextTier(qty);
        if (!next) {
            $nudgeEl.text('');
            return;
        }

        var need = next.min_qty - qty;
        var msg;

        if (next.discount_type === 'percentage') {
            msg = 'Add ' + need + ' more to save ' + next.price + '% per unit';
        } else {
            msg = 'Add ' + need + ' more for ' + nudge.currency_symbol + next.price + '/unit';
        }

        $nudgeEl.text(msg);
    }

    $('form.cart').on('change input', 'input.qty', updateNudge);

    if (nudge.is_variable) {
        $(document).on('found_variation', function (e, variation) {
            var vid = String(variation.variation_id);
            activeTiers = nudge.variation_tiers[vid] || [];
            updateNudge();
        });

        $(document).on('reset_data', function () {
            activeTiers = [];
            $nudgeEl.text('');
        });
    } else {
        updateNudge();
    }

}(jQuery));
