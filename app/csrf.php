<?php
declare(strict_types=1);

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valid(mixed $token): bool
{
    return is_string($token) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

/** Hentikan request yang bukan POST atau tokennya salah. */
function csrf_require(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !csrf_valid($_POST['_csrf'] ?? null)) {
        http_response_code(419);
        exit('Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.');
    }
}
