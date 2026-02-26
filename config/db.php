<?php

/**
 * Database connection configuration for Personal Task Manager.
 *
 * This file is required by both config/web.php and config/console.php so that
 * the same database settings apply whether the application is handling an HTTP
 * request or running a console command (e.g. php yii migrate).
 *
 * Yii2 uses this array to create a yii\db\Connection component.
 * All available options are documented at:
 *   https://www.yiiframework.com/doc/api/2.0/yii-db-connection
 *
 * ⚠ Production checklist:
 *   - Change 'username' and 'password' to a dedicated MySQL user with
 *     only the permissions this app needs (SELECT, INSERT, UPDATE, DELETE,
 *     CREATE, DROP for migrations).  Never use root in production.
 *   - Store credentials in environment variables or a secrets manager,
 *     not in version control.
 *   - Ensure this file is listed in .gitignore if it contains real passwords.
 */

return [
    // Yii2 ActiveRecord uses this class to interact with the database.
    'class' => 'yii\db\Connection',

    // Data Source Name — tells PHP's PDO driver which database server and
    // schema to connect to.
    //   mysql  — use the MySQL PDO driver
    //   host   — 127.0.0.1 (same machine; 'localhost' may use a Unix socket instead)
    //   dbname — the MySQL schema/database that holds the application tables
    'dsn' => 'mysql:host=127.0.0.1;dbname=rockna_tasks',

    // Database user.  'root' is fine for local development; use a restricted
    // user in staging/production environments.
    'username' => 'root',

    // Database password.  Empty string works when MySQL is configured for
    // passwordless root access on localhost (default after --initialize-insecure).
    'password' => '',

    // Character set for the connection.  utf8mb4 supports the full Unicode range
    // including emoji, which utf8 (MySQL's 3-byte variant) does not.
    'charset' => 'utf8mb4',
];
