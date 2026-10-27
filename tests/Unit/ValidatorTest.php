<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase
{
    private const FIELDS = [
        'name' => ['type' => 'text', 'label' => 'Nama', 'required' => true, 'max' => 10],
        'slug' => ['type' => 'slug', 'label' => 'Slug', 'from' => 'name'],
        'price' => ['type' => 'money', 'label' => 'Harga', 'required' => true],
        'stock' => ['type' => 'int', 'label' => 'Stok', 'min' => 0, 'default' => 0],
        'category' => ['type' => 'enum', 'label' => 'Kategori', 'required' => true, 'options' => ['kaos', 'musik']],
        'is_limited' => ['type' => 'bool', 'label' => 'Terbatas'],
        'image_url' => ['type' => 'image', 'label' => 'Gambar'],
    ];

    public function testValidInputIsCleaned(): void
    {
        [$data, $errors] = validate(self::FIELDS, [
            'name' => ' Kaos Hitam ', 'price' => '250000', 'stock' => '', 'category' => 'kaos', 'is_limited' => '1',
        ]);
        $this->assertSame([], $errors);
        $this->assertSame([
            'name' => 'Kaos Hitam', 'slug' => 'kaos-hitam', 'price' => '250000',
            'stock' => 0, 'category' => 'kaos', 'is_limited' => true,
        ], $data);
    }

    public function testErrorsPerField(): void
    {
        [, $errors] = validate(self::FIELDS, [
            'name' => 'Nama yang terlalu panjang', 'price' => '250.000', 'stock' => '-1', 'category' => 'sepatu',
        ]);
        $this->assertSame(['name', 'price', 'stock', 'category'], array_keys($errors));
        $this->assertSame('Nama maksimal 10 karakter.', $errors['name']);
    }

    public function testRequiredMissing(): void
    {
        [, $errors] = validate(self::FIELDS, []);
        $this->assertSame('Nama wajib diisi.', $errors['name']);
        $this->assertArrayHasKey('slug', $errors);
    }

    public function testEmailPhoneDatetime(): void
    {
        $fields = [
            'email' => ['type' => 'email', 'label' => 'Email', 'required' => true],
            'phone' => ['type' => 'phone', 'label' => 'Telepon', 'required' => true],
            'start_at' => ['type' => 'datetime', 'label' => 'Mulai', 'required' => true],
        ];
        [$data, $errors] = validate($fields, ['email' => 'A@Example.com', 'phone' => '+62 811-2222-3333', 'start_at' => '2027-08-24T19:00']);
        $this->assertSame([], $errors);
        $this->assertSame('a@example.com', $data['email']);
        $this->assertSame('2027-08-24 19:00:00', $data['start_at']);

        [, $bad] = validate($fields, ['email' => 'bukan-email', 'phone' => 'abc', 'start_at' => '2027-13-40T99:00']);
        $this->assertCount(3, $bad);
    }

    public function testLinkRejectsJavascriptScheme(): void
    {
        $f = ['url' => ['type' => 'link', 'label' => 'URL']];
        $this->assertSame([], validate($f, ['url' => 'https://www.google.com/maps?q=x'])[1]);
        $this->assertSame([], validate($f, ['url' => 'images/product-1.jpg'])[1]);
        $this->assertArrayHasKey('url', validate($f, ['url' => 'javascript:alert(1)'])[1]);
        $this->assertArrayHasKey('url', validate($f, ['url' => '//external.example/path'])[1]);
    }

    public function testJsonLinksMustBeHttp(): void
    {
        $f = ['streaming_links' => ['type' => 'json', 'label' => 'Link', 'default' => '{}']];
        $this->assertSame('{"spotify":"https://open.spotify.com/x"}',
            validate($f, ['streaming_links' => '{"spotify": "https://open.spotify.com/x"}'])[0]['streaming_links']);
        $this->assertArrayHasKey('streaming_links', validate($f, ['streaming_links' => '{"x": "javascript:1"}'])[1]);
        $this->assertSame('{}', validate($f, [])[0]['streaming_links']);
    }

    public function testPasswordHashedAndOptionalOnEdit(): void
    {
        $f = ['password_hash' => ['type' => 'password', 'label' => 'Password']];
        [$data] = validate($f, ['password_hash' => 'rahasia123']);
        $this->assertTrue(password_verify('rahasia123', $data['password_hash']));
        $this->assertArrayHasKey('password_hash', validate($f, [])[1]);
        $this->assertSame([[], []], validate($f, [], true));
        $this->assertArrayHasKey('password_hash', validate($f, ['password_hash' => 'pendek'])[1]);
    }

    public function testJsonRequiresObjectRatherThanArray(): void
    {
        $f = ['streaming_links' => ['type' => 'json', 'label' => 'Link']];
        $this->assertArrayHasKey('streaming_links', validate($f, ['streaming_links' => '["https://example.com"]'])[1]);
        $this->assertSame('{}', validate($f, ['streaming_links' => '{}'])[0]['streaming_links']);
    }

    public function testArrayPayloadDoesNotCausePhpWarning(): void
    {
        [, $errors] = validate(self::FIELDS, ['name' => ['array'], 'slug' => ['array'], 'category' => ['array']]);
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('slug', $errors);
        $this->assertArrayHasKey('category', $errors);
    }

    public function testInvalidCalendarDateAndTrailingTextAreRejected(): void
    {
        $date = ['release_date' => ['type' => 'date', 'label' => 'Tanggal']];
        $this->assertArrayHasKey('release_date', validate($date, ['release_date' => '2027-02-30'])[1]);
        $this->assertArrayHasKey('release_date', validate($date, ['release_date' => '2027-03-01suffix'])[1]);
    }
}
