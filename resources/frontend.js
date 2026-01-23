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

                // Get variation ID if it's a variable product
                // Use stored currentVariationId first, then check input field
                let variationId = currentVariationId;
                if (variationId === null) {
                    variationId = getCurrentVariationId();
                }
                
                // Double-check by reading the input field directly
                const variationInput = $('input[name="variation_id"]');
                if (variationInput.length && variationInput.val()) {
                    variationId = parseInt(variationInput.val());
                }
                
                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'whtprole_get_role_based_price',
                        product_id: $('input[name="product_id"], input[name="add-to-cart"]').val(),
                        variation_id: variationId,
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

        // Get variation ID if it's a variable product
        // Use stored currentVariationId first, then check input field
        let variationId = currentVariationId;
        if (variationId === null) {
            variationId = getCurrentVariationId();
        }
        
        // Double-check by reading the input field directly
        const variationInput = $('input[name="variation_id"]');
        if (variationInput.length && variationInput.val()) {
            variationId = parseInt(variationInput.val());
        }
        
        $.ajax({
            url: whtproleTieredPricingVar.ajaxUrl,
            type: 'POST',
            data: {
                action: 'whtprole_calculate_savings',
                product_id: productId,
                variation_id: variationId,
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
    
    // Store current variation ID globally
    let currentVariationId = null;
    
    // Function to get current variation ID
    function getCurrentVariationId() {
        const variationInput = $('input[name="variation_id"]');
        if (variationInput.length && variationInput.val()) {
            return parseInt(variationInput.val());
        }
        // Also check if variation is stored in form data
        const form = $('form.variations_form');
        if (form.length) {
            const formData = form.serializeArray();
            const variationField = formData.find(function(item) {
                return item.name === 'variation_id';
            });
            if (variationField && variationField.value) {
                return parseInt(variationField.value);
            }
        }
        return null;
    }
    
    // Update pricing table prices when variation changes
    function updatePricingTablePrices(variationId) {
        const pricingWrapper = $('.premium-pricing-wrapper');
        if (!pricingWrapper.length) {
            return;
        }
        
        // Get base regular price from data attribute
        let baseRegularPrice = parseFloat(pricingWrapper.data('base-regular-price')) || 0;
        
        // If variation is selected, get its price via AJAX
        if (variationId) {
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
            
            // Get variation price
            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'whtprole_get_variation_price',
                    variation_id: variationId,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        // Use base_price (sale price if available, otherwise regular price)
                        if (response.data.base_price) {
                            baseRegularPrice = parseFloat(response.data.base_price);
                            recalculatePricingTablePrices(baseRegularPrice);
                        } else if (response.data.regular_price) {
                            // Fallback to regular_price for backward compatibility
                            baseRegularPrice = parseFloat(response.data.regular_price);
                            recalculatePricingTablePrices(baseRegularPrice);
                        }
                    }
                },
                error: function() {
                    // Fallback: try to get price from variation data
                    const variationData = $('form.variations_form').data('product_variations');
                    if (variationData && Array.isArray(variationData)) {
                        const variation = variationData.find(function(v) {
                            return v.variation_id == variationId;
                        });
                        if (variation) {
                            // Use display_price (which is the sale price if available, otherwise regular price)
                            if (variation.display_price) {
                                baseRegularPrice = parseFloat(variation.display_price);
                                recalculatePricingTablePrices(baseRegularPrice);
                            } else if (variation.price) {
                                baseRegularPrice = parseFloat(variation.price);
                                recalculatePricingTablePrices(baseRegularPrice);
                            }
                        }
                    } else {
                        // If no variation data, use base price
                        recalculatePricingTablePrices(baseRegularPrice);
                    }
                }
            });
        } else {
            // No variation selected, use base price
            recalculatePricingTablePrices(baseRegularPrice);
        }
    }
    
    // Recalculate and update pricing table prices
    function recalculatePricingTablePrices(regularPrice) {
        if (!regularPrice || regularPrice <= 0) {
            return;
        }
        
        const pricingWrapper = $('.premium-pricing-wrapper');
        if (!pricingWrapper.length) {
            return;
        }
        
        pricingWrapper.data('base-regular-price', regularPrice);
        
        // Update each tier
        pricingWrapper.find('.premium-tier').each(function() {
            const $tier = $(this);
            const tierPrice = parseFloat($tier.data('tier-price')) || 0;
            const discountType = $tier.data('tier-discount-type') || '';
            const minQty = parseInt($tier.data('tier-min-qty')) || 0;
            
            if (!tierPrice || !minQty) {
                return;
            }
            
            // Calculate discount
            let calculatedPrice = 0;
            let savings = 0;
            let savingsPercent = 0;
            
            if (discountType === 'fixed') {
                calculatedPrice = regularPrice - tierPrice;
                savings = regularPrice - calculatedPrice;
                savingsPercent = regularPrice > 0 ? (savings / regularPrice) * 100 : 0;
            } else if (discountType === 'percentage') {
                calculatedPrice = regularPrice - (regularPrice * tierPrice / 100);
                savings = regularPrice - calculatedPrice;
                savingsPercent = tierPrice;
            } else {
                // Direct price override
                calculatedPrice = tierPrice;
                savings = regularPrice - tierPrice;
                savingsPercent = regularPrice > 0 ? (savings / regularPrice) * 100 : 0;
            }
            
            // Ensure price is valid
            if (calculatedPrice <= 0) {
                calculatedPrice = regularPrice;
                savings = 0;
                savingsPercent = 0;
            }
            
            // Update displayed prices using WooCommerce price format
            const $regularPriceEl = $tier.find('.regular-price');
            const $salePriceEl = $tier.find('.sale-price');
            const $savingsBadge = $tier.find('.savings-badge .save-amount');
            const $progressFill = $tier.find('.progress-fill');
            
            // Update regular price (show if there are savings)
            if (savings > 0 && regularPrice != tierPrice) {
                const regularPriceFormatted = formatWooCommercePrice(regularPrice);
                if ($regularPriceEl.length) {
                    $regularPriceEl.html(regularPriceFormatted).show();
                } else {
                    $salePriceEl.before('<span class="regular-price">' + regularPriceFormatted + '</span>');
                }
            } else {
                $regularPriceEl.hide();
            }
            
            // Update sale price
            if ($salePriceEl.length) {
                const salePriceFormatted = formatWooCommercePrice(calculatedPrice);
                $salePriceEl.html(salePriceFormatted);
            }
            
            // Update savings badge
            if (savings > 0) {
                if ($savingsBadge.length) {
                    $savingsBadge.text(Math.round(savingsPercent) + '%');
                }
                if ($tier.find('.savings-badge').length === 0) {
                    $tier.find('.tier-right').html('<div class="savings-badge"><span class="save-amount">' + Math.round(savingsPercent) + '%</span></div>');
                }
            } else {
                $tier.find('.savings-badge').remove();
            }
            
            // Update progress bar
            if ($progressFill.length) {
                const progressWidth = Math.min(100, (savingsPercent / 50) * 100);
                $progressFill.css('width', progressWidth + '%');
            }
        });
        
        // Recalculate featured tier
        updateFeaturedTier();
    }
    
    // Update featured tier based on best savings
    function updateFeaturedTier() {
        const pricingWrapper = $('.premium-pricing-wrapper');
        if (!pricingWrapper.length) {
            return;
        }
        
        let bestSavingsPercent = 0;
        let bestTierIndex = -1;
        
        pricingWrapper.find('.premium-tier').each(function(index) {
            const savingsText = $(this).find('.save-amount').text();
            if (savingsText) {
                const savingsPercent = parseFloat(savingsText.replace('%', ''));
                if (savingsPercent > bestSavingsPercent) {
                    bestSavingsPercent = savingsPercent;
                    bestTierIndex = index;
                }
            }
        });
        
        // Update featured classes
        pricingWrapper.find('.premium-tier').each(function(index) {
            const $tier = $(this);
            if (index === bestTierIndex && bestSavingsPercent > 0) {
                $tier.addClass('featured-tier');
                if ($tier.find('.featured-badge').length === 0) {
                    $tier.prepend('<span class="featured-badge">Best</span>');
                }
            } else {
                $tier.removeClass('featured-tier');
                $tier.find('.featured-badge').remove();
            }
        });
    }
    
    // Format price using WooCommerce format
    function formatWooCommercePrice(price) {
        // Try to use WooCommerce's price format if available
        if (typeof wc_add_to_cart_params !== 'undefined') {
            const decimals = parseInt(wc_add_to_cart_params.currency_format_num_decimals) || 2;
            const decimalSep = wc_add_to_cart_params.currency_format_decimal_sep || '.';
            const thousandSep = wc_add_to_cart_params.currency_format_thousand_sep || ',';
            const symbol = wc_add_to_cart_params.currency_format_symbol || '$';
            const position = wc_add_to_cart_params.currency_format_symbol_pos || 'left';
            
            let formatted = parseFloat(price).toFixed(decimals);
            formatted = formatted.replace('.', decimalSep);
            if (thousandSep && thousandSep !== decimalSep) {
                const parts = formatted.split(decimalSep);
                parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousandSep);
                formatted = parts.join(decimalSep);
            }
            
            return position === 'left' ? symbol + formatted : formatted + symbol;
        }
        
        // Fallback formatting
        return '$' + parseFloat(price).toFixed(2);
    }
    
    // Handle variation changes for variable products
    function handleVariationChange() {
        $(document).on('found_variation', function(event, variation) {
            // Store the current variation ID
            currentVariationId = variation.variation_id || getCurrentVariationId();
            
            // Update pricing table with new variation price
            if (currentVariationId) {
                updatePricingTablePrices(currentVariationId);
            }
            
            // Update pricing when variation is selected
            const qtyInput = $('.qty');
            if (qtyInput.length) {
                const quantity = parseInt(qtyInput.val()) || 1;
                // Small delay to ensure variation input is updated
                setTimeout(function() {
                    updatePriceDisplay();
                    updateSavingsCalculator(quantity);
                }, 100);
            }
        });
        
        // Also listen for variation clearing
        $(document).on('reset_data', function() {
            currentVariationId = null;
            // Reset pricing table to base price
            const pricingWrapper = $('.premium-pricing-wrapper');
            if (pricingWrapper.length) {
                const basePrice = parseFloat(pricingWrapper.data('base-regular-price')) || 0;
                recalculatePricingTablePrices(basePrice);
            }
            
            const qtyInput = $('.qty');
            if (qtyInput.length) {
                const quantity = parseInt(qtyInput.val()) || 1;
                updatePriceDisplay();
                updateSavingsCalculator(quantity);
            }
        });
        
        // Listen for variation input changes directly
        $(document).on('change', 'input[name="variation_id"]', function() {
            currentVariationId = $(this).val() ? parseInt($(this).val()) : null;
            
            // Update pricing table with new variation price
            if (currentVariationId) {
                updatePricingTablePrices(currentVariationId);
            } else {
                const pricingWrapper = $('.premium-pricing-wrapper');
                if (pricingWrapper.length) {
                    const basePrice = parseFloat(pricingWrapper.data('base-regular-price')) || 0;
                    recalculatePricingTablePrices(basePrice);
                }
            }
            
            const qtyInput = $('.qty');
            if (qtyInput.length) {
                const quantity = parseInt(qtyInput.val()) || 1;
                updatePriceDisplay();
                updateSavingsCalculator(quantity);
            }
        });
    }

    // Init
    validateQuantity();
    updatePriceDisplay();
    initializePricingTable();
    initializeSavingsCalculator();
    handleVariationChange();

    // Cart update re-init
    $(document.body).on('updated_cart_totals', function() {
        validateQuantity();
        updatePriceDisplay();
        initializeSavingsCalculator();
    });
});
