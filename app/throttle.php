<?php
declare(strict_types=1);

function throttle_dir(): string
{
    $env = getenv('NOAH_THROTTLE_DIR');
    $dir = ($env !== false && $env !== '') ? $env : sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'noah_throttle';
    if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
        throw new RuntimeException('Folder throttle tidak bisa dibuat.');
    }
    return $dir;
}

function throttle_file(string $key): string
{
    return throttle_dir() . DIRECTORY_SEPARATOR . hash('sha256', $key) . '.json';
}

/** @return list<int> */
function throttle_hits(string $key): array
{
    return throttle_locked($key, LOCK_SH, static fn ($stream): array => throttle_read($stream));
}

/** @param resource $stream
 *  @return list<int>
 */
function throttle_read($stream): array
{
    rewind($stream);
    $contents = stream_get_contents($stream);
    if ($contents === false) {
        throw new RuntimeException('Data throttle tidak bisa dibaca.');
    }
    $data = json_decode($contents, true);
    return is_array($data) ? array_map('intval', $data) : [];
}

/** @param callable(resource):mixed $callback */
function throttle_locked(string $key, int $mode, callable $callback): mixed
{
    $stream = fopen(throttle_file($key), 'c+');
    if ($stream === false) {
        throw new RuntimeException('Data throttle tidak bisa dibuka.');
    }
    try {
        if (!flock($stream, $mode)) {
            throw new RuntimeException('Data throttle tidak bisa dikunci.');
        }
        try {
            return $callback($stream);
        } finally {
            flock($stream, LOCK_UN);
        }
    } finally {
        fclose($stream);
    }
}

function throttle_prune(array $hits, int $now, int $window): array
{
    return array_values(array_filter($hits, fn (int $t): bool => $t > $now - $window));
}

function throttle_exceeded(string $key, int $max, int $window, ?int $now = null): bool
{
    return count(throttle_prune(throttle_hits($key), $now ?? time(), $window)) >= $max;
}

function throttle_record(string $key, int $window, ?int $now = null): void
{
    $time = $now ?? time();
    throttle_locked($key, LOCK_EX, static function ($stream) use ($time, $window): void {
        $hits = [...throttle_prune(throttle_read($stream), $time, $window), $time];
        rewind($stream);
        if (!ftruncate($stream, 0) || fwrite($stream, json_encode($hits, JSON_THROW_ON_ERROR)) === false || !fflush($stream)) {
            throw new RuntimeException('Data throttle tidak bisa disimpan.');
        }
    });
}

function throttle_clear(string $key): void
{
    throttle_locked($key, LOCK_EX, static function ($stream): void {
        if (!ftruncate($stream, 0) || !fflush($stream)) {
            throw new RuntimeException('Data throttle tidak bisa dibersihkan.');
        }
    });
}
