<template>
    <div class="general-settings-wrapper">
        <div class="page-header">
            <h1>Template Options</h1>
            <p class="description">Choose a tiered pricing template and customize its look and behavior.</p>
        </div>

        <div class="settings-container">
            <!-- Show Tiered Pricing -->
            <div class="setting-item">
                <label class="setting-label">Show tiered pricing</label>
                <div class="setting-content">
                    <div class="toggle-wrapper">
                        <el-switch v-model="settings.showTieredPricing" active-color="#303133" size="default" />
                        <span class="toggle-status">{{ settings.showTieredPricing ? 'On' : 'Off' }}</span>
                    </div>
                    <p class="help-text">
                        Automatically display tiered pricing on the product page. Prices remain dynamic even if the
                        tiered pricing is not displayed.
                    </p>
                </div>
            </div>

            <!-- Default Template -->
            <div class="setting-item">
                <label class="setting-label">Default template</label>
                <div class="setting-content">
                    <el-radio-group v-model="settings.defaultTemplate" class="template-group">
                        <el-radio-button v-for="template in templates" :key="template.value" :label="template.value">
                            {{ template.label }}
                        </el-radio-button>
                    </el-radio-group>
                    <p class="help-text">
                        Default tiered pricing template. Template can be customized individually per product.
                    </p>
                </div>
            </div>

            <!-- Pricing Title -->
            <div class="setting-item">
                <label class="setting-label">
                    Pricing title
                    <el-tooltip content="Enter a custom title to display above the tiered pricing table"
                        placement="top">
                        <el-icon class="help-icon">
                            <QuestionFilled />
                        </el-icon>
                    </el-tooltip>
                </label>
                <div class="setting-content">
                    <el-input v-model="settings.pricingTitle" placeholder="Buy more save more!" class="compact-input" />
                </div>
            </div>

            <!-- Pricing Title -->
            <div class="setting-item">
                <label class="setting-label">
                    Active Pricing Color
                </label>
                <div class="setting-content">
                    <el-color-picker v-model="settings.activePricingColor" show-alpha />
                </div>
            </div>

            <!-- Position on Product Page -->
            <div class="setting-item">
                <label class="setting-label">
                    Position on the product page
                    <el-tooltip content="Choose where to display the tiered pricing table on the product page"
                        placement="top">
                        <el-icon class="help-icon">
                            <QuestionFilled />
                        </el-icon>
                    </el-tooltip>
                </label>
                <div class="setting-content">
                    <el-select v-model="settings.position" placeholder="Select position" class="compact-input">
                        <el-option label="Above add to cart button" value="above_add_to_cart" />
                        <el-option label="Below add to cart button" value="below_add_to_cart" />
                        <el-option label="Before product meta" value="before_product_meta" />
                        <el-option label="After product meta" value="after_product_meta" />
                        <el-option label="After product summary" value="after_product_summary" />
                    </el-select>
                </div>
            </div>

            <!-- Show Quantity Column -->
            <div class="setting-item">
                <label class="setting-label">Show quantity column</label>
                <div class="setting-content">
                    <el-switch v-model="settings.showQuantityColumn" active-color="#303133" />
                </div>
            </div>

            <!--Columns titles -->
            <div class="setting-item">
                <label class="setting-label">Columns titles</label>
                <div class="setting-content">
                    <el-input style="margin-bottom: 8px;" v-model="settings.quantityLabel" placeholder="Quantity" class="compact-input" />
                    <el-input style="margin-bottom: 8px;" v-model="settings.discountLabel" placeholder="Discount" class="compact-input" />
                    <el-input v-model="settings.priceLabel" placeholder="Price" class="compact-input" />
                </div>
            </div>

            <!-- Show Discount Column -->
            <div class="setting-item">
                <label class="setting-label">Show discount column</label>
                <div class="setting-content">
                    <el-switch v-model="settings.showDiscountColumn" active-color="#303133" />
                </div>
            </div>

            <!-- Enable Responsive Table -->
            <div class="setting-item">
                <label class="setting-label">Enable responsive table</label>
                <div class="setting-content">
                    <el-switch v-model="settings.responsiveTable" active-color="#303133" />
                </div>
            </div>
        </div>

        <!-- Bottom Actions -->
        <div class="bottom-actions">
            <el-button @click="resetSettings">
                Reset to Defaults
            </el-button>
            <el-button type="primary" @click="saveSettings" :loading="saving">
                Save Changes
            </el-button>
        </div>
    </div>
</template>

<script>
import { QuestionFilled } from '@element-plus/icons-vue'

