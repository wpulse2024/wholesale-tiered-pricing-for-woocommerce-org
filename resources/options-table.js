(function($) {
    'use strict';

    // ── Price formatter (uses WC currency params) ─────────────────────────────
    function formatPrice(amount) {
        var wc       = (typeof woocommerce_params !== 'undefined') ? woocommerce_params : {};
        var decimals = parseInt(wc.currency_format_num_decimals) || 2;
        var decSep   = wc.currency_format_decimal_sep  || '.';
        var thouSep  = wc.currency_format_thousand_sep || ',';
        var symbol   = wc.currency_symbol || '';
        var format   = wc.currency_format  || '%1$s%2$s';

        var fixed   = amount.toFixed(decimals);
        var parts   = fixed.split('.');
        parts[0]    = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thouSep);
        var number  = parts.join(decSep);
        var sym     = '<span class="woocommerce-Price-currencySymbol">' + symbol + '</span>';

        return '<span class="woocommerce-Price-amount amount"><bdi>' +
               format.replace('%1$s', sym).replace('%2$s', number) +
               '</bdi></span>';
    }

    // ── Update "Total:" row for a specific tier × qty ─────────────────────────
    function updateTierTotals($tier, qty) {
        var basePrice  = parseFloat($tier.closest('.radio-pricing-wrapper').data('base-regular-price')) || 0;
        var tierPrice  = parseFloat($tier.data('price')) || 0;

        if (basePrice > 0 && tierPrice > 0) {
            $tier.find('.total-regular').html(formatPrice(basePrice * qty));
            $tier.find('.total-sale').html(formatPrice(tierPrice * qty));
        }
    }

    // ── Select the correct tier for a given qty ───────────────────────────────
    function updateTierSelection(quantity) {
        var $tiers   = $('.radio-tier');
        var reversed = $tiers.get().slice().reverse(); // highest min-qty first
        var $match   = null;

        reversed.forEach(function(el) {
            if (quantity >= parseInt($(el).data('min-qty')) && !$match) {
                $match = $(el);
            }
        });

        $tiers.removeClass('active');
        $tiers.find('.tier-radio-input').prop('checked', false);

        if ($match) {
            $match.addClass('active');
            $match.find('.tier-radio-input').prop('checked', true);
            updateTierTotals($match, quantity);
        }
    }

    // ── Qty field changed → update tier highlight & totals ───────────────────
    $(document).on('change input', '.quantity input.qty, input.qty', function() {
        updateTierSelection(parseInt($(this).val()) || 1);
    });

    // ── Radio input changed → update qty field & totals ──────────────────────
    $(document).on('change', '.tier-radio-input', function() {
        if (!$(this).is(':checked')) return;

        var $tier  = $(this).closest('.radio-tier');
        var minQty = parseInt($(this).data('min-qty'));

        // Update qty field (this will also re-trigger tier selection via qty handler)
        $('.quantity input.qty, input.qty').val(minQty).trigger('change');

        // Ensure correct active state & totals (qty change may lag on some themes)
        $('.radio-tier').removeClass('active');
        $tier.addClass('active');
        updateTierTotals($tier, minQty);
    });

    // ── Click on tier row label → trigger radio ───────────────────────────────
    $(document).on('click', '.radio-tier', function(e) {
        if (!$(e.target).is('input')) {
            $(this).find('.tier-radio-input').prop('checked', true).trigger('change');
        }
    });

    // ── Init on DOM ready ─────────────────────────────────────────────────────
    $(document).ready(function() {
        var qty = parseInt($('.quantity input.qty, input.qty').val()) || 1;
        updateTierSelection(qty);
    });

    // ── Variable product: re-init after variation is chosen ───────────────────
    $(document).on('found_variation', function() {
        setTimeout(function() {
            var qty = parseInt($('.quantity input.qty').val()) || 1;
            updateTierSelection(qty);
        }, 150);
    });

    // ── Variable product: clear on variation reset ────────────────────────────
    $(document).on('reset_data', function() {
        $('.radio-tier').removeClass('active');
        $('.tier-radio-input').prop('checked', false);
    });

})(jQuery);
