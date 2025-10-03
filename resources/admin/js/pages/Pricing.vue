<template>
    <div class="tiered-pricing-wrapper">
        <div class="page-header">
            <h1>Tiered Pricing Rules</h1>
            <p class="description">Configure pricing rules and discounts for different user roles</p>
        </div>

        <!-- Pricing Rules -->
        <div class="rules-container">
            <div v-for="(rule, index) in pricingRules" :key="rule.id" class="rule-card">
                <div class="rule-header" @click="toggleRule(rule.id)">
                    <h3 class="rule-title">{{ getRoleLabel(rule.role) }} - Rule #{{ index + 1 }}</h3>
                    <div class="header-actions">
                        <button type="button" class="btn-icon btn-danger" @click.stop="removeRule(index)">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                                <path d="M15 5L5 15M5 5L15 15" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" />
                            </svg>
                        </button>
                        <svg class="chevron" :class="{ 'rotate': activeRules.includes(rule.id) }" width="20" height="20"
                            viewBox="0 0 20 20" fill="none">
                            <path d="M6 8L10 12L14 8" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                    </div>
                </div>

                <transition name="slide">
                    <div v-show="activeRules.includes(rule.id)" class="rule-body">
                        <div class="form-group">
                            <label>User Role</label>
                            <select v-model="rule.role" class="form-control">
                                <option v-for="role in userRoles" :key="role.key" :value="role.key">
                                    {{ role.name }}
                                </option>
                            </select>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Step Quantity</label>
                                <input type="number" v-model.number="rule.step_qty" class="form-control" min="1" />
                            </div>

                            <div class="form-group">
                                <label>Min Quantity</label>
                                <input type="number" v-model.number="rule.min_qty" class="form-control" min="1" />
                            </div>

                            <div class="form-group">
                                <label>Max Quantity</label>
                                <input type="text" v-model="rule.max_qty" class="form-control"
                                    placeholder="e.g., 100 or unlimited" />
                            </div>
                        </div>

                        <!-- Tiered Pricing Section -->
                        <div class="tiers-section">
                            <div class="tiers-header">
                                <h4>Tiered Pricing</h4>
                                <button type="button" class="btn btn-primary btn-sm" @click="addTier(index)">
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
                                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
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
        <div v-if="pricingRules.length === 0" class="empty-state-main">
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
            ajaxurl: window.wholesaleTieredPricingVars?.ajaxUrl,
            nonce: window.wholesaleTieredPricingVars?.nonce,
            userRoles: window.wholesaleTieredPricingVars?.userRoles,
            saving: false,
            ruleIdCounter: 1,
            tierIdCounter: 1
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
                console.log('loading rules')
                const response = await fetch(ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        action: 'wc_role_pricing_get_pricing_rules',
                        nonce: this.nonce
                    })
                })
                const data = await response.json()
                this.pricingRules = data?.data
            } catch (error) {
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
                id: this.ruleIdCounter++,
                role: 'customer',
                step_qty: 1,
                min_qty: 1,
                max_qty: '',
                tiered_pricing: []
            }
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
            const role = this.userRoles.find(r => r.key === roleKey)
            return role ? role.name : roleKey
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
                        action: 'wc_role_pricing_save_pricing_rules',
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

<style scoped>
* {
    box-sizing: border-box;
}

.tiered-pricing-wrapper {
    max-width: 1200px;
    margin: 0 auto;
    padding: 32px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.page-header {
    margin-bottom: 32px;
}

.page-header h1 {
    font-size: 32px;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 8px 0;
}

.description {
    color: #6b7280;
    font-size: 16px;
    margin: 0;
}

/* Rule Cards */
.rules-container {
    display: flex;
    flex-direction: column;
    gap: 16px;
    margin-bottom: 24px;
}

.rule-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    overflow: hidden;
}

.rule-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    background: #fff;
    cursor: pointer;
    transition: background 0.2s;
}

.rule-header:hover {
    background: #f9fafb;
}

.rule-title {
    font-size: 16px;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 12px;
}

.chevron {
    transition: transform 0.3s;
    color: #6b7280;
}

.chevron.rotate {
    transform: rotate(180deg);
}

.rule-body {
    padding: 24px;
    border-top: 1px solid #e5e7eb;
}

/* Form Styles */
.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-size: 14px;
    font-weight: 500;
    color: #374151;
    margin-bottom: 6px;
}

.form-control,
.form-control-sm {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    transition: border-color 0.2s;
}

.form-control:focus,
.form-control-sm:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-control-sm {
    padding: 8px 10px;
    font-size: 13px;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
}

/* Tiers Section */
.tiers-section {
    margin-top: 24px;
    padding-top: 24px;
    border-top: 1px solid #e5e7eb;
}

.tiers-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.tiers-header h4 {
    font-size: 16px;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
}

.tiers-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.tier-item {
    display: flex;
    align-items: flex-end;
    gap: 12px;
    padding: 16px;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
}

.tier-field {
    flex: 1;
}

.tier-field label {
    display: block;
    font-size: 13px;
    font-weight: 500;
    color: #374151;
    margin-bottom: 6px;
}

.tier-type {
    flex: 1.5;
}

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-primary {
    background: #3b82f6;
    color: #fff;
}

.btn-primary:hover:not(:disabled) {
    background: #2563eb;
}

.btn-success {
    background: #10b981;
    color: #fff;
}

.btn-success:hover:not(:disabled) {
    background: #059669;
}

.btn-sm {
    padding: 8px 16px;
    font-size: 13px;
}

.btn-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 8px;
    border: none;
    border-radius: 6px;
    background: transparent;
    cursor: pointer;
    transition: all 0.2s;
    color: #6b7280;
}

.btn-icon:hover {
    background: #f3f4f6;
}

.btn-danger {
    color: #ef4444;
}

.btn-danger:hover {
    background: #fee2e2;
}

.btn-danger-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 8px;
    border: none;
    border-radius: 6px;
    background: #fee2e2;
    color: #ef4444;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-danger-icon:hover {
    background: #fecaca;
}

/* Empty States */
.empty-state {
    text-align: center;
    padding: 24px;
    color: #9ca3af;
    font-size: 14px;
}

.empty-state-main {
    text-align: center;
    padding: 64px 24px;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    margin-bottom: 24px;
}

.empty-state-main h3 {
    font-size: 20px;
    color: #374151;
    margin: 16px 0 8px 0;
}

.empty-state-main p {
    color: #6b7280;
    margin: 0 0 24px 0;
}

/* Bottom Actions */
.bottom-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
}

/* Animations */
.slide-enter-active,
.slide-leave-active {
    transition: all 0.3s ease;
    max-height: 1000px;
}

.slide-enter-from,
.slide-leave-to {
    max-height: 0;
    opacity: 0;
}

/* Responsive */
@media (max-width: 768px) {
    .tiered-pricing-wrapper {
        padding: 16px;
    }

    .form-row {
        grid-template-columns: 1fr;
    }

    .tier-item {
        flex-wrap: wrap;
    }

    .bottom-actions {
        flex-direction: column;
        gap: 12px;
    }

    .bottom-actions button {
        width: 100%;
    }
}
</style>