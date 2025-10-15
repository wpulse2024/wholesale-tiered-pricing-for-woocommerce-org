const mix = require('laravel-mix');
const path = require('path'); // Added missing path import

mix.js('resources/admin/js/app.js', 'assets/admin/app.js')
   .vue({ version: 3 }) // Ensure Vue 3 compatibility
   .sass('resources/admin/scss/app.scss', 'assets/admin/app.css')
   .js('resources/global-settings.js', 'assets/global-settings.js')
   .sass('resources/scss/global-setting.scss', 'assets/global-setting.css')
   .js('resources/frontend.js', 'assets/frontend.js')
   .sass('resources/scss/frontend.scss', 'assets/frontend.css')
   .sass('resources/scss/admin.scss', 'assets/admin.css')
   .sass('resources/scss/minimal-template.scss', 'assets/minimal-template.css')
   .sass('resources/scss/template/options-table.scss', 'assets/options-table.css')
   .js('resources/admin.js', 'assets/admin.js')
   .js('resources/options-table.js', 'assets/options-table.js')
//    .copy('resources/images', 'assets/images')
   .sourceMaps();

// Explicitly configure Webpack resolve.extensions and add alias for admin folder
mix.webpackConfig({
    resolve: {
        extensions: ['.js', '.vue', '.json'], // Updated extensions for better compatibility
        alias: {
            '@': path.resolve(__dirname, 'resources/js')
        }
    }
});