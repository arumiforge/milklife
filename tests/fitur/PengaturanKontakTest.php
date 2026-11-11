<?php

use App\Libraries\NomorWa;
use App\Models\TautanModel;
use Tests\Support\KirimFormulir;
use Tests\Support\UjiBasisData;

/**
 * Halaman pengaturan kontak (F2-06): `/admin/pengaturan/kontak`, untuk
 * narahubung dan media sosial di tabel `tautan`.
 *
 * @internal
 */
final class PengaturanKontakTest extends UjiBasisData
{
    use KirimFormulir;

    private const ALAMAT = 'admin/pengaturan/kontak';

    private function buatKontak(string $jenis, string $label, string $nilai, int $urutan = 1): int
    {
        return (int) model(TautanModel::class)->insert(['jenis' => $jenis, 'label' => $label, 'nilai' => $nilai, 'urutan' => $urutan]);
    }

    private function buatNarahubung(string $label = 'Bu Rina', string $nomor = '6281234567890', int $urutan = 1): int
    {
        return $this->buatKontak(TautanModel::NARAHUBUNG, $label, $nomor, $urutan);
    }

    private function buatMediaSosial(string $label = 'Instagram', string $alamat = 'https://www.instagram.com/contoh', int $urutan = 1): int
    {
        return $this->buatKontak(TautanModel::MEDIA_SOSIAL, $label, $alamat, $urutan);
    }

    // ---------- Akses ----------

    public function testTertutupUntukYangBelumMasuk(): void
    {
        $id = $this->buatNarahubung();

        foreach ([self::ALAMAT, self::ALAMAT . '/tambah?jenis=narahubung', self::ALAMAT . "/{$id}/ubah"] as $alamat) {
            $this->assertBelumMasukDialihkan($alamat);
        }

        foreach ([self::ALAMAT, self::ALAMAT . "/{$id}", self::ALAMAT . "/{$id}/hapus"] as $alamat) {
            $this->assertBelumMasukDialihkan($alamat, 'post');
        }

        $this->seeNumRecords(1, 'tautan', []);
    }

    public function testTokenCsrfWajib(): void
    {
        $id    = $this->buatNarahubung();
        $isian = ['jenis' => 'narahubung', 'label' => 'Pak Budi', 'nilai' => '0812-1111-2222', 'urutan' => '2'];

        $this->assertTokenCsrfWajib(self::ALAMAT, $isian);
        $this->assertTokenCsrfWajib(self::ALAMAT . "/{$id}", $isian);
        $this->assertTokenCsrfWajib(self::ALAMAT . "/{$id}/hapus");

        $this->seeNumRecords(1, 'tautan', []);
        $this->seeInDatabase('tautan', ['id' => $id, 'label' => 'Bu Rina']);
    }

    // ---------- Daftar ----------

    public function testDaftarKosongMenampilkanDuaBagianDenganTombolTambah(): void
    {
        $hasil = $this->bukaSebagaiAdmin(self::ALAMAT);
        $hasil->assertOK();

        $xpath = $this->xpathDari($hasil);
        $this->assertSame(1, $xpath->query('//nav[@aria-label="Navigasi pengaturan"]//a[@aria-current="page"][contains(., "Kontak")]')->length);

        foreach (['narahubung' => 'Belum ada narahubung.', 'media-sosial' => 'Belum ada media sosial.'] as $bagian => $kosong) {
            $section = $xpath->query('//section[@id="' . $bagian . '"]')->item(0);
            $this->assertNotNull($section, "Bagian {$bagian} tidak ada.");
            $this->assertStringContainsString($kosong, $section->textContent);
            $this->assertSame(1, $xpath->query('.//a[@href="' . site_url(self::ALAMAT . '/tambah') . '?jenis=' . $bagian . '"]', $section)->length);
            $this->assertSame(0, $xpath->query('.//table', $section)->length);
        }
    }

