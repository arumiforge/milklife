<?php

use App\Libraries\HariLomba\DaftarPeserta;
use App\Libraries\HariLomba\KodeDaftarUlang;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Aturan murni hari lomba (F8-03): ID dari isi kode QR bukti atau ketikan
 * admin, dan nama lembar Excel daftar peserta.
 *
 * @internal
 */
final class HariLombaKodeDaftarUlangTest extends CIUnitTestCase
{
    public function testIdDariAlamatBuktiKodeQr(): void
    {
        $this->assertSame('MAC2A0000126', KodeDaftarUlang::idDari('https://daftar.contoh.test/bukti/0123456789abcdef01234567/MAC2A0000126'));
        $this->assertSame('MAC2A0000126', KodeDaftarUlang::idDari('http://localhost:8080/index.php/bukti/0123456789abcdef01234567/MAC2A0000126?pdf=1'));
        $this->assertSame('MAC2B0000126', KodeDaftarUlang::idDari("  https://contoh.test/bukti/0123456789ABCDEF01234567/mac2b0000126\n"));
    }

    public function testIdDiketik(): void
    {
        $this->assertSame('MAC2A0000126', KodeDaftarUlang::idDari('MAC2A0000126'));
        $this->assertSame('MAC2A0000126', KodeDaftarUlang::idDari(' mac2a0000126 '));
    }

    public function testIsianYangBukanIdDitolak(): void
    {
        foreach (['', 'halo', 'MAC', 'MAC2A-0000126', 'https://contoh.test/bukti/pendek/MAC2A0000126', 'https://contoh.test/lain', '20300001', null, 12] as $isian) {
            $this->assertNull(KodeDaftarUlang::idDari($isian), var_export($isian, true));
        }
    }

    public function testJudulLembarExcelAmanPendekDanTidakKembar(): void
    {
        $pakai = [];

        $this->assertSame('KU 10 Kanga s Escape putra', DaftarPeserta::judulLembar("KU 10 Kanga's Escape putra", $pakai));
        $this->assertSame('KU 10 Lari 60 m putra', DaftarPeserta::judulLembar('KU 10 Lari 60 m putra', $pakai));
        $this->assertSame('KU 10 Lari 60 m putra 2', DaftarPeserta::judulLembar('KU 10 Lari 60 m putra', $pakai));

        $panjang = DaftarPeserta::judulLembar('KU 12 Estafet 8 × 50 m mixed: babak [final]/ulang', $pakai);
        $this->assertLessThanOrEqual(31, mb_strlen($panjang));
        $this->assertDoesNotMatchRegularExpression('~[*:/\\\\?\[\]]~', $panjang);
    }
}
