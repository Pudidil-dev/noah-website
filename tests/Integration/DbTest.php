<?php
declare(strict_types=1);

final class DbTest extends DbTestCase
{
    public function testConnectsToTestDatabase(): void
    {
        $this->assertSame('noah_test', self::$pdo->query('SELECT current_database()')->fetchColumn());
    }

    public function testTimezoneIsJakarta(): void
    {
        $this->assertSame('Asia/Jakarta', self::$pdo->query('SHOW TIME ZONE')->fetchColumn());
    }

    public function testBooleanParamIsConverted(): void
    {
        $this->assertSame('t', db_param(true));
        $this->assertSame('f', db_param(false));
        $this->assertSame(5, db_param(5));
    }

    public function testNestedTransactionUsesSavepoint(): void
    {
        try {
            with_transaction(self::$pdo, function (PDO $pdo): void {
                $pdo->exec("INSERT INTO subscribers (email) VALUES ('a@example.com')");
                throw new RuntimeException('batal');
            });
        } catch (RuntimeException) {
        }

        $this->assertSame(0, (int) self::$pdo->query('SELECT COUNT(*) FROM subscribers')->fetchColumn());
        $this->assertTrue(self::$pdo->inTransaction());
    }

    public function testThreeLevelsOfTransactionRollBackOnlyFailingInnerOperation(): void
    {
        with_transaction(self::$pdo, function (PDO $pdo): void {
            $pdo->exec("INSERT INTO subscribers (email) VALUES ('outer@example.com')");
            try {
                with_transaction($pdo, function (PDO $pdo): void {
                    $pdo->exec("INSERT INTO subscribers (email) VALUES ('inner@example.com')");
                    throw new RuntimeException('inner failed');
                });
            } catch (RuntimeException) {
            }
        });

        $this->assertSame(
            ['outer@example.com'],
            self::$pdo->query('SELECT email FROM subscribers ORDER BY email')->fetchAll(PDO::FETCH_COLUMN)
        );
    }

    public function testFetchHelpersUsePreparedParameters(): void
    {
        $id = $this->insert('INSERT INTO subscribers (email, is_active) VALUES (?, ?)', ['test@example.com', true]);
        $this->assertGreaterThan(0, $id);
        $this->assertSame(
            [['email' => 'test@example.com']],
            fetch_all(self::$pdo, 'SELECT email FROM subscribers WHERE is_active = ? AND email = ?', [true, 'test@example.com'])
        );
        $this->assertSame(
            ['email' => 'test@example.com'],
            fetch_one(self::$pdo, 'SELECT email FROM subscribers WHERE id = ?', [$id])
        );
        $this->assertNull(fetch_one(self::$pdo, 'SELECT email FROM subscribers WHERE id = ?', [-1]));
    }

    public function testTopLevelTransactionCommitsSuccessAndRollsBackFailure(): void
    {
        self::$pdo->rollBack();
        try {
            with_transaction(self::$pdo, function (PDO $pdo): void {
                $pdo->exec("INSERT INTO subscribers (email) VALUES ('failed@example.com')");
                throw new RuntimeException('batal');
            });
        } catch (RuntimeException) {
        }

        $this->assertFalse(self::$pdo->inTransaction());
        $this->assertSame(0, (int) self::$pdo->query('SELECT COUNT(*) FROM subscribers')->fetchColumn());

        $result = with_transaction(self::$pdo, function (PDO $pdo): string {
            $pdo->exec("INSERT INTO subscribers (email) VALUES ('committed@example.com')");
            return 'sukses';
        });
        $this->assertSame('sukses', $result);
        $this->assertFalse(self::$pdo->inTransaction());
        $this->assertSame(1, (int) self::$pdo->query('SELECT COUNT(*) FROM subscribers')->fetchColumn());

        self::$pdo->exec("DELETE FROM subscribers WHERE email = 'committed@example.com'");
    }
}
