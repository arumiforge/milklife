<?php

use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\DownloadResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Support\KirimFormulir;
use Tests\Support\PanelDataContoh;
use Tests\Support\UjiBasisData;

/**
 * Hasil lomba (F8-04, K124): babak per nomor, isian prestasi dan peringkat
 * otomatis, koreksi peringkat Final, Terbitkan/Tarik kembali, halaman publik,
 * unduhan PDF dan Excel, tautan halaman depan, dan menu admin. Semua nama,
 * NISN, dan NPSN karangan.
 *
 * @internal
 */
final class HasilLombaTest extends UjiBasisData
{
    use KirimFormulir;
    use PanelDataContoh;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siapkanDataPanel();
    }

    protected function tearDown(): void
    {
        $this->bersihkanBerkasPanel();
        parent::tearDown();
    }

    /**
     * Tiga sekolah dengan atlet Lari 60 m putra KU 10; sekolah ketiga belum Lunas.
     *
     * @return array{a: int, b: int, c: int, menunggu: int}
     */
    private function isiPelari(): array
    {
        $a = $this->buatPendaftaranPanel(['status' => 'lunas', 'atlet' => [
            $this->atletPanel(['nama' => 'Andi Karangan', 'nomor_dada' => '2001']),
            $this->atletPanel(['nama' => 'Bayu Karangan', 'nomor_dada' => '2002']),
        ]]);
        $b = $this->buatPendaftaranPanel([
            'sekolah' => ['nama' => 'SD KARANGAN JAYA', 'npsn' => '99999921', 'jenjang' => 'A', 'kabupaten' => 'Pati'],
            'status'  => 'lunas',
            'atlet'   => [$this->atletPanel(['nama' => 'Candra Karangan', 'nomor_dada' => '2003'])],
        ]);
        $c = $this->buatPendaftaranPanel([
            'sekolah' => ['nama' => 'SD KARANGAN SARI', 'npsn' => '99999922', 'jenjang' => 'A', 'kabupaten' => 'Pati'],
            'atlet'   => [$this->atletPanel(['nama' => 'Dimas Karangan'])],
        ]);

        return ['a' => $a['atlet'][0], 'b' => $a['atlet'][1], 'c' => $b['atlet'][0], 'menunggu' => $c['atlet'][0]];
    }

    private function buatBabak(int $nomorId, string $nama, bool $final): int
    {
        $this->kirimSebagaiAdmin('admin/hasil/' . $nomorId . '/babak', ['nama' => $nama, 'final' => $final ? '1' : '']);

        return (int) $this->db->table('babak')->where('nomor_lomba_id', $nomorId)->where('nama', $nama)->get()->getRowArray()['id'];
    }

    /**
     * @return array<string, int> kunci peserta => id baris hasil
     */
    private function idHasil(int $babakId): array
    {
        $id = [];

        foreach ($this->db->table('hasil')->where('babak_id', $babakId)->get()->getResultArray() as $h) {
            $id[$h['atlet_id'] !== null ? 'a' . $h['atlet_id'] : 'r' . $h['kontingen'] . '|' . $h['regu']] = (int) $h['id'];
        }

        return $id;
    }

    /**
     * @return array<int, int|null> atlet_id => peringkat
     */
    private function peringkat(int $babakId): array
    {
        $hasil = [];

        foreach ($this->db->table('hasil')->where('babak_id', $babakId)->get()->getResultArray() as $h) {
            $hasil[(int) $h['atlet_id']] = $h['peringkat'] === null ? null : (int) $h['peringkat'];
        }

        ksort($hasil);

        return $hasil;
    }

    private function assertTidakDitemukan(string $alamat): void
    {
        try {
            $this->get($alamat);
            $this->fail("{$alamat} seharusnya tidak ditemukan.");
        } catch (PageNotFoundException) {
            $this->addToAssertionCount(1);
        }
    }

    public function testHalamanAdminHanyaUntukYangSudahMasuk(): void
    {
        $this->assertBelumMasukDialihkan('admin/hasil');
        $this->assertBelumMasukDialihkan('admin/hasil/' . $this->idInduk['lari60putra']);
        $this->assertBelumMasukDialihkan('admin/hasil/' . $this->idInduk['lari60putra'] . '/babak', 'post');
        $this->assertTokenCsrfWajib('admin/hasil/' . $this->idInduk['lari60putra'] . '/terbitkan');
    }

    public function testDaftarNomorDanMenuSampingSaja(): void
    {
        $hasil = $this->bukaSebagaiAdmin('admin/hasil');

        $hasil->assertOK();
        $hasil->assertSee('Hasil Lomba');
        $hasil->assertSee('KU 10');
        $hasil->assertSee('Frog Jump putra');
        $hasil->assertSee('Belum ada babak');

        $xpath = $this->xpathDari($hasil);
        $this->assertSame(1, $xpath->query('//nav[contains(@class, "menu-samping")]//a[contains(@href, "admin/hasil")][@aria-current="page"]')->length, 'Butir Hasil lomba aktif di menu samping.');
        $this->assertSame(0, $xpath->query('//nav[contains(@class, "menu-bawah")]//a[contains(@href, "admin/hasil")]')->length, 'Tidak di menu bawah ponsel (K132, K133).');
    }

    public function testBabakFinalPesertaPrestasiDanPeringkatOtomatis(): void
    {
        $atlet = $this->isiPelari();
        $nomor = $this->idInduk['lari60putra'];
        $babak = $this->buatBabak($nomor, 'Final', true);

        $halaman = $this->bukaSebagaiAdmin('admin/hasil/babak/' . $babak);
        $halaman->assertOK();
        $halaman->assertSee('Andi Karangan');
        $halaman->assertSee('Candra Karangan');
        $halaman->assertDontSee('Dimas Karangan'); // belum Lunas

        $this->kirimSebagaiAdmin('admin/hasil/babak/' . $babak . '/peserta', ['peserta' => ['a' . $atlet['a'], 'a' . $atlet['b'], 'a' . $atlet['c'], 'a' . $atlet['menunggu']]]);
        $id = $this->idHasil($babak);
        $this->assertSame(['a' . $atlet['a'], 'a' . $atlet['b'], 'a' . $atlet['c']], array_keys($id), 'Atlet pendaftaran Menunggu tidak ikut.');

        $simpan = $this->kirimSebagaiAdmin('admin/hasil/babak/' . $babak, ['hasil' => [
            $id['a' . $atlet['a']] => ['prestasi' => '8,45', 'keterangan' => ''],
            $id['a' . $atlet['b']] => ['prestasi' => '8.45', 'keterangan' => ''],
            $id['a' . $atlet['c']] => ['prestasi' => '', 'keterangan' => 'DNS'],
        ]]);
        $this->assertBerhasilDialihkan($simpan, 'admin/hasil/babak/' . $babak, 'Hasil Final tersimpan; peringkat sudah diurutkan.');
        $this->assertSame([$atlet['a'] => 1, $atlet['b'] => 1, $atlet['c'] => null], $this->peringkat($babak), 'Prestasi sama = peringkat sama; DNS tanpa peringkat.');

        $this->kirimSebagaiAdmin('admin/hasil/babak/' . $babak, ['hasil' => [
            $id['a' . $atlet['a']] => ['prestasi' => '8,45'],
            $id['a' . $atlet['b']] => ['prestasi' => '8,32'],
            $id['a' . $atlet['c']] => ['prestasi' => '1:02,1'],
        ]]);
        $this->assertSame([$atlet['a'] => 2, $atlet['b'] => 1, $atlet['c'] => 3], $this->peringkat($babak));
        $this->assertSame('6210', $this->db->table('hasil')->where('id', $id['a' . $atlet['c']])->get()->getRowArray()['prestasi']);

        $nomorHalaman = $this->bukaSebagaiAdmin('admin/hasil/' . $nomor);
        $nomorHalaman->assertSee('8,32');
        $nomorHalaman->assertSee('1:02,10');
        $this->assertSame(3, $this->xpathDari($nomorHalaman)->query('//tr[@data-juara]')->length);
    }

    public function testPrestasiSalahTidakMenyimpanApaPunDanMenyebutContoh(): void
    {
        $atlet = $this->isiPelari();
        $babak = $this->buatBabak($this->idInduk['lari60putra'], 'Penyisihan seri 1', false);
        $this->kirimSebagaiAdmin('admin/hasil/babak/' . $babak . '/peserta', ['peserta' => ['a' . $atlet['a'], 'a' . $atlet['b']]]);
        $id = $this->idHasil($babak);

        $hasil = $this->kirimSebagaiAdmin('admin/hasil/babak/' . $babak, ['hasil' => [
            $id['a' . $atlet['a']] => ['prestasi' => '8,45'],
            $id['a' . $atlet['b']] => ['prestasi' => 'delapan'],
        ]]);

        $hasil->assertOK();
        $hasil->assertSee('1:05,32');
        $hasil->assertSee('value="delapan"', null);
        $this->assertSame([$atlet['a'] => null, $atlet['b'] => null], $this->peringkat($babak));
        $this->assertNull($this->db->table('hasil')->where('id', $id['a' . $atlet['a']])->get()->getRowArray()['prestasi']);
    }

    public function testSatuFinalPerNomorDanNamaBabakUnik(): void
    {
        $nomor = $this->idInduk['lari60putra'];
        $this->buatBabak($nomor, 'Final', true);

        $finalKedua = $this->kirimSebagaiAdmin('admin/hasil/' . $nomor . '/babak', ['nama' => 'Final B', 'final' => '1']);
        $this->assertGagalDialihkan($finalKedua, 'admin/hasil/' . $nomor . '#tambah-babak');
        $this->assertSame(1, $this->db->table('babak')->where('nomor_lomba_id', $nomor)->countAllResults());

        $kembar = $this->kirimSebagaiAdmin('admin/hasil/' . $nomor . '/babak', ['nama' => '  final ']);
        $this->assertGagalDialihkan($kembar, 'admin/hasil/' . $nomor . '#tambah-babak');
        $this->assertSame(1, $this->db->table('babak')->where('nomor_lomba_id', $nomor)->countAllResults());

        $this->buatBabak($nomor, 'Penyisihan seri 1', false);
        $halaman = $this->bukaSebagaiAdmin('admin/hasil/' . $nomor);
        $this->assertLessThan(strpos($halaman->getBody(), 'id="babak-' . $this->db->table('babak')->where('final', 1)->get()->getRowArray()['id'] . '"'), strpos($halaman->getBody(), 'Penyisihan seri 1'), 'Final tampil paling akhir.');
    }

    public function testKoreksiPeringkatFinalDitandaiDiubahAdmin(): void
    {
        $atlet = $this->isiPelari();
        $babak = $this->buatBabak($this->idInduk['lari60putra'], 'Final', true);
        $this->kirimSebagaiAdmin('admin/hasil/babak/' . $babak . '/peserta', ['peserta' => ['a' . $atlet['a'], 'a' . $atlet['b']]]);
        $id = $this->idHasil($babak);

        $this->kirimSebagaiAdmin('admin/hasil/babak/' . $babak, ['hasil' => [
            $id['a' . $atlet['a']] => ['prestasi' => '8,45', 'peringkat' => '1'],
            $id['a' . $atlet['b']] => ['prestasi' => '8,45', 'peringkat' => '2'],
        ]]);

        $this->assertSame([$atlet['a'] => 1, $atlet['b'] => 2], $this->peringkat($babak));
        $this->assertSame(2, $this->db->table('hasil')->where('babak_id', $babak)->where('peringkat_manual', 1)->countAllResults());
        $this->bukaSebagaiAdmin('admin/hasil/babak/' . $babak)->assertSee('diubah admin');

        // Dikosongkan: kembali otomatis.
        $this->kirimSebagaiAdmin('admin/hasil/babak/' . $babak, ['hasil' => [
            $id['a' . $atlet['a']] => ['prestasi' => '8,45', 'peringkat' => ''],
            $id['a' . $atlet['b']] => ['prestasi' => '8,45', 'peringkat' => ''],
        ]]);
        $this->assertSame([$atlet['a'] => 1, $atlet['b'] => 1], $this->peringkat($babak));

        // Peringkat salah ditolak dengan contoh.
        $salah = $this->kirimSebagaiAdmin('admin/hasil/babak/' . $babak, ['hasil' => [$id['a' . $atlet['a']] => ['prestasi' => '8,45', 'peringkat' => 'satu']]]);
        $salah->assertSee('Peringkat harus angka');
    }

    public function testBabakPenyisihanTanpaKoreksiPeringkatDanCatatanLolos(): void
    {
        $atlet = $this->isiPelari();
        $nomor = $this->idInduk['lari60putra'];
        $seri  = $this->buatBabak($nomor, 'Penyisihan seri 1', false);
        $this->kirimSebagaiAdmin('admin/hasil/babak/' . $seri . '/peserta', ['peserta' => ['a' . $atlet['a'], 'a' . $atlet['b']]]);
        $id = $this->idHasil($seri);
        $this->kirimSebagaiAdmin('admin/hasil/babak/' . $seri, ['hasil' => [
            $id['a' . $atlet['a']] => ['prestasi' => '9,10', 'peringkat' => '5'],
            $id['a' . $atlet['b']] => ['prestasi' => '9,00'],
        ]]);

        $this->assertSame([$atlet['a'] => 2, $atlet['b'] => 1], $this->peringkat($seri), 'Koreksi peringkat hanya di Final.');

        $final = $this->buatBabak($nomor, 'Final', true);
        $this->bukaSebagaiAdmin('admin/hasil/babak/' . $final)->assertSee('Penyisihan seri 1: 9,00 (ke-1)');
    }

    public function testKeluarkanPesertaDanHapusBabak(): void
    {
        $atlet = $this->isiPelari();
        $nomor = $this->idInduk['lari60putra'];
        $babak = $this->buatBabak($nomor, 'Final', true);
        $this->kirimSebagaiAdmin('admin/hasil/babak/' . $babak . '/peserta', ['peserta' => ['a' . $atlet['a'], 'a' . $atlet['b']]]);
        $id = $this->idHasil($babak);

        $this->kirimSebagaiAdmin('admin/hasil/babak/' . $babak, ['hasil' => [
            $id['a' . $atlet['a']] => ['prestasi' => '8,45'],
            $id['a' . $atlet['b']] => ['prestasi' => '8,20', 'keluarkan' => '1'],
        ]]);
        $this->assertSame([$atlet['a'] => 1], $this->peringkat($babak));

        $ubah = $this->kirimSebagaiAdmin('admin/hasil/babak/' . $babak . '/ubah', ['nama' => 'Final A']);
        $this->assertBerhasilDialihkan($ubah, 'admin/hasil/babak/' . $babak);
        $this->assertSame(['Final A', '0'], array_values($this->db->table('babak')->select('nama, final')->where('id', $babak)->get()->getRowArray()));

        $hapus = $this->kirimSebagaiAdmin('admin/hasil/babak/' . $babak . '/hapus');
        $this->assertBerhasilDialihkan($hapus, 'admin/hasil/' . $nomor);
        $this->assertSame(0, $this->db->table('hasil')->countAllResults());
    }

    public function testTerbitkanTarikKembaliDanHalamanPublik(): void
    {
        $atlet = $this->isiPelari();
        $nomor = $this->idInduk['lari60putra'];

        $this->assertGagalDialihkan($this->kirimSebagaiAdmin('admin/hasil/' . $nomor . '/terbitkan'), 'admin/hasil/' . $nomor, 'Belum ada babak. Tambahkan babak Final dan isi hasilnya dulu, lalu terbitkan.');

        $babak = $this->buatBabak($nomor, 'Final', true);
        $this->kirimSebagaiAdmin('admin/hasil/babak/' . $babak . '/peserta', ['peserta' => ['a' . $atlet['a'], 'a' . $atlet['c']]]);
        $id = $this->idHasil($babak);
        $this->kirimSebagaiAdmin('admin/hasil/babak/' . $babak, ['hasil' => [
            $id['a' . $atlet['a']] => ['prestasi' => '8,45'],
            $id['a' . $atlet['c']] => ['prestasi' => '8,60'],
        ]]);

        // Belum terbit: publik kosong, alamat nomor tidak ditemukan, tidak ada tautan di halaman depan.
        $this->get('hasil')->assertSee('Belum ada hasil');
        $this->assertTidakDitemukan('hasil/' . $nomor);
        $this->get('/')->assertDontSee('tombol-hasil');

        $terbit = $this->kirimSebagaiAdmin('admin/hasil/' . $nomor . '/terbitkan');
        $this->assertBerhasilDialihkan($terbit, 'admin/hasil/' . $nomor, 'Hasil Lari 60 m putra KU 10 sudah terbit di halaman depan. Perubahan berikutnya langsung tampil.');
        $this->assertNotNull($this->db->table('nomor_lomba')->where('id', $nomor)->get()->getRowArray()['hasil_terbit_pada']);

        $publik = $this->get('hasil');
        $publik->assertSee('Lari 60 m putra');
        $publik->assertSee('Andi Karangan');
        $publik->assertSee('SD KARANGAN JAYA');
        $publik->assertDontSee('Frog Jump');
        $this->get('hasil/' . $nomor)->assertSee('8,60');
        $this->get('/')->assertSee('tombol-hasil');

        $this->kirimSebagaiAdmin('admin/hasil/' . $nomor . '/tarik');
        $this->assertNull($this->db->table('nomor_lomba')->where('id', $nomor)->get()->getRowArray()['hasil_terbit_pada']);
        $this->get('hasil')->assertDontSee('Andi Karangan');
        $this->assertTidakDitemukan('hasil/' . $nomor);
    }

    public function testNomorBereguPerReguDanJarak(): void
    {
        $this->db->table('nomor_lomba')->where('id', $this->idInduk['kanga'])->update(['jenis_hasil' => 'jarak']);
        $x = $this->buatPendaftaranPanel(['status' => 'lunas', 'atlet' => array_merge(
            $this->timPanel('kanga', 4),
            $this->timPanel('kanga', 4, ['regu' => ['kanga' => ['regu' => 'B', 'peran' => 'inti']]]),
        )]);
        $y = $this->buatPendaftaranPanel([
            'sekolah' => ['nama' => 'SD KARANGAN JAYA', 'npsn' => '99999921', 'jenjang' => 'A', 'kabupaten' => 'Pati'],
            'status'  => 'lunas',
            'atlet'   => $this->timPanel('kanga', 4),
        ]);
        $this->assertNotEmpty($x['atlet']);
        $this->assertNotEmpty($y['atlet']);

        $babak   = $this->buatBabak($this->idInduk['kanga'], 'Final', true);
        $halaman = $this->bukaSebagaiAdmin('admin/hasil/babak/' . $babak);
        $halaman->assertSee('SD KARANGAN MAJU Regu A');
        $halaman->assertSee('SD KARANGAN MAJU Regu B');
        $halaman->assertSee('SD KARANGAN JAYA');
        $halaman->assertDontSee('SD KARANGAN JAYA Regu A');

        $this->kirimSebagaiAdmin('admin/hasil/babak/' . $babak . '/peserta', ['peserta' => ['r99999911|A', 'r99999911|B', 'r99999921|A']]);
        $id = $this->idHasil($babak);
        $this->assertSame(['r99999911|A', 'r99999911|B', 'r99999921|A'], array_keys($id));

        $this->kirimSebagaiAdmin('admin/hasil/babak/' . $babak, ['hasil' => [
            $id['r99999911|A'] => ['prestasi' => '12,5'],
            $id['r99999911|B'] => ['prestasi' => '13'],
            $id['r99999921|A'] => ['prestasi' => '3:45'],
        ]]);
        $this->bukaSebagaiAdmin('admin/hasil/babak/' . $babak)->assertSee('3,45');

        $baris = array_column($this->db->table('hasil')->where('babak_id', $babak)->get()->getResultArray(), null, 'id');
        $this->assertNull($baris[$id['r99999911|A']]['prestasi'], 'Isian salah: tidak ada yang tersimpan.');

        $this->kirimSebagaiAdmin('admin/hasil/babak/' . $babak, ['hasil' => [
            $id['r99999911|A'] => ['prestasi' => '12,5'],
            $id['r99999911|B'] => ['prestasi' => '13'],
            $id['r99999921|A'] => ['prestasi' => '3,45'],
        ]]);
        $baris = array_column($this->db->table('hasil')->where('babak_id', $babak)->get()->getResultArray(), 'peringkat', 'id');
        $this->assertSame(['2', '1', '3'], [$baris[$id['r99999911|A']], $baris[$id['r99999911|B']], $baris[$id['r99999921|A']]], 'Jarak terjauh juara.');
    }

    public function testUnduhPdfDanExcel(): void
    {
        $atlet = $this->isiPelari();
        $nomor = $this->idInduk['lari60putra'];
        $babak = $this->buatBabak($nomor, 'Final', true);
        $this->kirimSebagaiAdmin('admin/hasil/babak/' . $babak . '/peserta', ['peserta' => ['a' . $atlet['a'], 'a' . $atlet['b']]]);
        $id = $this->idHasil($babak);
        $this->kirimSebagaiAdmin('admin/hasil/babak/' . $babak, ['hasil' => [
            $id['a' . $atlet['a']] => ['prestasi' => '8,45'],
            $id['a' . $atlet['b']] => ['keterangan' => 'DQ'],
        ]]);

        $pdf = $this->bukaSebagaiAdmin('admin/hasil/' . $nomor . '/pdf');
        $pdf->assertOK();
        $this->assertStringStartsWith('application/pdf', $pdf->response()->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('HasilLari60MPutra_KU10_', $pdf->response()->getHeaderLine('Content-Disposition'));
        $this->assertStringStartsWith('%PDF', (string) $pdf->response()->getBody());

        $excel = $this->bukaSebagaiAdmin('admin/hasil/' . $nomor . '/excel')->response();
        $this->assertInstanceOf(DownloadResponse::class, $excel);

        ob_start();
        $excel->sendBody();
        $berkas = tempnam(sys_get_temp_dir(), 'hasil');
        file_put_contents($berkas, (string) ob_get_clean());
        $lembar = IOFactory::load($berkas)->getActiveSheet()->toArray();
        unlink($berkas);

        $this->assertSame(['Peringkat', 'No. Atlet', 'Nama', 'Sekolah', 'Waktu', 'Keterangan'], $lembar[0]);
        $this->assertSame(['1', '2001', 'Andi Karangan', 'SD KARANGAN MAJU', '8,45', null], array_map(static fn ($v) => $v === null ? null : (string) $v, $lembar[1]));
        $this->assertSame('DQ (diskualifikasi)', $lembar[2][5]);
    }

    public function testJenisHasilDiPengaturanNomorLomba(): void
    {
        $nomor = $this->idInduk['frog'];

        $this->bukaSebagaiAdmin('admin/pengaturan/nomor-lomba/' . $nomor . '/ubah')->assertSee('Jenis hasil');

        $isian = array_map('strval', array_filter($this->db->table('nomor_lomba')->where('id', $nomor)->get()->getRowArray(), static fn ($v) => $v !== null));
        $hasil = $this->kirimSebagaiAdmin('admin/pengaturan/nomor-lomba/' . $nomor, ['jenis_hasil' => 'jarak', 'urutan' => ''] + $isian);

        $this->assertTrue($hasil->isRedirect(), (string) $hasil->getBody());
        $this->assertSame('jarak', $this->db->table('nomor_lomba')->where('id', $nomor)->get()->getRowArray()['jenis_hasil']);
    }
}
