<template>
    <div class="product-inclusion-wrapper">
        <div class="page-header">
            <h1>Product Inclusion & Exclusion</h1>
            <p class="description">Configure which products or categories the pricing rules apply to</p>
        </div>
        <div class="page-header">
            <h1>Apply to</h1>
            <p class="description">Choose whether to include or exclude products from the pricing rules</p>
            <el-radio-group v-model="applyType">
                <el-radio label="include">Include</el-radio>
                <el-radio label="exclude">Exclude</el-radio>
            </el-radio-group>
        </div>
        <!-- Included Products Section -->
        <div class="section-card" v-if="applyType === 'include'">
            <div class="section-header">
                <h2>
                    Included Products
                </h2>
                <p class="description">Select which products or categories should have the pricing rules applied.</p>
            </div>

            <div class="info-notice">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                    <circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="1.5" />
                    <path d="M10 6H10.01M10 9V14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                </svg>
                <p>
                    If you do not specify products or product categories, the rule will apply to all products in your
                    store
                    (excluding those selected in the exclusions section).
                </p>
            </div>

            <div class="form-group-horizontal">
                <label>Apply for categories</label>
                <div class="input-wrapper">
                    <div class="search-input-container">
                        <svg class="search-icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <circle cx="9" cy="9" r="6" stroke="currentColor" stroke-width="1.5" />
                            <path d="M13.5 13.5L17 17" stroke="currentColor" stroke-width="1.5"
                                stroke-linecap="round" />
                        </svg>
                        <el-select filterable v-model="includeCategories" multiple
                            placeholder="Search for a category ...">
                            <el-option v-for="category in categories" :key="category.term_id" :label="category.name"
                                :value="category.term_id"></el-option>
                        </el-select>
                    </div>
                </div>
            </div>

            <div class="form-group-horizontal">
                <label>Apply for specific products</label>
                <div class="input-wrapper">
                    <div class="search-input-container">
                        <svg class="search-icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <circle cx="9" cy="9" r="6" stroke="currentColor" stroke-width="1.5" />
                            <path d="M13.5 13.5L17 17" stroke="currentColor" stroke-width="1.5"
                                stroke-linecap="round" />
                        </svg>
                        <el-select filterable v-model="includeProducts" multiple
                            placeholder="Search for a product ...">
                            <el-option v-for="product in products" :key="product.id" :label="product.name"
                                :value="product.id"></el-option>
                        </el-select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Exclusions Section -->
        <div class="section-card" v-if="applyType === 'exclude'">
            <div class="section-header">
                <h2>
                    Excluded Products
                </h2>
                <p class="description">Select which products or categories should be excluded from the pricing rules.</p>
            </div>

            <div class="form-group-horizontal">
                <label>Exclude for categories</label>
                <div class="input-wrapper">
                    <div class="search-input-container">
                        <svg class="search-icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <circle cx="9" cy="9" r="6" stroke="currentColor" stroke-width="1.5" />
                            <path d="M13.5 13.5L17 17" stroke="currentColor" stroke-width="1.5"
                                stroke-linecap="round" />
                        </svg>
                        <el-select filterable v-model="excludeCategories" multiple
                            placeholder="Search for a category ...">
                            <el-option v-for="category in categories" :key="category.term_id" :label="category.name"
                                :value="category.term_id"></el-option>
                        </el-select>
                    </div>
                </div>
            </div>

            <div class="form-group-horizontal">
                <label>Exclude for specific products</label>
                <div class="input-wrapper">
                    <div class="search-input-container">
                        <svg class="search-icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <circle cx="9" cy="9" r="6" stroke="currentColor" stroke-width="1.5" />
                            <path d="M13.5 13.5L17 17" stroke="currentColor" stroke-width="1.5"
                                stroke-linecap="round" />
                        </svg>
                        <el-select filterable v-model="excludeProducts" multiple
                            placeholder="Search for a product ...">
                            <el-option v-for="product in products" :key="product.id" :label="product.name"
                                :value="product.id"></el-option>
                        </el-select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Actions -->
        <div class="bottom-actions">
            <button type="button" class="btn btn-secondary" @click="resetForm">
                Reset
            </button>
            <button type="button" class="btn btn-success" @click="saveSettings" :disabled="saving">
                {{ saving ? 'Saving...' : 'Save Changes' }}
            </button>
        </div>
    </div>
</template>

