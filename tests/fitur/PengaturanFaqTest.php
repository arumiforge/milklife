<?php

use App\Libraries\Bantuan\FaqBawaan;
use App\Models\FaqModel;
use Tests\Support\KirimFormulir;
use Tests\Support\UjiBasisData;

/**
 * Pengaturan > Bantuan (F9-04): `/admin/pengaturan/bantuan`, untuk tabel `faq`.
 *
 * @internal
 */
final class PengaturanFaqTest extends UjiBasisData
{
    use KirimFormulir;

    private const ALAMAT = 'admin/pengaturan/bantuan';

    private function buatFaq(string $kategori = 'sebelum', string $pertanyaan = 'Apakah harus punya akun?', int $urutan = 1, int $aktif = 1, string $jawaban = 'Tidak perlu.'): int
    {
        return (int) model(FaqModel::class)->insert(['kategori' => $kategori, 'pertanyaan' => $pertanyaan, 'jawaban' => $jawaban, 'urutan' => $urutan, 'aktif' => $aktif]);
    }

    /**
     * @return array<string, string>
     */
    private function isian(array $ganti = []): array
    {
        return $ganti + ['kategori' => 'pembayaran', 'pertanyaan' => 'Bukti transfer hilang?', 'jawaban' => "Ambil tangkapan layar dari **m-banking**.", 'urutan' => '', 'aktif' => '1'];
    }

    // ---------- Akses ----------

    public function testTertutupUntukYangBelumMasuk(): void
    {
        $id = $this->buatFaq();

        foreach ([self::ALAMAT, self::ALAMAT . '/tambah', self::ALAMAT . "/{$id}/ubah"] as $alamat) {
            $this->assertBelumMasukDialihkan($alamat);
        }

        foreach ([self::ALAMAT, self::ALAMAT . '/bawaan', self::ALAMAT . "/{$id}", self::ALAMAT . "/{$id}/hapus", self::ALAMAT . "/{$id}/aktif", self::ALAMAT . "/{$id}/naik"] as $alamat) {
            $this->assertBelumMasukDialihkan($alamat, 'post');
        }

        $this->seeNumRecords(1, 'faq', []);
    }

    public function testTokenCsrfWajib(): void
    {
        $id = $this->buatFaq();

        $this->assertTokenCsrfWajib(self::ALAMAT, $this->isian());
        $this->assertTokenCsrfWajib(self::ALAMAT . "/{$id}", $this->isian());
        $this->assertTokenCsrfWajib(self::ALAMAT . "/{$id}/hapus");
        $this->assertTokenCsrfWajib(self::ALAMAT . "/{$id}/aktif");
        $this->assertTokenCsrfWajib(self::ALAMAT . '/bawaan');

        $this->seeNumRecords(1, 'faq', []);
        $this->seeInDatabase('faq', ['id' => $id, 'aktif' => 1]);
    }

    // ---------- Daftar ----------

    public function testDaftarKosongMenawarkanTanyaJawabBawaanDanSemuaKategori(): void
    {
        $hasil = $this->bukaSebagaiAdmin(self::ALAMAT);
        $hasil->assertOK();

        $xpath = $this->xpathDari($hasil);
        $this->assertSame(1, $xpath->query('//nav[@aria-label="Navigasi pengaturan"]//a[@aria-current="page"][contains(., "Bantuan")]')->length);
        $this->assertSame(1, $xpath->query('//form[@action="' . site_url(self::ALAMAT . '/bawaan') . '"]//button[contains(., "Pasang tanya-jawab bawaan")]')->length);

        foreach (FaqModel::KATEGORI as $kunci => $kategori) {
            $bagian = $xpath->query('//section[@id="kategori-' . str_replace('_', '-', $kunci) . '"]')->item(0);
            $this->assertNotNull($bagian, "Bagian {$kunci} tidak ada.");
            $this->assertStringContainsString($kategori['label'], $bagian->textContent);
            $this->assertStringContainsString('Belum ada tanya-jawab di kategori ini.', $bagian->textContent);
            $this->assertSame(1, $xpath->query('.//a[@href="' . site_url(self::ALAMAT . '/tambah') . '?kategori=' . $kunci . '"]', $bagian)->length);
        }
    }