    public function testDaftarMenampilkanNarahubungDanMediaSosialTerpisah(): void
    {
        $budi = $this->buatNarahubung('Pak <Budi>', '6281200001111', 2);
        $rina = $this->buatNarahubung('Bu Rina', '6281234567890', 1);
        $ig   = $this->buatMediaSosial('Instagram', 'https://www.instagram.com/contoh', 1);

        $hasil = $this->bukaSebagaiAdmin(self::ALAMAT);
        $hasil->assertOK();

        $xpath = $this->xpathDari($hasil);
        $baris = $xpath->query('//section[@id="narahubung"]//tbody/tr');
        $this->assertSame([(string) $rina, (string) $budi], [$baris->item(0)->getAttribute('data-id'), $baris->item(1)->getAttribute('data-id')]);

        // Nomor WA ditampilkan mudah dibaca dan menuju WA.
        $this->assertStringContainsString(NomorWa::tampilan('6281234567890'), $baris->item(0)->textContent);
        $this->assertStringContainsString('0812-3456-7890', $baris->item(0)->textContent);
        $this->assertSame(1, $xpath->query('.//a[@href="https://wa.me/6281234567890"]', $baris->item(0))->length);
        $this->assertStringContainsString('Pak <Budi>', $baris->item(1)->textContent);
        $this->assertStringNotContainsString('Pak <Budi>', (string) $hasil->getBody(), 'Nama harus di-escape.');

        $media = $xpath->query('//section[@id="media-sosial"]//tbody/tr');
        $this->assertSame(1, $media->length);
        $this->assertSame((string) $ig, $media->item(0)->getAttribute('data-id'));
        $this->assertSame(1, $xpath->query('.//a[@href="https://www.instagram.com/contoh"][@rel="noopener noreferrer"]', $media->item(0))->length);
        $this->assertSame(1, $xpath->query('.//i[contains(@class, "bi-instagram")]', $media->item(0))->length);

        // Hapus lewat POST, token CSRF, dan jendela konfirmasi.
        $hapus = $xpath->query('.//form[@data-konfirmasi]', $media->item(0))->item(0);
        $this->assertSame('post', $hapus->getAttribute('method'));
        $this->assertSame(site_url(self::ALAMAT . "/{$ig}/hapus"), $hapus->getAttribute('action'));
        $this->assertStringContainsString('Media sosial "Instagram" akan dihapus', $hapus->getAttribute('data-konfirmasi'));
        $this->assertSame(1, $xpath->query('.//input[@name="' . config('Security')->tokenName . '"]', $hapus)->length);
        $hasil->assertSee(aset('js/konfirmasi.js'));
    }

    // ---------- Tambah ----------

    public function testFormulirTambahMengikutiJenis(): void
    {
        $this->buatNarahubung('Bu Rina', '6281234567890', 4);

        $hasil = $this->bukaSebagaiAdmin(self::ALAMAT . '/tambah?jenis=narahubung');
        $hasil->assertOK();
        $hasil->assertSee('Nama narahubung');
        $hasil->assertSee('Nomor WA');
        $xpath = $this->xpathDari($hasil);
        $this->assertSame(site_url(self::ALAMAT), $xpath->query('//main//form[@method="post"]')->item(0)->getAttribute('action'));
        $this->assertSame('narahubung', $xpath->query('//input[@type="hidden"][@name="jenis"]')->item(0)->getAttribute('value'));
        $this->assertSame('tel', $xpath->query('//input[@name="nilai"]')->item(0)->getAttribute('type'));
        $this->assertSame('5', $xpath->query('//input[@name="urutan"]')->item(0)->getAttribute('value'));
        $this->assertSame(1, $xpath->query('//nav[@aria-label="Navigasi pengaturan"]//a[@aria-current="page"][contains(., "Kontak")]')->length);

        $hasil = $this->bukaSebagaiAdmin(self::ALAMAT . '/tambah?jenis=media-sosial');
        $hasil->assertOK();
        $hasil->assertSee('Nama media sosial');
        $xpath = $this->xpathDari($hasil);
        $this->assertSame('media-sosial', $xpath->query('//input[@type="hidden"][@name="jenis"]')->item(0)->getAttribute('value'));
        $this->assertSame('url', $xpath->query('//input[@name="nilai"]')->item(0)->getAttribute('type'));
        $this->assertSame('1', $xpath->query('//input[@name="urutan"]')->item(0)->getAttribute('value'), 'Urutan dihitung per jenis.');
    }

    public function testTambahTanpaJenisYangDikenalDialihkan(): void
    {
        $pesan = 'Jenis kontak belum dipilih. Tekan tombol Tambah narahubung atau Tambah media sosial di bawah.';

        $this->assertGagalDialihkan($this->bukaSebagaiAdmin(self::ALAMAT . '/tambah'), self::ALAMAT, $pesan);
        $this->assertGagalDialihkan($this->bukaSebagaiAdmin(self::ALAMAT . '/tambah?jenis=email'), self::ALAMAT, $pesan);
        $this->assertGagalDialihkan($this->kirimSebagaiAdmin(self::ALAMAT, ['label' => 'Bu Rina', 'nilai' => '081234567890']), self::ALAMAT, $pesan);
        $this->assertGagalDialihkan($this->kirimSebagaiAdmin(self::ALAMAT, ['jenis' => 'media_sosial', 'label' => 'IG', 'nilai' => 'instagram.com/contoh']), self::ALAMAT, $pesan);
        $this->assertGagalDialihkan($this->kirimSebagaiAdmin(self::ALAMAT, ['jenis' => ['narahubung'], 'label' => 'Bu Rina', 'nilai' => '081234567890']), self::ALAMAT, $pesan);
        $this->seeNumRecords(0, 'tautan', []);
    }

