<template>
        <div class="tiered-pricing-wrapper" v-loading="loading">
        <div class="page-header">
            <h1>Tiered Pricing Rules</h1>
            <p class="description">Configure pricing rules and discounts for different user roles</p>
        </div>

        <!-- Pricing Rules -->
        <div v-if="pricingRules.length > 0" class="rules-container">
            <div v-for="(rule, index) in pricingRules" :key="rule.id" class="rule-card">
                <div class="rule-header" @click="toggleRule(rule.id)">
                    <h3 class="rule-title">{{ getRuleTitle(rule) }} - Rule #{{ index + 1 }}</h3>
                    <div class="header-actions">
                        <button type="button" class="btn-icon btn-danger" @click.stop="removeRule(index)">
                            <svg width="14" height="14" viewBox="0 0 20 20" fill="none">
                                <path d="M15 5L5 15M5 5L15 15" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" />
                            </svg>
                        </button>
                        <svg style="margin-top: -3px;" class="chevron" :class="{ 'rotate': activeRules.includes(rule.id) }" width="16" height="16"
                            viewBox="0 0 16 16" fill="none">
                            <path d="M6 8L10 12L14 8" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                    </div>
                </div>

                <transition name="slide">
                    <div v-show="activeRules.includes(rule.id)" class="rule-body">
                        <div class="form-group">
                            <label>User Roles (Select Multiple)</label>
                            <el-select
                                v-model="rule.roles"
                                multiple
                                placeholder="Select user roles"
                                style="width: 100%"
                                @change="handleRolesChange(index)"
                                collapse-tags
                                collapse-tags-tooltip
                                :max-collapse-tags="2">
                                <el-option
                                    label="Global (All Logged-in Users)"
                                    value="guest">
                                </el-option>
                                <el-option
                                    v-for="role in userRoles"
                                    :key="role.key"
                                    :label="role.name"
                                    :value="role.key">
                                </el-option>
                            </el-select>
                            <p class="description" style="margin-top: 5px; font-size: 12px; color: #666;">
                                Select one or more user roles. "Global" applies to all logged-in users.
                            </p>
                            <p v-if="rule.roles && rule.roles.length > 0" style="margin-top: 5px; font-size: 12px; color: #999;">
                                <strong>Selected:</strong> {{ getSelectedRolesLabel(rule.roles) }}
                            </p>
                        </div>

                        <div v-if="hasGlobalRole(rule.roles)" class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" v-model="rule.also_for_guest" />
                                <span>Make it for guest user also</span>
                            </label>
                            <p class="description" style="margin-top: 5px; font-size: 12px; color: #666;">
                                When enabled, this Global pricing rule will also apply to guest (non-logged-in) users
                            </p>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Min Quantity</label>
                                <input type="number" v-model.number="rule.min_qty" class="form-control" min="1" />
                            </div>

                            <div class="form-group">
                                <label>Max Quantity</label>
                                <input type="text" v-model="rule.max_qty" class="form-control"
                                    placeholder="e.g., 100 or unlimited" />
                            </div>

                            <div class="form-group">
                                <label>Step Quantity</label>
                                <input type="number" v-model.number="rule.step_qty" class="form-control" min="1" />
                            </div>
                        </div>

                        <!-- Tiered Pricing Section -->
                        <div class="tiers-section">
                            <div class="tiers-header">
                                <div class="tiers-title">
                                    <h4 style="margin-bottom: 6px;">Tiered Pricing</h4>
                                    <p class="description">Set pricing tiers for different quantities</p>
                                </div>
                                <button type="button" class="btn btn-sm" @click="addTier(index)">
                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                        <path d="M8 3V13M3 8H13" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" />
                                    </svg>
                                    Add Tier
                                </button>
                            </div>

                            <div class="tiers-list">
                                <div v-for="(tier, tierIndex) in rule.tiered_pricing" :key="tier.id" class="tier-item">
                                    <div class="tier-field">
                                        <label>Min Qty</label>
                                        <input type="number" v-model.number="tier.min_qty" class="form-control-sm"
                                            min="0" />
                                    </div>

                                    <div class="tier-field tier-type">
                                        <label>Type</label>
                                        <select v-model="tier.discount_type" class="form-control-sm">
                                            <option value="percentage">Percentage</option>
                                            <option value="fixed">Fixed Amount</option>
                                        </select>
                                    </div>

                                    <div class="tier-field">
                                        <label>Value</label>
                                        <input type="number" v-model.number="tier.price" class="form-control-sm"
                                            step="0.01" min="0" />
                                    </div>

                                    <button type="button" class="btn-icon btn-danger-icon"
                                        @click="removeTier(index, tierIndex)">
                                        <svg width="14" height="14" viewBox="0 0 20 20" fill="none">
                                            <path
                                                d="M7 4V3C7 2.44772 7.44772 2 8 2H12C12.5523 2 13 2.44772 13 3V4M5 4H15M14 4V16C14 16.5523 13.5523 17 13 17H7C6.44772 17 6 16.5523 6 16V4H14Z"
                                                stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                                        </svg>
                                    </button>
                                </div>

                                <div v-if="rule.tiered_pricing.length === 0" class="empty-state">
                                    <p>No tiers added yet. Click "Add Tier" to create pricing tiers.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </transition>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else-if="!loading" class="empty-state-main">
            <svg width="80" height="80" viewBox="0 0 80 80" fill="none">
                <circle cx="40" cy="40" r="40" fill="#F3F4F6" />
                <path d="M40 24V56M24 40H56" stroke="#9CA3AF" stroke-width="4" stroke-linecap="round" />
            </svg>
            <h3>No Pricing Rules</h3>
            <p>Get started by adding your first pricing rule</p>
            <button type="button" class="btn btn-primary" @click="addRule">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                    <path d="M10 4V16M4 10H16" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                </svg>
                Add Pricing Rule
            </button>
        </div>

        <!-- Bottom Actions -->
        <div v-if="pricingRules.length > 0" class="bottom-actions">
            <button type="button" class="btn btn-primary" @click="addRule">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                    <path d="M10 4V16M4 10H16" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                </svg>
                Add Pricing Rule
            </button>
            <button type="button" class="btn btn-success" @click="saveRules" :disabled="saving">
                {{ saving ? 'Saving...' : 'Save Changes' }}
            </button>
        </div>
    </div>