export default {
    name: 'GeneralSettings',

    components: {
        QuestionFilled
    },

    data() {
        return {
            settings: {
                showTieredPricing: true,
                defaultTemplate: 'table',
                pricingTitle: 'Buy more save more!',
                position: 'above_add_to_cart',
                showQuantityColumn: true,
                showDiscountColumn: true,
                responsiveTable: true,
                activePricingColor: '#ff9a00',
                quantityLabel: 'Quantity',
                discountLabel: 'Discount',
                priceLabel: 'Price'
            },
            templates: [
                { value: 'table', label: 'Table' },
                { value: 'options', label: 'Options' },
                { value: 'minimal_table', label: 'Minimal table' },
                { value: 'plain_text', label: 'Plain text' },
                { value: 'compact_list', label: 'Compact list' }
            ],
            saving: false,
            nonce: window.whtproleTieredPricingVar?.nonce,
            ajaxurl: window.whtproleTieredPricingVar?.ajaxUrl
        }
    },

    mounted() {
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
                        action: 'whtprole_pricing_get_general_settings',
                        nonce: this.nonce
                    })
                })

                const data = await response.json()
                if (data.success) {
                    this.settings = { ...this.settings, ...data.data }
                }
            } catch (error) {
                console.error('Error loading settings:', error)
            }
        },

        resetSettings() {
            this.$confirm('Are you sure you want to reset all settings to default values?', 'Warning', {
                confirmButtonText: 'OK',
                cancelButtonText: 'Cancel',
                type: 'warning'
            }).then(() => {
                this.settings = {
                    showTieredPricing: true,
                    defaultTemplate: 'table',
                    pricingTitle: 'Buy more save more!',
                    position: 'above_add_to_cart',
                    showQuantityColumn: true,
                    showDiscountColumn: true,
                    responsiveTable: true,
                    activePricingColor: '#ff9a00',
                    quantityLabel: 'Quantity',
                    discountLabel: 'Discount',
                    priceLabel: 'Price'
                }
                this.$message.success('Settings reset to defaults')
            }).catch(() => { })
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
                        action: 'whtprole_pricing_save_general_settings',
                        nonce: this.nonce,
                        settings: JSON.stringify(this.settings)
                    })
                })

                const data = await response.json()
                if (data.success) {
                    this.$message.success('Settings saved successfully!')
                } else {
                    this.$message.error('Failed to save settings')
                }
            } catch (error) {
                console.error('Error saving settings:', error)
                this.$message.error('An error occurred while saving settings')
            } finally {
                this.saving = false
            }
        }
    }
}
</script>

<style scoped>
.general-settings-wrapper {
}

.page-header {
    margin-bottom: 10px;
    border-bottom: 1px solid #dcdfe6;
    padding: 10px 20px;

}

/* Settings Container */
.settings-container {
    background: #fff;
    border-radius: 4px;
    margin-bottom: 16px;
    padding: 0px 20px;
}

.setting-item {
    display: flex;
    align-items: flex-start;
    padding: 16px 0px;
    border-bottom: 1px solid #f0f0f0;
}

.setting-item:last-child {
    border-bottom: none;
}

.setting-label {
    min-width: 240px;
    font-size: 14px;
    font-weight: 500;
    color: #303133;
    padding-top: 8px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.help-icon {
    font-size: 16px;
    color: #909399;
    cursor: help;
}

.setting-content {
    flex: 1;
    max-width: 600px;
}

.toggle-wrapper {
    display: flex;
    align-items: center;
    gap: 10px;
}

.toggle-status {
    font-size: 14px;
    color: #606266;
    font-weight: 500;
}

.help-text {
    margin: 10px 0 0 0;
    font-size: 13px;
    color: #909399;
    line-height: 1.5;
}

/* Template Group */
.template-group {
    display: flex;
    flex-wrap: wrap;
    gap: 0;
}

:deep(.el-radio-button) {
    margin: 0;
}

:deep(.el-radio-button__inner) {
    padding: 10px 16px;
    font-size: 13px;
    border-color: #dcdfe6;
}

:deep(.el-radio-button__original-radio:checked + .el-radio-button__inner) {
    background-color: #303133;
    border-color: #303133;
    color: #fff;
}

:deep(.el-radio-button:first-child .el-radio-button__inner) {
    border-radius: 4px 0 0 4px;
}

:deep(.el-radio-button:last-child .el-radio-button__inner) {
    border-radius: 0 4px 4px 0;
}

/* Compact Input */
.compact-input {
    width: 100%;
}

:deep(.el-input__wrapper) {
    box-shadow: 0 0 0 1px #dcdfe6 inset;
}

:deep(.el-input__wrapper.is-focus) {
    box-shadow: 0 0 0 1px #303133 inset !important;
}

/* Switch */
:deep(.el-switch.is-checked .el-switch__core) {
    background-color: #303133;
    border-color: #303133;
}

/* Select */
:deep(.el-select .el-input.is-focus .el-input__wrapper) {
    box-shadow: 0 0 0 1px #303133 inset !important;
}

/* Bottom Actions */
.bottom-actions {
    padding: 16px 20px;
}

:deep(.el-button--primary) {
    background-color: #303133;
    border-color: #303133;
}

:deep(.el-button--primary:hover) {
    background-color: #404040;
    border-color: #404040;
}

:deep(.el-button--primary:active) {
    background-color: #202020;
    border-color: #202020;
}

/* Responsive */
@media (max-width: 768px) {
    .general-settings-wrapper {
        padding: 12px;
    }

    .setting-item {
        flex-direction: column;
        padding: 14px 16px;
    }

    .setting-label {
        min-width: auto;
        padding-top: 0;
        margin-bottom: 10px;
    }

    .template-group {
        width: 100%;
    }

    :deep(.el-radio-button__inner) {
        padding: 8px 12px;
        font-size: 12px;
    }

    .bottom-actions {
        flex-direction: column;
        gap: 10px;
    }

    .bottom-actions button {
        width: 100%;
    }
}
</style>