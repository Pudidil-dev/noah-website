<?php
declare(strict_types=1);

const UPLOAD_MAX_BYTES = 2 * 1024 * 1024;
const UPLOAD_MIME = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

function store_upload(array $file, string $folder, string $baseDir, string $baseUrl, ?callable $move = null): array
{
    $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($error === UPLOAD_ERR_NO_FILE) return [null, null];
    if ($error !== UPLOAD_ERR_OK) return [null, 'Upload gagal.'];
    if (!is_string($file['tmp_name'] ?? null) || !is_file($file['tmp_name'])) return [null, 'File tidak ditemukan.'];
    if (($file['size'] ?? 0) > UPLOAD_MAX_BYTES || filesize($file['tmp_name']) > UPLOAD_MAX_BYTES) return [null, 'Ukuran gambar maksimal 2 MB.'];
    if (preg_match('/^[a-z_]+$/', $folder) !== 1) throw new InvalidArgumentException('Folder upload tidak valid.');
    $ext = UPLOAD_MIME[(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name'])] ?? null;
    if ($ext === null || @getimagesize($file['tmp_name']) === false) return [null, 'Format gambar harus JPG, PNG, atau WEBP.'];
    $dir = $baseDir . '/' . $folder;
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) return [null, 'Folder upload tidak bisa dibuat.'];
    $name = bin2hex(random_bytes(16)) . '.' . $ext;
    $mover = $move ?? 'move_uploaded_file';
    return $mover($file['tmp_name'], "$dir/$name") ? ["$baseUrl/$folder/$name", null] : [null, 'File gagal disimpan.'];
}

function delete_upload(?string $path, string $baseDir, string $baseUrl): bool
{
    if ($path === null || !str_starts_with($path, $baseUrl . '/')) return false;
    $root = realpath($baseDir);
    $full = realpath($baseDir . substr($path, strlen($baseUrl)));
    return $root !== false && $full !== false && str_starts_with($full, $root . DIRECTORY_SEPARATOR)
        && is_file($full) && unlink($full);
}
