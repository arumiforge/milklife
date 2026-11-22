<?php

use App\Controllers\LayarController;
use App\Libraries\Hasil\HasilLomba;
use App\Libraries\Hasil\PapanVideotron;
use App\Libraries\Hasil\SeriLintasan;
use App\Libraries\Hasil\TampilanVideotron;
use App\Libraries\Panel\StatusPembayaran;
use CodeIgniter\Config\Services;
use CodeIgniter\Exceptions\PageNotFoundException;
use Tests\Support\KirimFormulir;
use Tests\Support\PanelDataContoh;
use Tests\Support\UnggahanSementara;
use Tests\Support\UjiBasisData;

/**
 * Daftar tampilan videotron `/layar` dan tampilan tetap `/layar/<jenis>/…`
 * (F11-09, K173): sakelar `Videotron.tampil` berlaku untuk semua alamat,
 * daftar per hari dan sesi, daftar start per seri setelah dikunci lalu hasil
 * seri setelah terbit, hasil per sesi, kelompok umur, nomor, dan klasemen;
 * hanya data terbit; JSON bertembolok dengan `ETag`; alamat salah 404.
 *
 * @internal
 */
final class TampilanVideotronTest extends UjiBasisData
{
    use KirimFormulir;
    use PanelDataContoh;
    use UnggahanSementara;

    private const TANGGAL = '2026-12-09';

    protected function setUp(): void
    {
        parent::setUp();
        $this->siapkanDataPanel();

        Services::resetSingle('throttler');
        service('cache')->clean();
    }

    protected function tearDown(): void
    {
        $this->bersihkanBerkasPanel();
        service('cache')->clean();
        parent::tearDown();
    }

    public function testBagianHari(): void
    {
        $this->assertSame('pagi', TampilanVideotron::bagianHari('07:00'));
        $this->assertSame('pagi', TampilanVideotron::bagianHari('10:59'));
        $this->assertSame('siang', TampilanVideotron::bagianHari('11:00'));
        $this->assertSame('siang', TampilanVideotron::bagianHari('14:59:00'));
        $this->assertSame('sore', TampilanVideotron::bagianHari('15:00'));
        $this->assertSame('tanpa-jam', TampilanVideotron::bagianHari(null));
    }

    public function testSakelarMatiSemuaAlamat404(): void
    {
        $babak = $this->siapkanBabak();

        foreach (['layar', 'layar/seri/' . $babak['penyisihan'] . '/1', 'layar/data/seri/' . $babak['penyisihan'] . '/1', 'layar/nomor/' . $babak['nomor'], 'layar/data/klasemen/A'] as $alamat) {
            $this->assert404($alamat);
        }
    }

    public function testDaftarPerHariSesiSeriDanNomor(): void
    {
        $babak = $this->siapkanBabak();
        setting('Videotron.tampil', true);

        $halaman = $this->get('layar');
        $halaman->assertOK();
        $halaman->assertHeader('X-Robots-Tag', 'noindex');
        $halaman->assertSee('Tampilan videotron');
        $this->assertStringNotContainsString('9922', (string) $halaman->response()->getBody(), 'NISN tidak boleh tampil.');

        $x = $this->xpathDari($halaman);
        $hari = '//*[@data-hari-layar="' . self::TANGGAL . '"]';
        $this->assertSame(['pagi', 'sore'], array_map(static fn (DOMElement $e): string => $e->getAttribute('data-sesi-layar'), iterator_to_array($x->query($hari . '//*[@data-sesi-layar]'))));
        $this->assertSame(1, $x->query($hari . '//*[@data-tautan-layar="layar/sesi/' . self::TANGGAL . '/pagi"]')->length);

        // Seri hanya untuk babak yang daftar serinya dikunci.
        $this->assertSame(1, $x->query('//*[@data-babak-layar="' . $babak['penyisihan'] . '"]//*[@data-tautan-layar="layar/seri/' . $babak['penyisihan'] . '/1"]')->length);
        $this->assertSame(1, $x->query('//*[@data-babak-layar="' . $babak['penyisihan'] . '"]//*[@data-tautan-layar="layar/seri/' . $babak['penyisihan'] . '/2"]')->length);
        $this->assertSame(0, $x->query('//*[@data-babak-layar="' . $babak['final'] . '"]//*[starts-with(@data-tautan-layar, "layar/seri/")]')->length);

        // Kelompok umur dan nomor, juga yang belum terbit; papan putar tetap ada.
        $this->assertSame(1, $x->query('//*[@data-tautan-layar="layar/kelompok-umur/' . $this->idInduk['ku10'] . '"]')->length);
        $this->assertSame(1, $x->query('//*[@data-tautan-layar="layar/nomor/' . $babak['nomor'] . '"]')->length);
        $this->assertSame(1, $x->query('//*[@data-tautan-layar="layar/putar"]//a[@href="' . site_url('layar/putar') . '"]')->length);
        $this->assertSame(1, $x->query('//button[@data-salin-alamat="' . site_url('layar/nomor/' . $babak['nomor']) . '"]')->length);
        $this->assertSame(1, $x->query('//script[contains(@src, "layar-daftar.js")]')->length);
        $halaman->assertSee('Klasemen muncul di sini setelah ada hasil Final yang terbit.');
    }