    public function testDaftarMenampilkanTanyaJawabPerKategoriMenurutUrutan(): void
    {
        $kedua     = $this->buatFaq('sebelum', 'Pertanyaan <kedua>?', 2);
        $pertama   = $this->buatFaq('sebelum', 'Pertanyaan pertama?', 1);
        $tersembun = $this->buatFaq('hasil', 'Pertanyaan tersembunyi?', 1, 0);

        $hasil = $this->bukaSebagaiAdmin(self::ALAMAT);
        $hasil->assertOK();
        $this->assertStringNotContainsString('Pertanyaan <kedua>?', (string) $hasil->getBody(), 'Pertanyaan harus di-escape.');
        $this->assertStringNotContainsString('Pasang tanya-jawab bawaan', (string) $hasil->getBody());

        $xpath = $this->xpathDari($hasil);
        $baris = $xpath->query('//section[@id="kategori-sebelum"]//tbody/tr');
        $this->assertSame([(string) $pertama, (string) $kedua], [$baris->item(0)->getAttribute('data-id'), $baris->item(1)->getAttribute('data-id')]);

        // Yang pertama tidak bisa naik, yang terakhir tidak bisa turun.
        $this->assertSame(1, $xpath->query('.//form[@action="' . site_url(self::ALAMAT . "/{$pertama}/naik") . '"]//button[@disabled]', $baris->item(0))->length);
        $this->assertSame(0, $xpath->query('.//form[@action="' . site_url(self::ALAMAT . "/{$pertama}/turun") . '"]//button[@disabled]', $baris->item(0))->length);
        $this->assertSame(1, $xpath->query('.//form[@action="' . site_url(self::ALAMAT . "/{$kedua}/turun") . '"]//button[@disabled]', $baris->item(1))->length);

        // Hapus dengan jendela konfirmasi.
        $hapus = $xpath->query('.//form[@data-konfirmasi]', $baris->item(0))->item(0);
        $this->assertSame(site_url(self::ALAMAT . "/{$pertama}/hapus"), $hapus->getAttribute('action'));
        $hasil->assertSee(aset('js/konfirmasi.js'));

        $sembunyi = $xpath->query('//tr[@id="faq-' . $tersembun . '"]')->item(0);
        $this->assertStringContainsString('Disembunyikan', $sembunyi->textContent);
        $this->assertStringContainsString('Tampilkan', $sembunyi->textContent);
    }

    public function testKartuBantuanAdaDiDaftarPengaturan(): void
    {
        $hasil = $this->bukaSebagaiAdmin('admin/pengaturan');
        $hasil->assertOK();
        $this->assertSame(1, $this->xpathDari($hasil)->query('//a[@data-kunci="bantuan"][@href="' . site_url(self::ALAMAT) . '"]')->length);
    }

    // ---------- Tambah ----------

    public function testFormulirTambahMemilihKategoriDariAlamat(): void
    {
        $this->buatFaq('hari_lomba', 'Lain?', 4);

        $hasil = $this->bukaSebagaiAdmin(self::ALAMAT . '/tambah?kategori=hari_lomba');
        $hasil->assertOK();

        $xpath = $this->xpathDari($hasil);
        $this->assertSame('hari_lomba', $xpath->query('//select[@id="kategori"]/option[@selected]')->item(0)->getAttribute('value'));
        $this->assertSame('5', $xpath->query('//input[@id="urutan"]')->item(0)->getAttribute('value'));
        $this->assertSame(1, $xpath->query('//input[@id="aktif"][@checked]')->length);
        // Daftar isian otomatis untuk admin.
        $hasil->assertSee('{tutup_pendaftaran}');
        $hasil->assertSee('{daftar_kelompok_umur}');
    }

    public function testTambahBerhasil(): void
    {
        $this->buatFaq('pembayaran', 'Sudah ada?', 3);

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, $this->isian(['pertanyaan' => "  Bukti   transfer\nhilang?  "]));