</template>

<script>
import { ElNotification } from 'element-plus'
export default {
    name: 'TieredPricingRules',

    data() {
        return {
            pricingRules: [],
            activeRules: [],
            ajaxurl: window.whtproleTieredPricingVar?.ajaxUrl,
            nonce: window.whtproleTieredPricingVar?.nonce,
            userRoles: window.whtproleTieredPricingVar?.userRoles,
            saving: false,
            ruleIdCounter: 1,
            tierIdCounter: 1,
            loading: true
        }
    },

    mounted() {
        let userRoles = this.userRoles
        this.userRoles = Object.keys(userRoles).map(role => ({ key: role, name: userRoles[role].name }))
        this.loadRules()
    },

    methods: {
        async loadRules() {
            try {
                this.loading = true
                const response = await fetch(ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        action: 'whtprole_pricing_get_pricing_rules',
                        nonce: this.nonce
                    })
                })
                const data = await response.json()
                // Ensure all rules have the new fields with proper initialization
                this.pricingRules = (data?.data || []).map(rule => {
                    // Normalize roles: support legacy 'role' (string) and new 'roles' (array)
                    let roles = [];
                    if (rule.roles && Array.isArray(rule.roles)) {
                        roles = rule.roles;
                    } else if (rule.role) {
                        // Legacy: single role as string
                        roles = [rule.role];
                    }
                    
                    return {
                        ...rule,
                        roles: roles, // Always use roles array
                        role: roles.length > 0 ? roles[0] : 'customer', // Keep for backward compatibility
                        also_for_guest: rule.also_for_guest === true || rule.also_for_guest === 'true' || rule.also_for_guest === 1 || rule.also_for_guest === '1'
                    }
                })
                this.loading = false
            } catch (error) {
                this.loading = false
                console.error('Error loading rules:', error)
            }
        },

        toggleRule(ruleId) {
            const index = this.activeRules.indexOf(ruleId)
            if (index > -1) {
                this.activeRules.splice(index, 1)
            } else {
                this.activeRules.push(ruleId)
            }
        },

        addRule() {
            const newRule = {
                id: this.pricingRules.length,
                roles: ['customer'], // New: array of roles
                role: 'customer', // Keep for backward compatibility
                step_qty: 1,
                min_qty: 1,
                max_qty: '',
                tiered_pricing: [],
                also_for_guest: false
            }
            // Ensure roles is reactive
            this.$set(newRule, 'roles', ['customer'])
            this.pricingRules.push(newRule)
            this.activeRules.push(newRule.id)
        },

        removeRule(index) {
            if (confirm('Are you sure you want to remove this pricing rule?')) {
                this.pricingRules.splice(index, 1)
            }
        },

        addTier(ruleIndex) {
            const newTier = {
                id: this.tierIdCounter++,
                min_qty: 0,
                discount_type: 'percentage',
                price: 0
            }
            this.pricingRules[ruleIndex].tiered_pricing.push(newTier)
        },

        removeTier(ruleIndex, tierIndex) {
            this.pricingRules[ruleIndex].tiered_pricing.splice(tierIndex, 1)
        },

        getRoleLabel(roleKey) {
            if (roleKey === 'guest') {
                return 'Global'
            }
            const role = this.userRoles.find(r => r.key === roleKey)
            return role ? role.name : roleKey
        },

        getRuleTitle(rule) {
            // Support both new (roles array) and legacy (role string) formats
            const roles = rule.roles && Array.isArray(rule.roles) ? rule.roles : (rule.role ? [rule.role] : [])
            
            if (roles.length === 0) {
                return 'No Role Selected'
            }
            
            if (roles.length === 1) {
                return this.getRoleLabel(roles[0])
            }
            
            // Multiple roles
            const roleLabels = roles.map(r => this.getRoleLabel(r))
            if (roleLabels.length <= 2) {
                return roleLabels.join(' & ')
            }
            return roleLabels.slice(0, 2).join(', ') + ' +' + (roleLabels.length - 2) + ' more'
        },

        hasGlobalRole(roles) {
            if (!roles || !Array.isArray(roles)) {
                return false
            }
            return roles.includes('guest')
        },

        handleRolesChange(ruleIndex) {
            const rule = this.pricingRules[ruleIndex]
            if (!rule.roles || !Array.isArray(rule.roles)) {
                // Ensure roles is always an array
                this.$set(rule, 'roles', [])
                return
            }
            
            // If Global is selected, remove other roles (Global is wildcard)
            if (rule.roles.includes('guest')) {
                // Use $nextTick to ensure Vue reactivity updates properly with el-select
                this.$nextTick(() => {
                    this.$set(rule, 'roles', ['guest'])
                    rule.role = 'guest'
                })
            } else {
                // Update legacy role field for backward compatibility
                if (rule.roles.length > 0) {
                    rule.role = rule.roles[0]
                } else {
                    rule.role = 'customer'
                }
            }
        },

        getSelectedRolesLabel(roles) {
            if (!roles || !Array.isArray(roles) || roles.length === 0) {
                return 'None'
            }
            return roles.map(roleKey => {
                if (roleKey === 'guest') {
                    return 'Global'
                }
                const role = this.userRoles.find(r => r.key === roleKey)
                return role ? role.name : roleKey
            }).join(', ')
        },

        async saveRules() {
            this.saving = true
            try {
                const response = await fetch(ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        action: 'whtprole_pricing_save_pricing_rules',
                        nonce: this.nonce,
                        rules: JSON.stringify(this.pricingRules)
                    })
                })
                const data = await response.json()
                if (data.success) {
                    ElNotification({
                        title: 'Success',
                        message: 'Pricing rules saved successfully',
                        type: 'success',
                    })
                } else {
                    ElNotification({
                        title: 'Error',
                        message: 'Failed to save pricing rules',
                        type: 'error',
                    })
                }
            } catch (error) {
                console.error('Error saving rules:', error)
            } finally {
                this.saving = false
            }
        }
    }
}
</script>