let mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix
    .options({
        processCssUrls: false
    })
    .js('resources/assets/js/app.js', 'public/js')
    .sass('resources/assets/sass/app.scss', 'public/css')
    .sass('node_modules/bulma/bulma.sass', 'public/css')
    .copy('node_modules/datatables-bulma/css/dataTables.bulma.css', 'public/css')
    .copy('node_modules/datatables-bulma/js/dataTables.bulma.min.js', 'public/js')
    .version();