        $baris = $this->db->table('faq')->where('pertanyaan', 'Bukti transfer hilang?')->get()->getRowArray();
        $this->assertNotNull($baris, 'Pertanyaan dirapikan menjadi satu baris.');
        $this->assertBerhasilDialihkan($hasil, self::ALAMAT . '#faq-' . $baris['id'], 'Tanya-jawab "Bukti transfer hilang?" tersimpan.');
        $this->assertSame(['pembayaran', '4', '1'], [$baris['kategori'], (string) $baris['urutan'], (string) $baris['aktif']]);
        $this->assertSame('Ambil tangkapan layar dari **m-banking**.', $baris['jawaban']);
    }

    public function testIsianSalahDitampilkanUlang(): void
    {
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, ['kategori' => 'tidak-ada', 'pertanyaan' => '', 'jawaban' => '', 'urutan' => 'satu']);

        $this->assertIsianSalahDitampilkanUlang($hasil, ['kategori', 'pertanyaan', 'jawaban', 'urutan']);
        $hasil->assertSee('Kategori belum dipilih.');
        $hasil->assertSee('Pertanyaan belum diisi.');
        $this->seeNumRecords(0, 'faq', []);

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, $this->isian(['pertanyaan' => str_repeat('a', 256), 'jawaban' => str_repeat('b', 5001)]));
        $this->assertIsianSalahDitampilkanUlang($hasil, ['pertanyaan', 'jawaban']);
        $hasil->assertSee('Pertanyaan terlalu panjang.');
        $hasil->assertSee('Jawaban terlalu panjang.');
        $this->seeNumRecords(0, 'faq', []);
    }

    public function testPratinjauDanPeringatanIsianTidakDikenal(): void
    {
        $id = $this->buatFaq('sebelum', 'Kapan tutup?', 1, 1, "Ditutup **{tutup_pendaftaran}** di {tempat_salah}.\n\n[jahat](javascript:alert)");
        setting('Pendaftaran.akhir', '2026-11-30 23:59:00');

        $hasil = $this->bukaSebagaiAdmin(self::ALAMAT . "/{$id}/ubah");
        $hasil->assertOK();

        $xpath     = $this->xpathDari($hasil);
        $pratinjau = $xpath->query('//*[contains(@class, "isi-jawaban-faq")]')->item(0);
        $this->assertNotNull($pratinjau);
        $this->assertStringContainsString('Ditutup 30 November 2026 pukul 23.59', $pratinjau->textContent);
        $this->assertSame(0, $xpath->query('.//a', $pratinjau)->length, 'Tautan javascript: dibuang.');
        $hasil->assertSee('Isian otomatis ini tidak dikenal');
        $hasil->assertSee('{tempat_salah}');
    }

    // ---------- Ubah dan hapus ----------

    public function testUbahBerhasilDanBisaDisembunyikan(): void
    {
        $id = $this->buatFaq();

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . "/{$id}", $this->isian(['kategori' => 'mengisi', 'urutan' => '7', 'aktif' => '']));

        $this->assertBerhasilDialihkan($hasil, self::ALAMAT . '#faq-' . $id, 'Perubahan tanya-jawab "Bukti transfer hilang?" tersimpan.');
        $this->seeInDatabase('faq', ['id' => $id, 'kategori' => 'mengisi', 'urutan' => 7, 'aktif' => 0]);
    }

    public function testYangTidakAdaDialihkanDenganPesan(): void
    {
        $this->assertGagalDialihkan($this->bukaSebagaiAdmin(self::ALAMAT . '/999/ubah'), self::ALAMAT);
        $this->assertGagalDialihkan($this->kirimSebagaiAdmin(self::ALAMAT . '/999', $this->isian()), self::ALAMAT);
        $this->assertGagalDialihkan($this->kirimSebagaiAdmin(self::ALAMAT . '/999/hapus'), self::ALAMAT);
        $this->assertGagalDialihkan($this->kirimSebagaiAdmin(self::ALAMAT . '/999/aktif'), self::ALAMAT);
        $this->assertGagalDialihkan($this->kirimSebagaiAdmin(self::ALAMAT . '/999/naik'), self::ALAMAT);
        $this->seeNumRecords(0, 'faq', []);
    }

    public function testHapus(): void
    {
        $id   = $this->buatFaq();
        $lain = $this->buatFaq('hasil', 'Lain?');

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . "/{$id}/hapus");

        $this->assertBerhasilDialihkan($hasil, self::ALAMAT, 'Tanya-jawab "Apakah harus punya akun?" sudah dihapus.');
        $this->dontSeeInDatabase('faq', ['id' => $id]);
        $this->seeInDatabase('faq', ['id' => $lain]);
    }

    public function testSembunyikanLaluTampilkan(): void
    {
        $id = $this->buatFaq();

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . "/{$id}/aktif");
        $this->assertBerhasilDialihkan($hasil, self::ALAMAT . '#faq-' . $id, '"Apakah harus punya akun?" disembunyikan dari halaman Bantuan.');
        $this->seeInDatabase('faq', ['id' => $id, 'aktif' => 0]);

        $this->kirimSebagaiAdmin(self::ALAMAT . "/{$id}/aktif");
        $this->seeInDatabase('faq', ['id' => $id, 'aktif' => 1]);
    }

    // ---------- Urutan ----------

    public function testNaikDanTurunMenukarTetanggaDiKategoriYangSama(): void
    {
        // Urutan kembar tetap bisa ditukar karena urutan ditulis ulang 1, 2, 3.
        $a    = $this->buatFaq('sebelum', 'A?', 5);
        $b    = $this->buatFaq('sebelum', 'B?', 5);
        $c    = $this->buatFaq('sebelum', 'C?', 9);
        $lain = $this->buatFaq('hasil', 'Lain?', 1);

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . "/{$c}/naik");
        $this->assertBerhasilDialihkan($hasil, self::ALAMAT . '#faq-' . $c, 'Urutan "C?" diubah.');
        $this->assertSame([$a, $c, $b], $this->idUrut('sebelum'));
        $this->assertSame(['1', '2', '3'], array_column($this->db->table('faq')->select('urutan')->where('kategori', 'sebelum')->orderBy('urutan')->get()->getResultArray(), 'urutan'));

        $this->kirimSebagaiAdmin(self::ALAMAT . "/{$a}/turun");
        $this->assertSame([$c, $a, $b], $this->idUrut('sebelum'));
        $this->seeInDatabase('faq', ['id' => $lain, 'urutan' => 1]);

        // Sudah paling atas.
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . "/{$c}/naik");
        $this->assertGagalDialihkan($hasil, self::ALAMAT . '#faq-' . $c, 'Tanya-jawab ini sudah paling atas di kategorinya.');
        $this->assertSame([$c, $a, $b], $this->idUrut('sebelum'));
    }

    public function testUrutanTetapBerubahWalauKoneksiPernahMenggagalkanTransaksi(): void
    {
        // Uji lain (atau permintaan sebelumnya di proses yang sama) bisa
        // meninggalkan status transaksi gagal di koneksi bersama: CodeIgniter
        // dalam mode ketat tidak mengembalikannya saat transaksi baru dimulai.
        $this->db->transStart();

        try {
            $this->db->query('SELECT * FROM tabel_yang_tidak_ada');
        } catch (Throwable) {
            // Kueri gagal memang disengaja.
        }

        $this->db->transComplete();
        $this->assertFalse($this->db->transStatus(), 'Prasyarat: status transaksi gagal tertinggal.');

        $a = $this->buatFaq('sebelum', 'A?', 1);
        $b = $this->buatFaq('sebelum', 'B?', 2);

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . "/{$b}/naik");

        $this->assertBerhasilDialihkan($hasil, self::ALAMAT . '#faq-' . $b, 'Urutan "B?" diubah.');
        $this->assertSame([$b, $a], $this->idUrut('sebelum'));
    }

    // ---------- Bawaan ----------

    public function testPasangBawaanHanyaBilaKosong(): void
    {
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . '/bawaan');

        $this->assertBerhasilDialihkan($hasil, self::ALAMAT, count(FaqBawaan::DAFTAR) . ' tanya-jawab bawaan dipasang. Periksa dan sesuaikan isinya.');
        $this->seeNumRecords(count(FaqBawaan::DAFTAR), 'faq', []);

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . '/bawaan');
        $this->assertGagalDialihkan($hasil, self::ALAMAT);
        $this->seeNumRecords(count(FaqBawaan::DAFTAR), 'faq', []);
    }

    /**
     * @return list<int>
     */
    private function idUrut(string $kategori): array
    {
        return array_map('intval', array_column(model(FaqModel::class)->semuaPerKategori()[$kategori], 'id'));
    }
}