    public function testSeriDaftarStartLaluHasilSetelahTerbit(): void
    {
        $babak = $this->siapkanBabak();
        setting('Videotron.tampil', true);
        $alamat = 'seri/' . $babak['penyisihan'] . '/2';

        // Belum terbit: daftar start seri 2 urut lintasan, tanpa NISN.
        $halaman = $this->get('layar/' . $alamat);
        $halaman->assertOK();
        $layar = $this->xpathDari($halaman)->query('//*[@data-layar]')->item(0);
        $this->assertInstanceOf(DOMElement::class, $layar);
        $this->assertSame(site_url('layar/data/' . $alamat), $layar->getAttribute('data-sumber'));

        $data = $this->dataTampilan($alamat);
        $this->assertTrue($data['pengaturan']['tetap']);
        $this->assertSame([], $data['hasil']);
        $this->assertCount(1, $data['start']);
        $this->assertTrue($data['start'][0]['lintasan']);
        $this->assertStringContainsString('Seri 2', $data['judul']);
        $diSeri2 = $this->db->table('hasil')->where('babak_id', $babak['penyisihan'])->where('seri', 2)->orderBy('lintasan')->get()->getResultArray();
        $this->assertSame(array_map('intval', array_column($diSeri2, 'lintasan')), array_column($data['start'][0]['baris'], 'lintasan'));
        $this->assertArrayNotHasKey('nisn', $data['start'][0]['baris'][0]);

        // Hasil tersimpan tetapi belum terbit: tetap daftar start.
        $this->isiHasil($babak['penyisihan']);
        service('cache')->clean();
        $this->assertSame([], $this->dataTampilan($alamat)['hasil']);

        // Terbit: hasil seri 2 saja, peringkat di dalam seri mulai dari 1.
        (new HasilLomba())->terbitkan($babak['nomor'], true);
        service('cache')->clean();
        $data = $this->dataTampilan($alamat);
        $this->assertSame([], $data['start']);
        $this->assertCount(1, $data['hasil']);
        $this->assertCount(count($diSeri2), $data['hasil'][0]['baris']);
        $this->assertSame(range(1, count($diSeri2)), array_column($data['hasil'][0]['baris'], 'peringkat'));
        $this->assertNotSame('', $data['hasil'][0]['baris'][0]['prestasi']);
    }

    public function testSesiNomorKelompokUmurDanKlasemen(): void
    {
        $babak = $this->siapkanBabak();
        setting('Videotron.tampil', true);
        $this->isiHasil($babak['penyisihan']);
        $this->isiHasil($babak['final']);

        // Belum terbit: semua tampilan kosong dengan keterangan.
        foreach (['sesi/' . self::TANGGAL . '/pagi', 'nomor/' . $babak['nomor'], 'kelompok-umur/' . $this->idInduk['ku10']] as $alamat) {
            $data = $this->dataTampilan($alamat);
            $this->assertSame([], $data['hasil'], $alamat);
            $this->assertNotSame('', $data['kosong']);
        }

        (new HasilLomba())->terbitkan($babak['nomor'], true);
        service('cache')->clean();

        $pagi = $this->dataTampilan('sesi/' . self::TANGGAL . '/pagi');
        $this->assertSame(['babak:' . $babak['penyisihan']], array_column($pagi['hasil'], 'kunci'));
        $this->assertStringContainsString('Sesi pagi', $pagi['judul']);
        $sore = $this->dataTampilan('sesi/' . self::TANGGAL . '/sore');
        $this->assertSame(['babak:' . $babak['final']], array_column($sore['hasil'], 'kunci'));
        $this->assertSame([], $this->dataTampilan('sesi/' . self::TANGGAL . '/siang')['hasil']);

        // Per nomor dan per kelompok umur: Final di atas.
        $nomor = $this->dataTampilan('nomor/' . $babak['nomor']);
        $this->assertSame(['babak:' . $babak['final'], 'babak:' . $babak['penyisihan']], array_column($nomor['hasil'], 'kunci'));
        $this->assertSame([1, 2, 3, 4, 5, 6], array_column($nomor['hasil'][0]['baris'], 'peringkat'));
        $ku = $this->dataTampilan('kelompok-umur/' . $this->idInduk['ku10']);
        $this->assertSame(array_column($nomor['hasil'], 'kunci'), array_column($ku['hasil'], 'kunci'));
        $this->assertSame([], $this->dataTampilan('kelompok-umur/' . $this->idInduk['ku12'])['hasil']);

        // Klasemen dari HasilLomba::klasemen(), lewat kunci yang dipakai daftar.
        $klasemen = (new PapanVideotron())->juaraUmum();
        $this->assertNotSame([], $klasemen);
        $data = $this->dataTampilan('klasemen/' . $klasemen[0]['kunci']);
        $this->assertSame(['juara_umum'], $data['pengaturan']['isi']);
        $this->assertSame($klasemen[0]['baris'], $data['juara_umum'][0]['baris']);
        $this->assertArrayNotHasKey('npsn', $data['juara_umum'][0]['baris'][0]);
        $this->assertSame([], $this->dataTampilan('klasemen/TIDAKADA')['juara_umum']);

        $daftar = $this->xpathDari($this->get('layar'));
        $this->assertSame(1, $daftar->query('//*[@data-tautan-layar="layar/klasemen/' . $klasemen[0]['kunci'] . '"]')->length);
    }

