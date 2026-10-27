<?php
declare(strict_types=1);

// Salin file ini menjadi config.php lalu sesuaikan. config.php tidak di-commit.
return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 5433,
        'name' => 'noah',
        'user' => 'noah',
        'pass' => 'noah_dev',
    ],
    'app' => [
        'upload_dir' => __DIR__ . '/uploads',
        'upload_url' => 'uploads',
    ],
];
