<?php

/**
 * Web entry point — every HTTP request to the application passes through here.
 *
 * Apache's web/.htaccess (or an equivalent Nginx rewrite rule) redirects all
 * requests that do not match a real file to this script.  The Yii2 front
 * controller then routes the request to the correct controller/action based on
 * the URL and the urlManager rules defined in config/web.php.
 *
 * Execution order:
 *   1. Define YII_DEBUG / YII_ENV constants for the current environment.
 *   2. Load Composer's PSR-4 autoloader (vendor/autoload.php).
 *   3. Load the Yii class file (sets up Yii::$app, Yii::$container, etc.).
 *   4. Load the web application configuration array from config/web.php.
 *   5. Instantiate yii\web\Application with the config and call run().
 *      run() dispatches the request, renders a view, and sends the response.
 *
 * Environment flags:
 *   YII_DEBUG — true enables detailed error pages and stack traces.
 *               Set to false in production to avoid leaking internals.
 *   YII_ENV   — 'dev' | 'prod' | 'test'.  Controls which config overrides
 *               Yii2 applies and which log targets are active.
 */

// Enable debug mode and set environment to 'dev'.
// Change both to false/'prod' before deploying to a live server.
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV')   or define('YII_ENV', 'dev');

// Composer autoloader — registers the app\ PSR-4 namespace (mapped to the
// project root in composer.json) plus all vendor package namespaces.
require __DIR__ . '/../vendor/autoload.php';

// Yii2 bootstrap — defines the Yii class (static helper facade) and
// registers Yii2's own class map for fast class resolution.
require __DIR__ . '/../vendor/yiisoft/yii2/Yii.php';

// Load the web application configuration array.
$config = require __DIR__ . '/../config/web.php';

// Create the Yii2 web application and run it.
// run() processes the incoming HTTP request and sends the HTTP response.
(new yii\web\Application($config))->run();
