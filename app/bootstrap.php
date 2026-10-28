<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Jakarta');
foreach (['db', 'helpers', 'csrf', 'validator', 'throttle', 'queries', 'fan-upload', 'forms'] as $module) {
    $file = __DIR__ . "/$module.php";
    if (is_file($file)) {
        require_once $file;
    }
}

if (PHP_SAPI !== 'cli') {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
    header('X-Content-Type-Options: nosniff');

    set_exception_handler(function (Throwable $e): void {
        error_log((string)$e);
        http_response_code(500);
        echo 'Terjadi kesalahan pada server. Silakan coba lagi nanti.';
    });
}
