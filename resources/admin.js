jQuery(document).ready(function($) {
    var ruleIndex = $('.pricing-rule-row').length;
    // Get user roles from localized script or fetch via AJAX
    var userRoles = (typeof whtproleAdminRoles !== 'undefined' && whtproleAdminRoles.roles) ? whtproleAdminRoles.roles : {};
    
    function fetchUserRoles(callback) {
        // If we already have roles from localized script, use them
        if (Object.keys(userRoles).length > 0) {
            callback(userRoles);
            return;
        }
        
        // Otherwise try to get from existing select elements
        var existingRoles = {};
        $('.role-multi-select option').each(function() {
            var value = $(this).val();
            var text = $(this).text();
            if (value && value !== '' && value !== 'guest') {
                existingRoles[value] = text;
            }
        });
        
        if (Object.keys(existingRoles).length > 0) {
            userRoles = existingRoles;
            callback(userRoles);
            return;
        }
        
        // Last resort: try AJAX
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
                } else {
                    callback({});
                }
            },
            error: function() {
                console.error('Failed to fetch user roles');
                callback({});
            }
        });
    }
    
    // Generate role options HTML for multi-select
    function generateRoleOptions(roles, selectedRoles) {
        selectedRoles = selectedRoles || [];
        var options = '<option value="guest"' + (selectedRoles.indexOf('guest') !== -1 ? ' selected' : '') + '>Global (All Logged-in Users)</option>';
        $.each(roles, function(roleKey, roleName) {
            var selected = selectedRoles.indexOf(roleKey) !== -1 ? ' selected' : '';
            options += `<option value="${roleKey}"${selected}>${roleName}</option>`;
        });
        return options;
    }
    
    // Add new pricing rule
    $('#add-pricing-rule').on('click', function(e) {
        e.preventDefault();
        
        // Try to get roles from existing select elements first, or fetch via AJAX
        var existingRoles = {};
        $('.role-multi-select option').each(function() {
            var value = $(this).val();
            var text = $(this).text();
            if (value && value !== '' && value !== 'guest') {
                existingRoles[value] = text;
            }
        });
        
        // If we have existing roles, use them; otherwise fetch via AJAX
        if (Object.keys(existingRoles).length > 0) {
            var roleOptions = generateRoleOptions(existingRoles, ['customer']); // Default to customer
            addNewRule(roleOptions);
        } else {
            fetchUserRoles(function(roles) {
                var roleOptions = generateRoleOptions(roles, ['customer']); // Default to customer
                addNewRule(roleOptions);
            });
        }
    });
    
    // Function to add new rule
    function addNewRule(roleOptions) {
        var newRule = `
                <div class="pricing-rule-row" data-index="${ruleIndex}">
                    <a href="#" class="remove-pricing-rule">Remove</a>
                    <div class="pricing-rule-fields">
                        <p class="form-field">
                            <label>User Roles (Select Multiple)</label>
                            <select name="role_pricing_rules[${ruleIndex}][roles][]" 
                                    multiple 
                                    class="role-multi-select" 
                                    style="min-height: 120px; width: 100%;"
                                    data-index="${ruleIndex}">
                                ${roleOptions}
                            </select>
                            <p class="description" style="margin-top: 5px; font-size: 12px; color: #666;">
                                Select one or more user roles. "Global" applies to all logged-in users. Hold Ctrl/Cmd to select multiple.
                            </p>
                            <input type="hidden" name="role_pricing_rules[${ruleIndex}][role]" value="customer" />
                        </p>
                        <p class="form-field guest-checkbox-field" style="display: none;">
                            <label>
                                <input type="checkbox" 
                                       name="role_pricing_rules[${ruleIndex}][also_for_guest]" 
                                       value="1" />
                                Make it for guest user also
                            </label>
                            <span class="description" style="display: block; margin-top: 5px; font-size: 12px; color: #666;">
                                When enabled, this Global pricing rule will also apply to guest (non-logged-in) users
                            </span>
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
        
        var $newRule = $(newRule);
        $('#role-pricing-rules').append($newRule);
        
        // Initialize Select2 on the multi-select (like Element Plus)
        var $select = $newRule.find('.role-multi-select');
        if ($select.length) {
            // Set default selection to customer
            $select.val(['customer']);
            
            // Initialize Select2 with Element Plus-like styling
            $select.select2({
                placeholder: 'Select user roles',
                allowClear: false,
                width: '100%',
                closeOnSelect: false,
                tags: false,
                multiple: true,
                templateResult: function(data) {
                    if (!data.id) {
                        return data.text;
                    }
                    return $('<span>' + data.text + '</span>');
                },
                templateSelection: function(data) {
                    if (!data.id) {
                        return data.text;
                    }
                    return $('<span class="select2-selection__choice">' + data.text + '</span>');
                }
            });
            
            // Trigger change to update hidden field
            $select.trigger('change');
        }
        
        ruleIndex++;
    }
    
    // Initialize Select2 on existing multi-selects on page load
    function initializeSelect2() {
        $('.role-multi-select').each(function() {
            if (!$(this).hasClass('select2-hidden-accessible')) {
                $(this).select2({
                    placeholder: 'Select user roles',
                    allowClear: false,
                    width: '100%',
                    closeOnSelect: false,
                    tags: false,
                    multiple: true,
                    templateResult: function(data) {
                        if (!data.id) {
                            return data.text;
                        }
                        return $('<span>' + data.text + '</span>');
                    },
                    templateSelection: function(data) {
                        if (!data.id) {
                            return data.text;
                        }
                        return $('<span class="select2-selection__choice">' + data.text + '</span>');
                    }
                });
            }
        });
    }
    
    // Initialize Select2 when document is ready
    initializeSelect2();
    
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
    
    // Handle multi-select role changes (works with Select2)
    $(document).on('change', '.role-multi-select', function() {
        var $select = $(this);
        var ruleIndex = $select.data('index');
        var selectedValues = $select.val() || [];
        var $row = $select.closest('.pricing-rule-row');
        var $guestCheckbox = $row.find('.guest-checkbox-field');
        var $hiddenRole = $row.find('input[name*="[role]"]');
        
        // Check if Global (guest) is selected
        var hasGlobal = Array.isArray(selectedValues) ? selectedValues.indexOf('guest') !== -1 : selectedValues === 'guest';
        
        // If Global is selected, remove other roles (wildcard behavior)
        if (hasGlobal && Array.isArray(selectedValues) && selectedValues.length > 1) {
            $select.val(['guest']).trigger('change');
            selectedValues = ['guest'];
        }
        
        // Show/hide guest checkbox based on Global selection
        if (hasGlobal) {
            $guestCheckbox.show();
        } else {
            $guestCheckbox.hide();
            $guestCheckbox.find('input[type="checkbox"]').prop('checked', false);
        }
        
        // Update hidden role field for backward compatibility
        if (Array.isArray(selectedValues) && selectedValues.length > 0) {
            $hiddenRole.val(selectedValues[0]);
        } else if (selectedValues) {
            $hiddenRole.val(selectedValues);
        } else {
            $hiddenRole.val('customer');
        }
    });
    
    // Initialize guest checkbox visibility on page load
    $('.role-multi-select').each(function() {
        var $select = $(this);
        var selectedValues = $select.val() || [];
        var hasGlobal = Array.isArray(selectedValues) ? selectedValues.indexOf('guest') !== -1 : selectedValues === 'guest';
        var $row = $select.closest('.pricing-rule-row');
        var $guestCheckbox = $row.find('.guest-checkbox-field');
        
        if (hasGlobal) {
            $guestCheckbox.show();
        } else {
            $guestCheckbox.hide();
        }
    });
    
    // Re-initialize Select2 when new rules are added via AJAX or DOM manipulation
    $(document).on('DOMNodeInserted', '#role-pricing-rules', function(e) {
        if ($(e.target).hasClass('pricing-rule-row') || $(e.target).closest('.pricing-rule-row').length) {
            setTimeout(function() {
                initializeSelect2();
            }, 100);
        }
    });
});