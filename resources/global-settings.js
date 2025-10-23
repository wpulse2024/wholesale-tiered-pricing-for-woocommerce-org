(function($){
    let ruleIndex = $('.pricing-rule').length;
    let userRoles = whtproleTieredPricingVar?.userRoles;
    $('#add-rule').on('click', function(){
        let html = `<div class="pricing-rule" style="border:1px solid #ccc;padding:15px;margin-bottom:15px;background:#fff;">
            <div class="pricing-rule-header_wrapper">
                <div class="pricing-rule-header">
                    <label>User Role:</label>
                    <select name="pricing_rules[`+ruleIndex+`][role]">
                        ${Object.keys(userRoles).map(role => `<option value="${role}">${userRoles[role].name}</option>`).join('')}
                    </select>
                </div>
                <div class="pricing-rule-header">
                    <label>Step Quantity:</label>
                    <input type="number" name="pricing_rules[`+ruleIndex+`][step_qty]" value="1">
                </div>
                <div class="pricing-rule-header">
                    <label>Min Quantity:</label>
                    <input type="number" name="pricing_rules[`+ruleIndex+`][min_qty]" value="0">
                </div>
                <div class="pricing-rule-header">
                    <label>Max Quantity:</label>
                    <input type="text" name="pricing_rules[`+ruleIndex+`][max_qty]" value="Unlimited">
                </div>
            </div>

            <h4>Tiered Pricing</h4>
            <div class="tiers"></div>
            <button type="button" class="remove-rule" style="color:#000;border:none;padding:6px 12px;cursor:pointer; border-radius:12px; background: #E8EAF1;">Remove Rule</button>
            <button type="button" class="add-tier" style="color:#000;border:none; padding:6px 12px;cursor:pointer; border-radius:12px; background:#E8EAF1;">Add Tier</button>
        </div>`;
        $('#pricing-rules-container').append(html);
        ruleIndex++;
    });

    $(document).on('click', '.add-tier', function(){
        let $rule = $(this).closest('.pricing-rule');
        let rIndex = $rule.index();
        let tierCount = $rule.find('.tier').length;
        let html = `<div class="tier" style="margin-bottom:8px;">
            <input type="number" name="pricing_rules[`+rIndex+`][tiered_pricing][`+tierCount+`][min_qty]" placeholder="Min Qty">

            <select name="pricing_rules[`+rIndex+`][tiered_pricing][`+tierCount+`][discount_type]">
                <option value="percentage">Percentage</option>
                <option value="fixed">Fixed Amount</option>
            </select>

            <input type="number" name="pricing_rules[`+rIndex+`][tiered_pricing][`+tierCount+`][price]" placeholder="Discount Value">
            <button type="button" class="remove-tier" style="background:#868A98;color:#fff;border:none;padding:10px; border-radius:12px;cursor:pointer;">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>`;
        $rule.find('.tiers').append(html);
    });

    $(document).on('click', '.remove-tier', function(){
        $(this).closest('.tier').remove();
    });

    $(document).on('click', '.remove-rule', function(){
        $(this).closest('.pricing-rule').remove();
    });
})(jQuery);