    public function testNomorTidakDilombakanKeluarDariVideotron(): void
    {
        $babak = $this->siapkanBabak();
        setting('Videotron.tampil', true);
        $this->isiHasil($babak['final']);
        (new HasilLomba())->terbitkan($babak['nomor'], true);
        $this->db->table('nomor_lomba')->where('id', $this->idInduk['lari60putri'])->update(['tidak_dilombakan' => 1, 'alasan_tidak_dilombakan' => 'Peserta kurang dari 3']);
        $this->db->table('nomor_lomba')->where('id', $babak['nomor'])->update(['tidak_dilombakan' => 1, 'alasan_tidak_dilombakan' => 'Peserta kurang dari 3']);
        service('cache')->clean();

        $x = $this->xpathDari($this->get('layar'));
        $this->assertSame(0, $x->query('//*[@data-tautan-layar="layar/nomor/' . $this->idInduk['lari60putri'] . '"]')->length);
        $this->assertSame(0, $x->query('//*[@data-tautan-layar="layar/nomor/' . $babak['nomor'] . '"]')->length);

        $data = $this->dataTampilan('nomor/' . $babak['nomor']);
        $this->assertSame([], $data['hasil']);
        $this->assertSame('Nomor ini tidak dilombakan.', $data['kosong']);
        $this->assertSame([], $this->dataTampilan('kelompok-umur/' . $this->idInduk['ku10'])['hasil']);
    }

    public function testJsonBertembolokDenganEtag(): void
    {
        $babak = $this->siapkanBabak();
        setting('Videotron.tampil', true);

        $jawaban = $this->get('layar/data/nomor/' . $babak['nomor']);
        $jawaban->assertOK();
        $jawaban->assertHeader('Cache-Control', 'public, max-age=' . LayarController::TEMBOLOK_DETIK);
        $jawaban->assertHeader('X-Robots-Tag', 'noindex');
        $data = json_decode($jawaban->getJSON(), true);
        $etag = $jawaban->response()->getHeaderLine('ETag');
        $this->assertSame('"' . $data['versi'] . '"', $etag);

        $ulang = $this->withHeaders(['If-None-Match' => $etag])->get('layar/data/nomor/' . $babak['nomor']);
        $ulang->assertStatus(304);
    }

    public function testLatarUnggahanAdmin(): void
    {
        setting('Videotron.tampil', true);
        $this->assertNull(PapanVideotron::latar());

        // Tanpa latar unggahan: latar bawaan dengan adegan Three.js.
        $layar = $this->xpathDari($this->get('layar/putar'))->query('//*[@data-layar]')->item(0);
        $this->assertInstanceOf(DOMElement::class, $layar);
        $this->assertFalse($layar->hasAttribute('data-tanpa-adegan'));

        // Bukan gambar ditolak, pengaturan tidak berubah.
        $this->siapkanBerkas('latar', 'latar.pdf', self::isiPdf());
        $salah = $this->kirimSebagaiAdmin('admin/pengaturan/videotron', ['tampil' => '1', 'lama' => '12', 'isi' => ['hasil']]);
        $this->assertIsianSalahDitampilkanUlang($salah, ['latar']);
        $this->assertSame('', (string) setting('Videotron.latar'));

        $this->siapkanBerkas('latar', 'latar.png', self::isiPng());
        $simpan = $this->kirimSebagaiAdmin('admin/pengaturan/videotron', ['tampil' => '1', 'lama' => '12', 'isi' => ['hasil']]);
        $this->assertBerhasilDialihkan($simpan, 'admin/pengaturan/videotron');
        $jalur = (string) setting('Videotron.latar');
        $this->assertStringStartsWith('unggahan/videotron/', $jalur);
        $this->assertFileExists($this->jalurUnggahan($jalur));

        // Semua tampilan memakai latar unggahan tanpa adegan Three.js.
        foreach (['layar/putar', 'layar/nomor/' . $this->idInduk['lari60putra']] as $alamat) {
            $layar = $this->xpathDari($this->get($alamat))->query('//*[@data-layar]')->item(0);
            $this->assertInstanceOf(DOMElement::class, $layar);
            $this->assertSame(base_url($jalur), $layar->getAttribute('data-latar-dasar'), $alamat);
            $this->assertTrue($layar->hasAttribute('data-tanpa-adegan'), $alamat);
        }

        $halaman = $this->bukaSebagaiAdmin('admin/pengaturan/videotron');
        $this->assertSame(1, $this->xpathDari($halaman)->query('//*[@data-latar-videotron]//img[@src="' . base_url($jalur) . '"]')->length);

        // Hapus: berkas dibuang dan kembali ke latar bawaan.
        service('superglobals')->setFilesArray([]);
        $this->kirimSebagaiAdmin('admin/pengaturan/videotron', ['tampil' => '1', 'lama' => '12', 'isi' => ['hasil'], 'hapus_latar' => '1']);
        $this->assertSame('', (string) setting('Videotron.latar'));
        $this->assertFileDoesNotExist($this->jalurUnggahan($jalur));
        $this->assertNull(PapanVideotron::latar());
    }

