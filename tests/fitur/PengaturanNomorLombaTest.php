<?php

use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Test\StreamFilterTrait;
use CodeIgniter\Test\TestResponse;
use Tests\Support\BarisUjiNomorLomba;
use Tests\Support\KirimFormulir;
use Tests\Support\UjiBasisData;

/**
 * Halaman pengaturan nomor lomba (F2-04): `admin/pengaturan/nomor-lomba`.
 *
 * @internal
 */
final class PengaturanNomorLombaTest extends UjiBasisData
{
    use BarisUjiNomorLomba;
    use KirimFormulir;
    use StreamFilterTrait;

    private const ALAMAT = 'admin/pengaturan/nomor-lomba';

    // ---------------------------------------------------------------
    // Pembantu
    // ---------------------------------------------------------------

    /**
     * Isi basis data seperti saat pemasangan (`php spark data:awal`).
     */
    private function jalankanDataAwal(): void
    {
        command('data:awal');
        $this->resetStreamFilterBuffer();
    }

    /**
     * Data awal kini mengisi aturan tim (K73); kosongkan lagi batas anggota
     * dan jumlah putra/putri supaya tampilan "belum lengkap" tetap teruji.
     */
    private function kosongkanAturanTim(): void
    {
        $this->db->query("UPDATE nomor_lomba SET batas_anggota = NULL, jumlah_putra = NULL, jumlah_putri = NULL WHERE jenis = 'beregu'");
    }

    private function idNomor(string $nama, string $kategori, string $ku): int
    {
        $baris = $this->db->table('nomor_lomba')
            ->select('nomor_lomba.id')
            ->join('kelompok_umur', 'kelompok_umur.id = nomor_lomba.kelompok_umur_id')
            ->where(['nomor_lomba.nama' => $nama, 'nomor_lomba.kategori' => $kategori, 'kelompok_umur.nama' => $ku])
            ->get()->getRowArray();

        $this->assertNotNull($baris, "Nomor {$nama} {$kategori} {$ku} tidak ada.");

        return (int) $baris['id'];
    }

    /**
     * @return array<string, mixed>
     */
    private function baris(int $id): array
    {
        return $this->db->table('nomor_lomba')->where('id', $id)->get()->getRowArray() ?? [];
    }

    private function jumlahNomor(): int
    {
        return $this->db->table('nomor_lomba')->countAllResults();
    }

    /**
     * @param array<string, string> $ubah
     *
     * @return array<string, string>
     */
    private static function isianBeregu(int $ku, array $ubah = []): array
    {
        return $ubah + [
            'kelompok_umur_id' => (string) $ku,
            'nama'             => 'Estafet 4 × 100 m',
            'jenis'            => 'beregu',
            'kategori'         => 'mix',
            'jumlah_inti'      => '4',
            'batas_anggota'    => '6',
            'jumlah_putra'     => '2',
            'jumlah_putri'     => '2',
            'biaya_beregu'     => 'Rp 100.000',
            'urutan'           => '',
            'aktif'            => '1',
        ];
    }

    private function assertDialihkanKeKelompokUmur(TestResponse $hasil, int $ku, int $id, string $pesan): void
    {
        $this->assertTrue($hasil->isRedirect(), 'Simpan yang berhasil harus dialihkan.');
        $this->assertSame(site_url(self::ALAMAT) . '?kelompok-umur=' . $ku . '#nomor-' . $id, $hasil->getRedirectUrl());
        $hasil->assertSessionHas('pesan_sukses', $pesan);
    }

    // ---------------------------------------------------------------
    // Penjagaan
    // ---------------------------------------------------------------

    public function testTertutupUntukYangBelumMasuk(): void
    {
        $id = $this->buatNomorLomba($this->buatKelompokUmur('KU 10'));

        foreach ([self::ALAMAT, self::ALAMAT . '/tambah', self::ALAMAT . "/{$id}/ubah"] as $alamat) {
            $this->assertBelumMasukDialihkan($alamat);
        }

        foreach ([self::ALAMAT, self::ALAMAT . "/{$id}", self::ALAMAT . "/{$id}/hapus"] as $alamat) {
            $this->assertBelumMasukDialihkan($alamat, 'post');
        }

        $this->assertSame(1, $this->jumlahNomor());
    }

    public function testTokenCsrfWajib(): void
    {
        $ku = $this->buatKelompokUmur('KU 10');
        $id = $this->buatNomorLomba($ku);

        $this->assertTokenCsrfWajib(self::ALAMAT, self::isianBeregu($ku));
        $this->assertTokenCsrfWajib(self::ALAMAT . "/{$id}", ['nama' => 'Lari Ganti'] + self::isianBeregu($ku));
        $this->assertTokenCsrfWajib(self::ALAMAT . "/{$id}/hapus");

        $this->assertSame(1, $this->jumlahNomor());
        $this->assertSame('Lari 60 m', $this->baris($id)['nama']);
    }

