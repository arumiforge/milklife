<?php

use App\Models\JadwalModel;
use Tests\Support\KirimFormulir;
use Tests\Support\UjiBasisData;

/**
 * Halaman pengaturan jadwal (F2-06): `/admin/pengaturan/jadwal`.
 *
 * @internal
 */
final class PengaturanJadwalTest extends UjiBasisData
{
    use KirimFormulir;

    private const ALAMAT = 'admin/pengaturan/jadwal';

    /**
     * Isian formulir yang benar.
     *
     * @var array<string, string>
     */
    private const ISIAN = [
        'judul'      => 'Technical Meeting',
        'keterangan' => 'Wajib dihadiri satu official tiap sekolah.',
        'waktu'      => '2026-12-05T09:00',
        'tempat'     => 'Aula Sekolah Karangan',
        'tautan'     => '',
        'urutan'     => '2',
        'tampil'     => '1',
    ];

    private function buatJadwal(array $data = []): int
    {
        return (int) model(JadwalModel::class)->insert($data + ['judul' => 'Sosialisasi Daring', 'urutan' => 1]);
    }

    // ---------- Akses ----------

    public function testTertutupUntukYangBelumMasuk(): void
    {
        $id = $this->buatJadwal();

        foreach ([self::ALAMAT, self::ALAMAT . '/tambah', self::ALAMAT . "/{$id}/ubah"] as $alamat) {
            $this->assertBelumMasukDialihkan($alamat);
        }

        foreach ([self::ALAMAT, self::ALAMAT . "/{$id}", self::ALAMAT . "/{$id}/hapus"] as $alamat) {
            $this->assertBelumMasukDialihkan($alamat, 'post');
        }

        $this->seeNumRecords(1, 'jadwal', []);
        $this->seeInDatabase('jadwal', ['id' => $id, 'judul' => 'Sosialisasi Daring']);
    }

    public function testTokenCsrfWajib(): void
    {
        $id = $this->buatJadwal();

        $this->assertTokenCsrfWajib(self::ALAMAT, self::ISIAN);
        $this->assertTokenCsrfWajib(self::ALAMAT . "/{$id}", self::ISIAN);
        $this->assertTokenCsrfWajib(self::ALAMAT . "/{$id}/hapus");

        $this->seeNumRecords(1, 'jadwal', []);
        $this->seeInDatabase('jadwal', ['id' => $id, 'judul' => 'Sosialisasi Daring']);
    }

    // ---------- Daftar ----------

    public function testDaftarKosongMenampilkanAjakanMenambah(): void
    {
        $hasil = $this->bukaSebagaiAdmin(self::ALAMAT);

        $hasil->assertOK();
        $hasil->assertSee('Belum ada jadwal');
        $xpath = $this->xpathDari($hasil);
        $this->assertSame(1, $xpath->query('//nav[@aria-label="Navigasi pengaturan"]//a[@aria-current="page"][contains(., "Jadwal")]')->length);
        $this->assertSame(1, $xpath->query('//main//a[@href="' . site_url(self::ALAMAT . '/tambah') . '"]')->length);
        $this->assertSame(0, $xpath->query('//table')->length);
    }

    public function testDaftarMenampilkanJadwalMenurutUrutan(): void
    {
        $kedua = $this->buatJadwal([
            'judul'  => 'Technical Meeting',
            'urutan' => 2,
            'waktu'  => '2026-12-05 09:00:00',
            'tempat' => 'Aula <Karangan>',
        ]);
        $pertama = $this->buatJadwal([
            'judul'  => 'Sosialisasi Daring',
            'urutan' => 1,
            'tautan' => 'https://zoom.us/j/1234567890',
            'tampil' => 0,
        ]);

        $hasil = $this->bukaSebagaiAdmin(self::ALAMAT);
        $hasil->assertOK();

        $xpath = $this->xpathDari($hasil);
        $baris = $xpath->query('//table/tbody/tr');
        $this->assertSame(2, $baris->length);
        $this->assertSame((string) $pertama, $baris->item(0)->getAttribute('data-id'));
        $this->assertSame((string) $kedua, $baris->item(1)->getAttribute('data-id'));

        // Waktu dalam bahasa Indonesia, waktu kosong, isi di-escape, tautan, tanda tampil.
        $this->assertStringContainsString('5 Desember 2026 pukul 09.00 WIB', $baris->item(1)->textContent);
        $this->assertStringContainsString('Aula <Karangan>', $baris->item(1)->textContent);
        $this->assertStringContainsString('Waktu belum pasti', $baris->item(0)->textContent);
        $this->assertStringContainsString('Disembunyikan', $baris->item(0)->textContent);
        $this->assertStringContainsString('Tampil', $baris->item(1)->textContent);
        $tautan = $xpath->query('.//a[@href="https://zoom.us/j/1234567890"]', $baris->item(0))->item(0);
        $this->assertNotNull($tautan);
        $this->assertSame('noopener noreferrer', $tautan->getAttribute('rel'));
        $this->assertStringNotContainsString('Aula <Karangan>', (string) $hasil->getBody(), 'Tempat harus di-escape.');

        // Hapus lewat POST, token CSRF, dan jendela konfirmasi.
        $hapus = $xpath->query('.//form[@data-konfirmasi]', $baris->item(1))->item(0);
        $this->assertNotNull($hapus);
        $this->assertSame('post', $hapus->getAttribute('method'));
        $this->assertSame(site_url(self::ALAMAT . "/{$kedua}/hapus"), $hapus->getAttribute('action'));
        $this->assertStringContainsString('Technical Meeting', $hapus->getAttribute('data-konfirmasi'));
        $this->assertSame(1, $xpath->query('.//input[@name="' . config('Security')->tokenName . '"]', $hapus)->length);
        $hasil->assertSee(aset('js/konfirmasi.js'));
        $this->assertSame(1, $xpath->query('.//a[@href="' . site_url(self::ALAMAT . "/{$kedua}/ubah") . '"]', $baris->item(1))->length);
    }

