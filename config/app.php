<?php
// config/app.php

define('APP_NAME',    'Katalog Skripsi Digital');
define('APP_URL',     'http://localhost/katalog-skripsi'); // Sesuaikan
define('BASE_PATH',   dirname(__DIR__));
define('UPLOAD_PATH', BASE_PATH . '/uploads/pdf/');
define('UPLOAD_URL',  APP_URL . '/uploads/pdf/');

define('MAX_FILE_SIZE_KB',  10240);     // 10 MB (NFR — Risiko 1)
define('MAX_FILE_SIZE_BYTE', MAX_FILE_SIZE_KB * 1024);
define('ALLOWED_MIME',      ['application/pdf']);

define('ITEMS_PER_PAGE', 12);           // NFR03: navigasi efisien

// Session
define('SESSION_TIMEOUT', 3600);        // 1 jam

// Timezone
date_default_timezone_set('Asia/Makassar');

// Error reporting (nonaktifkan di production)
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', BASE_PATH . '/logs/error.log');
