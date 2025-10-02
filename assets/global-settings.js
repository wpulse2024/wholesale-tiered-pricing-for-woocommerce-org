(function($){
    let ruleIndex = $('.pricing-rule').length;
    let userRoles = wholesaleTieredPricingVars?.userRoles;
    $('#add-rule').on('click', function(){
        let html = `<div class="pricing-rule" style="border:1px solid #ccc;padding:15px;margin-bottom:15px;background:#fff;">
            <label>User Role:
                <select name="pricing_rules[`+ruleIndex+`][role]">
                    ${Object.keys(userRoles).map(role => `<option value="${role}">${userRoles[role].name}</option>`).join('')}
                </select>
            </label>

            <label>Step Quantity:
                <input type="number" name="pricing_rules[`+ruleIndex+`][step_qty]" value="1">
            </label>

            <label>Min Quantity:
                <input type="number" name="pricing_rules[`+ruleIndex+`][min_qty]" value="0">
            </label>

            <label>Max Quantity:
                <input type="text" name="pricing_rules[`+ruleIndex+`][max_qty]" value="Unlimited">
            </label>

            <h4>Tiered Pricing</h4>
            <div class="tiers"></div>
            <button type="button" class="add-tier" style="background:#0073aa;color:#fff;border:none;padding:4px 8px;cursor:pointer;">Add Tier</button>

            <button type="button" class="remove-rule" style="background:#dc3545;color:#fff;border:none;padding:4px 8px;cursor:pointer;">Remove Rule</button>
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
            <button type="button" class="remove-tier" style="background:#dc3545;color:#fff;border:none;padding:4px 8px;cursor:pointer;">Remove</button>
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