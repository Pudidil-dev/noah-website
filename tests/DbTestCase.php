<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

abstract class DbTestCase extends TestCase
{
    protected static PDO $pdo;

    public static function setUpBeforeClass(): void
    {
        self::$pdo = db();
        if (self::$pdo->query('SELECT current_database()')->fetchColumn() !== 'noah_test') {
            throw new RuntimeException('Test database harus noah_test; skema tidak dihapus.');
        }

        self::$pdo->exec('DROP SCHEMA public CASCADE; CREATE SCHEMA public;');
        self::$pdo->exec((string) file_get_contents(dirname(__DIR__) . '/database/schema.sql'));
    }

    protected function setUp(): void
    {
        self::$pdo->beginTransaction();
    }

    protected function tearDown(): void
    {
        if (self::$pdo->inTransaction()) {
            self::$pdo->rollBack();
        }
    }

    protected function insert(string $sql, array $params = []): int
    {
        $stmt = self::$pdo->prepare($sql . ' RETURNING id');
        $stmt->execute(array_map('db_param', $params));
        return (int) $stmt->fetchColumn();
    }
}