    public function testAlamatSalah404(): void
    {
        $babak = $this->siapkanBabak();
        setting('Videotron.tampil', true);

        foreach ([
            'layar/seri/999999/1', 'layar/seri/abc/1', 'layar/seri/' . $babak['penyisihan'] . '/0',
            'layar/sesi/2026-13-40/pagi', 'layar/sesi/' . self::TANGGAL . '/malam', 'layar/data/sesi/kemarin/pagi',
            'layar/nomor/999999', 'layar/kelompok-umur/999999', 'layar/klasemen/' . str_repeat('a', 41), 'layar/lain/1',
        ] as $alamat) {
            $this->assert404($alamat);
        }
    }

    /**
     * Enam atlet Lari 60 m putra KU 10: Penyisihan pukul 08.30 dibagi dua seri
     * lalu dikunci, Final pukul 15.30 dari semua peserta.
     *
     * @return array{nomor: int, penyisihan: int, final: int}
     */
    private function siapkanBabak(): array
    {
        $atlet = [];

        foreach (range(1, 6) as $i) {
            $atlet[] = $this->atletPanel(['nama' => 'Atlet Rekaan ' . $i, 'nisn' => '992200000' . $i, 'nomor_dada' => (string) (100 + $i)]);
        }

        $p = $this->buatPendaftaranPanel(['atlet' => $atlet]);
        (new StatusPembayaran())->tandaiLunas($p['id']);

        $hasil      = new HasilLomba();
        $nomor      = $this->idInduk['lari60putra'];
        $penyisihan = $hasil->tambahBabak($nomor, 'Penyisihan', false, 'semua')['id'];
        $final      = $hasil->tambahBabak($nomor, 'Final', true, 'semua')['id'];

        $this->db->table('babak')->where('id', $penyisihan)->update(['tanggal' => self::TANGGAL, 'jam_mulai' => '08:30:00', 'lokasi' => 'Lintasan']);
        $this->db->table('babak')->where('id', $final)->update(['tanggal' => self::TANGGAL, 'jam_mulai' => '15:30:00', 'lokasi' => 'Lintasan']);

        $seri = new SeriLintasan();
        $seri->bagiOtomatis($penyisihan, ['jumlah_seri' => 2, 'benih' => 77]);
        $this->assertNull($seri->kunci($penyisihan, true));

        return ['nomor' => $nomor, 'penyisihan' => $penyisihan, 'final' => $final];
    }

    private function isiHasil(int $babak): void
    {
        $isian = [];

        foreach ($this->db->table('hasil')->where('babak_id', $babak)->orderBy('id')->get()->getResultArray() as $i => $h) {
            $isian[(int) $h['id']] = ['prestasi' => number_format(9.1 + $i * 0.2, 2, ',', ''), 'keterangan' => '', 'peringkat' => ''];
        }

        $this->assertSame([], (new HasilLomba())->simpanHasil($babak, $isian));
    }

    /**
     * @return array<string, mixed>
     */
    private function dataTampilan(string $alamat): array
    {
        $jawaban = $this->get('layar/data/' . $alamat);
        $jawaban->assertOK();

        return json_decode($jawaban->getJSON(), true);
    }

    private function assert404(string $alamat): void
    {
        try {
            $jawaban = $this->get($alamat);
            $jawaban->assertStatus(404);
        } catch (PageNotFoundException) {
            $this->addToAssertionCount(1);
        }
    }
}
