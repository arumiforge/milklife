<?php

use App\Controllers\Admin\Pengaturan\PembayaranController;
use Config\Pembayaran;
use Tests\Support\KirimFormulir;
use Tests\Support\UjiBasisData;
use Tests\Support\UnggahanSementara;

/**
 * Halaman pengaturan pembayaran (F2-05): rekening, nomor WA, logo bank, dan
 * templat pesan konfirmasi.
 *
 * @internal
 */
final class PengaturanPembayaranTest extends UjiBasisData
{
    use KirimFormulir;
    use UnggahanSementara;

    private const ALAMAT = 'admin/pengaturan/pembayaran';

    /**
     * Isian lengkap yang sah, dengan nama karangan.
     *
     * @return array<string, string>
     */
    private static function isianSah(array $ganti = []): array
    {
        return $ganti + [
            'bank'             => 'Bank Karangan',
            'nomor_rekening'   => '0123 4567-89.01',
            'pemilik_rekening' => 'Panitia Lomba Karangan',
            'nomor_wa'         => '0812-3456-7890',
            'templat_pesan'    => "Halo, konfirmasi {nama_sekolah} ({npsn}).\r\nTotal {total}.",
        ];
    }

    public function testTertutupUntukYangBelumMasuk(): void
    {
        $this->assertBelumMasukDialihkan(self::ALAMAT);
        $this->assertBelumMasukDialihkan(self::ALAMAT, 'post');
    }

    public function testFormulirTerbukaDenganNilaiAwal(): void
    {
        $hasil = $this->bukaSebagaiAdmin(self::ALAMAT);
        $hasil->assertOK();
        $hasil->assertSee('Rekening tujuan transfer');

        $xpath = $this->xpathDari($hasil);
        $this->assertSame(1, $xpath->query('//nav[@aria-label="Navigasi pengaturan"]')->length);
        $this->assertSame('multipart/form-data', $xpath->query('//form[@action="' . site_url(self::ALAMAT) . '"]')->item(0)?->getAttribute('enctype'));
        $this->assertSame('', $xpath->query('//input[@name="bank"]')->item(0)?->getAttribute('value'));
        $this->assertSame((new Pembayaran())->templat_pesan, $xpath->query('//textarea[@name="templat_pesan"]')->item(0)?->textContent);

        // Semua penanda tercantum, beserta contoh hasil dengan data karangan.
        foreach (array_keys(Pembayaran::PENANDA) as $penanda) {
            $this->assertSame(1, $xpath->query('//*[@data-penanda="' . $penanda . '"]')->length, "Penanda {$penanda} tidak tercantum.");
        }

        $contoh = (string) $xpath->query('//*[@id="contoh-pesan"]')->item(0)?->textContent;
        $this->assertStringContainsString('Nama sekolah: SD Negeri Contoh 1', $contoh);
        $this->assertStringContainsString('Total: Rp 500.000', $contoh);
        $this->assertStringNotContainsString('{', $contoh);

        // Belum ada logo: kotak tanpa gambar, tanpa pilihan hapus.
        $this->assertSame(1, $xpath->query('//*[@data-pratinjau="logo_bank"]//*[contains(concat(" ", @class, " "), " tanpa-gambar ")]')->length);
        $this->assertSame(0, $xpath->query('//input[@name="hapus_logo_bank"]')->length);
        $this->assertSame((string) \App\Libraries\Unggahan::BATAS_GAMBAR, $xpath->query('//input[@name="logo_bank"]')->item(0)?->getAttribute('data-ukuran-maksimal'));
    }

    public function testFormulirMenampilkanNilaiTersimpan(): void
    {
        setting('Pembayaran.bank', 'Bank Karangan');
        setting('Pembayaran.nomor_rekening', '0123456789');
        setting('Pembayaran.nomor_wa', '6281234567890');
        setting('Pembayaran.templat_pesan', 'Sekolah {nama_sekolah}, ID {id_pendaftaran}');

        $hasil = $this->bukaSebagaiAdmin(self::ALAMAT);
        $xpath = $this->xpathDari($hasil);

        $this->assertSame('Bank Karangan', $xpath->query('//input[@name="bank"]')->item(0)?->getAttribute('value'));
        $this->assertSame('0123456789', $xpath->query('//input[@name="nomor_rekening"]')->item(0)?->getAttribute('value'));
        $this->assertSame('0812-3456-7890', $xpath->query('//input[@name="nomor_wa"]')->item(0)?->getAttribute('value'));
        $this->assertSame('Sekolah SD Negeri Contoh 1, ID MAC2A0000126', $xpath->query('//*[@id="contoh-pesan"]')->item(0)?->textContent);
        $this->assertSame(1, $xpath->query('//a[starts-with(@href, "https://wa.me/6281234567890?text=")]')->length);
    }

