(function($) {
    'use strict';
    
    const productId = wholesaleTieredPricingVars?.productId;
    const regularPrice = wholesaleTieredPricingVars?.regularPrice;
    let currentQuantity = 1;
    
    function updateTierSelection(quantity) {
        const tiers = $('.radio-tier').get().reverse(); // Start from highest tier
        let selectedTier = null;
        
        tiers.forEach(function(tier) {
            const minQty = parseInt($(tier).data('min-qty'));
            if (quantity >= minQty && !selectedTier) {
                selectedTier = tier;
            }
        });
        
        if (selectedTier) {
            const radio = $(selectedTier).find('.tier-radio-input');
            radio.prop('checked', true);
            
            // Update visual state
            $('.radio-tier').removeClass('active');
            $(selectedTier).addClass('active');
            
        }
    }
    
    // Listen to quantity changes
    $(document).on('change input', '.quantity input.qty, input.qty', function() {
        currentQuantity = parseInt($(this).val()) || 1;
        updateTierSelection(currentQuantity);
    });
    
    // Manual tier selection
    $('.tier-radio-input').on('change', function() {
        if ($(this).is(':checked')) {
            const minQty = parseInt($(this).data('min-qty'));
            const tierPrice = parseFloat($(this).data('price'));
            
            // Update quantity input
            $('.quantity input.qty, input.qty').val(minQty).trigger('change');
            
            // Update visual state
            $('.radio-tier').removeClass('active');
            $(this).closest('.radio-tier').addClass('active');
        }
    });
    
    // Click on label to select
    $('.radio-tier').on('click', function(e) {
        if (!$(e.target).is('input')) {
            $(this).find('.tier-radio-input').prop('checked', true).trigger('change');
        }
    });
    
    // Initialize on page load
    $(document).ready(function() {
        const initialQty = parseInt($('.quantity input.qty, input.qty').val()) || 1;
        updateTierSelection(initialQty);
    });
    
    // For variable products
    $(document).on('found_variation', function(event, variation) {
        const initialQty = parseInt($('.quantity input.qty').val()) || 1;
        setTimeout(function() {
            updateTierSelection(initialQty);
        }, 100);
    });
    
})(jQuery);