<script>
import { ElNotification } from 'element-plus'
export default {
    name: 'ProductInclusion',

    data() {
        return {
            categories: [],
            products: [],
            // Include
            includeCategories: [],
            includeProducts: [],

            // Exclude
            excludeCategories: [],
            excludeProducts: [],
            applyType: 'include',

            saving: false,
            searchTimeout: null,
            nonce: window.wholesaleTieredPricingVars?.nonce,
            ajaxurl: window.wholesaleTieredPricingVars?.ajaxUrl
        }
    },

    mounted() {
        this.categories = window?.wholesaleTieredPricingVars?.categories || []
        this.products = window?.wholesaleTieredPricingVars?.products || []
        this.loadSettings()
    },

    methods: {
        async loadSettings() {
            try {
                const response = await fetch(this.ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        action: 'wc_role_pricing_get_product_settings',
                        nonce: this.nonce
                    })
                })

                const data = await response.json()
                if (data.success) {
                    this.includeCategories = data.data.include_categories || []
                    this.includeProducts = data.data.include_products || []
                    this.excludeCategories = data.data.exclude_categories || []
                    this.excludeProducts = data.data.exclude_products || []
                    this.applyType = data.data.apply_type || 'include'
                }
            } catch (error) {
                console.error('Error loading settings:', error)
            }
        },

        resetForm() {
            if (confirm('Are you sure you want to reset all selections?')) {
                this.includeCategories = []
                this.includeProducts = []
                this.excludeCategories = []
                this.excludeProducts = []
                this.applyType = 'include'
            }
        },

        async saveSettings() {
            this.saving = true

            try {
                const response = await fetch(this.ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        action: 'wc_role_pricing_save_product_settings',
                        nonce: this.nonce,
                        settings: JSON.stringify({
                            include_categories: this.includeCategories,
                            include_products: this.includeProducts,
                            exclude_categories: this.excludeCategories,
                            exclude_products: this.excludeProducts,
                            apply_type: this.applyType
                        })
                    })
                })

                const data = await response.json()
                if (data.success) {
                    ElNotification({
                        title: 'Success',
                        message: 'Settings saved successfully',
                        type: 'success',
                    })
                } else {
                    alert('Failed to save settings. Please try again.')
                }
            } catch (error) {
                console.error('Error saving settings:', error)
                alert('An error occurred while saving settings.')
            } finally {
                this.saving = false
            }
        }
    }
}
</script>

<style lang="scss" scoped>
* {
    box-sizing: border-box;
}

.product-inclusion-wrapper {
    padding: 20px;
}

/* Section Card */
.section-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    margin-bottom: 24px;
    .description {
        font-size: 12px;
        color: #6b7280;
        margin: 0;
        margin-top: 6px;
    }
}

.section-header {
    margin-bottom: 20px;
    padding: 12px 20px;
    border-bottom: 1px solid #e5e7eb;
}

.section-header h2 {
    font-size: 16px;
    font-weight: 500;
    color: #374151;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Info Notice */
.info-notice {
    display: flex;
    gap: 12px;
    padding: 10px;
    background: #fbfbfc;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    margin-bottom: 24px;
    margin: 0 20px;
    color: #6b7280;
}

.info-notice svg {
    flex-shrink: 0;
    margin-top: 2px;
}

.info-notice p {
    margin: 0;
    font-size: 12px;
    line-height: 1.6;
}

/* Form Group Horizontal */
.form-group-horizontal {
    gap: 24px;
    padding: 12px 20px;
    align-items: flex-start;
    &:last-child {
        margin-bottom: 6px;
    }
}

.form-group-horizontal label {
    min-width: 200px;
    font-size: 12px;
    font-weight: 500;
    color: #374151;
    padding-top: 10px;
}

.input-wrapper {
    flex: 1;
    position: relative;
    margin-top: 8px;
}

/* Search Input */
.search-input-container {
    position: relative;
}

.search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
    pointer-events: none;
}

.search-input {
    width: 100%;
    padding: 10px 12px 10px 42px;
    border: 1px solid #d1d5db;
    border-radius: 12px;
    font-size: 15px;
    transition: border-color 0.2s;
}

.search-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.search-input::placeholder {
    color: #9ca3af;
}

/* Search Results Dropdown */
.search-results {
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    right: 0;
    background: #fff;
    border: 1px solid #d1d5db;
    border-radius: 12px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    max-height: 240px;
    overflow-y: auto;
    z-index: 10;
}

.search-result-item {
    padding: 10px 16px;
    cursor: pointer;
    transition: background 0.2s;
    font-size: 14px;
    color: #374151;
}

.search-result-item:hover {
    background: #f3f4f6;
}

/* Selected Items */
.selected-items {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 12px;
}

.selected-tag {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 12px;
    background: #dbeafe;
    color: #1e40af;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 500;
}

.selected-tag.tag-exclude {
    background: #fee2e2;
    color: #991b1b;
}

.selected-tag button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    border: none;
    background: transparent;
    cursor: pointer;
    color: currentColor;
    opacity: 0.7;
    transition: opacity 0.2s;
}

.selected-tag button:hover {
    opacity: 1;
}

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    border: none;
    border-radius: 12px;
    font-size: 15px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-secondary {
    background: #f3f4f6;
    color: #374151;
}

.btn-secondary:hover:not(:disabled) {
    background: #e5e7eb;
}

.bottom-actions button {
    height: 40px !important;
}

/* Responsive */
@media (max-width: 768px) {
    .product-inclusion-wrapper {
        padding: 16px;
    }

    .form-group-horizontal {
        flex-direction: column;
        gap: 12px;
    }

    .form-group-horizontal label {
        padding-top: 0;
    }

    .bottom-actions {
        flex-direction: column;
        gap: 12px;
    }

    .bottom-actions button {
        width: 100%;
        height: 32px !important;
    }
}
</style>