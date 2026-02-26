<?php

/**
 * Console application configuration for Personal Task Manager.
 *
 * This file is loaded by the project-level `yii` script (in the project root)
 * when running console commands such as:
 *
 *   php yii migrate           — run pending database migrations
 *   php yii migrate/create    — generate a new migration file
 *   php yii migrate/down      — roll back the last migration
 *
 * IMPORTANT: Do NOT use `php vendor/bin/yii migrate` for this project.
 * The vendor/bin/yii script starts a minimal Yii2 app that has no `db`
 * component configured, so migration commands will fail with:
 *   "Unknown component ID: db"
 *
 * Always use `php yii migrate` (the project-root yii script) instead.
 *
 * See https://www.yiiframework.com/doc/guide/2.0/en/tutorial-console
 * for more on Yii2 console applications.
 */

// Share the database connection settings with the web config.
// Both web and console applications must connect to the same database.
$db = require __DIR__ . '/db.php';

return [
    // Unique identifier for the console application.
    // Keeps session/cache namespaces separate from the web app.
    'id' => 'personal-task-manager-console',

    // Absolute path to the project root directory.
    'basePath' => dirname(__DIR__),

    // Aliases for Bower/NPM asset paths (not needed for console, but avoids
    // "alias not defined" warnings if any model/component references them).
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],

    'components' => [
        // The db component is required by Yii2's migration runner.
        // Without it, `php yii migrate` cannot connect to the database.
        'db' => $db,
    ],
];