    // ---------- Tambah ----------

    public function testFormulirTambahKosongDenganUrutanBerikutnya(): void
    {
        $this->buatJadwal(['urutan' => 3]);

        $hasil = $this->bukaSebagaiAdmin(self::ALAMAT . '/tambah');
        $hasil->assertOK();

        $xpath  = $this->xpathDari($hasil);
        $form   = $xpath->query('//main//form[@method="post"]')->item(0);
        $this->assertSame(site_url(self::ALAMAT), $form->getAttribute('action'));
        $this->assertSame('', $xpath->query('//input[@name="judul"]')->item(0)->getAttribute('value'));
        $this->assertSame('datetime-local', $xpath->query('//input[@name="waktu"]')->item(0)->getAttribute('type'));
        $this->assertSame('4', $xpath->query('//input[@name="urutan"]')->item(0)->getAttribute('value'));
        $this->assertTrue($xpath->query('//input[@name="tampil"]')->item(0)->hasAttribute('checked'));
        $this->assertSame(1, $xpath->query('//nav[@aria-label="Navigasi pengaturan"]//a[@aria-current="page"][contains(., "Jadwal")]')->length);
    }

    public function testTambahBerhasil(): void
    {
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, [
            'judul'  => '  Sosialisasi Daring  ',
            'waktu'  => '2026-11-20T13:30',
            'tautan' => 'meet.google.com/abc-defg-hij',
            'urutan' => '1',
            'tampil' => '1',
        ]);

