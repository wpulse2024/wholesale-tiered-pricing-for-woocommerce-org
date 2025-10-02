jQuery(document).ready(function($) {
    // Highlight active tier row
    function highlightActiveTier(quantity) {
        $('.pricing-table tbody tr').removeClass('active-tier');
        $('.pricing-table tbody tr').each(function() {
            const qtyText = $(this).find('td:first').text().replace('+', '');
            const minQty = parseInt(qtyText) || 0;
            if (quantity >= minQty) {
                $(this).addClass('active-tier');
            }
        });
    }

    // Validate quantity input
    function validateQuantity() {
        const qtyInput = $('.qty');
        if (qtyInput.length) {
            const min = parseInt(qtyInput.attr('min')) || 1;
            const max = parseInt(qtyInput.attr('max')) || 999999999;
            const step = parseInt(qtyInput.attr('step')) || 1;

            qtyInput.on('change blur', function() {
                let value = parseInt($(this).val()) || min;

                if (value < min) {
                    value = min;
                    showQuantityNotice('Minimum quantity is ' + min, 'error');
                }

                if (max > 0 && value > max) {
                    value = max;
                    showQuantityNotice('Maximum quantity is ' + max, 'error');
                }

                if ((value - min) % step !== 0) {
                    value = min + Math.round((value - min) / step) * step;
                    showQuantityNotice('Quantity must be in multiples of ' + step, 'error');
                }

                $(this).val(value);
                highlightActiveTier(value);
            });
        }
    }

    // Show notice
    function showQuantityNotice(message, type = 'info') {
        $('.wholesale-tiered-pricing-for-woocommerce-notice').remove();
        const noticeClass = type === 'error' ? 'wholesale-tiered-pricing-for-woocommerce-notice error' : 'wholesale-tiered-pricing-for-woocommerce-notice';
        const notice = $('<div class="' + noticeClass + '">' + message + '</div>');
        $('.single-product-summary').prepend(notice);

        setTimeout(function() {
            notice.fadeOut('slow', function() {
                $(this).remove();
            });
        }, 5000);
    }

    // Update price display via AJAX
    function updatePriceDisplay() {
        const qtyInput = $('.qty');
        if (qtyInput.length) {
            qtyInput.on('change', function() {
                const quantity = parseInt($(this).val()) || 1;

                $.ajax({
                    url: wc_add_to_cart_params.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'get_role_based_price',
                        product_id: $('input[name="product_id"], input[name="add-to-cart"]').val(),
                        quantity: quantity,
                        nonce: wc_add_to_cart_params.wc_ajax_nonce
                    },
                    success: function(response) {
                        if (response.success && response.data.price_html) {
                            $('.price').html(response.data.price_html);
                            highlightActiveTier(quantity);
                        }
                    }
                });
            });
        }
    }

    // Row click → set qty
    function initializePricingTable() {
        $('.pricing-table tbody tr').on('click', function() {
            const quantityText = $(this).find('td:first').text().replace('+', '');
            const quantity = parseInt(quantityText);
            if (quantity && $('.qty').length) {
                $('.qty').val(quantity).trigger('change');
                highlightActiveTier(quantity);
                showQuantityNotice('Updated to ' + quantity + ' items for best pricing 🎉');
            }
        });
        $('.pricing-table tbody tr').css('cursor', 'pointer');
    }

    // Init
    validateQuantity();
    updatePriceDisplay();
    initializePricingTable();

    // Cart update re-init
    $(document.body).on('updated_cart_totals', function() {
        validateQuantity();
        updatePriceDisplay();
    });
});
