<?php

use App\Controllers\LayarController;
use App\Libraries\Hasil\HasilLomba;
use App\Libraries\Hasil\PapanVideotron;
use App\Libraries\Panel\StatusPembayaran;
use CodeIgniter\Config\Services;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\I18n\Time;
use Tests\Support\KirimFormulir;
use Tests\Support\PanelDataContoh;
use Tests\Support\UjiBasisData;

/**
 * Papan videotron `/layar` (F9-13, K141): sakelar bawaan mati, hanya data
 * terbit, JSON yang bisa ditembolok, jadwal dan status dari `JadwalPertandingan` (F9-07), latar
 * G9-06 hanya bila berkasnya ada, dan Pengaturan > Videotron.
 *
 * Tampilan di berbagai ukuran layar diuji terpisah dengan Playwright
 * (`npm run uji:layar`, `alat/uji-layar.mjs`).
 *
 * @internal
 */
final class PapanVideotronTest extends UjiBasisData
{
    use KirimFormulir;
    use PanelDataContoh;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siapkanDataPanel();

        Services::resetSingle('throttler');
        service('cache')->clean();
        $_SERVER['SCRIPT_NAME'] ??= '/index.php';
    }

    protected function tearDown(): void
    {
        $this->bersihkanBerkasPanel();
        service('cache')->clean();
        parent::tearDown();
    }

    public function testSakelarBawaanMatiMenjawab404(): void
    {
        $this->assertFalse(PapanVideotron::tampil());

        foreach (['layar', 'layar/data'] as $alamat) {
            try {
                $this->get($alamat);
                $this->fail($alamat . ' seharusnya 404 selama sakelar mati.');
            } catch (PageNotFoundException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testHanyaHasilTerbitDanKlasemen(): void
    {
        [$terbit, $belum] = $this->duaNomorBerhasil();
        setting('Videotron.tampil', true);

        $halaman = $this->get('layar');
        $halaman->assertOK();
        $halaman->assertHeader('X-Robots-Tag', 'noindex');
        $layar = $this->xpathDari($halaman)->query('//*[@data-layar]')->item(0);
        $this->assertInstanceOf(DOMElement::class, $layar);
        $this->assertSame(site_url('layar/data'), $layar->getAttribute('data-sumber'));
        $this->assertSame((string) LayarController::SEGAR_DETIK, $layar->getAttribute('data-segar'));
        $this->assertStringNotContainsString('9911', (string) $halaman->response()->getBody(), 'NISN tidak boleh tampil.');

        $awal = json_decode($layar->getAttribute('data-awal'), true);
        $this->assertSame([$terbit], array_column($awal['hasil'], 'id'));

        $jawaban = $this->get('layar/data');
        $jawaban->assertOK();
        $jawaban->assertHeader('Cache-Control', 'public, max-age=' . LayarController::TEMBOLOK_DETIK);
        $data = json_decode($jawaban->getJSON(), true);

        $this->assertSame([$terbit], array_column($data['hasil'], 'id'));
        $this->assertNotContains($belum, array_column($data['hasil'], 'id'));
        $this->assertSame('Final', $data['hasil'][0]['babak']);
        $this->assertSame([1, 2, 3], array_column($data['hasil'][0]['baris'], 'peringkat'));
        $this->assertSame('9,40', $data['hasil'][0]['baris'][0]['prestasi']);
        $this->assertArrayNotHasKey('nisn', $data['hasil'][0]['baris'][0]);
        $this->assertSame(['hasil', 'juara_umum', 'berlangsung', 'jadwal'], $data['pengaturan']['isi']);
        $this->assertSame(PapanVideotron::LAMA_BAWAAN, $data['pengaturan']['lama']);

        // Juara umum dari klasemen nomor terbit saja, tanpa NPSN dan rincian piala.
        $this->assertCount(1, $data['juara_umum']);
        $this->assertSame('medali', $data['juara_umum'][0]['metode'], 'Metode ikut terkirim untuk layar sempit.');
        $baris = $data['juara_umum'][0]['baris'];
        $this->assertSame('SD KARANGAN MAJU', $baris[0]['sekolah']);
        $this->assertSame([1, 1, 1], [$baris[0]['emas'], $baris[0]['perak'], $baris[0]['perunggu']]);
        $this->assertArrayNotHasKey('npsn', $baris[0]);
        $this->assertArrayNotHasKey('piala', $baris[0]);

        $this->assertSame([], $data['jadwal'], 'Belum ada babak terjadwal.');
        $this->assertSame([], $data['berlangsung']);

        // Tanpa perubahan: ETag sama dijawab 304 tanpa isi.
        $etag = $jawaban->response()->getHeaderLine('ETag');
        $this->assertSame('"' . $data['versi'] . '"', $etag);
        $ulang = $this->withHeaders(['If-None-Match' => $etag])->get('layar/data');
        $ulang->assertStatus(304);
        $this->assertSame('', (string) $ulang->response()->getBody());
    }

    public function testJadwalBerikutnyaDanSedangBerlangsung(): void
    {
        [$terbit, $belum] = $this->duaNomorBerhasil();

        $lain = $this->idInduk['frog'];
        $babakLain = (new HasilLomba())->tambahBabak($lain, 'Penyisihan', false, 'kosong')['id'];
        $finalLain = (new HasilLomba())->tambahBabak($lain, 'Final', true, 'kosong')['id'];
        $babakBelum = (int) $this->db->table('babak')->where('nomor_lomba_id', $belum)->where('final', 1)->get()->getRow()->id;
        $babakTerbit = (int) $this->db->table('babak')->where('nomor_lomba_id', $terbit)->where('final', 1)->get()->getRow()->id;

        $atur = function (int $babak, string $tanggal, string $jam, string $lokasi): void {
            $this->db->table('babak')->where('id', $babak)->update(['tanggal' => $tanggal, 'jam_mulai' => $jam, 'lokasi' => $lokasi]);
        };

        // Status sama dengan /jadwal (`JadwalPertandingan::status()`): nomor terbit
        // berhasil = Selesai; jam mulai lewat = Berlangsung; selain itu Belum mulai.
        $atur($babakTerbit, '2026-12-09', '08:00:00', 'Lintasan');
        $atur($babakBelum, '2026-12-09', '09:00:00', 'Lintasan');
        $atur($babakLain, '2026-12-09', '09:10:00', 'Bak lompat');
        $atur($finalLain, '2026-12-10', '08:30:00', 'Bak lompat');

        $sekarang = Time::createFromFormat('Y-m-d H:i', '2026-12-09 09:30', 'Asia/Jakarta');
        $data     = (new PapanVideotron(null, $sekarang))->data();

        $this->assertSame([$babakBelum, $babakLain], array_column($data['berlangsung'], 'id'));
        $this->assertSame('09.00', $data['berlangsung'][0]['jam']);
        $this->assertSame('Lintasan', $data['berlangsung'][0]['lokasi']);
        $this->assertSame([$finalLain], array_column($data['jadwal'], 'id'));
        $this->assertSame('Besok', $data['jadwal'][0]['hari']);
        $this->assertSame('08.30', $data['jadwal'][0]['jam']);

        // Hari berikutnya: babak kemarin tidak lagi tampil sebagai sedang berlangsung.
        $besok = Time::createFromFormat('Y-m-d H:i', '2026-12-10 07:00', 'Asia/Jakarta');
        $data  = (new PapanVideotron(null, $besok))->data();
        $this->assertSame([], $data['berlangsung']);
        $this->assertSame('Hari ini', $data['jadwal'][0]['hari']);
    }

    public function testLatarGambarHanyaBilaBerkasnyaAda(): void
    {
        setting('Videotron.tampil', true);
        $folder = FCPATH . 'aset/gambar/layar/';
        $berkas = $folder . LayarController::GAMBAR_LATAR['dasar'];
        $buat   = ! is_dir($folder);

        $layar = $this->xpathDari($this->get('layar'))->query('//*[@data-layar]')->item(0);
        $this->assertInstanceOf(DOMElement::class, $layar);

        if (! is_file($berkas)) {
            $this->assertFalse($layar->hasAttribute('data-latar-dasar'));

            if ($buat) {
                mkdir($folder, 0775, true);
            }
            file_put_contents($berkas, 'uji');

            try {
                service('cache')->clean();
                $layar = $this->xpathDari($this->get('layar'))->query('//*[@data-layar]')->item(0);
                $this->assertInstanceOf(DOMElement::class, $layar);
                $this->assertStringContainsString('gambar/layar/latar-dasar.webp', $layar->getAttribute('data-latar-dasar'));
            } finally {
                unlink($berkas);

                if ($buat) {
                    rmdir($folder);
                }
            }
        } else {
            $this->assertTrue($layar->hasAttribute('data-latar-dasar'));
        }
    }

    public function testPengaturanVideotron(): void
    {
        $this->assertBelumMasukDialihkan('admin/pengaturan/videotron');
        $this->assertBelumMasukDialihkan('admin/pengaturan/videotron', 'post');
        $this->assertTokenCsrfWajib('admin/pengaturan/videotron');

        $daftar = $this->bukaSebagaiAdmin('admin/pengaturan');
        $daftar->assertOK();
        $this->assertSame(1, $this->xpathDari($daftar)->query('//a[@data-kunci="videotron"]')->length);

        $halaman = $this->bukaSebagaiAdmin('admin/pengaturan/videotron');
        $halaman->assertOK();
        $halaman->assertSee('Tampilkan papan hasil di /layar');
        $this->assertSame(0, $this->xpathDari($halaman)->query('//input[@id="tampil"][@checked]')->length);

        $salah = $this->kirimSebagaiAdmin('admin/pengaturan/videotron', ['tampil' => '1', 'lama' => '2', 'isi' => []]);
        $this->assertIsianSalahDitampilkanUlang($salah, ['lama']);
        $salah->assertSee('Pilih paling sedikit satu isi');
        $this->assertFalse(PapanVideotron::tampil());

        cache()->save(LayarController::KUNCI_TEMBOLOK, ['versi' => 'lama'], 60);
        $simpan = $this->kirimSebagaiAdmin('admin/pengaturan/videotron', ['tampil' => '1', 'lama' => '20', 'isi' => ['jadwal', 'hasil', 'palsu']]);
        $this->assertBerhasilDialihkan($simpan, 'admin/pengaturan/videotron');
        $this->assertNull(cache(LayarController::KUNCI_TEMBOLOK), 'Tembolok data layar dibuang setelah pengaturan disimpan.');

        $this->assertTrue(PapanVideotron::tampil());
        $this->assertSame(['isi' => ['jadwal', 'hasil'], 'lama' => 20], PapanVideotron::pengaturan());
        $this->get('layar')->assertOK();

        $this->kirimSebagaiAdmin('admin/pengaturan/videotron', ['lama' => '12', 'isi' => ['hasil']]);
        $this->assertFalse(PapanVideotron::tampil());
    }

    public function testRapikanIsiDanLama(): void
    {
        $this->assertSame(['juara_umum', 'hasil'], PapanVideotron::rapikanIsi(' juara_umum,hasil,,hasil,lain'));
        $this->assertSame([], PapanVideotron::rapikanIsi(null));
        $this->assertSame(PapanVideotron::LAMA_MIN, PapanVideotron::rapikanLama('1'));
        $this->assertSame(PapanVideotron::LAMA_MAKS, PapanVideotron::rapikanLama(999));
        $this->assertSame(PapanVideotron::LAMA_BAWAAN, PapanVideotron::rapikanLama('abc'));
    }

    /**
     * Lari 60 m putra terbit (Final berperingkat), Lari 60 m putri belum terbit.
     *
     * @return array{0: int, 1: int} id nomor terbit, id nomor belum terbit
     */
    private function duaNomorBerhasil(): array
    {
        $p = $this->buatPendaftaranPanel(['atlet' => [
            $this->atletPanel(['nama' => 'Budi Karangan', 'nisn' => '9911100001']),
            $this->atletPanel(['nama' => 'Andi Karangan', 'nisn' => '9911100002']),
            $this->atletPanel(['nama' => 'Cahyo Karangan', 'nisn' => '9911100003']),
            $this->atletPanel(['nama' => 'Siti Karangan', 'nisn' => '9911100004', 'jenis_kelamin' => 'P', 'nomor' => ['lari60putri']]),
        ]]);
        (new StatusPembayaran())->tandaiLunas($p['id']);

        $hasil  = new HasilLomba();
        $terbit = $this->idInduk['lari60putra'];
        $belum  = $this->idInduk['lari60putri'];

        $final = $hasil->tambahBabak($terbit, 'Final', true, 'semua')['id'];
        $isian = [];

        foreach ($this->db->table('hasil')->where('babak_id', $final)->orderBy('atlet_id')->get()->getResultArray() as $i => $h) {
            $isian[(int) $h['id']] = ['prestasi' => ['9,40', '9,80', '10,10'][$i], 'keterangan' => '', 'peringkat' => ''];
        }
        $this->assertSame([], $hasil->simpanHasil($final, $isian));
        $hasil->terbitkan($terbit, true);

        $finalBelum = $hasil->tambahBabak($belum, 'Final', true, 'semua')['id'];
        $isianBelum = [];

        foreach ($this->db->table('hasil')->where('babak_id', $finalBelum)->get()->getResultArray() as $h) {
            $isianBelum[(int) $h['id']] = ['prestasi' => '11,00', 'keterangan' => '', 'peringkat' => ''];
        }
        $hasil->simpanHasil($finalBelum, $isianBelum);

        return [$terbit, $belum];
    }
}
