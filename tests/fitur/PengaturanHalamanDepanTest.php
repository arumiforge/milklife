<?php

use App\Controllers\Admin\Pengaturan\HalamanDepanController;
use App\Libraries\Unggahan;
use Tests\Support\KirimFormulir;
use Tests\Support\UjiBasisData;
use Tests\Support\UnggahanSementara;

/**
 * Halaman pengaturan isi halaman depan (F2-05): empat gambar, teks, dan
 * pesan WA.
 *
 * @internal
 */
final class PengaturanHalamanDepanTest extends UjiBasisData
{
    use KirimFormulir;
    use UnggahanSementara;

    private const ALAMAT = 'admin/pengaturan/halaman-depan';

    private const KELAS_TANPA_GAMBAR = '[contains(concat(" ", @class, " "), " tanpa-gambar ")]';

    /**
     * @return array<string, string>
     */
    private static function isianSah(array $ganti = []): array
    {
        return $ganti + [
            'judul'          => 'Ayo ikut Lomba Karangan!',
            'teks'           => "Paragraf pertama.\r\n\r\nParagraf kedua.",
            'footer'         => 'Panitia Lomba Karangan 2026',
            'nomor_wa_tanya' => '+62 812 3456 7890',
            'pesan_tanya'    => 'Halo admin, saya mau bertanya.',
            'pesan_bagikan'  => 'Yuk daftar di http://contoh.test/',
        ];
    }

    public function testTertutupUntukYangBelumMasuk(): void
    {
        $this->assertBelumMasukDialihkan(self::ALAMAT);
        $this->assertBelumMasukDialihkan(self::ALAMAT, 'post');
    }

    public function testFormulirTerbukaDenganKotakTanpaGambar(): void
    {
        $hasil = $this->bukaSebagaiAdmin(self::ALAMAT);
        $hasil->assertOK();
        $hasil->assertSee('Gambar dan logo');

        $xpath = $this->xpathDari($hasil);
        $this->assertSame(1, $xpath->query('//nav[@aria-label="Navigasi pengaturan"]')->length);
        $this->assertSame('multipart/form-data', $xpath->query('//form[@action="' . site_url(self::ALAMAT) . '"]')->item(0)?->getAttribute('enctype'));

        foreach (array_keys(HalamanDepanController::GAMBAR) as $kunci) {
            $this->assertSame(1, $xpath->query('//*[@data-pratinjau="' . $kunci . '"]//*' . self::KELAS_TANPA_GAMBAR)->length, "Kotak tanpa gambar {$kunci}.");
            $this->assertSame(1, $xpath->query('//input[@type="file" and @name="' . $kunci . '"]')->length);
            $this->assertSame(0, $xpath->query('//input[@name="hapus_' . $kunci . '"]')->length);
            $this->assertSame((string) Unggahan::BATAS_GAMBAR, $xpath->query('//input[@name="' . $kunci . '"]')->item(0)?->getAttribute('data-ukuran-maksimal'));
        }

        foreach (['judul', 'nomor_wa_tanya'] as $kotak) {
            $this->assertSame('', $xpath->query('//input[@name="' . $kotak . '"]')->item(0)?->getAttribute('value'));
        }
        foreach (['teks', 'footer', 'pesan_tanya', 'pesan_bagikan'] as $kotak) {
            $this->assertSame(1, $xpath->query('//textarea[@name="' . $kotak . '"]')->length);
        }
    }

    public function testFormulirMenampilkanNilaiTersimpan(): void
    {
        setting('HalamanDepan.judul', 'Judul <Karangan>');
        setting('HalamanDepan.teks', "Baris satu\nBaris dua");
        setting('HalamanDepan.nomor_wa_tanya', '6281234567890');

        $xpath = $this->xpathDari($this->bukaSebagaiAdmin(self::ALAMAT));

        $this->assertSame('Judul <Karangan>', $xpath->query('//input[@name="judul"]')->item(0)?->getAttribute('value'));
        $this->assertSame("Baris satu\nBaris dua", $xpath->query('//textarea[@name="teks"]')->item(0)?->textContent);
        $this->assertSame('0812-3456-7890', $xpath->query('//input[@name="nomor_wa_tanya"]')->item(0)?->getAttribute('value'));
    }

