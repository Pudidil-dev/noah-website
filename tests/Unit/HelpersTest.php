<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class HelpersTest extends TestCase
{
    public function testEscapeHtml(): void
    {
        $this->assertSame('&lt;b&gt;&quot;x&quot;&#039;', e('<b>"x"\''));
        $this->assertSame('', e(null));
    }

    public function testSlugify(): void
    {
        $this->assertSame('keterkaitan-keterikatan-vinyl', slugify('Keterkaitan Keterikatan Vinyl'));
        $this->assertSame('hiatus-refleksi', slugify('  Hiatus & Refleksi! '));
        $this->assertSame('', slugify('***'));
    }

    public function testRupiah(): void
    {
        $this->assertSame('Rp 1.500.000', rupiah('1500000.00'));
        $this->assertSame('Rp 0', rupiah(0));
    }

    public function testTanggalIndonesia(): void
    {
        $this->assertSame('05 September 2024', tanggal_id('2024-09-05 10:00:00+07'));
        $this->assertSame('15 Nov 2012', tanggal_singkat('2012-11-15'));
        $this->assertSame('AGU', bulan_singkat('2027-08-24'));
        $this->assertSame('Oktober', bulan_panjang('2027-10-24'));
        $this->assertSame('', date_part(null, 'Y'));
        $this->assertSame('2019', date_part('2019-01-01', 'Y'));
    }

    public function testDurasi(): void
    {
        $this->assertSame('04:30', durasi(270));
        $this->assertSame('', durasi(null));
    }
}
