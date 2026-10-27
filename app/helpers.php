<?php
declare(strict_types=1);

const BULAN_ID = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli',
    'Agustus', 'September', 'Oktober', 'November', 'Desember'];

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function slugify(string $text): string
{
    $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($text))) ?? '';
    return trim($slug, '-');
}

function rupiah(string|int|float $amount): string
{
    return 'Rp ' . number_format((float)$amount, 0, ',', '.');
}

function date_part(?string $datetime, string $format): string
{
    return $datetime === null || $datetime === '' ? '' : (new DateTimeImmutable($datetime))->format($format);
}

function bulan_panjang(string $datetime): string
{
    return BULAN_ID[(int)date_part($datetime, 'n')];
}

function bulan_singkat(string $datetime): string
{
    return strtoupper(substr(bulan_panjang($datetime), 0, 3));
}

function tanggal_id(string $datetime): string
{
    return date_part($datetime, 'd') . ' ' . bulan_panjang($datetime) . ' ' . date_part($datetime, 'Y');
}

function tanggal_singkat(string $datetime): string
{
    return date_part($datetime, 'd') . ' ' . substr(bulan_panjang($datetime), 0, 3) . ' ' . date_part($datetime, 'Y');
}

function durasi(?int $seconds): string
{
    return $seconds === null ? '' : sprintf('%02d:%02d', intdiv($seconds, 60), $seconds % 60);
}

function redirect(string $url): never
{
    header('Location: ' . $url, true, 303);
    exit;
}

/** Simpan pesan (2 argumen) atau ambil-lalu-hapus pesan (1 argumen). */
function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }
    $value = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $value;
}
