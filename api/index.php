<?php

/**
 * Entry point for Vercel Serverless PHP Runtime (vercel-php).
 *
 * In AWS Lambda / Vercel serverless containers, the filesystem is strictly read-only
 * except for the `/tmp` directory. This script creates the necessary directory
 * structure inside `/tmp` on cold-start and forwards the incoming request
 * to Laravel's standard `public/index.php`.
 */

// 1. Ensure required ephemeral storage directories exist in /tmp
$ephemeralDirs = [
    '/tmp/storage',
    '/tmp/storage/app',
    '/tmp/storage/app/public',
    '/tmp/storage/framework',
    '/tmp/storage/framework/cache',
    '/tmp/storage/framework/cache/data',
    '/tmp/storage/framework/sessions',
    '/tmp/storage/framework/views',
    '/tmp/storage/logs',
    '/tmp/views',
];

foreach ($ephemeralDirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// 2. Set environment variables to redirect storage, caches and compiled views to /tmp
putenv('APP_STORAGE=/tmp/storage');
putenv('LARAVEL_STORAGE_PATH=/tmp/storage');
putenv('VIEW_COMPILED_PATH=/tmp/views');
putenv('APP_CONFIG_CACHE=/tmp/config.php');
putenv('APP_EVENTS_CACHE=/tmp/events.php');
putenv('APP_PACKAGES_CACHE=/tmp/packages.php');
putenv('APP_ROUTES_CACHE=/tmp/routes.php');
putenv('APP_SERVICES_CACHE=/tmp/services.php');
putenv('CACHE_STORE=array');
putenv('CACHE_DRIVER=array');

$_ENV['APP_STORAGE'] = '/tmp/storage';
$_ENV['LARAVEL_STORAGE_PATH'] = '/tmp/storage';
$_ENV['VIEW_COMPILED_PATH'] = '/tmp/views';
$_ENV['APP_CONFIG_CACHE'] = '/tmp/config.php';
$_ENV['APP_EVENTS_CACHE'] = '/tmp/events.php';
$_ENV['APP_PACKAGES_CACHE'] = '/tmp/packages.php';
$_ENV['APP_ROUTES_CACHE'] = '/tmp/routes.php';
$_ENV['APP_SERVICES_CACHE'] = '/tmp/services.php';
$_ENV['CACHE_STORE'] = 'array';
$_ENV['CACHE_DRIVER'] = 'array';

$_SERVER['APP_STORAGE'] = '/tmp/storage';
$_SERVER['LARAVEL_STORAGE_PATH'] = '/tmp/storage';
$_SERVER['VIEW_COMPILED_PATH'] = '/tmp/views';
$_SERVER['APP_CONFIG_CACHE'] = '/tmp/config.php';
$_SERVER['APP_EVENTS_CACHE'] = '/tmp/events.php';
$_SERVER['APP_PACKAGES_CACHE'] = '/tmp/packages.php';
$_SERVER['APP_ROUTES_CACHE'] = '/tmp/routes.php';
$_SERVER['APP_SERVICES_CACHE'] = '/tmp/services.php';
$_SERVER['CACHE_STORE'] = 'array';
$_SERVER['CACHE_DRIVER'] = 'array';

// 3. Delegate request execution to Laravel's public entrypoint
require __DIR__ . '/../public/index.php';

