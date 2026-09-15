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

// 2. Set environment variables to redirect storage and compiled views to /tmp
putenv('APP_STORAGE=/tmp/storage');
putenv('LARAVEL_STORAGE_PATH=/tmp/storage');
putenv('VIEW_COMPILED_PATH=/tmp/views');
putenv('CACHE_STORE=array');
putenv('CACHE_DRIVER=array');

$_ENV['APP_STORAGE'] = '/tmp/storage';
$_ENV['LARAVEL_STORAGE_PATH'] = '/tmp/storage';
$_ENV['VIEW_COMPILED_PATH'] = '/tmp/views';
$_ENV['CACHE_STORE'] = 'array';
$_ENV['CACHE_DRIVER'] = 'array';

$_SERVER['APP_STORAGE'] = '/tmp/storage';
$_SERVER['LARAVEL_STORAGE_PATH'] = '/tmp/storage';
$_SERVER['VIEW_COMPILED_PATH'] = '/tmp/views';
$_SERVER['CACHE_STORE'] = 'array';
$_SERVER['CACHE_DRIVER'] = 'array';

// 3. Delegate request execution to Laravel's public entrypoint
require __DIR__ . '/../public/index.php';

