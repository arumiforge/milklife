<?php

use App\Commands\DataAwal;
use CodeIgniter\Exceptions\PageNotFoundException;
use Tests\Support\KirimFormulir;
use Tests\Support\UjiBasisData;

/**
 * Halaman pengaturan kelompok umur `admin/pengaturan/kelompok-umur` (F2-03).
 *
 * @internal
 */
final class PengaturanKelompokUmurTest extends UjiBasisData
{
    use KirimFormulir;

    private const ALAMAT = 'admin/pengaturan/kelompok-umur';

    // ---------------------------------------------------------------
    // Akses
    // ---------------------------------------------------------------

    public function testTertutupUntukYangBelumMasuk(): void
    {
        $id = $this->buatKu();

        foreach ([self::ALAMAT, self::ALAMAT . '/tambah', self::ALAMAT . "/{$id}/ubah"] as $alamat) {
            $this->assertBelumMasukDialihkan($alamat);
        }

        foreach ([self::ALAMAT, self::ALAMAT . "/{$id}", self::ALAMAT . "/{$id}/hapus"] as $alamat) {
            $this->assertBelumMasukDialihkan($alamat, 'post');
        }

        $this->seeNumRecords(1, 'kelompok_umur', []);
    }

    public function testTokenCsrfWajib(): void
    {
        $id = $this->buatKu();

        $this->assertTokenCsrfWajib(self::ALAMAT, $this->isian(['nama' => 'KU 12']));
        $this->assertTokenCsrfWajib(self::ALAMAT . "/{$id}", $this->isian(['nama' => 'KU Diubah']));
        $this->assertTokenCsrfWajib(self::ALAMAT . "/{$id}/hapus");

        $this->seeNumRecords(1, 'kelompok_umur', []);
        $this->seeInDatabase('kelompok_umur', ['id' => $id, 'nama' => 'KU 10']);
    }

    public function testKelompokUmurYangTidakAdaTidakDitemukan(): void
    {
        foreach ([['get', self::ALAMAT . '/999/ubah'], ['post', self::ALAMAT . '/999'], ['post', self::ALAMAT . '/999/hapus']] as [$metode, $alamat]) {
            try {
                $metode === 'get' ? $this->bukaSebagaiAdmin($alamat) : $this->kirimSebagaiAdmin($alamat, $this->isian(['nama' => 'KU 99']));
                $this->fail("{$alamat} seharusnya tidak ditemukan.");
            } catch (PageNotFoundException $e) {
                $this->assertSame('Kelompok umur ini tidak ditemukan. Mungkin sudah dihapus.', $e->getMessage());
            }
        }

        $this->seeNumRecords(0, 'kelompok_umur', []);
    }

    // ---------------------------------------------------------------
    // Daftar
    // ---------------------------------------------------------------

    public function testKeadaanKosong(): void
    {
        $hasil = $this->bukaSebagaiAdmin(self::ALAMAT);

        $hasil->assertOK();
        $hasil->assertSee('Belum ada kelompok umur');
        $hasil->assertSee('Tambahkan kelompok umur pertama');

        $xpath = $this->xpathDari($hasil);
        $this->assertSame(1, $xpath->query('//nav[@aria-label="Navigasi pengaturan"]//a[@aria-current="page" and contains(., "Kelompok Umur")]')->length);
        $this->assertGreaterThan(0, $xpath->query('//a[@href="' . site_url(self::ALAMAT . '/tambah') . '"]')->length);
        $this->assertSame(0, $xpath->query('//table')->length);
    }

    public function testDaftarDariDataAwal(): void
    {
        DataAwal::isi($this->db);

        $hasil = $this->bukaSebagaiAdmin(self::ALAMAT);
        $hasil->assertOK();

        $baris = $this->barisTabel($hasil);
        $this->assertSame(['KU 8 (Seneng Playon)', 'KU 10', 'KU 12', 'KU 15', 'KU 18'], array_column($baris, 0));
        $this->assertSame(['Lahir 2019 ke atas', 'Lahir 2016–2018', 'Lahir 2015', 'Lahir 2011–2014', 'Lahir 2008–2010'], array_column($baris, 1));
        $this->assertSame(['Belum dipilih'], array_values(array_unique(array_column($baris, 2))));
        $this->assertSame(['Belum diisi'], array_values(array_unique(array_column($baris, 3))));
        $this->assertSame(['1', '2', '3', '4', '5'], array_column($baris, 4));
        $this->assertSame(['Aktif'], array_values(array_unique(array_column($baris, 5))));
    }

