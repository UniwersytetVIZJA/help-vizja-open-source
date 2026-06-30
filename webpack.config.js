const Encore = require('@symfony/webpack-encore');

if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')

    .cleanupOutputBeforeBuild()
    .enableIntegrityHashes(Encore.isProduction())
    .enablePostCssLoader()
    .enableSassLoader()
    .enableSingleRuntimeChunk()
    .enableStimulusBridge('./assets/controllers.json')
    .enableSourceMaps(!Encore.isProduction())
    .enableTypeScriptLoader()
    .enableVersioning(Encore.isProduction())
    .splitEntryChunks()


    // enables the Symfony UX Stimulus bridge (used in assets/bootstrap.js)
    .enableStimulusBridge('./assets/controllers.json')

    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = '3.38';
    })

    .addEntry('admin_default', './assets/routes/admin/default.ts')
    .addEntry('student_default', './assets/routes/student/default.ts')
;

module.exports = Encore.getWebpackConfig();
