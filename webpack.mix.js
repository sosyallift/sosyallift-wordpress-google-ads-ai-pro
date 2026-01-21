const mix = require('laravel-mix');

mix.setPublicPath('assets/dist');

// Admin JS
mix.js('assets/js/admin/app.js', 'js/admin.js')
   .js('assets/js/admin/dashboard.js', 'js/dashboard.js')
   .js('assets/js/admin/charts.js', 'js/charts.js')
   .js('assets/js/admin/realtime.js', 'js/realtime.js')
   .js('assets/js/admin/notifications.js', 'js/notifications.js');

// Vendor JS
mix.combine([
    'node_modules/apexcharts/dist/apexcharts.min.js',
    'node_modules/datatables.net/js/jquery.dataTables.min.js',
    'node_modules/select2/dist/js/select2.min.js'
], 'assets/dist/js/vendor.js');

// Admin CSS
mix.sass('assets/scss/admin/main.scss', 'css/admin.css')
   .sass('assets/scss/admin/dashboard.scss', 'css/dashboard.css')
   .sass('assets/scss/admin/dark-mode.scss', 'css/dark-mode.css');

// Vendor CSS
mix.combine([
    'node_modules/apexcharts/dist/apexcharts.css',
    'node_modules/datatables.net-dt/css/jquery.dataTables.min.css',
    'node_modules/select2/dist/css/select2.min.css'
], 'assets/dist/css/vendor.css');

// Copy assets
mix.copy('node_modules/chart.js/dist/chart.min.js', 'assets/dist/js/chart.min.js');

// Versioning for production
if (mix.inProduction()) {
    mix.version();
}

// Source maps for development
if (!mix.inProduction()) {
    mix.sourceMaps();
}

// Webpack config
mix.webpackConfig({
    externals: {
        jquery: 'jQuery',
        lodash: '_',
        wp: 'wp'
    },
    stats: {
        children: true
    }
});