    public function testDaftarMenampilkanJenjangBiayaAwalanDanStatus(): void
    {
        $this->buatKu(['nama' => 'KU 10', 'tahun_lahir_awal' => '2016', 'tahun_lahir_akhir' => '2018', 'jenjang' => ['A', 'B'], 'biaya_individu' => '50000', 'awalan' => '2, 6']);
        $this->buatKu(['nama' => 'KU Lama', 'aktif' => '']);

        $baris = $this->barisTabel($this->bukaSebagaiAdmin(self::ALAMAT));

        $this->assertSame(['KU 10', 'Lahir 2016–2018', 'SD/MI SMP/MTs', 'Rp50.000', '2, 6', 'Aktif'], array_slice($baris[0], 0, 6));
        $this->assertSame(['KU Lama', 'Belum diisi', 'Belum dipilih', 'Belum diisi', 'Belum diisi', 'Mati'], array_slice($baris[1], 0, 6));
    }

    public function testTombolHapusBerkonfirmasiHanyaBilaBolehDihapus(): void
    {
        DataAwal::isi($this->db);
        $bebas = $this->buatKu(['nama' => 'KU Bebas']);

        $hasil = $this->bukaSebagaiAdmin(self::ALAMAT);
        $xpath = $this->xpathDari($hasil);

        $formBebas = $xpath->query('//form[@action="' . site_url(self::ALAMAT . "/{$bebas}/hapus") . '"]')->item(0);
        $this->assertNotNull($formBebas);
        $this->assertSame('post', $formBebas->getAttribute('method'));
        $this->assertSame('Kelompok umur KU Bebas akan dihapus beserta jenjang dan awalan nomor dadanya. Tindakan ini tidak bisa dibatalkan.', $formBebas->getAttribute('data-konfirmasi'));
        $this->assertSame(1, $xpath->query('.//input[@name="' . config('Security')->tokenName . '"]', $formBebas)->length);

        // Kelompok umur data awal punya nomor lomba: tanpa jendela konfirmasi, server menjelaskan sebabnya.
        $this->assertSame(1, $xpath->query('//form[@data-konfirmasi]')->length);
        $this->assertSame(6, $xpath->query('//form[contains(@action, "/hapus")]')->length);
        $hasil->assertSee(aset('js/konfirmasi.js'));
    }

    // ---------------------------------------------------------------
    // Tambah
    // ---------------------------------------------------------------

    public function testFormulirTambah(): void
    {
        DataAwal::isi($this->db);

        $hasil = $this->bukaSebagaiAdmin(self::ALAMAT . '/tambah');
        $hasil->assertOK();
        $hasil->assertSee('Tambah Kelompok Umur');

        $xpath = $this->xpathDari($hasil);
        $this->assertSame(site_url(self::ALAMAT), $xpath->query('//form[@id="formulir-kelompok-umur"]')->item(0)?->getAttribute('action'));
        $this->assertSame('6', $xpath->query('//input[@name="urutan"]')->item(0)?->getAttribute('value'));
        $this->assertSame('checked', $xpath->query('//input[@name="aktif"]')->item(0)?->getAttribute('checked'));
        $this->assertSame(['A', 'B', 'C'], $this->nilaiSemua($xpath, '//input[@name="jenjang[]"]/@value'));
        $this->assertSame(0, $xpath->query('//input[@name="jenjang[]" and @checked]')->length);
        $this->assertSame(['SD/MI', 'SMP/MTs', 'SMA/SMK/MA'], $this->nilaiSemua($xpath, '//input[@name="jenjang[]"]/following-sibling::label'));
        $this->assertTeksTampil($hasil, 'Sudah dipakai kelompok umur lain: 1 untuk KU 8 (Seneng Playon); 2 untuk KU 10; 3 untuk KU 12; 4 untuk KU 15; 5 untuk KU 18.');
    }