    public function testSimpanBerhasil(): void
    {
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, self::isianSah());

        $this->assertBerhasilDialihkan($hasil, self::ALAMAT, 'Pengaturan pembayaran tersimpan.');
        $this->assertSame('Bank Karangan', setting('Pembayaran.bank'));
        $this->assertSame('012345678901', setting('Pembayaran.nomor_rekening'), 'Nomor rekening disimpan hanya angka, nol di depan tetap ada.');
        $this->assertSame('Panitia Lomba Karangan', setting('Pembayaran.pemilik_rekening'));
        $this->assertSame('6281234567890', setting('Pembayaran.nomor_wa'));
        $this->assertSame("Halo, konfirmasi {nama_sekolah} ({npsn}).\nTotal {total}.", setting('Pembayaran.templat_pesan'));
        $this->assertSame('', setting('Pembayaran.logo_bank'));
    }

    public function testRekeningBolehKosong(): void
    {
        setting('Pembayaran.bank', 'Bank Lama');

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, self::isianSah(['bank' => '', 'nomor_rekening' => '', 'pemilik_rekening' => '', 'nomor_wa' => '']));

        $this->assertBerhasilDialihkan($hasil, self::ALAMAT);
        $this->assertSame('', setting('Pembayaran.bank'));
        $this->assertSame('', setting('Pembayaran.nomor_rekening'));
        $this->assertSame('', setting('Pembayaran.nomor_wa'));
    }

    public function testIsianSalahDitampilkanUlang(): void
    {
        setting('Pembayaran.bank', 'Bank Lama');

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, self::isianSah([
            'bank'           => 'Bank <Baru>',
            'nomor_rekening' => '0123-ABC',
            'nomor_wa'       => '021-555-1234',
            'templat_pesan'  => '',
        ]));

        $this->assertIsianSalahDitampilkanUlang($hasil, ['nomor_rekening', 'nomor_wa', 'templat_pesan']);
        $hasil->assertSee('Nomor rekening hanya boleh berisi angka');
        $hasil->assertSee('Nomor WA belum benar.');
        $hasil->assertSee('Isi pesan konfirmasi belum diisi.');

        // Isian yang diketik tetap ada (di-escape), nilai tersimpan tidak berubah.
        $xpath = $this->xpathDari($hasil);
        $this->assertSame('Bank <Baru>', $xpath->query('//input[@name="bank"]')->item(0)?->getAttribute('value'));
        $this->assertSame('0123-ABC', $xpath->query('//input[@name="nomor_rekening"]')->item(0)?->getAttribute('value'));
        $this->assertSame('Bank Lama', setting('Pembayaran.bank'));
    }

    public function testPenandaTidakDikenalDitolak(): void
    {
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, self::isianSah(['templat_pesan' => 'Halo {nama_sekolah}, total {jumlah} {jumlah} {Total}']));

        $this->assertIsianSalahDitampilkanUlang($hasil, ['templat_pesan']);
        $hasil->assertSee('Penanda {jumlah}, {Total} tidak dikenal.');
        $this->assertSame((new Pembayaran())->templat_pesan, setting('Pembayaran.templat_pesan'));
    }

    public function testPanjangIsianDibatasi(): void
    {
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, self::isianSah([
            'bank'             => str_repeat('B', 51),
            'pemilik_rekening' => str_repeat('P', 101),
            'templat_pesan'    => str_repeat('x', 2001),
            'nomor_rekening'   => str_repeat('1', 31),
        ]));

        $this->assertIsianSalahDitampilkanUlang($hasil, ['bank', 'pemilik_rekening', 'templat_pesan', 'nomor_rekening']);
    }

    public function testTokenCsrfWajib(): void
    {
        $this->assertTokenCsrfWajib(self::ALAMAT, self::isianSah());
        $this->assertSame('', setting('Pembayaran.bank'));
    }

    public function testUnggahLogoBank(): void
    {
        $this->siapkanBerkas('logo_bank', 'logo.png', self::isiPng());
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, self::isianSah());

        $this->assertBerhasilDialihkan($hasil, self::ALAMAT);
        $jalur = (string) setting('Pembayaran.logo_bank');
        $this->assertMatchesRegularExpression('#^unggahan/pembayaran/[a-f0-9]{32}\.png$#', $jalur);
        $this->assertSame([$jalur], $this->isiFolderUnggahan());

        // Pratinjau tampil, dengan pilihan hapus.
        service('superglobals')->setFilesArray([]);
        $xpath = $this->xpathDari($this->bukaSebagaiAdmin(self::ALAMAT));
        $this->assertSame(1, $xpath->query('//*[@data-pratinjau="logo_bank"]//img[@src="' . base_url($jalur) . '"]')->length);
        $this->assertSame(0, $xpath->query('//*[@data-pratinjau="logo_bank"]//*[contains(concat(" ", @class, " "), " tanpa-gambar ")]')->length);
        $this->assertSame(1, $xpath->query('//input[@name="hapus_logo_bank"]')->length);
    }

    public function testGantiLogoMenghapusBerkasLama(): void
    {
        $this->siapkanBerkas('logo_bank', 'logo.png', self::isiPng());
        $this->kirimSebagaiAdmin(self::ALAMAT, self::isianSah());
        $lama = (string) setting('Pembayaran.logo_bank');

        $this->siapkanBerkas('logo_bank', 'logo-baru.jpg', self::isiJpg());
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, self::isianSah(['hapus_logo_bank' => '1']));

        $this->assertBerhasilDialihkan($hasil, self::ALAMAT);
        $baru = (string) setting('Pembayaran.logo_bank');
        $this->assertNotSame($lama, $baru);
        $this->assertStringEndsWith('.jpg', $baru, 'Logo baru mengalahkan centang hapus.');
        $this->assertSame([$baru], $this->isiFolderUnggahan());
    }

    public function testHapusLogo(): void
    {
        $this->siapkanBerkas('logo_bank', 'logo.webp', self::isiWebp());
        $this->kirimSebagaiAdmin(self::ALAMAT, self::isianSah());
        $this->assertCount(1, $this->isiFolderUnggahan());

        service('superglobals')->setFilesArray([]);
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, self::isianSah(['hapus_logo_bank' => '1']));

        $this->assertBerhasilDialihkan($hasil, self::ALAMAT);
        $this->assertSame('', setting('Pembayaran.logo_bank'));
        $this->assertSame([], $this->isiFolderUnggahan());
    }

    public function testSimpanTanpaMemilihLogoTidakMengubahLogo(): void
    {
        $this->siapkanBerkas('logo_bank', 'logo.png', self::isiPng());
        $this->kirimSebagaiAdmin(self::ALAMAT, self::isianSah());
        $jalur = (string) setting('Pembayaran.logo_bank');

        service('superglobals')->setFilesArray([]);
        $this->kirimSebagaiAdmin(self::ALAMAT, self::isianSah(['bank' => 'Bank Lain']));

        $this->assertSame($jalur, setting('Pembayaran.logo_bank'));
        $this->assertSame([$jalur], $this->isiFolderUnggahan());
    }

    public function testBukanGambarDitolak(): void
    {
        $this->siapkanBerkas('logo_bank', 'logo.png', '<?php echo 1;');
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, self::isianSah());

        $this->assertIsianSalahDitampilkanUlang($hasil, ['logo_bank']);
        $hasil->assertSee('Jenis gambar tidak bisa dipakai.');
        $this->assertSame('', setting('Pembayaran.logo_bank'));
        $this->assertSame('', setting('Pembayaran.bank'), 'Bila logo ditolak, isian lain juga tidak disimpan.');
        $this->assertSame([], $this->isiFolderUnggahan());
    }

    public function testGambarKebesaranDitolak(): void
    {
        $this->siapkanBerkas('logo_bank', 'logo.png', '', UPLOAD_ERR_INI_SIZE);
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, self::isianSah());

        $this->assertIsianSalahDitampilkanUlang($hasil, ['logo_bank']);
        $hasil->assertSee('Ukuran gambar terlalu besar.');
        $this->assertSame([], $this->isiFolderUnggahan());
    }

    public function testLogoSahTidakTersimpanBilaIsianLainSalah(): void
    {
        $this->siapkanBerkas('logo_bank', 'logo.png', self::isiPng());
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, self::isianSah(['nomor_wa' => 'bukan nomor']));

        $this->assertIsianSalahDitampilkanUlang($hasil, ['nomor_wa']);
        $hasil->assertSee('Logo bank yang tadi dipilih belum tersimpan');
        $this->assertSame('', setting('Pembayaran.logo_bank'));
        $this->assertSame([], $this->isiFolderUnggahan());
    }

    public function testContohPesanDanPenandaTidakDikenal(): void
    {
        $this->assertSame(
            'SD Negeri Contoh 1 / 12345678 / MAC2A0000126 / Rp 300.000 + Rp 200.000 = Rp 500.000',
            PembayaranController::contohPesan('{nama_sekolah} / {npsn} / {id_pendaftaran} / {subtotal_individu} + {subtotal_beregu} = {total}'),
        );
        $this->assertSame([], PembayaranController::penandaTidakDikenal((new Pembayaran())->templat_pesan));
        $this->assertSame(['{nama}', '{ total}'], PembayaranController::penandaTidakDikenal('{nama} {nama} {npsn} { total} {}'));
    }
}
