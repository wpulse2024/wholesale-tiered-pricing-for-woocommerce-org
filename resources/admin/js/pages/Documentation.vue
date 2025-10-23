<template>
    <div class="documentation-wrapper">
      <!-- Top Navigation Bar -->
      <div class="top-navbar">
        <div class="navbar-container">
          <el-menu
            :default-active="activeSection"
            mode="horizontal"
            @select="handleMenuSelect"
            class="navbar-menu"
          >
            <el-menu-item index="getting-started">
              <el-icon><Discount /></el-icon>
              <span>Getting Started</span>
            </el-menu-item>
            <el-menu-item index="pricing-rules">
              <el-icon><Price /></el-icon>
              <span>Pricing Rules</span>
            </el-menu-item>
            <el-menu-item index="product-inclusion">
              <el-icon><Goods /></el-icon>
              <span>Products</span>
            </el-menu-item>
            <el-menu-item index="templates">
              <el-icon><Grid /></el-icon>
              <span>Templates</span>
            </el-menu-item>
            <el-menu-item index="faq">
              <el-icon><QuestionFilled /></el-icon>
              <span>FAQ</span>
            </el-menu-item>
          </el-menu>
          <el-button type="primary" size="small" class="support-btn">
            <el-icon><Service /></el-icon>
            Support
          </el-button>
        </div>
      </div>
  
      <!-- Content Area -->
      <div class="doc-content-wrapper">
        <div class="doc-content">
          <!-- Breadcrumb -->
          <el-breadcrumb separator="/" class="breadcrumb">
            <el-breadcrumb-item>Documentation</el-breadcrumb-item>
            <el-breadcrumb-item>{{ currentSectionTitle }}</el-breadcrumb-item>
          </el-breadcrumb>
  
          <!-- Getting Started Section -->
          <div v-show="activeSection === 'getting-started'" class="doc-section">
            <h1>Getting Started</h1>
            <p class="lead">Welcome to WooCommerce Role-Based Pricing documentation. This guide will help you set up and configure tiered pricing for your store.</p>
  
            <div class="doc-card">
              <h2>📦 Installation</h2>
              <ol>
                <li>Download the plugin from your account</li>
                <li>Go to WordPress Admin → Plugins → Add New</li>
                <li>Click "Upload Plugin" and select the downloaded file</li>
                <li>Click "Install Now" and then "Activate"</li>
              </ol>
            </div>
  
            <div class="doc-card">
              <h2>🚀 Quick Setup</h2>
              <p>Follow these steps to configure your first pricing rule:</p>
              <div class="steps-grid">
                <div class="step-card">
                  <div class="step-number">1</div>
                  <h3>Create Pricing Rules</h3>
                  <p>Navigate to the Pricing Rules tab and create rules for different user roles.</p>
                </div>
                <div class="step-card">
                  <div class="step-number">2</div>
                  <h3>Configure Products</h3>
                  <p>Select which products or categories the rules should apply to.</p>
                </div>
                <div class="step-card">
                  <div class="step-number">3</div>
                  <h3>Customize Display</h3>
                  <p>Choose a template and customize how pricing appears on your store.</p>
                </div>
              </div>
            </div>
  
            <el-alert
              title="💡 Pro Tip"
              type="info"
              :closable="false"
            >
              Start with simple rules and test them thoroughly before creating complex pricing structures.
            </el-alert>
          </div>
  
          <!-- Pricing Rules Section -->
          <div v-show="activeSection === 'pricing-rules'" class="doc-section">
            <h1>Pricing Rules</h1>
            <p class="lead">Configure role-based tiered pricing rules for your WooCommerce store.</p>
  
            <div class="doc-card">
              <h2>Creating a Rule</h2>
              <p>To create a new pricing rule:</p>
              <ol>
                <li>Click "Add Pricing Rule" button</li>
                <li>Select a user role (Customer, Wholesale, etc.)</li>
                <li>Set step quantity, min quantity, and max quantity</li>
                <li>Add pricing tiers with discounts</li>
              </ol>
  
              <div class="code-block">
                <div class="code-header">
                  <span>💾 Example Configuration</span>
                  <el-button size="small" text @click="copyCode">
                    <el-icon><CopyDocument /></el-icon>
                    Copy
                  </el-button>
                </div>
                <pre><code>User Role: Customer
  Step Quantity: 5
  Min Quantity: 1
  Max Quantity: 100
  
  Tiers:
  - Min Qty: 10  | Type: Percentage | Value: 10%
  - Min Qty: 50  | Type: Percentage | Value: 20%
  - Min Qty: 100 | Type: Fixed      | Value: $5</code></pre>
              </div>
            </div>
  
            <div class="doc-card">
              <h2>Rule Parameters</h2>
              <el-table :data="ruleParams" style="width: 100%" stripe>
                <el-table-column prop="parameter" label="Parameter" width="180" />
                <el-table-column prop="description" label="Description" />
                <el-table-column prop="example" label="Example" width="120" />
              </el-table>
            </div>
          </div>
  
          <!-- Product Inclusion Section -->
          <div v-show="activeSection === 'product-inclusion'" class="doc-section">
            <h1>Product Inclusion & Exclusion</h1>
            <p class="lead">Control which products or categories your pricing rules apply to.</p>
  
            <div class="doc-card">
              <h2>✅ Include Products</h2>
              <p>Specify which products should have tiered pricing:</p>
              <ul>
                <li><strong>Apply for categories:</strong> Select product categories</li>
                <li><strong>Apply for specific products:</strong> Search and select individual products</li>
              </ul>
              <el-alert
                title="⚠️ Note"
                type="warning"
                :closable="false"
              >
                If you don't specify any products, rules will apply to all products (except exclusions).
              </el-alert>
            </div>
  
            <div class="doc-card">
              <h2>❌ Exclude Products</h2>
              <p>Prevent pricing rules from applying to certain products:</p>
              <ul>
                <li>Select categories to exclude</li>
                <li>Select specific products to exclude</li>
              </ul>
            </div>
          </div>
  
          <!-- Templates Section -->
          <div v-show="activeSection === 'templates'" class="doc-section">
            <h1>Templates</h1>
            <p class="lead">Choose from multiple display templates for your tiered pricing.</p>
  
            <div class="templates-grid">
              <div class="template-card">
                <div class="template-preview">
                  <el-icon :size="40"><Grid /></el-icon>
                </div>
                <h3>Table</h3>
                <p>Classic table format with quantity and price columns</p>
                <el-tag size="small">Default</el-tag>
              </div>
              <div class="template-card">
                <div class="template-preview">
                  <el-icon :size="40"><Memo /></el-icon>
                </div>
                <h3>Blocks</h3>
                <p>Modern block-style layout with cards</p>
                <el-tag size="small" type="success">Popular</el-tag>
              </div>
              <div class="template-card">
                <div class="template-preview">
                  <el-icon :size="40"><List /></el-icon>
                </div>
                <h3>Options</h3>
                <p>Simple list format with pricing options</p>
              </div>
              <div class="template-card">
                <div class="template-preview">
                  <el-icon :size="40"><ArrowDown /></el-icon>
                </div>
                <h3>Dropdown</h3>
                <p>Compact dropdown selector</p>
              </div>
              <div class="template-card">
                <div class="template-preview">
                  <el-icon :size="40"><Operation /></el-icon>
                </div>
                <h3>Horizontal Table</h3>
                <p>Wide table layout for desktop</p>
              </div>
              <div class="template-card">
                <div class="template-preview">
                  <el-icon :size="40"><Document /></el-icon>
                </div>
                <h3>Plain Text</h3>
                <p>Minimal text-only display</p>
              </div>
            </div>
          </div>
  
          <!-- Shortcodes Section -->
          <div v-show="activeSection === 'shortcodes'" class="doc-section">
            <h1>Shortcodes</h1>
            <p class="lead">Use shortcodes to display tiered pricing anywhere on your site.</p>
  
            <div class="doc-card">
              <h2>📊 Display Pricing Table</h2>
              <div class="code-block">
                <pre><code>[tiered_pricing_table product_id="123"]</code></pre>
              </div>
              <p><strong>Attributes:</strong></p>
              <el-descriptions :column="1" border>
                <el-descriptions-item label="product_id">Product ID (required)</el-descriptions-item>
                <el-descriptions-item label="template">Template type: table, blocks, dropdown (optional)</el-descriptions-item>
                <el-descriptions-item label="title">Custom title text (optional)</el-descriptions-item>
              </el-descriptions>
            </div>
  
            <div class="doc-card">
              <h2>🏷️ Show Discount Badge</h2>
              <div class="code-block">
                <pre><code>[tiered_pricing_badge product_id="123"]</code></pre>
              </div>
            </div>
  
            <div class="doc-card">
              <h2>💰 Current User Price</h2>
              <div class="code-block">
                <pre><code>[tiered_pricing_price product_id="123" quantity="10"]</code></pre>
              </div>
            </div>
          </div>
  
          <!-- API Section -->
          <div v-show="activeSection === 'api'" class="doc-section">
            <h1>API Reference</h1>
            <p class="lead">Programmatically interact with the pricing plugin.</p>
  
            <div class="doc-card">
              <h2>Get Pricing Rules</h2>
              <div class="code-block">
                <div class="code-header">
                  <span>PHP</span>
                </div>
                <pre><code>$rules = WHTPRole_Pricing()->get_pricing_rules($product_id);
  foreach ($rules as $rule) {
      echo $rule['min_qty'] . ' - ' . $rule['price'];
  }</code></pre>
              </div>
            </div>
  
            <div class="doc-card">
              <h2>Calculate Tiered Price</h2>
              <div class="code-block">
                <div class="code-header">
                  <span>PHP</span>
                </div>
                <pre><code>$price = WHTPRole_Pricing()->calculate_price($product_id, $quantity);
  echo wc_price($price);</code></pre>
              </div>
            </div>
  
            <div class="doc-card">
              <h2>JavaScript Hooks</h2>
              <div class="code-block">
                <div class="code-header">
                  <span>JavaScript</span>
                </div>
                <pre><code>// Listen for price updates
  document.addEventListener('tiered_pricing_updated', function(e) {
      console.log('New price:', e.detail.price);
      console.log('Quantity:', e.detail.quantity);
  });</code></pre>
              </div>
            </div>
          </div>
  
          <!-- FAQ Section -->
          <div v-show="activeSection === 'faq'" class="doc-section">
            <h1>Frequently Asked Questions</h1>
            <p class="lead">Common questions and answers about the pricing plugin.</p>
  
            <el-collapse v-model="activeFaq">
              <el-collapse-item title="How do I create different pricing for wholesale customers?" name="1">
                <p>Create a new pricing rule and select "Wholesale" as the user role. Then configure your quantity tiers and discount values specifically for that role.</p>
              </el-collapse-item>
              <el-collapse-item title="Can I exclude certain products from pricing rules?" name="2">
                <p>Yes! Go to the Product Inclusion page and add products or categories to the Exclusions section. These products will not be affected by any pricing rules.</p>
              </el-collapse-item>
              <el-collapse-item title="What's the difference between percentage and fixed discount?" name="3">
                <p>Percentage discount reduces the price by a percentage (e.g., 10% off). Fixed discount reduces the price by a fixed amount (e.g., $5 off per item).</p>
              </el-collapse-item>
              <el-collapse-item title="Can I use multiple pricing rules for the same product?" name="4">
                <p>Yes, you can create different rules for different user roles. The plugin will automatically apply the correct rule based on the logged-in user's role.</p>
              </el-collapse-item>
              <el-collapse-item title="How do I display the pricing table on custom pages?" name="5">
                <p>Use the [tiered_pricing_table] shortcode with the product_id parameter. You can add this to any page or post.</p>
              </el-collapse-item>
              <el-collapse-item title="Does the plugin work with variable products?" name="6">
                <p>Yes, the plugin supports both simple and variable products. Each variation can have its own pricing rules.</p>
              </el-collapse-item>
            </el-collapse>
          </div>
  
          <!-- Need Help Section -->
          <div class="help-section">
            <el-card shadow="hover">
              <div class="help-content">
                <el-icon class="help-icon" :size="50"><QuestionFilled /></el-icon>
                <div class="help-text">
                  <h3>Need More Help?</h3>
                  <p>Can't find what you're looking for? Our support team is here to help you.</p>
                </div>
                <el-button type="primary" size="large">
                  <el-icon><Service /></el-icon>
                  Contact Support
                </el-button>
              </div>
            </el-card>
          </div>
        </div>
      </div>
    </div>
  </template>
  
  <script>
  
  export default {
    name: 'DocumentationPage',
  
    data() {
      return {
        activeSection: 'getting-started',
        activeFaq: ['1'],
        ruleParams: [
          { parameter: 'User Role', description: 'The WordPress user role this rule applies to', example: 'Customer' },
          { parameter: 'Step Quantity', description: 'Quantity increment for bulk pricing', example: '5' },
          { parameter: 'Min Quantity', description: 'Minimum quantity required', example: '1' },
          { parameter: 'Max Quantity', description: 'Maximum quantity allowed', example: '100' }
        ]
      }
    },
  
    computed: {
      currentSectionTitle() {
        const titles = {
          'getting-started': 'Getting Started',
          'pricing-rules': 'Pricing Rules',
          'product-inclusion': 'Product Inclusion',
          'templates': 'Templates',
          'shortcodes': 'Shortcodes',
          'api': 'API Reference',
          'faq': 'FAQ'
        }
        return titles[this.activeSection] || 'Documentation'
      }
    },
  
    methods: {
      handleMenuSelect(index) {
        this.activeSection = index
        window.scrollTo({ top: 0, behavior: 'smooth' })
      },
  
      copyCode() {
        this.$message.success('Code copied to clipboard!')
      }
    }
  }
  </script>
  
  <style scoped lang="scss">
  .documentation-wrapper {
    min-height: 100vh;
    max-width: 100%;
  }
  
  /* Top Navbar */
  .top-navbar {
    background: #fff;
    border-bottom: 1px solid #e4e7ed;
    position: sticky;
    top: 0;
    z-index: 1000;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
    border-radius: 12px 12px 0px 0px;
  }
  
  .navbar-container {
    max-width: 1400px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    padding: 0 20px;
  }
  
  .navbar-menu {
    flex: 1;
    border: none;
    background: transparent;
  }
  
  :deep(.navbar-menu .el-menu-item) {
    height: 60px;
    line-height: 60px;
    border-bottom: 2px solid transparent;
  }
  
  :deep(.navbar-menu .el-menu-item.is-active) {
    color: #303133;
    border-bottom-color: #303133;
    font-weight: 500;
  }
  
  :deep(.navbar-menu .el-menu-item:hover) {
    background-color: transparent;
    color: #303133;
  }
  
  .support-btn {
    margin-left: 16px;
  }
  
  /* Content Area */
  .doc-content-wrapper {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
  }

  
  .breadcrumb {
    margin-bottom: 12px;
  }
  
  .doc-section h1 {
    font-size: 18px;
    font-weight: 500;
    color: #303133;
    margin: 0 0 2px 0;
  }
  
  .lead {
    font-size: 12px;
    color: #606266;
    margin: 0 0 24px 0;
    line-height: 1.6;
  }
  
  /* Doc Cards */
  .doc-card {
    background: #fff;
    border: 1px solid #e4e7ed;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 24px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
  }
  
  .doc-card h2 {
    font-size: 16px;
    font-weight: 500;
    color: #303133;
    margin: 0 0 4px 0;
  }
  
  .doc-card p {
    color: #606266;
    line-height: 1.7;
    margin: 0 0 12px 0;
    font-size: 15px;
  }
  
  .doc-card ol,
  .doc-card ul {
    color: #606266;
    line-height: 1.8;
    padding-left: 12px;
    font-size: 14px;
  }
  
  .doc-card li {
    margin-bottom: 10px;
  }
  
  /* Code Block */
  .code-block {
    background: #282c34;
    border-radius: 8px;
    margin: 20px 0;
    overflow: hidden;
  }
  
  .code-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 20px;
    background: #21252b;
    color: #abb2bf;
    font-size: 13px;
    font-weight: 500;
  }
  
  .code-block pre {
    margin: 0;
    padding: 20px;
    overflow-x: auto;
  }
  
  .code-block code {
    font-family: 'Monaco', 'Courier New', monospace;
    font-size: 14px;
    color: #abb2bf;
    line-height: 1.7;
  }
  
  /* Steps Grid */
  .steps-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 20px;
    margin-top: 20px;
  }
  
  .step-card {
    padding: 24px;
    background: #f5f7fa;
    border-radius: 8px;
    text-align: center;
    border: 1px solid #e4e7ed;
  }
  
  .step-number {
    width: 42px;
    height: 42px;
    background: #303133;
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 18px;
    margin: 0 auto 16px;
  }
  
  .step-card h3 {
    font-size: 16px;
    margin: 0 0 10px 0;
    color: #303133;
  }
  
  .step-card p {
    font-size: 14px;
    color: #606266;
    margin: 0;
  }
  
  /* Templates Grid */
  .templates-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 20px;
    margin-top: 20px;
  }
  
  .template-card {
    background: #fff;
    border: 1px solid #e4e7ed;
    border-radius: 8px;
    padding: 24px;
    text-align: center;
    transition: all 0.3s;
    cursor: pointer;
  }
  
  .template-card:hover {
    border-color: #303133;
    transform: translateY(-4px);
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
  }
  
  .template-preview {
    width: 90px;
    height: 90px;
    background: #f5f7fa;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #606266;
    margin: 0 auto 16px;
  }
  
  .template-card h3 {
    font-size: 17px;
    margin: 0 0 8px 0;
    color: #303133;
  }
  
  .template-card p {
    font-size: 13px;
    color: #909399;
    margin: 0 0 12px 0;
  }
  
  /* Help Section */
  .help-section {
    margin-top: 24px;

  }
  
  .help-content {
    display: flex;
    align-items: center;
    gap: 24px;
    padding: 0px;
  }
  
  .help-icon {
    color: #010c16;
  }
  
  .help-text {
    flex: 1;
  }
  
  .help-text h3 {
    margin: 0 0 6px 0;
    font-size: 16px;
    color: #303133;
  }
  
  .help-text p {
    margin: 0;
    color: #606266;
    font-size: 12px;
  }
  
  /* Alerts */
  :deep(.el-alert) {
    margin: 20px 0;
    border-radius: 6px;
  }
  
  /* Primary Button */
  :deep(.el-button--primary) {
    background-color: #303133;
    border-color: #303133;
  }
  
  :deep(.el-button--primary:hover) {
    background-color: #404040;
    border-color: #404040;
  }
  
  /* Responsive */
  @media (max-width: 1024px) {
    .navbar-menu {
      display: none;
    }
    
    .navbar-brand {
      margin-right: auto;
    }
  }
  
  @media (max-width: 768px) {
    .doc-content-wrapper {
      padding: 20px 12px;
    }
  
    .doc-section h1 {
      font-size: 28px;
    }
  
    .steps-grid,
    .templates-grid {
      grid-template-columns: 1fr;
    }
  
    .help-content {
      flex-direction: column;
      text-align: center;
    }
  }
  </style>