    public function testTambahBerhasil(): void
    {
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, $this->isian([
            'nama'              => 'KU 10',
            'tahun_lahir_awal'  => '2016',
            'tahun_lahir_akhir' => '2018',
            'jenjang'           => ['A', 'B'],
            'biaya_individu'    => 'Rp50.000',
            'awalan'            => '2, 6',
            'urutan'            => '2',
            'aktif'             => '1',
        ]));

        $this->assertBerhasilDialihkan($hasil, self::ALAMAT, 'Kelompok umur KU 10 tersimpan.');

        $ku = $this->db->table('kelompok_umur')->get()->getRowArray();
        $this->assertSame(['KU 10', '2016', '2018', '50000', '2', '1'], [$ku['nama'], $ku['tahun_lahir_awal'], $ku['tahun_lahir_akhir'], $ku['biaya_individu'], $ku['urutan'], $ku['aktif']]);
        $this->seeNumRecords(2, 'kelompok_umur_jenjang', ['kelompok_umur_id' => $ku['id']]);
        $this->seeInDatabase('awalan_nomor_dada', ['kelompok_umur_id' => $ku['id'], 'awalan' => '2', 'urutan' => 1, 'nomor_terakhir' => 0]);
        $this->seeInDatabase('awalan_nomor_dada', ['kelompok_umur_id' => $ku['id'], 'awalan' => '6', 'urutan' => 2, 'nomor_terakhir' => 0]);
    }

    public function testTambahTanpaBatasDanBiayaBoleh(): void
    {
        // Admin bisa mengisi bertahap; kelengkapan diperiksa dasbor (F2-09).
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, ['nama' => 'KU 10']);

        $this->assertBerhasilDialihkan($hasil, self::ALAMAT, 'Kelompok umur KU 10 tersimpan.');
        $this->seeInDatabase('kelompok_umur', ['nama' => 'KU 10', 'tahun_lahir_awal' => null, 'tahun_lahir_akhir' => null, 'biaya_individu' => null, 'urutan' => 1, 'aktif' => 0]);
    }

    public function testIsianSalahDitampilkanUlang(): void
    {
        DataAwal::isi($this->db);

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, $this->isian([
            'nama'              => '',
            'tahun_lahir_awal'  => '20l6',
            'tahun_lahir_akhir' => '2018',
            'jenjang'           => ['A', 'X'],
            'biaya_individu'    => '50.000,00',
            'awalan'            => '2',
            'urutan'            => 'pertama',
        ]));

        $this->assertIsianSalahDitampilkanUlang($hasil, ['nama', 'tahun_lahir_awal', 'jenjang', 'biaya_individu', 'awalan', 'urutan']);
        $hasil->assertSee('Ada 6 isian yang perlu diperbaiki:');
        $hasil->assertSee('Nama kelompok umur belum diisi. Tulis namanya, misalnya KU 10.');
        $hasil->assertSee('Tahun lahir paling awal belum benar.');
        $hasil->assertSee('Pilihan jenjang tidak dikenal.');
        $hasil->assertSee('Biaya individu belum benar. Tulis angka rupiah tanpa koma, misalnya 50000');
        $hasil->assertSee('Awalan 2 sudah dipakai KU 10.');
        $hasil->assertSee('Urutan belum benar.');

        // Isian yang diketik tampil lagi.
        $xpath = $this->xpathDari($hasil);
        $this->assertSame('20l6', $xpath->query('//input[@name="tahun_lahir_awal"]')->item(0)?->getAttribute('value'));
        $this->assertSame('50.000,00', $xpath->query('//input[@name="biaya_individu"]')->item(0)?->getAttribute('value'));
        $this->assertSame(['A'], $this->nilaiSemua($xpath, '//input[@name="jenjang[]" and @checked]/@value'));
        $this->assertSame('jenjang-galat', $xpath->query('//*[@id="jenjang-galat"]')->item(0)?->getAttribute('id'));

        $this->seeNumRecords(5, 'kelompok_umur', []);
    }

    public function testRentangBertabrakanDitolak(): void
    {
        DataAwal::isi($this->db);

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, $this->isian(['nama' => 'KU 13', 'tahun_lahir_awal' => '2014', 'tahun_lahir_akhir' => '2015', 'aktif' => '1']));

        $this->assertIsianSalahDitampilkanUlang($hasil, ['tahun_lahir_awal', 'tahun_lahir_akhir']);
        $hasil->assertSee('Tahun lahir ini bertabrakan dengan KU 12 (lahir 2015). Satu tahun lahir hanya boleh masuk satu kelompok umur aktif.');
        $this->seeNumRecords(0, 'kelompok_umur', ['nama' => 'KU 13']);

        // Kelompok umur yang mati boleh disimpan dengan rentang yang sama.
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, $this->isian(['nama' => 'KU 13', 'tahun_lahir_awal' => '2014', 'tahun_lahir_akhir' => '2015', 'aktif' => '']));
        $this->assertBerhasilDialihkan($hasil, self::ALAMAT);
    }

    // ---------------------------------------------------------------
    // Ubah
    // ---------------------------------------------------------------

    public function testFormulirUbahTerisi(): void
    {
        $id = $this->buatKu(['nama' => 'KU 10', 'tahun_lahir_awal' => '2016', 'tahun_lahir_akhir' => '2018', 'jenjang' => ['B'], 'biaya_individu' => '50000', 'awalan' => '6, 2', 'urutan' => '3']);
        $this->db->table('awalan_nomor_dada')->where('awalan', '6')->update(['nomor_terakhir' => 45]);

        $hasil = $this->bukaSebagaiAdmin(self::ALAMAT . "/{$id}/ubah");
        $hasil->assertOK();
        $hasil->assertSee('Ubah KU 10');

        $xpath = $this->xpathDari($hasil);
        $this->assertSame(site_url(self::ALAMAT . "/{$id}"), $xpath->query('//form[@id="formulir-kelompok-umur"]')->item(0)?->getAttribute('action'));

        foreach (['nama' => 'KU 10', 'tahun_lahir_awal' => '2016', 'tahun_lahir_akhir' => '2018', 'biaya_individu' => '50000', 'awalan' => '6, 2', 'urutan' => '3'] as $kotak => $nilai) {
            $this->assertSame($nilai, $xpath->query('//input[@name="' . $kotak . '"]')->item(0)?->getAttribute('value'), $kotak);
        }

        $this->assertSame(['B'], $this->nilaiSemua($xpath, '//input[@name="jenjang[]" and @checked]/@value'));
        $this->assertTeksTampil($hasil, 'Awalan 6 sudah dipakai sampai nomor dada 6045, jadi tidak bisa dihapus.');
        $hasil->assertDontSee('Sudah dipakai kelompok umur lain');
    }

    public function testLengkapiKelompokUmurDataAwal(): void
    {
        DataAwal::isi($this->db);
        $id = (int) $this->db->table('kelompok_umur')->where('nama', 'KU 10')->get()->getRow()->id;

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . "/{$id}", $this->isian([
            'nama'              => 'KU 10',
            'tahun_lahir_awal'  => '2016',
            'tahun_lahir_akhir' => '2018',
            'jenjang'           => ['A'],
            'biaya_individu'    => '50000',
            'awalan'            => '2, 7',
            'urutan'            => '2',
            'aktif'             => '1',
        ]));

        $this->assertBerhasilDialihkan($hasil, self::ALAMAT, 'Kelompok umur KU 10 tersimpan.');
        $this->seeInDatabase('kelompok_umur', ['id' => $id, 'biaya_individu' => 50000, 'tahun_lahir_awal' => 2016, 'tahun_lahir_akhir' => 2018]);
        $this->seeInDatabase('kelompok_umur_jenjang', ['kelompok_umur_id' => $id, 'jenjang' => 'A']);
        $this->seeNumRecords(2, 'awalan_nomor_dada', ['kelompok_umur_id' => $id]);
        $this->seeNumRecords(11, 'nomor_lomba', ['kelompok_umur_id' => $id]);

        $baris = $this->barisTabel($this->bukaSebagaiAdmin(self::ALAMAT));
        $this->assertSame(['KU 10', 'Lahir 2016–2018', 'SD/MI', 'Rp50.000', '2, 7', 'Aktif'], array_slice($baris[1], 0, 6));
    }

    public function testUbahIsianSalahDitampilkanUlangTanpaMenyimpan(): void
    {
        $id = $this->buatKu(['nama' => 'KU 10', 'tahun_lahir_awal' => '2016', 'tahun_lahir_akhir' => '2018']);

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . "/{$id}", $this->isian(['nama' => 'KU 10 Baru', 'tahun_lahir_awal' => '2018', 'tahun_lahir_akhir' => '2016']));

        $this->assertIsianSalahDitampilkanUlang($hasil, ['tahun_lahir_awal']);
        $hasil->assertSee('Tahun lahir paling awal tidak boleh lebih besar dari tahun lahir paling akhir.');
        $this->seeInDatabase('kelompok_umur', ['id' => $id, 'nama' => 'KU 10', 'tahun_lahir_awal' => 2016]);
    }

    public function testBatasTahunLahirTidakBisaMengeluarkanAtletTerdaftar(): void
    {
        $id = $this->buatKu(['nama' => 'KU 10', 'tahun_lahir_awal' => '2015', 'tahun_lahir_akhir' => '2018']);
        $this->db->table('pendaftaran')->insert(['jenjang' => 'A', 'nama_sekolah' => 'SD KARANGAN', 'kabupaten' => 'Kudus', 'subtotal_individu' => 0, 'subtotal_beregu' => 0, 'total' => 0]);
        $pendaftaran = $this->db->insertID();

        foreach (['9900000001' => '2016-05-17', '9900000002' => '2017-01-02'] as $nisn => $lahir) {
            $this->db->table('atlet')->insert([
                'pendaftaran_id' => $pendaftaran, 'nama' => 'ATLET KARANGAN', 'nisn' => $nisn,
                'tanggal_lahir'  => $lahir, 'jenis_kelamin' => 'P', 'kelompok_umur_id' => $id,
            ]);
        }

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . "/{$id}", $this->isian(['nama' => 'KU 10', 'tahun_lahir_awal' => '2017', 'tahun_lahir_akhir' => '2016']));
        $this->assertIsianSalahDitampilkanUlang($hasil, ['tahun_lahir_awal']);

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . "/{$id}", $this->isian(['nama' => 'KU 10', 'tahun_lahir_awal' => '2017', 'tahun_lahir_akhir' => '2018']));
        $this->assertIsianSalahDitampilkanUlang($hasil, ['tahun_lahir_awal']);
        $hasil->assertSee('Sudah ada atlet terdaftar di kelompok umur ini yang lahir tahun 2016. Tulis 2016 atau lebih kecil, atau kosongkan.');

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . "/{$id}", $this->isian(['nama' => 'KU 10', 'tahun_lahir_awal' => '2015', 'tahun_lahir_akhir' => '2016']));
        $this->assertIsianSalahDitampilkanUlang($hasil, ['tahun_lahir_akhir']);
        $hasil->assertSee('Sudah ada atlet terdaftar di kelompok umur ini yang lahir tahun 2017. Tulis 2017 atau lebih besar, atau kosongkan.');
        $this->seeInDatabase('kelompok_umur', ['id' => $id, 'tahun_lahir_awal' => 2015, 'tahun_lahir_akhir' => 2018]);

        // Rentang yang tetap mencakup semua atletnya, atau batas kosong, diterima.
        $this->kirimSebagaiAdmin(self::ALAMAT . "/{$id}", $this->isian(['nama' => 'KU 10', 'tahun_lahir_awal' => '2016', 'tahun_lahir_akhir' => '2017']))->assertRedirect();
        $this->seeInDatabase('kelompok_umur', ['id' => $id, 'tahun_lahir_awal' => 2016, 'tahun_lahir_akhir' => 2017]);
        $this->kirimSebagaiAdmin(self::ALAMAT . "/{$id}", $this->isian(['nama' => 'KU 10', 'tahun_lahir_awal' => '', 'tahun_lahir_akhir' => '2017']))->assertRedirect();
        $this->seeInDatabase('kelompok_umur', ['id' => $id, 'tahun_lahir_awal' => null, 'tahun_lahir_akhir' => 2017]);
    }

    public function testBatasTahunLahirBebasDiubahBilaBelumAdaAtlet(): void
    {
        $id = $this->buatKu(['nama' => 'KU 10', 'tahun_lahir_awal' => '2016', 'tahun_lahir_akhir' => '2018']);

        $this->kirimSebagaiAdmin(self::ALAMAT . "/{$id}", $this->isian(['nama' => 'KU 10', 'tahun_lahir_awal' => '2019', 'tahun_lahir_akhir' => '2020']))->assertRedirect();
        $this->seeInDatabase('kelompok_umur', ['id' => $id, 'tahun_lahir_awal' => 2019, 'tahun_lahir_akhir' => 2020]);
    }

    public function testMengaktifkanYangBertabrakanDitolak(): void
    {
        DataAwal::isi($this->db);
        $id = $this->buatKu(['nama' => 'KU 10 Lama', 'tahun_lahir_awal' => '2017', 'tahun_lahir_akhir' => '2019', 'aktif' => '']);

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . "/{$id}", $this->isian(['nama' => 'KU 10 Lama', 'tahun_lahir_awal' => '2017', 'tahun_lahir_akhir' => '2019', 'aktif' => '1']));

        $this->assertIsianSalahDitampilkanUlang($hasil, ['tahun_lahir_awal']);
        $hasil->assertSee('bertabrakan dengan KU 8 (Seneng Playon) (lahir 2019 ke atas)');
        $this->seeInDatabase('kelompok_umur', ['id' => $id, 'aktif' => 0]);
    }

    public function testAwalanTerpakaiTidakBisaDihapusLewatFormulir(): void
    {
        $id = $this->buatKu(['nama' => 'KU 10', 'awalan' => '2, 6']);
        $this->db->table('awalan_nomor_dada')->where('awalan', '2')->update(['nomor_terakhir' => 999]);

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . "/{$id}", $this->isian(['nama' => 'KU 10', 'awalan' => '6']));

        $this->assertIsianSalahDitampilkanUlang($hasil, ['awalan']);
        $hasil->assertSee('Awalan 2 sudah dipakai untuk nomor dada, jadi tidak bisa dihapus.');
        $this->seeNumRecords(2, 'awalan_nomor_dada', ['kelompok_umur_id' => $id]);

        // Menambah awalan baru di belakang awalan yang penuh boleh.
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . "/{$id}", $this->isian(['nama' => 'KU 10', 'awalan' => '2, 6, 8']));
        $this->assertBerhasilDialihkan($hasil, self::ALAMAT);
        $this->seeInDatabase('awalan_nomor_dada', ['kelompok_umur_id' => $id, 'awalan' => '2', 'nomor_terakhir' => 999, 'urutan' => 1]);
        $this->seeInDatabase('awalan_nomor_dada', ['kelompok_umur_id' => $id, 'awalan' => '8', 'urutan' => 3]);
    }

    // ---------------------------------------------------------------
    // Hapus
    // ---------------------------------------------------------------

    public function testHapusBerhasilBesertaAwalanDanJenjang(): void
    {
        $id = $this->buatKu(['nama' => 'KU 10', 'jenjang' => ['A', 'C'], 'awalan' => '2, 6']);

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . "/{$id}/hapus");

        $this->assertBerhasilDialihkan($hasil, self::ALAMAT, 'Kelompok umur KU 10 sudah dihapus beserta jenjang dan awalan nomor dadanya.');
        $this->seeNumRecords(0, 'kelompok_umur', []);
        $this->seeNumRecords(0, 'kelompok_umur_jenjang', []);
        $this->seeNumRecords(0, 'awalan_nomor_dada', []);
    }

    public function testHapusDitolakBilaPunyaNomorLomba(): void
    {
        DataAwal::isi($this->db);
        $id = (int) $this->db->table('kelompok_umur')->where('nama', 'KU 8 (Seneng Playon)')->get()->getRow()->id;

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . "/{$id}/hapus");

        $this->assertGagalDialihkan($hasil, self::ALAMAT, 'KU 8 (Seneng Playon) masih punya 2 nomor lomba, jadi tidak bisa dihapus. Hapus dulu nomor lombanya di halaman Nomor Lomba, atau matikan kelompok umur ini supaya tidak muncul di pendaftaran.');
        $this->seeInDatabase('kelompok_umur', ['id' => $id]);
        $this->seeNumRecords(1, 'awalan_nomor_dada', ['kelompok_umur_id' => $id]);
    }

    public function testHapusDitolakBilaAwalanSudahDipakai(): void
    {
        $id = $this->buatKu(['nama' => 'KU 10', 'jenjang' => ['A'], 'awalan' => '2']);
        $this->db->table('awalan_nomor_dada')->update(['nomor_terakhir' => 1]);

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . "/{$id}/hapus");

        $this->assertGagalDialihkan($hasil, self::ALAMAT, 'Awalan nomor dada 2 milik KU 10 sudah dipakai, jadi kelompok umur ini tidak bisa dihapus. Matikan saja supaya tidak muncul di pendaftaran.');
        $this->seeNumRecords(1, 'kelompok_umur', []);
        $this->seeNumRecords(1, 'kelompok_umur_jenjang', []);
        $this->seeNumRecords(1, 'awalan_nomor_dada', []);
    }

    public function testHapusDitolakBilaSudahDipakaiAtlet(): void
    {
        $id = $this->buatKu(['nama' => 'KU 10']);
        $this->db->table('pendaftaran')->insert(['jenjang' => 'A', 'nama_sekolah' => 'SD KARANGAN', 'kabupaten' => 'Kudus', 'subtotal_individu' => 0, 'subtotal_beregu' => 0, 'total' => 0]);
        $this->db->table('atlet')->insert([
            'pendaftaran_id' => $this->db->insertID(), 'nama' => 'ATLET KARANGAN', 'nisn' => '9900000001',
            'tanggal_lahir'  => '2016-05-17', 'jenis_kelamin' => 'P', 'kelompok_umur_id' => $id,
        ]);

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . "/{$id}/hapus");

        $this->assertGagalDialihkan($hasil, self::ALAMAT, 'KU 10 sudah dipakai 1 atlet, jadi tidak bisa dihapus. Matikan saja supaya tidak muncul di pendaftaran.');
        $this->seeNumRecords(1, 'kelompok_umur', []);
    }

    // ---------------------------------------------------------------
    // Bantuan
    // ---------------------------------------------------------------

    /**
     * Isian formulir dengan nilai kosong, ditimpa `$isi`.
     *
     * @param array<string, mixed> $isi
     *
     * @return array<string, mixed>
     */
    private function isian(array $isi = []): array
    {
        return $isi + [
            'nama'              => '',
            'tahun_lahir_awal'  => '',
            'tahun_lahir_akhir' => '',
            'biaya_individu'    => '',
            'awalan'            => '',
            'urutan'            => '',
            'aktif'             => '1',
        ];
    }

    /**
     * Buat kelompok umur lewat formulir tambah.
     *
     * @param array<string, mixed> $isi
     */
    private function buatKu(array $isi = []): int
    {
        $this->kirimSebagaiAdmin(self::ALAMAT, $this->isian($isi + ['nama' => 'KU 10']))->assertRedirect();

        return (int) $this->db->table('kelompok_umur')->selectMax('id')->get()->getRow()->id;
    }

    /**
     * Isi teks tiap baris tabel daftar (sel dirapikan; ikon dibuang).
     *
     * @return list<list<string>>
     */
    private function barisTabel(CodeIgniter\Test\TestResponse $hasil): array
    {
        $xpath = $this->xpathDari($hasil);
        $baris = [];

        foreach ($xpath->query('//table[@id="tabel-kelompok-umur"]/tbody/tr') as $tr) {
            $sel = [];

            foreach ($xpath->query('./th|./td', $tr) as $td) {
                $sel[] = trim((string) preg_replace('/\s+/u', ' ', $td->textContent));
            }

            $baris[] = $sel;
        }

        return $baris;
    }

    /**
     * Teks tampil di halaman, dengan spasi dan baris baru dirapikan.
     */
    private function assertTeksTampil(CodeIgniter\Test\TestResponse $hasil, string $teks): void
    {
        $isi = $this->xpathDari($hasil)->query('//main')->item(0)?->textContent ?? '';

        $this->assertStringContainsString($teks, trim((string) preg_replace('/\s+/u', ' ', $isi)));
    }

    /**
     * @return list<string>
     */
    private function nilaiSemua(DOMXPath $xpath, string $kueri): array
    {
        $nilai = [];

        foreach ($xpath->query($kueri) as $simpul) {
            $nilai[] = trim($simpul->textContent);
        }

        return $nilai;
    }
}
