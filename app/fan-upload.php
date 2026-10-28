<?php
declare(strict_types=1);

/** Kiriman publik hanya boleh berupa gambar atau MP3 sesuai kategori. */
function fan_upload_file(array $file, string $type, string $baseDir, string $baseUrl): array
{
    if (!in_array($type, ['story', 'artwork', 'cover'], true)) {
        return [null, 'Kategori kiriman tidak valid.'];
    }
    $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if (!is_int($error) || $error !== UPLOAD_ERR_OK) {
        return [null, $error === UPLOAD_ERR_NO_FILE ? 'Pilih berkas yang akan dikirim.' : 'Berkas gagal diunggah.'];
    }

    $tmp = $file['tmp_name'] ?? null;
    $name = $file['name'] ?? null;
    $size = $file['size'] ?? null;
    if (!is_string($tmp) || !is_uploaded_file($tmp) || !is_string($name) || !is_int($size)) {
        return [null, 'Berkas tidak valid.'];
    }

    $max = $type === 'cover' ? 8 * 1024 * 1024 : 2 * 1024 * 1024;
    $actualSize = filesize($tmp);
    if ($actualSize === false || $size < 1 || $actualSize < 1 || $size > $max || $actualSize > $max) {
        return [null, $type === 'cover' ? 'MP3 maksimal 8 MB.' : 'Gambar maksimal 2 MB.'];
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if ($type === 'cover') {
        if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'mp3' || $mime !== 'audio/mpeg') {
            return [null, 'Cover lagu harus berupa MP3.'];
        }
        $extension = 'mp3';
    } else {
        $extension = $extensions[$mime] ?? null;
        if ($extension === null || @getimagesize($tmp) === false) {
            return [null, 'Gambar harus berformat JPG, PNG, atau WEBP.'];
        }
    }

    $directory = rtrim($baseDir, '/\\') . DIRECTORY_SEPARATOR . 'fans';
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        return [null, 'Berkas belum dapat disimpan.'];
    }
    $root = realpath($baseDir);
    $resolved = realpath($directory);
    if ($root === false || $resolved === false || dirname($resolved) !== $root) {
        return [null, 'Folder unggahan tidak valid.'];
    }
    $filename = bin2hex(random_bytes(16)) . '.' . $extension;
    if (!move_uploaded_file($tmp, $resolved . DIRECTORY_SEPARATOR . $filename)) {
        return [null, 'Berkas belum dapat disimpan.'];
    }

    return [rtrim($baseUrl, '/') . '/fans/' . $filename, null];
}

/** Hapus hanya berkas kiriman yang sebelumnya dibuat di direktori fans. */
function delete_fan_upload(?string $path, string $baseDir, string $baseUrl): void
{
    $prefix = rtrim($baseUrl, '/') . '/fans/';
    if ($path === null || !str_starts_with($path, $prefix)) {
        return;
    }
    $filename = substr($path, strlen($prefix));
    if (!preg_match('/^[0-9a-f]{32}\.(?:jpg|png|webp|mp3)$/', $filename)) {
        return;
    }
    $directory = realpath(rtrim($baseDir, '/\\') . DIRECTORY_SEPARATOR . 'fans');
    $root = realpath($baseDir);
    if ($directory === false || $root === false || dirname($directory) !== $root) {
        return;
    }
    $file = $directory . DIRECTORY_SEPARATOR . $filename;
    if (is_file($file) && !is_link($file)) {
        unlink($file);
    }
}