    // ---------------------------------------------------------------
    // Daftar
    // ---------------------------------------------------------------

    public function testKosongTanpaKelompokUmur(): void
    {
        $hasil = $this->bukaSebagaiAdmin(self::ALAMAT);

        $hasil->assertOK();
        $hasil->assertSee('Belum ada kelompok umur');
        $xpath = $this->xpathDari($hasil);
        $this->assertSame(1, $xpath->query('//main//a[@href="' . site_url('admin/pengaturan/kelompok-umur') . '"][contains(., "Buka Kelompok Umur")]')->length);
        $this->assertSame(1, $xpath->query('//nav[@aria-label="Navigasi pengaturan"]//a[@aria-current="page"][contains(., "Nomor Lomba")]')->length);
    }

    public function testKosongTanpaNomorLomba(): void
    {
        $this->buatKelompokUmur('KU 10');

        $hasil = $this->bukaSebagaiAdmin(self::ALAMAT);

        $hasil->assertOK();
        $hasil->assertSee('Belum ada nomor lomba');
        $this->assertSame(1, $this->xpathDari($hasil)->query('//main//a[@href="' . site_url(self::ALAMAT . '/tambah') . '"]')->length);
    }

    public function testDaftarDataAwalDikelompokkanPerKelompokUmur(): void
    {
        $this->jalankanDataAwal();
        $this->kosongkanAturanTim();

        $hasil = $this->bukaSebagaiAdmin(self::ALAMAT);
        $hasil->assertOK();
        $xpath = $this->xpathDari($hasil);

        $this->assertSame(69, $xpath->query('//tr[starts-with(@id, "nomor-")]')->length);
        $this->assertSame(
            ['KU 8 (Seneng Playon)', 'KU 10', 'KU 12', 'KU 15', 'KU 18'],
            array_map(
                static fn (DOMNode $judul): string => trim((string) $judul->firstChild?->textContent),
                iterator_to_array($xpath->query('//section[contains(@class, "kelompok-nomor-lomba")]//h2')),
            ),
        );

        // Sebelas nomor beregu tanpa batas anggota.
        $this->assertSame(11, $xpath->query('//tr[@data-belum-lengkap="1"]')->length);
        $hasil->assertSee('11 nomor beregu belum lengkap.');
        $hasil->assertSee('Belum lengkap: batas anggota belum diisi.');
        $hasil->assertSee('Belum lengkap: batas anggota, jumlah putra dan putri minimal belum diisi.');

        // Isi baris beregu: jenis, kategori, inti, dan biaya dalam rupiah.
        $estafet = $xpath->query('//tr[@id="nomor-' . $this->idNomor('Estafet 8 × 50 m', 'mix', 'KU 10') . '"]')->item(0);
        $this->assertNotNull($estafet);
        $this->assertStringContainsString('Beregu', $estafet->textContent);
        $this->assertStringContainsString('Mix', $estafet->textContent);
        $this->assertStringContainsString('8 inti', $estafet->textContent);
        $this->assertStringContainsString('Rp 150.000', $estafet->textContent);
        $this->assertStringContainsString('Aktif', $estafet->textContent);

        // Semua nomor belum dipakai, jadi semua bisa dihapus lewat jendela konfirmasi.
        $this->assertSame(69, $xpath->query('//tr//form[@data-konfirmasi][@method="post"]')->length);
        $hasil->assertSee(aset('js/konfirmasi.js'));
    }

