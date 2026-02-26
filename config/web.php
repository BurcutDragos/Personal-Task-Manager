<?php

/**
 * Web application configuration for Personal Task Manager.
 *
 * This file is loaded by web/index.php via:
 *   $config = require __DIR__ . '/../config/web.php';
 *   (new yii\web\Application($config))->run();
 *
 * All Yii2 web application settings live here: routing, session, caching,
 * logging, security, and asset management.
 *
 * See https://www.yiiframework.com/doc/guide/2.0/en/concept-configurations
 * for the full list of supported keys.
 */

// Load the database connection settings from a separate file so that db.php
// can be shared between the web (web.php) and console (console.php) configs.
$db = require __DIR__ . '/db.php';

return [
    // -------------------------------------------------------------------------
    // Application identity
    // -------------------------------------------------------------------------

    // Unique internal identifier for this Yii2 application.
    // Used as a namespace prefix for session/cache keys.
    'id' => 'personal-task-manager',

    // Human-readable name shown in the browser title and error pages.
    'name' => 'Personal Task Manager',

    // Absolute path to the project root (parent of this config/ directory).
    'basePath' => dirname(__DIR__),

    // Default URL to use when no specific route is given (e.g. visiting /).
    'defaultRoute' => 'task/index',

    // -------------------------------------------------------------------------
    // Aliases
    // -------------------------------------------------------------------------

    /*
     * Yii2 expects certain aliases for Bower/NPM asset packages.
     * Because we load jQuery and Bootstrap via CDN (instead of bower-asset),
     * we point these aliases at dummy vendor directories so that no asset
     * bundle tries to look for files in the wrong place.
     */
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],

    // -------------------------------------------------------------------------
    // Components — Yii2's service locator (dependency injection container)
    // -------------------------------------------------------------------------

    'components' => [

        // ----- Request -------------------------------------------------------
        'request' => [
            /*
             * Cookie validation key — used to sign session cookies.
             * IMPORTANT: Change this to a long random string in production!
             * Generate one with: php -r "echo Yii::$app->security->generateRandomString();"
             */
            'cookieValidationKey' => 'PersonalTaskManager-change-in-production',

            // Enable Cross-Site Request Forgery protection for all state-changing routes.
            // A hidden _csrf token is embedded in every form by ActiveForm automatically.
            'enableCsrfValidation' => true,
        ],

        // ----- Database ------------------------------------------------------
        // Settings loaded from config/db.php (DSN, username, password, charset)
        'db' => $db,

        // ----- Cache ---------------------------------------------------------
        /*
         * FileCache stores cached values in the runtime/cache/ directory.
         * Suitable for a single-server setup.  For multi-server deployments
         * consider switching to MemCache or Redis.
         */
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],

        // ----- Session -------------------------------------------------------
        // Using PHP's default session handler (files in sys_get_temp_dir()).
        'session' => [
            'class' => 'yii\web\Session',
        ],

        // ----- URL Manager ---------------------------------------------------
        /*
         * Enables "pretty URLs" (e.g. /task/create instead of index.php?r=task/create).
         * showScriptName:false requires the web server to rewrite all requests to
         * index.php — handled by web/.htaccess (Apache) or a similar Nginx rule.
         */
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName'  => false,
            'rules'           => [],
        ],

        // ----- Logging -------------------------------------------------------
        /*
         * Logs errors and warnings to a file in runtime/logs/.
         * traceLevel:3 in debug mode includes a stack trace so you can quickly
         * identify exactly where a log message was generated.
         */
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets'    => [
                [
                    'class'  => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],

        // ----- Asset Manager -------------------------------------------------
        /*
         * Override the JqueryAsset bundle to prevent Yii2 from trying to load
         * jQuery from vendor/bower-asset/jquery (which is not installed because
         * we use yidas/yii2-composer-bower-skip to skip bower assets).
         *
         * jQuery is instead loaded directly from CDN in views/layouts/main.php.
         * sourcePath:null tells the asset manager the bundle has no local files.
         * js:[] removes the script tags the bundle would normally generate.
         */
        'assetManager' => [
            'bundles' => [
                'yii\web\JqueryAsset' => [
                    'sourcePath' => null,
                    'js'         => [],
                ],
            ],
        ],

    ],
];
