<?php
// src/config/config.php

// --- Autoloader and Environment Variables ---
// This assumes you will run `composer require vlucas/phpdotenv`
// The following lines will typically be in your public/index.php file
// to ensure they are loaded on every request.
//
// require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
// $dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__, 2));
// $dotenv->load();


// --- Session Management ---
// Ensures a session is available for all parts of the application.
if (session_status() === PHP_SESSION_NONE) {
    // Secure session settings can be added here in the future
    session_start();
}


// --- Error Reporting ---
// Set to `true` for development to see all errors, `false` for production.
define('DEBUG_MODE', $_ENV['DEBUG_MODE'] ?? true);

if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
    // In production, it's best to log errors to a file.
    // ini_set('log_errors', 1);
    // ini_set('error_log', '/path/to/your/php-error.log');
}


// --- Application Constants ---
// Define a constant for the project's root directory.
define('ROOT_PATH', dirname(__DIR__, 2));
// Define a constant for the src directory.
define('SRC_PATH', __DIR__);


// --- Database Configuration ---
// These values are pulled from the .env file for security.
// The `??` operator provides a default value if the .env variable is not set.
define('DB_HOST', $_ENV['DB_HOST'] ?? '127.0.0.1');
define('DB_PORT', $_ENV['DB_PORT'] ?? '3306');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'php_blog_db');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_CHARSET', $_ENV['DB_CHARSET'] ?? 'utf8mb4');


// --- Application & Email Configuration ---
define('APP_NAME', $_ENV['APP_NAME'] ?? 'PHP Multi-User Blog');
define('APP_URL', $_ENV['APP_URL'] ?? 'http://localhost');

define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? null);
define('SMTP_PORT', $_ENV['SMTP_PORT'] ?? 587);
define('SMTP_USER', $_ENV['SMTP_USER'] ?? null);
define('SMTP_PASS', $_ENV['SMTP_PASS'] ?? null);
define('SMTP_FROM_EMAIL', $_ENV['SMTP_FROM_EMAIL'] ?? 'no-reply@example.com');
define('SMTP_FROM_NAME', $_ENV['SMTP_FROM_NAME'] ?? APP_NAME);


// --- Timezone ---
// It's good practice to set a default timezone.
date_default_timezone_set('UTC');
