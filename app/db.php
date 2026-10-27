<?php
declare(strict_types=1);

function config(): array
{
    static $config = null;
    if ($config === null) {
        $path = dirname(__DIR__) . '/config.php';
        if (!is_file($path)) {
            throw new RuntimeException('config.php belum dibuat. Salin dari config.example.php.');
        }
        $loaded = require $path;
        $envDb = getenv('NOAH_DB_NAME');
        $config = ($envDb !== false && $envDb !== '')
            ? array_replace_recursive($loaded, ['db' => ['name' => $envDb]])
            : $loaded;
    }
    return $config;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $c = config()['db'];
        $dsn = sprintf('pgsql:host=%s;port=%d;dbname=%s', $c['host'], $c['port'], $c['name']);
        $pdo = new PDO($dsn, $c['user'], $c['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->exec("SET TIME ZONE 'Asia/Jakarta'");
    }
    return $pdo;
}

/** PDO pgsql mengirim false sebagai '' (tidak valid untuk boolean), jadi ubah ke 't'/'f'. */
function db_param(mixed $value): mixed
{
    return is_bool($value) ? ($value ? 't' : 'f') : $value;
}

function fetch_all(PDO $pdo, string $sql, array $params = []): array
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_map('db_param', $params));
    return $stmt->fetchAll();
}

function fetch_one(PDO $pdo, string $sql, array $params = []): ?array
{
    return fetch_all($pdo, $sql, $params)[0] ?? null;
}

/** Jalankan $fn dalam transaksi; transaksi bertingkat memakai savepoint masing-masing. */
function with_transaction(PDO $pdo, callable $fn): mixed
{
    static $savepointId = 0;

    if ($pdo->inTransaction()) {
        $savepoint = 'sp_noah_' . ++$savepointId;
        $pdo->exec('SAVEPOINT ' . $savepoint);
        try {
            $result = $fn($pdo);
            $pdo->exec('RELEASE SAVEPOINT ' . $savepoint);
            return $result;
        } catch (Throwable $e) {
            $pdo->exec('ROLLBACK TO SAVEPOINT ' . $savepoint);
            $pdo->exec('RELEASE SAVEPOINT ' . $savepoint);
            throw $e;
        }
    }

    $pdo->beginTransaction();
    try {
        $result = $fn($pdo);
        $pdo->commit();
        return $result;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}
