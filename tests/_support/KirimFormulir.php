<?php

namespace Tests\Support;

use CodeIgniter\Config\Services;
use CodeIgniter\Security\Exceptions\SecurityException;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\TestResponse;
use DOMDocument;
use DOMXPath;

/**
 * Bantuan uji halaman admin: membuka halaman dan mengirim formulir `POST`
 * sebagai admin yang sudah masuk, dengan token CSRF yang sah.
 *
 * CSRF tetap aktif di uji. `kirimSebagaiAdmin()` menyiapkan token di sesi lalu
 * mengirimkannya bersama isian, sama seperti peramban yang membuka formulirnya
 * lebih dulu.
 *
 * Pemakaian (kelas uji turunan `UjiBasisData` atau `UjiHalaman`; nilai
 * pengaturan tidak terbawa antar-uji, lihat `PengaturanBersih`):
 *
 *     final class PengaturanAcaraTest extends UjiBasisData
 *     {
 *         use KirimFormulir;
 *
 *         public function testFormulirTerbuka(): void
 *         {
 *             $this->bukaSebagaiAdmin('admin/pengaturan/acara')->assertOK();
 *         }
 *
 *         public function testTertutupUntukYangBelumMasuk(): void
 *         {
 *             $this->assertBelumMasukDialihkan('admin/pengaturan/acara');
 *             $this->assertBelumMasukDialihkan('admin/pengaturan/acara', 'post');
 *         }
 *
 *         public function testSimpanBerhasil(): void
 *         {
 *             $hasil = $this->kirimSebagaiAdmin('admin/pengaturan/acara', ['nama' => 'Lomba Karangan']);
 *
 *             $this->assertBerhasilDialihkan($hasil, 'admin/pengaturan/acara', 'Data acara tersimpan.');
 *             $this->assertSame('Lomba Karangan', setting('Acara.nama'));
 *         }
 *
 *         public function testIsianSalahDitampilkanUlang(): void
 *         {
 *             $hasil = $this->kirimSebagaiAdmin('admin/pengaturan/acara', ['nama' => '']);
 *
 *             $this->assertIsianSalahDitampilkanUlang($hasil, ['nama']);
 *             $hasil->assertSee('Nama acara belum diisi.');
 *         }
 *
 *         public function testTokenCsrfWajib(): void
 *         {
 *             $this->assertTokenCsrfWajib('admin/pengaturan/acara', ['nama' => 'Lomba Karangan']);
 *             $this->assertSame('', setting('Acara.nama'));
 *         }
 *     }
 *
 * Contoh lengkap pola halaman: `tests/fitur/BerandaPengaturanPolaHalamanTest.php`.
 */
trait KirimFormulir
{
    use FeatureTestTrait;

    /**
     * Buka halaman (`GET`) sebagai admin yang sudah masuk.
     *
     * @param array<string, mixed> $sesi Isi sesi tambahan.
     */
    protected function bukaSebagaiAdmin(string $alamat, array $sesi = []): TestResponse
    {
        return $this->withSession($sesi + UjiHalaman::SESI_ADMIN)->get($alamat);
    }

    /**
     * Kirim formulir `POST` sebagai admin yang sudah masuk, dengan token CSRF sah.
     *
     * Isian berupa larik ditulis seperti di formulir:
     * `['jenjang' => ['A', 'B']]` sama dengan kotak centang `jenjang[]`.
     *
     * @param array<string, mixed> $isian
     * @param array<string, mixed> $sesi  Isi sesi tambahan (menimpa sesi admin bawaan).
     */
    protected function kirimSebagaiAdmin(string $alamat, array $isian = [], array $sesi = []): TestResponse
    {
        $namaToken = config('Security')->tokenName;
        $token     = bin2hex(random_bytes(32));

        // Layanan keamanan dibuat ulang supaya membaca token dari sesi uji ini.
        Services::resetSingle('security');

        return $this->withSession([$namaToken => $token] + $sesi + UjiHalaman::SESI_ADMIN)
            ->post($alamat, [$namaToken => $token] + $isian);
    }

