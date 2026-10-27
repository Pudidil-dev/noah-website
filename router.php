<?php
declare(strict_types=1);

// PHP development server: keep implementation files and configuration off the web.
$path = rawurldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
$blocked = str_contains($path, "\0")
    || preg_match('#(?:^|/)\.(?!well-known(?:/|$))#', $path)
    || preg_match('#(?:^|/)\.\.(?:/|$)#', $path)
    || preg_match('#^/(?:app|database|docs|tests|vendor)(?:/|$)#', $path)
    || preg_match('#^/admin/(?:core|views|custom)(?:/|$)#', $path)
    || in_array($path, ['/admin/resources.php', '/config.php', '/config.example.php', '/composer.json', '/composer.lock', '/phpunit.xml', '/docker-compose.yml', '/router.php', '/README.md', '/todo.md'], true)
    || preg_match('#\.(?:dbml|sql|log|md|xml|lock)$#i', $path)
    || preg_match('#^/uploads/.*\.(?:php\d*|phtml|phar)$#i', $path);

if ($blocked) {
    http_response_code(403);
    exit('Akses ditolak.');
}

return false;
