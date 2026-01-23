jQuery(document).ready(function($) {
    var ruleIndex = $('.pricing-rule-row').length;
    var userRoles = []; // Store user roles globally
    
    function fetchUserRoles(callback) {
        if (userRoles.length > 0) {
            callback(userRoles);
            return;
        }
        
        $.ajax({
            url: ajaxurl, // WordPress AJAX URL
            type: 'POST',
            data: {
                action: 'whtprole_get_user_roles'
            },
            success: function(response) {
                if (response.success) {
                    userRoles = response.data;
                    callback(userRoles);
                }
            },
            error: function() {
                console.error('Failed to fetch user roles');
                callback([]);
            }
        });
    }
    
    // Generate role options HTML
    function generateRoleOptions(roles) {
        var options = '<option value="">Select Role</option>';
        options += '<option value="guest">Global</option>';
        $.each(roles, function(roleKey, roleName) {
            options += `<option value="${roleKey}">${roleName}</option>`;
        });
        return options;
    }
    
    // Add new pricing rule
    $('#add-pricing-rule').on('click', function(e) {
        e.preventDefault();
        
        fetchUserRoles(function(roles) {
            var roleOptions = generateRoleOptions(roles);
            
            var newRule = `
                <div class="pricing-rule-row" data-index="${ruleIndex}">
                    <a href="#" class="remove-pricing-rule">Remove</a>
                    <div class="pricing-rule-fields">
                        <p class="form-field">
                            <label>User Role</label>
                            <select name="role_pricing_rules[${ruleIndex}][role]">
                                ${roleOptions}
                            </select>
                        </p>
                        <p class="form-field">
                            <label>Step Quantity</label>
                            <input type="number" name="role_pricing_rules[${ruleIndex}][step_qty]" value="1" min="1" style="width: 100%;"/>
                        </p>
                        <p class="form-field">
                            <label>Min Quantity</label>
                            <input type="number" name="role_pricing_rules[${ruleIndex}][min_qty]" value="0" min="0" style="width: 100%;" />
                        </p>
                        <p class="form-field">
                            <label>Max Quantity</label>
                            <input type="number" name="role_pricing_rules[${ruleIndex}][max_qty]" value="" min="0" placeholder="Unlimited" style="width: 100%;" />
                        </p>
                    </div>
                    <div class="tiered-pricing-section">
                        <h4>Tiered Pricing</h4>
                        <div class="tiered-pricing-rules"></div>
                        <button type="button" class="button add-tier-rule" data-parent="${ruleIndex}">Add Tier</button>
                    </div>
                </div>
            `;
            
            $('#role-pricing-rules').append(newRule);
            ruleIndex++;
        });
    });
    
    // Remove pricing rule
    $(document).on('click', '.remove-pricing-rule', function(e) {
        e.preventDefault();
        $(this).closest('.pricing-rule-row').remove();
    });
    
    // Add tier rule
    $(document).on('click', '.add-tier-rule', function(e) {
        e.preventDefault();
        var parentIndex = $(this).data('parent');
        var tierIndex = $(this).siblings('.tiered-pricing-rules').find('.tier-rule-row').length;
        
        var newTier = `
            <div class="tier-rule-row">
                <input type="number" name="role_pricing_rules[${parentIndex}][tiered_pricing][${tierIndex}][min_qty]" 
                       placeholder="Min Qty" min="1" style="width: 150px;" />
                <input type="number" name="role_pricing_rules[${parentIndex}][tiered_pricing][${tierIndex}][price]" 
                       placeholder="Price" step="0.01" min="0" style="width: 150px;" />
                <button type="button" class="button remove-tier-rule">Remove</button>
                <select name="role_pricing_rules[${parentIndex}][tiered_pricing][${tierIndex}][discount_type]">
                    <option value="fixed">Fixed</option>
                    <option value="percentage">Percentage</option>
                </select>
            </div>
        `;
        
        $(this).siblings('.tiered-pricing-rules').append(newTier);
    });
    
    // Remove tier rule
    $(document).on('click', '.remove-tier-rule', function(e) {
        e.preventDefault();
        $(this).closest('.tier-rule-row').remove();
    });
});