    public function testTambahNarahubungMembakukanNomorWa(): void
    {
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, [
            'jenis'  => 'narahubung',
            'label'  => '  Bu Rina (panitia)  ',
            'nilai'  => '+62 812-3456-7890',
            'urutan' => '1',
        ]);

        $this->assertBerhasilDialihkan($hasil, self::ALAMAT, 'Narahubung "Bu Rina (panitia)" tersimpan.');
        $this->seeInDatabase('tautan', ['jenis' => 'narahubung', 'label' => 'Bu Rina (panitia)', 'nilai' => '6281234567890', 'urutan' => 1]);
    }

    public function testTambahMediaSosialMerapikanAlamat(): void
    {
        $this->buatMediaSosial('YouTube', 'https://youtube.com/@contoh', 3);

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, [
            'jenis'  => 'media-sosial',
            'label'  => 'Instagram @contoh',
            'nilai'  => 'instagram.com/contoh',
            'urutan' => '',
        ]);

        $this->assertBerhasilDialihkan($hasil, self::ALAMAT, 'Media sosial "Instagram @contoh" tersimpan.');
        $this->seeInDatabase('tautan', ['jenis' => 'media_sosial', 'label' => 'Instagram @contoh', 'nilai' => 'https://instagram.com/contoh', 'urutan' => 4]);
    }

    public function testNarahubungDenganIsianSalahDitampilkanUlang(): void
    {
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, [
            'jenis'  => 'narahubung',
            'label'  => '',
            'nilai'  => '021-555-1234',
            'urutan' => 'satu',
        ]);

        $this->assertIsianSalahDitampilkanUlang($hasil, ['label', 'nilai', 'urutan']);
        $hasil->assertSee('Nama narahubung belum diisi.');
        $hasil->assertSee(NomorWa::PESAN_GALAT);
        $hasil->assertSee('Urutan belum benar.');
        $xpath = $this->xpathDari($hasil);
        $this->assertSame('021-555-1234', $xpath->query('//input[@name="nilai"]')->item(0)->getAttribute('value'));
        $this->assertSame('narahubung', $xpath->query('//input[@type="hidden"][@name="jenis"]')->item(0)->getAttribute('value'));
        $this->seeNumRecords(0, 'tautan', []);
    }

    public function testMediaSosialDenganIsianSalahDitampilkanUlang(): void
    {
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, [
            'jenis'  => 'media-sosial',
            'label'  => str_repeat('a', 101),
            'nilai'  => 'javascript:alert(1)',
            'urutan' => '1',
        ]);

        $this->assertIsianSalahDitampilkanUlang($hasil, ['label', 'nilai']);
        $hasil->assertSee('Nama media sosial terlalu panjang.');
        $hasil->assertSee(TautanModel::PESAN_ALAMAT_WEB);
        $this->seeNumRecords(0, 'tautan', []);

        $kosong = $this->kirimSebagaiAdmin(self::ALAMAT, ['jenis' => 'media-sosial', 'label' => 'Instagram', 'nilai' => '']);
        $this->assertIsianSalahDitampilkanUlang($kosong, ['nilai']);
        $kosong->assertSee('Alamat belum diisi.');
    }

    public function testNilaiKembarDalamSatuJenisDitolak(): void
    {
        $this->buatNarahubung('Bu Rina', '6281234567890');
        $this->buatMediaSosial('Instagram', 'https://instagram.com/contoh');

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, ['jenis' => 'narahubung', 'label' => 'Pak Budi', 'nilai' => '0812 3456 7890']);
        $this->assertIsianSalahDitampilkanUlang($hasil, ['nilai']);
        $hasil->assertSee('Nomor WA ini sudah ada di daftar narahubung atas nama Bu Rina.');

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, ['jenis' => 'media-sosial', 'label' => 'IG lain', 'nilai' => 'instagram.com/contoh']);
        $this->assertIsianSalahDitampilkanUlang($hasil, ['nilai']);

        $this->seeNumRecords(2, 'tautan', []);
    }

    // ---------- Ubah ----------

    public function testFormulirUbahNarahubungMenampilkanNomorMudahDibaca(): void
    {
        $id = $this->buatNarahubung('Bu Rina', '6281234567890', 3);

        $hasil = $this->bukaSebagaiAdmin(self::ALAMAT . "/{$id}/ubah");
        $hasil->assertOK();
        $hasil->assertSee('Ubah narahubung');

        $xpath = $this->xpathDari($hasil);
        $this->assertSame(site_url(self::ALAMAT . "/{$id}"), $xpath->query('//main//form[@method="post"]')->item(0)->getAttribute('action'));
        $this->assertSame('Bu Rina', $xpath->query('//input[@name="label"]')->item(0)->getAttribute('value'));
        $this->assertSame('0812-3456-7890', $xpath->query('//input[@name="nilai"]')->item(0)->getAttribute('value'));
        $this->assertSame('3', $xpath->query('//input[@name="urutan"]')->item(0)->getAttribute('value'));
        $this->assertSame(0, $xpath->query('//input[@name="jenis"]')->length, 'Jenis kontak yang sudah ada tidak diubah.');
    }

    public function testUbahNarahubungBerhasil(): void
    {
        $id = $this->buatNarahubung('Bu Rina', '6281234567890', 3);

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . "/{$id}", [
            'label'  => 'Bu Rina Karangan',
            'nilai'  => '0812-3456-7890',
            'urutan' => '1',
        ]);

        $this->assertBerhasilDialihkan($hasil, self::ALAMAT, 'Perubahan narahubung "Bu Rina Karangan" tersimpan.');
        $this->seeInDatabase('tautan', ['id' => $id, 'jenis' => 'narahubung', 'label' => 'Bu Rina Karangan', 'nilai' => '6281234567890', 'urutan' => 1]);
    }

    public function testUbahMengabaikanKirimanJenis(): void
    {
        $id = $this->buatMediaSosial('Instagram', 'https://instagram.com/contoh');

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . "/{$id}", [
            'jenis'  => 'narahubung',
            'label'  => 'Instagram resmi',
            'nilai'  => 'https://www.instagram.com/contoh.resmi',
            'urutan' => '2',
        ]);

        $this->assertBerhasilDialihkan($hasil, self::ALAMAT, 'Perubahan media sosial "Instagram resmi" tersimpan.');
        $this->seeInDatabase('tautan', ['id' => $id, 'jenis' => 'media_sosial', 'nilai' => 'https://www.instagram.com/contoh.resmi']);
    }

    public function testUbahDenganIsianSalahTidakMenyimpan(): void
    {
        $id = $this->buatMediaSosial('Instagram', 'https://instagram.com/contoh');

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . "/{$id}", ['label' => '', 'nilai' => 'bukan alamat', 'urutan' => '1']);

        $this->assertIsianSalahDitampilkanUlang($hasil, ['label', 'nilai']);
        $hasil->assertSee('Nama media sosial belum diisi.');
        $this->seeInDatabase('tautan', ['id' => $id, 'label' => 'Instagram', 'nilai' => 'https://instagram.com/contoh']);
    }

    public function testUbahTanpaMengubahNilaiTidakDianggapKembar(): void
    {
        $id = $this->buatNarahubung('Bu Rina', '6281234567890');

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . "/{$id}", ['label' => 'Bu Rina', 'nilai' => '6281234567890', 'urutan' => '']);

        $this->assertBerhasilDialihkan($hasil, self::ALAMAT);
        $this->seeInDatabase('tautan', ['id' => $id, 'urutan' => 1]);
    }

    public function testKontakYangTidakAdaDialihkanDenganPesan(): void
    {
        $pesan = 'Kontak itu tidak ditemukan, mungkin sudah dihapus. Pilih kontak dari daftar di bawah.';

        $this->assertGagalDialihkan($this->bukaSebagaiAdmin(self::ALAMAT . '/999/ubah'), self::ALAMAT, $pesan);
        $this->assertGagalDialihkan($this->kirimSebagaiAdmin(self::ALAMAT . '/999', ['label' => 'A', 'nilai' => '081234567890']), self::ALAMAT, $pesan);
        $this->assertGagalDialihkan($this->kirimSebagaiAdmin(self::ALAMAT . '/999/hapus'), self::ALAMAT, $pesan);
        $this->seeNumRecords(0, 'tautan', []);
    }

    // ---------- Hapus ----------

    public function testHapusBerhasil(): void
    {
        $rina = $this->buatNarahubung('Bu Rina', '6281234567890');
        $ig   = $this->buatMediaSosial('Instagram');

        $this->assertBerhasilDialihkan($this->kirimSebagaiAdmin(self::ALAMAT . "/{$rina}/hapus"), self::ALAMAT, 'Narahubung "Bu Rina" sudah dihapus.');
        $this->dontSeeInDatabase('tautan', ['id' => $rina]);

        $this->assertBerhasilDialihkan($this->kirimSebagaiAdmin(self::ALAMAT . "/{$ig}/hapus"), self::ALAMAT, 'Media sosial "Instagram" sudah dihapus.');
        $this->seeNumRecords(0, 'tautan', []);
    }
}
