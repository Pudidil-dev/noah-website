<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CsrfTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function testTokenIsStableWithinSession(): void
    {
        $this->assertSame(64, strlen(csrf_token()));
        $this->assertSame(csrf_token(), csrf_token());
    }

    public function testValidation(): void
    {
        $token = csrf_token();
        $this->assertTrue(csrf_valid($token));
        $this->assertFalse(csrf_valid('salah'));
        $this->assertFalse(csrf_valid(null));
    }

    public function testFieldContainsEscapedToken(): void
    {
        $this->assertStringContainsString('name="_csrf" value="' . csrf_token() . '"', csrf_field());
    }
}