        $this->assertBerhasilDialihkan($hasil, self::ALAMAT, 'Jadwal "Sosialisasi Daring" tersimpan.');
        $this->seeInDatabase('jadwal', [
            'judul'      => 'Sosialisasi Daring',
            'keterangan' => null,
            'waktu'      => '2026-11-20 13:30:00',
            'tempat'     => null,
            'tautan'     => 'https://meet.google.com/abc-defg-hij',
            'urutan'     => 1,
            'tampil'     => 1,
        ]);
    }

    public function testTambahHanyaJudulDanTanpaCentangTampil(): void
    {
        $this->buatJadwal(['urutan' => 6]);

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, ['judul' => 'Pengambilan Nomor Dada', 'urutan' => '']);

        $this->assertBerhasilDialihkan($hasil, self::ALAMAT);
        $this->seeInDatabase('jadwal', ['judul' => 'Pengambilan Nomor Dada', 'waktu' => null, 'urutan' => 7, 'tampil' => 0]);
    }

    public function testIsianSalahDitampilkanUlang(): void
    {
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, [
            'judul'      => '',
            'keterangan' => str_repeat('a', 1001),
            'waktu'      => '2026-02-30T09:00',
            'tempat'     => str_repeat('t', 151),
            'tautan'     => 'javascript:alert(1)',
            'urutan'     => '-1',
            'tampil'     => '1',
        ]);

        $this->assertIsianSalahDitampilkanUlang($hasil, ['judul', 'keterangan', 'waktu', 'tempat', 'tautan', 'urutan']);
        $hasil->assertSee('Ada 6 isian yang perlu diperbaiki:');
        $hasil->assertSee('Judul belum diisi.');
        $hasil->assertSee('Waktu belum benar.');
        $hasil->assertSee('Tautan belum benar.');
        $hasil->assertSee('Urutan belum benar.');
        $this->assertSame('javascript:alert(1)', $this->xpathDari($hasil)->query('//input[@name="tautan"]')->item(0)->getAttribute('value'));
        $this->seeNumRecords(0, 'jadwal', []);
    }

    public function testJudulTerlaluPanjangDanUrutanTerlaluBesarDitolak(): void
    {
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, ['judul' => str_repeat('j', 151), 'urutan' => '10000'] + self::ISIAN);

        $this->assertIsianSalahDitampilkanUlang($hasil, ['judul', 'urutan']);
        $hasil->assertSee('Judul terlalu panjang.');
        $this->seeNumRecords(0, 'jadwal', []);
    }

    // ---------- Ubah ----------

    public function testFormulirUbahTerisi(): void
    {
        $id = $this->buatJadwal([
            'judul'      => 'Technical Meeting',
            'keterangan' => 'Bawa surat tugas.',
            'waktu'      => '2026-12-05 09:00:00',
            'tempat'     => 'Aula',
            'tautan'     => 'https://zoom.us/j/1',
            'urutan'     => 3,
            'tampil'     => 0,
        ]);

        $hasil = $this->bukaSebagaiAdmin(self::ALAMAT . "/{$id}/ubah");
        $hasil->assertOK();

        $xpath = $this->xpathDari($hasil);
        $this->assertSame(site_url(self::ALAMAT . "/{$id}"), $xpath->query('//main//form[@method="post"]')->item(0)->getAttribute('action'));
        $this->assertSame('Technical Meeting', $xpath->query('//input[@name="judul"]')->item(0)->getAttribute('value'));
        $this->assertSame('2026-12-05T09:00', $xpath->query('//input[@name="waktu"]')->item(0)->getAttribute('value'));
        $this->assertSame('https://zoom.us/j/1', $xpath->query('//input[@name="tautan"]')->item(0)->getAttribute('value'));
        $this->assertSame('Bawa surat tugas.', $xpath->query('//textarea[@name="keterangan"]')->item(0)->textContent);
        $this->assertSame('3', $xpath->query('//input[@name="urutan"]')->item(0)->getAttribute('value'));
        $this->assertFalse($xpath->query('//input[@name="tampil"]')->item(0)->hasAttribute('checked'));
        $this->assertSame(1, $xpath->query('//nav[@aria-label="Navigasi pengaturan"]//a[@aria-current="page"][contains(., "Jadwal")]')->length);
    }

    public function testUbahBerhasilDanMengosongkanKotak(): void
    {
        $id = $this->buatJadwal(['tempat' => 'Aula', 'waktu' => '2026-12-05 09:00:00', 'keterangan' => 'Lama']);

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . "/{$id}", [
            'judul'  => 'Sosialisasi Daring Kedua',
            'waktu'  => '',
            'tempat' => '',
            'tautan' => 'https://www.youtube.com/live/abc',
            'urutan' => '5',
        ]);

        $this->assertBerhasilDialihkan($hasil, self::ALAMAT, 'Perubahan jadwal "Sosialisasi Daring Kedua" tersimpan.');
        $this->seeInDatabase('jadwal', [
            'id'         => $id,
            'judul'      => 'Sosialisasi Daring Kedua',
            'keterangan' => null,
            'waktu'      => null,
            'tempat'     => null,
            'tautan'     => 'https://www.youtube.com/live/abc',
            'urutan'     => 5,
            'tampil'     => 0,
        ]);
    }

    public function testUbahDenganIsianSalahTidakMenyimpan(): void
    {
        $id = $this->buatJadwal(['judul' => 'Judul Lama']);

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . "/{$id}", ['judul' => '', 'tautan' => 'ftp://contoh.id/berkas'] + self::ISIAN);

        $this->assertIsianSalahDitampilkanUlang($hasil, ['judul', 'tautan']);
        $this->assertSame(site_url(self::ALAMAT . "/{$id}"), $this->xpathDari($hasil)->query('//main//form[@method="post"]')->item(0)->getAttribute('action'));
        $this->seeInDatabase('jadwal', ['id' => $id, 'judul' => 'Judul Lama']);
    }

    public function testJadwalYangTidakAdaDialihkanDenganPesan(): void
    {
        $pesan = 'Jadwal itu tidak ditemukan, mungkin sudah dihapus. Pilih jadwal dari daftar di bawah.';

        $this->assertGagalDialihkan($this->bukaSebagaiAdmin(self::ALAMAT . '/999/ubah'), self::ALAMAT, $pesan);
        $this->assertGagalDialihkan($this->kirimSebagaiAdmin(self::ALAMAT . '/999', self::ISIAN), self::ALAMAT, $pesan);
        $this->assertGagalDialihkan($this->kirimSebagaiAdmin(self::ALAMAT . '/999/hapus'), self::ALAMAT, $pesan);
        $this->seeNumRecords(0, 'jadwal', []);
    }

    // ---------- Hapus ----------

    public function testHapusBerhasil(): void
    {
        $id   = $this->buatJadwal(['judul' => 'Technical Meeting']);
        $lain = $this->buatJadwal(['judul' => 'Sosialisasi Daring']);

        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT . "/{$id}/hapus");

        $this->assertBerhasilDialihkan($hasil, self::ALAMAT, 'Jadwal "Technical Meeting" sudah dihapus.');
        $this->dontSeeInDatabase('jadwal', ['id' => $id]);
        $this->seeInDatabase('jadwal', ['id' => $lain]);
    }

    public function testHapusLewatGetTidakAda(): void
    {
        $id = $this->buatJadwal();

        $this->expectException(CodeIgniter\Exceptions\PageNotFoundException::class);

        try {
            $this->bukaSebagaiAdmin(self::ALAMAT . "/{$id}/hapus");
        } finally {
            $this->seeInDatabase('jadwal', ['id' => $id]);
        }
    }
}
