const mix = require('laravel-mix');
const path = require('path'); // Added missing path import

mix.js('resources/admin/js/app.js', 'plugin-assets/admin/app.js')
   .vue({ version: 3 }) // Ensure Vue 3 compatibility
   .sass('resources/admin/scss/app.scss', 'plugin-assets/admin/app.css')
   .js('resources/global-settings.js', 'plugin-assets/global-settings.js')
   .sass('resources/scss/global-setting.scss', 'plugin-assets/global-setting.css')
   .js('resources/frontend.js', 'plugin-assets/frontend.js')
   .sass('resources/scss/frontend.scss', 'plugin-assets/frontend.css')
   .sass('resources/scss/admin.scss', 'plugin-assets/admin.css')
   .sass('resources/scss/minimal-template.scss', 'plugin-assets/minimal-template.css')
   .sass('resources/scss/template/options-table.scss', 'plugin-assets/options-table.css')
   .js('resources/admin.js', 'plugin-assets/admin.js')
   .js('resources/options-table.js', 'plugin-assets/options-table.js')
   .js('resources/report.js', 'plugin-assets/report.js')
   .sass('resources/scss/report.scss', 'plugin-assets/report.css')
//    .copy('resources/images', 'plugin-assets/images')
   .sourceMaps(false);

// Explicitly configure Webpack resolve.extensions and add alias for admin folder
mix.webpackConfig({
    resolve: {
        extensions: ['.js', '.vue', '.json'], // Updated extensions for better compatibility
        alias: {
            '@': path.resolve(__dirname, 'resources/js')
        }
    }
});