    /**
     * Halaman admin yang dibuka tanpa masuk dialihkan ke halaman masuk.
     *
     * Untuk `post`, token CSRF sah ikut dikirim supaya yang diuji memang
     * penjaga halaman admin, bukan pemeriksaan CSRF.
     *
     * @param 'get'|'post' $metode
     */
    protected function assertBelumMasukDialihkan(string $alamat, string $metode = 'get'): void
    {
        if ($metode === 'post') {
            $namaToken = config('Security')->tokenName;
            $token     = bin2hex(random_bytes(32));

            Services::resetSingle('security');
            $hasil = $this->withSession([$namaToken => $token])->post($alamat, [$namaToken => $token]);
        } else {
            $hasil = $this->withSession([])->get($alamat);
        }

        $hasil->assertRedirectTo(site_url('admin/masuk'));
        $this->assertArrayNotHasKey('admin_id', $_SESSION ?? []);
    }

    /**
     * Kiriman tanpa token CSRF dan dengan token salah sama-sama ditolak
     * (`SecurityException`, kode 403) sebelum sampai ke pengendali.
     *
     * @param array<string, mixed> $isian
     */
    protected function assertTokenCsrfWajib(string $alamat, array $isian = []): void
    {
        $namaToken = config('Security')->tokenName;

        foreach ([
            'tanpa token' => [[], []],
            'token salah' => [[$namaToken => str_repeat('a', 64)], [$namaToken => str_repeat('b', 64)]],
        ] as $keadaan => [$sesi, $tokenKirim]) {
            Services::resetSingle('security');

            try {
                $this->withSession($sesi + UjiHalaman::SESI_ADMIN)->post($alamat, $tokenKirim + $isian);
                $this->fail("Kiriman {$keadaan} ke {$alamat} seharusnya ditolak.");
            } catch (SecurityException $e) {
                $this->assertSame(403, $e->getCode(), "Kode penolakan kiriman {$keadaan}.");
            }
        }
    }

    /**
     * Simpan berhasil: dialihkan ke `$alamat` dengan pesan kilat `pesan_sukses`.
     */
    protected function assertBerhasilDialihkan(TestResponse $hasil, string $alamat, ?string $pesan = null): void
    {
        $hasil->assertRedirectTo(site_url($alamat));
        $hasil->assertSessionHas('pesan_sukses', $pesan);
    }

    /**
     * Gagal karena keadaan data: dialihkan ke `$alamat` dengan pesan kilat `pesan_galat`.
     */
    protected function assertGagalDialihkan(TestResponse $hasil, string $alamat, ?string $pesan = null): void
    {
        $hasil->assertRedirectTo(site_url($alamat));
        $hasil->assertSessionHas('pesan_galat', $pesan);
    }

    /**
     * Isian salah: formulir ditampilkan lagi (status 200, tanpa pengalihan)
     * dengan ringkasan `admin/_galat_isian` dan `is-invalid` pada setiap kotak
     * yang disebut (dicari dari atribut `name`, termasuk `name="kotak[]"`).
     *
     * @param list<string> $kotak Nama kotak isian yang harus ditandai salah.
     */
    protected function assertIsianSalahDitampilkanUlang(TestResponse $hasil, array $kotak): void
    {
        $hasil->assertStatus(200);
        $this->assertFalse($hasil->isRedirect(), 'Isian salah tidak boleh dialihkan.');

        $xpath = $this->xpathDari($hasil);
        $this->assertSame(1, $xpath->query('//*[@id="galat-isian"]')->length, 'Ringkasan isian salah (admin/_galat_isian) tidak tampil.');

        foreach ($kotak as $nama) {
            $kueri = sprintf(
                '//*[(@name="%1$s" or @name="%1$s[]") and contains(concat(" ", normalize-space(@class), " "), " is-invalid ")]',
                $nama,
            );
            $this->assertGreaterThan(0, $xpath->query($kueri)->length, "Kotak {$nama} tidak ditandai is-invalid.");
        }
    }

    /**
     * Pembaca HTML jawaban untuk pencarian XPath.
     */
    protected function xpathDari(TestResponse $hasil): DOMXPath
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $hasil->getBody());
        libxml_clear_errors();

        return new DOMXPath($dom);
    }
}