    public function testSaringPerKelompokUmurDanBelumLengkap(): void
    {
        $this->jalankanDataAwal();
        $this->kosongkanAturanTim();
        $ku10 = (int) $this->db->table('kelompok_umur')->where('nama', 'KU 10')->get()->getRow()->id;

        $hasil = $this->bukaSebagaiAdmin(self::ALAMAT . '?kelompok-umur=' . $ku10);
        $xpath = $this->xpathDari($hasil);
        $this->assertSame(11, $xpath->query('//tr[starts-with(@id, "nomor-")]')->length);
        $this->assertSame(1, $xpath->query('//section[contains(@class, "kelompok-nomor-lomba")]')->length);
        $this->assertSame('selected', $xpath->query('//select[@name="kelompok-umur"]/option[@value="' . $ku10 . '"]')->item(0)?->getAttribute('selected'));

        $hasil = $this->bukaSebagaiAdmin(self::ALAMAT . '?belum-lengkap=1');
        $xpath = $this->xpathDari($hasil);
        $this->assertSame(11, $xpath->query('//tr[starts-with(@id, "nomor-")]')->length);
        $this->assertSame(11, $xpath->query('//tr[@data-belum-lengkap="1"]')->length);
        $this->assertSame(4, $xpath->query('//section[contains(@class, "kelompok-nomor-lomba")]')->length, 'KU 8 tanpa nomor beregu tidak tampil.');

        $hasil = $this->bukaSebagaiAdmin(self::ALAMAT . '?kelompok-umur=' . $ku10 . '&belum-lengkap=1');
        $this->assertSame(5, $this->xpathDari($hasil)->query('//tr[starts-with(@id, "nomor-")]')->length);

        // Saringan kelompok umur yang tidak ada diabaikan.
        $hasil = $this->bukaSebagaiAdmin(self::ALAMAT . '?kelompok-umur=999');
        $this->assertSame(69, $this->xpathDari($hasil)->query('//tr[starts-with(@id, "nomor-")]')->length);
    }

    public function testSaringBelumLengkapSaatSemuaLengkap(): void
    {
        $this->buatNomorLomba($this->buatKelompokUmur('KU 10'));

        $hasil = $this->bukaSebagaiAdmin(self::ALAMAT . '?belum-lengkap=1');

        $hasil->assertOK();
        $hasil->assertSee('Semua nomor sudah lengkap');
        $hasil->assertDontSee('id="ringkasan-belum-lengkap"');
    }

    public function testNomorDipakaiTanpaTombolHapusDanKelompokUmurTidakAktifDitandai(): void
    {
        $ku     = $this->buatKelompokUmur('KU 10', ['aktif' => 0]);
        $dipakai = $this->buatNomorLomba($ku);
        $bebas  = $this->buatNomorLomba($ku, ['nama' => 'Frog Jump', 'aktif' => 0]);
        $this->pakaiOlehAtlet($dipakai, $ku);

        $hasil = $this->bukaSebagaiAdmin(self::ALAMAT);
        $xpath = $this->xpathDari($hasil);

        $this->assertSame(0, $xpath->query('//tr[@id="nomor-' . $dipakai . '"]//form')->length);
        $this->assertSame(1, $xpath->query('//tr[@id="nomor-' . $bebas . '"]//form[@data-konfirmasi]')->length);
        $this->assertStringContainsString('Tidak aktif', (string) $xpath->query('//tr[@id="nomor-' . $bebas . '"]')->item(0)?->textContent);
        $hasil->assertSee('Kelompok umur tidak aktif');
    }

    // ---------------------------------------------------------------
    // Tambah
    // ---------------------------------------------------------------

    public function testFormulirTambahMemilihKelompokUmurDariAlamat(): void
    {
        $this->buatKelompokUmur('KU 8');
        $ku10 = $this->buatKelompokUmur('KU 10');

        $hasil = $this->bukaSebagaiAdmin(self::ALAMAT . '/tambah?kelompok-umur=' . $ku10);
        $hasil->assertOK();
        $xpath = $this->xpathDari($hasil);

        $this->assertSame('selected', $xpath->query('//select[@name="kelompok_umur_id"]/option[@value="' . $ku10 . '"]')->item(0)?->getAttribute('selected'));
        $this->assertSame('checked', $xpath->query('//input[@name="jenis"][@value="individu"]')->item(0)?->getAttribute('checked'));
        $this->assertSame('disabled', $xpath->query('//input[@name="kategori"][@value="mix"]')->item(0)?->getAttribute('disabled'));
        $this->assertTrue($xpath->query('//fieldset[@id="bagian-tim"]')->item(0)?->hasAttribute('hidden'));
        $this->assertSame('checked', $xpath->query('//input[@name="aktif"][@type="checkbox"]')->item(0)?->getAttribute('checked'));
        $this->assertSame(0, $xpath->query('//*[@id="bagian-hapus"]')->length);
        $this->assertSame(site_url(self::ALAMAT), $xpath->query('//form[@id="formulir-nomor-lomba"]')->item(0)?->getAttribute('action'));
    }

    public function testTambahNomorIndividu(): void
    {
        $ku = $this->buatKelompokUmur('KU 10');
        $this->buatNomorLomba($ku, ['urutan' => 6]);

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, [
            'kelompok_umur_id' => (string) $ku,
            'nama'             => '  Frog   Jump ',
            'jenis'            => 'individu',
            'kategori'         => 'putri',
            // Isian tim ikut terkirim dari kotak yang tersembunyi: dibuang.
            'jumlah_inti'   => '4',
            'batas_anggota' => '6',
            'biaya_beregu'  => '100000',
            'urutan'        => '',
            'aktif'         => '1',
        ]);

