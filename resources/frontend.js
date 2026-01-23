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
            qtyInput.on('change input', function() {
                const quantity = parseInt($(this).val()) || 1;
                updateSavingsCalculator(quantity);

                // Use the localized script variables
                const ajaxUrl = typeof whtproleTieredPricingVar !== 'undefined' 
                    ? whtproleTieredPricingVar.ajaxUrl 
                    : (typeof wc_add_to_cart_params !== 'undefined' 
                        ? wc_add_to_cart_params.ajax_url 
                        : ajaxurl || '/wp-admin/admin-ajax.php');
                
                const nonce = typeof whtproleTieredPricingVar !== 'undefined' 
                    ? whtproleTieredPricingVar.nonce 
                    : (typeof wc_add_to_cart_params !== 'undefined' 
                        ? wc_add_to_cart_params.wc_ajax_nonce 
                        : '');

                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'whtprole_get_role_based_price',
                        product_id: $('input[name="product_id"], input[name="add-to-cart"]').val(),
                        quantity: quantity,
                        nonce: nonce
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

    // Update Savings Calculator
    function updateSavingsCalculator(quantity) {
        const calculator = $('.whtprole-savings-calculator');
        if (!calculator.length) {
            return;
        }

        const productId = calculator.data('product-id');
        const regularPrice = parseFloat(calculator.data('regular-price')) || 0;

        if (!productId || !quantity || quantity <= 0) {
            return;
        }

        $.ajax({
            url: whtproleTieredPricingVar.ajaxUrl,
            type: 'POST',
            data: {
                action: 'whtprole_calculate_savings',
                product_id: productId,
                quantity: quantity,
                nonce: whtproleTieredPricingVar.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    const data = response.data;
                    const totalSavings = data.total_savings || 0;
                    const savingsPercent = data.savings_percent || 0;

                    // Use formatted prices from server if available, otherwise format client-side
                    const regularTotalFormatted = data.formatted_regular_total || formatPrice(data.total_regular || (regularPrice * quantity));
                    const discountedTotalFormatted = data.formatted_discounted_total || formatPrice(data.total_discounted || data.total_regular);
                    const totalSavingsFormatted = data.formatted_total_savings || formatPrice(totalSavings);

                    calculator.find('.regular-total').html(regularTotalFormatted);
                    calculator.find('.discounted-total').html(discountedTotalFormatted);
                    calculator.find('.total-savings').html(totalSavingsFormatted + ' <span class="savings-percent">(' + savingsPercent.toFixed(1) + '%)</span>');

                    // Always show calculator, but add class when there are savings
                    calculator.show();
                    if (totalSavings > 0) {
                        calculator.addClass('has-savings');
                    } else {
                        calculator.removeClass('has-savings');
                    }
                }
            }
        });
    }

    // Format price helper
    function formatPrice(price) {
        // Simple price formatting - WooCommerce will handle proper formatting on server side
        const formatted = parseFloat(price).toFixed(2);
        // Try to get currency symbol from WooCommerce if available
        if (typeof wc_add_to_cart_params !== 'undefined' && wc_add_to_cart_params.currency_format_symbol) {
            return wc_add_to_cart_params.currency_format_symbol + formatted;
        }
        // Fallback formatting
        return '$' + formatted;
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

    // Initialize Savings Calculator on page load
    function initializeSavingsCalculator() {
        const calculator = $('.whtprole-savings-calculator');
        if (calculator.length) {
            // Show calculator by default
            calculator.show();
            
            const qtyInput = $('.qty');
            if (qtyInput.length) {
                const initialQuantity = parseInt(qtyInput.val()) || 1;
                updateSavingsCalculator(initialQuantity);
            } else {
                // If no quantity input, show with default quantity of 1
                updateSavingsCalculator(1);
            }
        }
    }

    // Init
    validateQuantity();
    updatePriceDisplay();
    initializePricingTable();
    initializeSavingsCalculator();

    // Cart update re-init
    $(document.body).on('updated_cart_totals', function() {
        validateQuantity();
        updatePriceDisplay();
        initializeSavingsCalculator();
    });
});
