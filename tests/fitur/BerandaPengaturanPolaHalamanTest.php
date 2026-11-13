<?php

use CodeIgniter\Config\Services;
use Tests\Support\KirimFormulir;
use Tests\Support\UjiBasisData;

/**
 * Contoh pola halaman pengaturan (rencana Tahap 2, "Pola halaman") dengan
 * pengendali contoh `Tests\Support\Controllers\ContohPengaturanController`:
 * tampilkan formulir → isian salah tampil ulang (200) → berhasil dialihkan
 * dengan `pesan_sukses`; gagal karena keadaan data dialihkan dengan
 * `pesan_galat`. Juga memastikan `KirimFormulir` dan `PengaturanBersih`
 * bekerja. Pelaksana halaman pengaturan bisa menyalin susunan uji ini.
 *
 * @internal
 */
final class BerandaPengaturanPolaHalamanTest extends UjiBasisData
{
    use KirimFormulir;

    private const ALAMAT = 'admin/pengaturan/contoh';

    protected function setUp(): void
    {
        parent::setUp();

        // Penentu rute bersama dibuat ulang supaya memakai rute contoh di bawah.
        Services::resetSingle('router');

        $pengendali = '\Tests\Support\Controllers\ContohPengaturanController';
        $this->withRoutes([
            ['GET', 'admin/masuk', '\App\Controllers\Admin\MasukController::index', ['filter' => 'admin']],
            ['GET', 'admin/pengaturan', '\App\Controllers\Admin\Pengaturan\BerandaPengaturanController::index', ['filter' => 'admin']],
            ['GET', self::ALAMAT, $pengendali . '::index', ['filter' => 'admin']],
            ['POST', self::ALAMAT, $pengendali . '::simpan', ['filter' => 'admin']],
            ['POST', self::ALAMAT . '/(:num)/hapus', $pengendali . '::hapus/$1', ['filter' => 'admin']],
        ]);
    }

    protected function tearDown(): void
    {
        // Rute contoh tidak boleh terbawa ke uji lain.
        Services::resetSingle('routes');
        Services::resetSingle('router');

        parent::tearDown();
    }

    public function testTampilkanFormulirBerisiNilaiTersimpan(): void
    {
        setting('Acara.nama', 'Lomba Karangan');

        $hasil = $this->bukaSebagaiAdmin(self::ALAMAT);
        $hasil->assertOK();

        $xpath = $this->xpathDari($hasil);
        $this->assertSame('Lomba Karangan', $xpath->query('//input[@name="nama"]')->item(0)?->getAttribute('value'));
        $this->assertSame(1, $xpath->query('//nav[@aria-label="Navigasi pengaturan"]')->length);
        $this->assertSame(0, $xpath->query('//*[@id="galat-isian"]')->length, 'Formulir baru tanpa ringkasan galat.');
        $this->assertSame(0, $xpath->query('//*[contains(@class, "is-invalid")]')->length);
        $this->assertSame(0, $xpath->query('//*[contains(@class, "pesan-kilat")]')->length);

        // Formulir hapus memakai POST, token CSRF, dan kalimat konfirmasi; skripnya dimuat.
        $hapus = $xpath->query('//form[@data-konfirmasi]')->item(0);
        $this->assertNotNull($hapus);
        $this->assertSame('post', $hapus->getAttribute('method'));
        $this->assertSame(1, $xpath->query('.//input[@name="' . config('Security')->tokenName . '"]', $hapus)->length);
        $hasil->assertSee(aset('js/konfirmasi.js'));
    }

    public function testIsianSalahDitampilkanUlangTanpaPengalihan(): void
    {
        setting('Acara.tempat', 'Tempat Lama');

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, ['nama' => '', 'tempat' => 'Stadion <Karangan>', 'biaya' => '150.000,50']);

        $this->assertIsianSalahDitampilkanUlang($hasil, ['nama', 'biaya']);
        $hasil->assertSee('Ada 2 isian yang perlu diperbaiki:');
        $hasil->assertSee('Nama acara belum diisi. Tulis nama acara, misalnya MilkLife Athletics Challenge.');
        $hasil->assertSee('Biaya belum benar. Tulis angka rupiah tanpa koma, misalnya 150000.');