        $id = $this->idNomor('Frog Jump', 'putri', 'KU 10');
        $this->assertDialihkanKeKelompokUmur($hasil, $ku, $id, 'Nomor Frog Jump putri di KU 10 tersimpan.');

        $baris = $this->baris($id);
        $this->assertSame('individu', $baris['jenis']);
        $this->assertSame('7', (string) $baris['urutan'], 'Urutan kosong: paling akhir di kelompok umurnya.');
        $this->assertSame('1', (string) $baris['aktif']);

        foreach (['jumlah_inti', 'batas_anggota', 'jumlah_putra', 'jumlah_putri', 'biaya_beregu'] as $kolom) {
            $this->assertNull($baris[$kolom], $kolom);
        }
    }

    public function testTambahNomorBereguMix(): void
    {
        $ku = $this->buatKelompokUmur('KU 18');

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, self::isianBeregu($ku, ['urutan' => '12', 'aktif' => '0']));

        $id = $this->idNomor('Estafet 4 × 100 m', 'mix', 'KU 18');
        $this->assertDialihkanKeKelompokUmur($hasil, $ku, $id, 'Nomor Estafet 4 × 100 m mix di KU 18 tersimpan.');
        $this->seeInDatabase('nomor_lomba', [
            'id'            => $id,
            'jenis'         => 'beregu',
            'jumlah_inti'   => 4,
            'batas_anggota' => 6,
            'jumlah_putra'  => 2,
            'jumlah_putri'  => 2,
            'biaya_beregu'  => 100000,
            'urutan'        => 12,
            'aktif'         => 0,
        ]);
    }

    public function testTambahNomorBereguPutraMembuangJumlahPutraPutri(): void
    {
        $ku = $this->buatKelompokUmur('KU 15');

        $this->kirimSebagaiAdmin(self::ALAMAT, self::isianBeregu($ku, ['nama' => 'Estafet 5 × 80 m', 'kategori' => 'putra', 'jumlah_inti' => '5', 'batas_anggota' => '5']));

        $baris = $this->baris($this->idNomor('Estafet 5 × 80 m', 'putra', 'KU 15'));
        $this->assertNull($baris['jumlah_putra']);
        $this->assertNull($baris['jumlah_putri']);
        $this->assertSame('5', (string) $baris['batas_anggota']);
    }

    public function testTambahNomorIndividuMixDitolak(): void
    {
        $ku = $this->buatKelompokUmur('KU 10');

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, [
            'kelompok_umur_id' => (string) $ku, 'nama' => 'Lari 60 m', 'jenis' => 'individu', 'kategori' => 'mix', 'aktif' => '1',
        ]);

        $this->assertIsianSalahDitampilkanUlang($hasil, ['kategori']);
        $hasil->assertSee('Nomor individu tidak bisa berkategori mix.');
        $this->assertSame(0, $this->jumlahNomor());
        $this->assertSame('Lari 60 m', $this->xpathDari($hasil)->query('//input[@name="nama"]')->item(0)?->getAttribute('value'));
    }

    public function testTambahNomorBereguTidakLengkapDitolak(): void
    {
        $ku = $this->buatKelompokUmur('KU 10');

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, self::isianBeregu($ku, [
            'batas_anggota' => '', 'jumlah_putra' => '5', 'jumlah_putri' => '', 'biaya_beregu' => '100.000,50',
        ]));

        $this->assertIsianSalahDitampilkanUlang($hasil, ['batas_anggota', 'jumlah_putri', 'biaya_beregu']);
        $hasil->assertSee('Ada 3 isian yang perlu diperbaiki:');
        $hasil->assertSee('Batas anggota belum diisi.');
        $this->assertSame(0, $this->jumlahNomor());

        // Bagian tim dan mix tetap tampil dengan isian yang diketik.
        $xpath = $this->xpathDari($hasil);
        $this->assertFalse($xpath->query('//fieldset[@id="bagian-tim"]')->item(0)?->hasAttribute('hidden'));
        $this->assertFalse($xpath->query('//*[@id="bagian-mix"]')->item(0)?->hasAttribute('hidden'));
        $this->assertSame('5', $xpath->query('//input[@name="jumlah_putra"]')->item(0)?->getAttribute('value'));
        $this->assertSame('100.000,50', $xpath->query('//input[@name="biaya_beregu"]')->item(0)?->getAttribute('value'));
    }

    public function testTambahIsianKosongDitolak(): void
    {
        $this->buatKelompokUmur('KU 10');

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, ['jenis' => 'beregu', 'kategori' => 'putra']);

        $this->assertIsianSalahDitampilkanUlang($hasil, ['kelompok_umur_id', 'nama', 'jumlah_inti', 'batas_anggota', 'biaya_beregu']);
        $hasil->assertSee('Kelompok umur belum dipilih.');
        $this->assertSame(0, $this->jumlahNomor());
    }

    public function testTambahKelompokUmurYangTidakAdaDitolak(): void
    {
        $this->buatKelompokUmur('KU 10');

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, ['kelompok_umur_id' => '999', 'nama' => 'Lari 60 m', 'jenis' => 'individu', 'kategori' => 'putra']);

        $this->assertIsianSalahDitampilkanUlang($hasil, ['kelompok_umur_id']);
        $this->assertSame(0, $this->jumlahNomor());
    }

    public function testTambahNomorKembarDitolak(): void
    {
        $ku = $this->buatKelompokUmur('KU 10');
        $this->buatNomorLomba($ku, ['nama' => 'Lari 60 m', 'kategori' => 'putra']);

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, [
            'kelompok_umur_id' => (string) $ku, 'nama' => 'lari 60 M', 'jenis' => 'individu', 'kategori' => 'putra',
        ]);

        $this->assertIsianSalahDitampilkanUlang($hasil, ['nama']);
        $hasil->assertSee('Nomor lari 60 M putra sudah ada di KU 10.');
        $this->assertSame(1, $this->jumlahNomor());

        // Kategori lain di kelompok umur yang sama boleh.
        $this->kirimSebagaiAdmin(self::ALAMAT, [
            'kelompok_umur_id' => (string) $ku, 'nama' => 'Lari 60 m', 'jenis' => 'individu', 'kategori' => 'putri',
        ]);
        $this->assertSame(2, $this->jumlahNomor());
    }

    // ---------------------------------------------------------------
    // Ubah
    // ---------------------------------------------------------------

    public function testFormulirUbahNomorDataAwalBelumLengkap(): void
    {
        $this->jalankanDataAwal();
        $this->kosongkanAturanTim();
        $id = $this->idNomor('Estafet 8 × 50 m', 'mix', 'KU 12');

        $hasil = $this->bukaSebagaiAdmin(self::ALAMAT . "/{$id}/ubah");
        $hasil->assertOK();
        $xpath = $this->xpathDari($hasil);

        $hasil->assertSee('Nomor beregu ini belum lengkap: batas anggota, jumlah putra dan putri minimal belum diisi.');
        $this->assertSame('Estafet 8 × 50 m', $xpath->query('//input[@name="nama"]')->item(0)?->getAttribute('value'));
        $this->assertSame('8', $xpath->query('//input[@name="jumlah_inti"]')->item(0)?->getAttribute('value'));
        $this->assertSame('150000', $xpath->query('//input[@name="biaya_beregu"]')->item(0)?->getAttribute('value'));
        $this->assertSame('', $xpath->query('//input[@name="batas_anggota"]')->item(0)?->getAttribute('value'));
        $this->assertSame('checked', $xpath->query('//input[@name="kategori"][@value="mix"]')->item(0)?->getAttribute('checked'));
        $this->assertFalse($xpath->query('//*[@id="bagian-mix"]')->item(0)?->hasAttribute('hidden'));
        $this->assertSame(site_url(self::ALAMAT . "/{$id}"), $xpath->query('//form[@id="formulir-nomor-lomba"]')->item(0)?->getAttribute('action'));
        $this->assertSame(1, $xpath->query('//*[@id="bagian-hapus"]//form[@data-konfirmasi][@action="' . site_url(self::ALAMAT . "/{$id}/hapus") . '"]')->length);
    }

    public function testUbahNomorYangTidakAda(): void
    {
        $this->expectException(PageNotFoundException::class);

        $this->bukaSebagaiAdmin(self::ALAMAT . '/999/ubah');
    }

    public function testPerbaruiNomorYangTidakAda(): void
    {
        $this->expectException(PageNotFoundException::class);

        $this->kirimSebagaiAdmin(self::ALAMAT . '/999', ['nama' => 'Lari']);
    }

    public function testLengkapiNomorDataAwal(): void
    {
        $this->jalankanDataAwal();
        $this->kosongkanAturanTim();
        $id    = $this->idNomor('Estafet 8 × 50 m', 'mix', 'KU 10');
        $lama  = $this->baris($id);
        $ku    = (int) $lama['kelompok_umur_id'];

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . "/{$id}", self::isianBeregu($ku, [
            'nama'          => 'Estafet 8 × 50 m',
            'jumlah_inti'   => '8',
            'batas_anggota' => '10',
            'jumlah_putra'  => '4',
            'jumlah_putri'  => '4',
            'biaya_beregu'  => '150000',
        ]));

        $this->assertDialihkanKeKelompokUmur($hasil, $ku, $id, 'Perubahan nomor Estafet 8 × 50 m mix di KU 10 tersimpan.');
        $baris = $this->baris($id);
        $this->assertSame('10', (string) $baris['batas_anggota']);
        $this->assertSame('4', (string) $baris['jumlah_putri']);
        $this->assertSame((string) $lama['urutan'], (string) $baris['urutan'], 'Urutan kosong: tetap di tempatnya.');

        $daftar = $this->bukaSebagaiAdmin(self::ALAMAT);
        $daftar->assertSee('10 nomor beregu belum lengkap.');
        $this->assertNull($this->xpathDari($daftar)->query('//tr[@id="nomor-' . $id . '"]')->item(0)?->getAttribute('data-belum-lengkap') ?: null);
    }

    public function testUbahIsianSalahDitampilkanUlang(): void
    {
        $ku = $this->buatKelompokUmur('KU 10');
        $id = $this->buatNomorLomba($ku, ['nama' => 'Formula 1', 'jenis' => 'beregu', 'jumlah_inti' => 4, 'biaya_beregu' => 100000]);

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . "/{$id}", self::isianBeregu($ku, [
            'nama' => 'Formula 1', 'kategori' => 'putra', 'jumlah_inti' => '4', 'batas_anggota' => '3',
        ]));

        $this->assertIsianSalahDitampilkanUlang($hasil, ['batas_anggota']);
        $hasil->assertSee('Batas anggota (3) kurang dari jumlah inti (4). Tulis 4 atau lebih.');
        $this->assertNull($this->baris($id)['batas_anggota']);
    }

    public function testUbahJenisDanPindahKelompokUmurBilaBelumDipakai(): void
    {
        $ku10 = $this->buatKelompokUmur('KU 10');
        $ku12 = $this->buatKelompokUmur('KU 12');
        $this->buatNomorLomba($ku12, ['nama' => 'Trilomba', 'urutan' => 5]);
        $id = $this->buatNomorLomba($ku10, ['urutan' => 1]);

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . "/{$id}", self::isianBeregu($ku12, ['nama' => 'Estafet 8 × 50 m', 'jumlah_inti' => '8', 'batas_anggota' => '8']));

        $this->assertDialihkanKeKelompokUmur($hasil, $ku12, $id, 'Perubahan nomor Estafet 8 × 50 m mix di KU 12 tersimpan.');
        $baris = $this->baris($id);
        $this->assertSame((string) $ku12, (string) $baris['kelompok_umur_id']);
        $this->assertSame('beregu', $baris['jenis']);
        $this->assertSame('6', (string) $baris['urutan'], 'Pindah kelompok umur: paling akhir di kelompok umur baru.');
    }

    public function testUbahBereguMenjadiIndividuMengosongkanIsianTim(): void
    {
        $ku = $this->buatKelompokUmur('KU 10');
        $id = $this->buatNomorLomba($ku, ['nama' => 'Frog Jump', 'jenis' => 'beregu', 'jumlah_inti' => 4, 'batas_anggota' => 4, 'biaya_beregu' => 100000]);

        $this->kirimSebagaiAdmin(self::ALAMAT . "/{$id}", self::isianBeregu($ku, ['nama' => 'Frog Jump', 'jenis' => 'individu', 'kategori' => 'putra']));

        $baris = $this->baris($id);
        $this->assertSame('individu', $baris['jenis']);
        $this->assertNull($baris['jumlah_inti']);
        $this->assertNull($baris['biaya_beregu']);
    }

    public function testNomorDipakaiTerkunciKelompokUmurJenisKategori(): void
    {
        $ku10 = $this->buatKelompokUmur('KU 10');
        $ku12 = $this->buatKelompokUmur('KU 12');
        $id   = $this->buatNomorLomba($ku10);
        $this->pakaiOlehAtlet($id, $ku10);

        // Formulir menampilkan pilihan terkunci dan keterangannya, tanpa tombol hapus.
        $formulir = $this->bukaSebagaiAdmin(self::ALAMAT . "/{$id}/ubah");
        $xpath    = $this->xpathDari($formulir);
        $formulir->assertSee('Nomor ini sudah dipakai atlet atau tim');
        $this->assertSame('disabled', $xpath->query('//select[@name="kelompok_umur_id"]')->item(0)?->getAttribute('disabled'));
        $this->assertSame((string) $ku10, $xpath->query('//input[@type="hidden"][@name="kelompok_umur_id"]')->item(0)?->getAttribute('value'));
        $this->assertSame('individu', $xpath->query('//input[@type="hidden"][@name="jenis"]')->item(0)?->getAttribute('value'));
        $this->assertSame('putra', $xpath->query('//input[@type="hidden"][@name="kategori"]')->item(0)?->getAttribute('value'));
        $this->assertSame(0, $xpath->query('//*[@id="bagian-hapus"]')->length);

        // Kiriman yang tetap memindahkan atau mengubah jenis dan kategori ditolak.
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . "/{$id}", self::isianBeregu($ku12, ['nama' => 'Lari 60 m', 'kategori' => 'putri']));

        $this->assertIsianSalahDitampilkanUlang($hasil, ['kelompok_umur_id', 'jenis', 'kategori']);
        $hasil->assertSee('kelompok umurnya tidak bisa dipindah');
        $this->assertSame((string) $ku10, (string) $this->baris($id)['kelompok_umur_id']);

        // Nama, urutan, dan aktif tetap bisa diubah.
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . "/{$id}", [
            'kelompok_umur_id' => (string) $ku10, 'nama' => 'Lari 60 Meter', 'jenis' => 'individu', 'kategori' => 'putra', 'urutan' => '3', 'aktif' => '0',
        ]);
        $this->assertDialihkanKeKelompokUmur($hasil, $ku10, $id, 'Perubahan nomor Lari 60 Meter putra di KU 10 tersimpan.');
        $this->seeInDatabase('nomor_lomba', ['id' => $id, 'nama' => 'Lari 60 Meter', 'urutan' => 3, 'aktif' => 0]);
    }

    public function testNomorBereguDipakaiTerkunciIsianTim(): void
    {
        $ku = $this->buatKelompokUmur('KU 10');
        $id = $this->buatNomorLomba($ku, [
            'nama' => 'Estafet 4 × 100 m', 'jenis' => 'beregu', 'kategori' => 'mix',
            'jumlah_inti' => 4, 'batas_anggota' => 6, 'jumlah_putra' => 2, 'jumlah_putri' => 2, 'biaya_beregu' => 100000,
        ]);
        $this->pakaiOlehTim($id);

        // Isian tim tampil hanya-baca; nama, biaya, urutan, dan aktif tidak.
        $xpath = $this->xpathDari($this->bukaSebagaiAdmin(self::ALAMAT . "/{$id}/ubah"));
        foreach (['jumlah_inti', 'batas_anggota', 'jumlah_putra', 'jumlah_putri'] as $kotak) {
            $this->assertSame('readonly', $xpath->query("//input[@name=\"{$kotak}\"]")->item(0)?->getAttribute('readonly'), $kotak);
        }
        foreach (['nama', 'biaya_beregu', 'urutan'] as $kotak) {
            $this->assertFalse($xpath->query("//input[@name=\"{$kotak}\"]")->item(0)?->hasAttribute('readonly'), $kotak);
        }

        // Setiap isian tim yang diubah ditolak sendiri-sendiri.
        foreach (['jumlah_inti' => '5', 'batas_anggota' => '8', 'jumlah_putra' => '1', 'jumlah_putri' => '3'] as $kotak => $nilai) {
            $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . "/{$id}", self::isianBeregu($ku, [$kotak => $nilai]));
            $this->assertIsianSalahDitampilkanUlang($hasil, [$kotak]);
            $hasil->assertSee('Nomor ini sudah dipakai atlet atau tim, jadi');
        }
        $this->seeInDatabase('nomor_lomba', ['id' => $id, 'jumlah_inti' => 4, 'batas_anggota' => 6, 'jumlah_putra' => 2, 'jumlah_putri' => 2]);

        // Nama, biaya beregu, urutan, dan aktif tetap bisa diubah.
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . "/{$id}", self::isianBeregu($ku, ['nama' => 'Estafet 4 × 50 m', 'biaya_beregu' => '120000', 'urutan' => '7', 'aktif' => '0']));
        $this->assertDialihkanKeKelompokUmur($hasil, $ku, $id, 'Perubahan nomor Estafet 4 × 50 m mix di KU 10 tersimpan.');
        $this->seeInDatabase('nomor_lomba', ['id' => $id, 'nama' => 'Estafet 4 × 50 m', 'biaya_beregu' => 120000, 'urutan' => 7, 'aktif' => 0, 'jumlah_inti' => 4]);
    }

    public function testNomorBereguBelumDipakaiIsianTimBebas(): void
    {
        $ku = $this->buatKelompokUmur('KU 10');
        $id = $this->buatNomorLomba($ku, [
            'nama' => 'Estafet 4 × 100 m', 'jenis' => 'beregu', 'kategori' => 'mix',
            'jumlah_inti' => 4, 'batas_anggota' => 6, 'jumlah_putra' => 2, 'jumlah_putri' => 2, 'biaya_beregu' => 100000,
        ]);

        $this->assertSame(0, $this->xpathDari($this->bukaSebagaiAdmin(self::ALAMAT . "/{$id}/ubah"))->query('//input[@readonly]')->length);

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . "/{$id}", self::isianBeregu($ku, ['jumlah_inti' => '5', 'batas_anggota' => '8', 'jumlah_putra' => '1', 'jumlah_putri' => '3']));
        $this->assertTrue($hasil->isRedirect());
        $this->seeInDatabase('nomor_lomba', ['id' => $id, 'jumlah_inti' => 5, 'batas_anggota' => 8, 'jumlah_putra' => 1, 'jumlah_putri' => 3]);
    }

    public function testNomorDipakaiYangBelumLengkapBolehDilengkapi(): void
    {
        $ku = $this->buatKelompokUmur('KU 10');
        $id = $this->buatNomorLomba($ku, ['nama' => 'Estafet 4 × 100 m', 'jenis' => 'beregu', 'kategori' => 'mix', 'jumlah_inti' => 4]);
        $this->pakaiOlehTim($id);

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . "/{$id}", self::isianBeregu($ku));
        $this->assertTrue($hasil->isRedirect());
        $this->seeInDatabase('nomor_lomba', ['id' => $id, 'jumlah_inti' => 4, 'batas_anggota' => 6, 'jumlah_putra' => 2]);
    }

    public function testUbahMenjadiKembarDitolak(): void
    {
        $ku = $this->buatKelompokUmur('KU 10');
        $this->buatNomorLomba($ku, ['nama' => 'Frog Jump']);
        $id = $this->buatNomorLomba($ku, ['nama' => 'Turbo Throw']);

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . "/{$id}", ['kelompok_umur_id' => (string) $ku, 'nama' => 'Frog Jump', 'jenis' => 'individu', 'kategori' => 'putra']);
        $this->assertIsianSalahDitampilkanUlang($hasil, ['nama']);

        // Menyimpan ulang nama sendiri tidak dianggap kembar.
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . "/{$id}", ['kelompok_umur_id' => (string) $ku, 'nama' => 'Turbo Throw', 'jenis' => 'individu', 'kategori' => 'putra', 'aktif' => '1']);
        $this->assertTrue($hasil->isRedirect());
    }

    // ---------------------------------------------------------------
    // Hapus
    // ---------------------------------------------------------------

    public function testHapusNomorYangBelumDipakai(): void
    {
        $ku = $this->buatKelompokUmur('KU 10');
        $id = $this->buatNomorLomba($ku);

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . "/{$id}/hapus");

        $this->assertSame(site_url(self::ALAMAT) . '?kelompok-umur=' . $ku, $hasil->getRedirectUrl());
        $hasil->assertSessionHas('pesan_sukses', 'Nomor Lari 60 m putra di KU 10 sudah dihapus.');
        $this->assertSame(0, $this->jumlahNomor());
    }

    public function testHapusNomorYangDipakaiAtletDitolak(): void
    {
        $ku = $this->buatKelompokUmur('KU 10');
        $id = $this->buatNomorLomba($ku);
        $this->pakaiOlehAtlet($id, $ku);

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . "/{$id}/hapus");

        $this->assertGagalDialihkan($hasil, self::ALAMAT, 'Nomor Lari 60 m putra di KU 10 sudah dipakai atlet atau tim, jadi tidak bisa dihapus. Matikan saja supaya tidak muncul di pendaftaran.');
        $this->assertSame(1, $this->jumlahNomor());
    }

    public function testHapusNomorYangDipakaiTimDitolak(): void
    {
        $ku = $this->buatKelompokUmur('KU 10');
        $id = $this->buatNomorLomba($ku, ['nama' => 'Formula 1', 'jenis' => 'beregu', 'jumlah_inti' => 4, 'biaya_beregu' => 100000]);
        $this->pakaiOlehTim($id);

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . "/{$id}/hapus");

        $this->assertGagalDialihkan($hasil, self::ALAMAT);
        $this->assertStringContainsString('tidak bisa dihapus', (string) session('pesan_galat'));
        $this->assertSame(1, $this->jumlahNomor());
    }

    public function testHapusNomorYangTidakAda(): void
    {
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . '/999/hapus');

        $this->assertGagalDialihkan($hasil, self::ALAMAT);
        $this->assertStringContainsString('tidak ditemukan', (string) session('pesan_galat'));
    }
}