    public function testSimpanBerhasil(): void
    {
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, self::isianSah());

        $this->assertBerhasilDialihkan($hasil, self::ALAMAT, 'Isi halaman depan tersimpan.');
        $this->assertSame('Ayo ikut Lomba Karangan!', setting('HalamanDepan.judul'));
        $this->assertSame("Paragraf pertama.\n\nParagraf kedua.", setting('HalamanDepan.teks'));
        $this->assertSame('Panitia Lomba Karangan 2026', setting('HalamanDepan.footer'));
        $this->assertSame('6281234567890', setting('HalamanDepan.nomor_wa_tanya'));
        $this->assertSame('Halo admin, saya mau bertanya.', setting('HalamanDepan.pesan_tanya'));
        $this->assertSame('Yuk daftar di http://contoh.test/', setting('HalamanDepan.pesan_bagikan'));
    }

    public function testSemuaBolehKosong(): void
    {
        setting('HalamanDepan.judul', 'Judul Lama');

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, array_fill_keys(array_keys(self::isianSah()), ''));

        $this->assertBerhasilDialihkan($hasil, self::ALAMAT);
        $this->assertSame('', setting('HalamanDepan.judul'));
        $this->assertSame('', setting('HalamanDepan.nomor_wa_tanya'));
    }

    public function testIsianSalahDitampilkanUlang(): void
    {
        setting('HalamanDepan.judul', 'Judul Lama');

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, self::isianSah([
            'judul'          => str_repeat('J', 151),
            'teks'           => str_repeat('t', 2001),
            'footer'         => str_repeat('f', 501),
            'nomor_wa_tanya' => '12345',
            'pesan_tanya'    => str_repeat('p', 1001),
            'pesan_bagikan'  => str_repeat('b', 1001),
        ]));

        $this->assertIsianSalahDitampilkanUlang($hasil, ['judul', 'teks', 'footer', 'nomor_wa_tanya', 'pesan_tanya', 'pesan_bagikan']);
        $hasil->assertSee('Judul terlalu panjang. Persingkat menjadi paling banyak 150 huruf.');
        $hasil->assertSee('Teks pengantar terlalu panjang. Persingkat menjadi paling banyak 2.000 huruf.');
        $hasil->assertSee('Nomor WA belum benar.');
        $this->assertSame('Judul Lama', setting('HalamanDepan.judul'));
    }

    public function testTokenCsrfWajib(): void
    {
        $this->assertTokenCsrfWajib(self::ALAMAT, self::isianSah());
        $this->assertSame('', setting('HalamanDepan.judul'));
    }

    public function testUnggahSemuaGambar(): void
    {
        $this->siapkanBerkas('gambar_kepala', 'kepala.jpg', self::isiJpg());
        $this->siapkanBerkas('logo_persegi', 'persegi.png', self::isiPng());
        $this->siapkanBerkas('logo_persegi_panjang', 'panjang.webp', self::isiWebp());
        $this->siapkanBerkas('logo_djarum', 'djarum.png', self::isiPng());

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, self::isianSah());

        $this->assertBerhasilDialihkan($hasil, self::ALAMAT);
        $jalur = [];
        foreach (array_keys(HalamanDepanController::GAMBAR) as $kunci) {
            $jalur[$kunci] = (string) setting('HalamanDepan.' . $kunci);
            $this->assertMatchesRegularExpression('#^unggahan/halaman-depan/[a-f0-9]{32}\.(jpg|png|webp)$#', $jalur[$kunci]);
        }
        $this->assertStringEndsWith('.jpg', $jalur['gambar_kepala']);
        $this->assertStringEndsWith('.webp', $jalur['logo_persegi_panjang']);
        $this->assertCount(4, $this->isiFolderUnggahan());

        // Pratinjau tampil, dengan pilihan hapus.
        service('superglobals')->setFilesArray([]);
        $xpath = $this->xpathDari($this->bukaSebagaiAdmin(self::ALAMAT));
        foreach ($jalur as $kunci => $j) {
            $this->assertSame(1, $xpath->query('//*[@data-pratinjau="' . $kunci . '"]//img[@src="' . base_url($j) . '"]')->length, "Pratinjau {$kunci}.");
            $this->assertSame(1, $xpath->query('//input[@name="hapus_' . $kunci . '"]')->length);
        }
        $this->assertSame(0, $xpath->query('//*' . self::KELAS_TANPA_GAMBAR)->length);
    }

    public function testGantiDanHapusGambar(): void
    {
        $this->siapkanBerkas('gambar_kepala', 'kepala.png', self::isiPng());
        $this->siapkanBerkas('logo_djarum', 'djarum.png', self::isiPng());
        $this->kirimSebagaiAdmin(self::ALAMAT, self::isianSah());
        $kepalaLama = (string) setting('HalamanDepan.gambar_kepala');
        $djarumLama = (string) setting('HalamanDepan.logo_djarum');

        service('superglobals')->setFilesArray([]);
        $this->siapkanBerkas('gambar_kepala', 'kepala-baru.jpg', self::isiJpg());
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, self::isianSah(['hapus_logo_djarum' => '1', 'hapus_logo_persegi' => '1']));

        $this->assertBerhasilDialihkan($hasil, self::ALAMAT);
        $kepalaBaru = (string) setting('HalamanDepan.gambar_kepala');
        $this->assertNotSame($kepalaLama, $kepalaBaru);
        $this->assertSame('', setting('HalamanDepan.logo_djarum'));
        $this->assertSame('', setting('HalamanDepan.logo_persegi'));
        $this->assertSame([$kepalaBaru], $this->isiFolderUnggahan(), 'Berkas lama yang diganti dan dihapus sudah dibuang.');
        $this->assertNotContains($djarumLama, $this->isiFolderUnggahan());
    }

    public function testSimpanTanpaMemilihGambarTidakMengubahGambar(): void
    {
        $this->siapkanBerkas('logo_persegi', 'persegi.png', self::isiPng());
        $this->kirimSebagaiAdmin(self::ALAMAT, self::isianSah());
        $jalur = (string) setting('HalamanDepan.logo_persegi');

        service('superglobals')->setFilesArray([]);
        $this->kirimSebagaiAdmin(self::ALAMAT, self::isianSah(['judul' => 'Judul Baru']));

        $this->assertSame($jalur, setting('HalamanDepan.logo_persegi'));
        $this->assertSame([$jalur], $this->isiFolderUnggahan());
    }

    public function testUnggahanDitolakTidakMenyimpanApaPun(): void
    {
        setting('HalamanDepan.judul', 'Judul Lama');

        $this->siapkanBerkas('gambar_kepala', 'kepala.png', self::isiPng());
        $this->siapkanBerkas('logo_persegi', 'persegi.png', self::isiPdf());
        $this->siapkanBerkas('logo_djarum', 'djarum.png', '', UPLOAD_ERR_INI_SIZE);

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, self::isianSah());

        $this->assertIsianSalahDitampilkanUlang($hasil, ['logo_persegi', 'logo_djarum']);
        $hasil->assertSee('Logo persegi: Jenis gambar tidak bisa dipakai.');
        $hasil->assertSee('Logo Bakti Olahraga Djarum Foundation: Ukuran gambar terlalu besar.');
        $hasil->assertSee('Gambar kepala yang tadi dipilih belum tersimpan');
        $this->assertSame('Judul Lama', setting('HalamanDepan.judul'));
        $this->assertSame('', setting('HalamanDepan.gambar_kepala'));
        $this->assertSame([], $this->isiFolderUnggahan());
    }

    public function testGambarKosongDitolak(): void
    {
        $this->siapkanBerkas('logo_persegi_panjang', 'panjang.png', '');
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, self::isianSah());

        $this->assertIsianSalahDitampilkanUlang($hasil, ['logo_persegi_panjang']);
        $this->assertSame([], $this->isiFolderUnggahan());
    }
}
