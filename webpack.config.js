const Encore = require('@symfony/webpack-encore');
const FaviconsWebpackPlugin = require('favicons-webpack-plugin');

// Manually configure the runtime environment if not already configured yet by the `encore` command.
// It's useful when you use tools that rely on `webpack.config.js` file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    // Directory where compiled assets will be stored.
    .setOutputPath('public/build/')
    // Public path used by the web server to access the output path.
    .setPublicPath('/build')
    // Only needed for CDN's or sub-directory deploy.
    //.setManifestKeyPrefix('build/')

    /*
     * ENTRY CONFIG
     *
     * Each entry will result in one JavaScript file (e.g. `app.js`)
     * and one CSS file (e.g. `app.scss`) if your JavaScript imports CSS.
     */
    .addEntry('app', './assets/app.js')

    // Enables the Symfony UX Stimulus bridge (used in `assets/bootstrap.js`).
    .enableStimulusBridge('./assets/controllers.json')

    // When enabled, Webpack 'splits' your files into smaller pieces for greater optimization.
    .splitEntryChunks()

    // Will require an extra script tag for `runtime.js`
    // but, you probably want this, unless you're building a single-page app.
    .enableSingleRuntimeChunk()

    /*
     * FEATURE CONFIG
     *
     * Enable & configure other features below.
     * For a full list of features, see: https://symfony.com/doc/current/frontend.html#adding-more-features
     */
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    // Enables hashed filenames (e.g. `app.abc123.css`).
    .enableVersioning(Encore.isProduction())

    .configureBabel((config) => {
        config.plugins.push('@babel/plugin-proposal-class-properties');
    })

    // Enables @babel/preset-env polyfills.
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = 3;
    })

    // Enables Sass/SCSS support.
    .enableSassLoader()
    .enablePostCssLoader()

    // Uncomment if you use TypeScript.
    //.enableTypeScriptLoader()

    // Uncomment if you use React.
    //.enableReactPreset()

    // Uncomment to get integrity='...' attributes on your script & link tags.
    // Requires WebpackEncoreBundle 1.4 or higher.
    //.enableIntegrityHashes(Encore.isProduction())

    // Uncomment if you're having problems with a jQuery plugin.
    //.autoProvidejQuery()

    /** Plugins */

    // Generate favicons.
    .addPlugin(new FaviconsWebpackPlugin({
        logo: './assets/images/logos/symfony_text_teal.svg',
        mode: 'webapp',
        devMode: 'webapp',
        outputPath: 'img/favicons',
        favicons: {
            icons: {
                android: false,
                appleIcon: false,
                appleStartup: false,
                coast: false,
                favicons: true,
                firefox: false,
                windows: false,
                yandex: false,
            }
        }
    }))
;

module.exports = Encore.getWebpackConfig();
