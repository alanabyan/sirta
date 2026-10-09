<?php

/*
 * SIRTA — pintu masuk Laravel untuk hosting bersama.
 * Disalin oleh GitHub Actions ke:  ~/domains/sirta.org/public_html/core-api/index.php
 *
 * Kode Laravel berada DI LUAR public_html, di ~/sirta/backend, sehingga .env, vendor, dan storage
 * tidak pernah dapat dibuka dari internet walaupun .htaccess bermasalah.
 *
 * Susunan:  core-api → public_html → sirta.org → domains → (folder rumah) / sirta / backend
 */

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$backend = dirname(__DIR__, 4).'/sirta/backend';

if (! is_file($backend.'/vendor/autoload.php')) {
    // Jangan tampilkan jalur folder ke pengunjung; catat ke log server saja.
    error_log("SIRTA: Laravel tidak ditemukan di {$backend} (vendor/autoload.php tidak ada). Lihat DEPLOY.md.");
    http_response_code(503);
    header('Content-Type: application/json');
    echo json_encode(['message' => 'Layanan sedang disiapkan. Silakan coba beberapa saat lagi.']);
    exit;
}

// Mode pemeliharaan (php artisan down) selama deploy.
if (file_exists($maintenance = $backend.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $backend.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once $backend.'/bootstrap/app.php';

// public_path() menunjuk ke folder ini, bukan ke backend/public yang tidak dipakai di server.
$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