        // Isian yang diketik tetap ada (di-escape); yang benar tidak ditandai.
        $xpath = $this->xpathDari($hasil);
        $this->assertSame('Stadion <Karangan>', $xpath->query('//input[@name="tempat"]')->item(0)?->getAttribute('value'));
        $this->assertStringNotContainsString('is-invalid', (string) $xpath->query('//input[@name="tempat"]')->item(0)?->getAttribute('class'));
        $this->assertSame('nama-galat', $xpath->query('//input[@name="nama"]')->item(0)?->getAttribute('aria-describedby'));
        $this->assertSame(1, $xpath->query('//*[@id="galat-isian"]//a[@href="#nama"]')->length, 'Ringkasan menautkan ke kotaknya.');

        // Tidak ada yang tersimpan.
        $this->assertSame('', setting('Acara.nama'));
        $this->assertSame('Tempat Lama', setting('Acara.tempat'));
    }

    public function testSimpanBerhasilDialihkanDenganPesanSukses(): void
    {
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, ['nama' => '  Lomba Karangan  ', 'tempat' => 'Stadion Karangan', 'biaya' => 'Rp 150.000']);

        $this->assertBerhasilDialihkan($hasil, self::ALAMAT, 'Data acara tersimpan.');
        $this->assertSame('Lomba Karangan', setting('Acara.nama'));
        $this->assertSame('Stadion Karangan', setting('Acara.tempat'));
        $this->seeInDatabase('settings', ['class' => 'Config\Acara', 'key' => 'nama', 'value' => 'Lomba Karangan']);

        // Halaman berikutnya menampilkan pesan lewat tata letak (admin/_pesan.php).
        $berikut = $this->withSession($_SESSION)->get(self::ALAMAT);
        $xpath   = $this->xpathDari($berikut);
        $pesan   = $xpath->query('//main//*[@data-pesan="pesan_sukses"][@role="status"]')->item(0);
        $this->assertNotNull($pesan);
        $this->assertStringContainsString('Data acara tersimpan.', $pesan->textContent);
        $this->assertStringContainsString('alert-success', $pesan->getAttribute('class'));
    }

    public function testGagalKarenaKeadaanDataDialihkanDenganPesanGalat(): void
    {
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . '/7/hapus');

        $this->assertGagalDialihkan($hasil, self::ALAMAT);
        $this->assertStringContainsString('Matikan saja', (string) session('pesan_galat'));

        $berikut = $this->withSession($_SESSION)->get(self::ALAMAT);
        $pesan   = $this->xpathDari($berikut)->query('//main//*[@data-pesan="pesan_galat"][@role="alert"]')->item(0);
        $this->assertNotNull($pesan);
        $this->assertStringContainsString('Data nomor 7 sudah dipakai, jadi tidak bisa dihapus.', $pesan->textContent);
    }

    public function testTokenCsrfWajib(): void
    {
        $this->assertTokenCsrfWajib(self::ALAMAT, ['nama' => 'Lomba Karangan']);
        $this->assertSame('', setting('Acara.nama'));
    }

    public function testTertutupUntukYangBelumMasuk(): void
    {
        $this->assertBelumMasukDialihkan(self::ALAMAT);
        $this->assertBelumMasukDialihkan(self::ALAMAT, 'post');
        $this->assertSame('', setting('Acara.nama'));
    }

    // Dua uji berikut berjalan berurutan: nilai dari uji pertama tidak boleh
    // terlihat di uji kedua (`PengaturanBersih`).

    public function testPengaturanBersihMenulis(): void
    {
        $this->kirimSebagaiAdmin(self::ALAMAT, ['nama' => 'Nilai Uji Sebelumnya']);

        $this->assertSame('Nilai Uji Sebelumnya', setting('Acara.nama'));
    }

    public function testPengaturanBersihTidakTerbawaDariUjiSebelumnya(): void
    {
        $this->assertSame('', setting('Acara.nama'));
        $this->seeNumRecords(0, 'settings', []);
        $this->bukaSebagaiAdmin(self::ALAMAT)->assertDontSee('Nilai Uji Sebelumnya');
    }
}
