<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ThrottleTest extends TestCase
{
    protected function setUp(): void
    {
        throttle_clear('uji');
    }

    protected function tearDown(): void
    {
        throttle_clear('uji');
    }

    public function testPruneKeepsOnlyWindow(): void
    {
        $this->assertSame([950, 1000], throttle_prune([100, 950, 1000], 1000, 100));
    }

    public function testExceededAfterMaxHits(): void
    {
        foreach ([1000, 1001, 1002] as $t) {
            $this->assertFalse(throttle_exceeded('uji', 3, 60, $t));
            throttle_record('uji', 60, $t);
        }
        $this->assertTrue(throttle_exceeded('uji', 3, 60, 1003));
        $this->assertFalse(throttle_exceeded('uji', 3, 60, 1100));
    }

    public function testClear(): void
    {
        throttle_record('uji', 60, 1000);
        throttle_clear('uji');
        $this->assertFalse(throttle_exceeded('uji', 1, 60, 1000));
    }

    public function testBucketsDoNotShareHits(): void
    {
        throttle_record('uji', 60, 1000);
        $this->assertTrue(throttle_exceeded('uji', 1, 60, 1000));
        $this->assertFalse(throttle_exceeded('uji:other-ip', 1, 60, 1000));
    }
}
