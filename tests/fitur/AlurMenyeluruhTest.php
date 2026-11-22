<?php

use App\Controllers\Admin\BuktiBayarController;
use App\Libraries\Hasil\HasilLomba;
use App\Libraries\Hasil\Klasemen;
use CodeIgniter\Config\Services;
use CodeIgniter\Test\TestResponse;
use Tests\Support\KirimFormulir;
use Tests\Support\PanelDataContoh;
use Tests\Support\UjiBasisData;
use Tests\Support\UnggahanSementara;

/**
 * Alur menyeluruh F7-06 dan F8-06 dengan minify aktif (`aplikasi.minify`):
 *
 * simpan pendaftaran dan susulan (konfirmasi tim, K57) → kirim ulang
 * idempoten (B2, K118) → cek → bukti PDF → unggah bukti bayar → Lunas dan
 * nomor atlet, susulan digabung (K109) → Ubah → rekap → daftar ulang dan
 * hadir → BIB massal → daftar peserta → hasil Final, Terbitkan, halaman
 * publik hasil → klasemen → hapus (NISN boleh didaftarkan lagi, hasil ikut
 * terhapus).
 *
 * Halaman HTML diperkecil (filter `perkecilhtml`), PDF dan Excel tidak.
 *
 * @internal
 */
final class AlurMenyeluruhTest extends UjiBasisData
{
    use KirimFormulir;
    use PanelDataContoh;
    use UnggahanSementara;

    protected function setUp(): void
    {
        parent::setUp();
        Services::resetSingle('throttler');
        service('cache')->clean();
        $this->siapkanDataPanel();
        $this->pakaiFolderFotoSementara();
        $this->pakaiFolderBuktiBayarSementara();
        config('Aplikasi')->minify = true;
    }

    protected function tearDown(): void
    {
        config('Aplikasi')->minify = false;
        $this->bersihkanBerkasPanel();
        $this->hapusFolderFotoSementara();
        parent::tearDown();
    }

    public function testAlurPendaftaranSampaiKlasemenDenganMinify(): void
    {
        // ---------- 1. Wizard: A (tim Kanga's Escape + individu), B susulan ----------
        $kirimA            = self::kirimanContoh();
        $kirimA['sekolah'] = ['jenjang' => 'A', 'kabupaten' => 'Kudus', 'npsn' => '99999911', 'nama' => ''];
        $kirimA['atlet']   = [self::atletContoh('9900000001', [10, 20])];

        foreach ([2, 3, 4] as $n) {
            $kirimA['atlet'][] = self::atletContoh('990000000' . $n, [20, 10]);
        }

        $kirimA['kunci_kirim'] = 'a1b2c3d4e5f60718293a4b5c6d7e8f90';
        $a                     = $this->simpanWizard($kirimA);

        // B2 (K118): kiriman yang sama dikirim ulang setelah sambungan putus → pendaftaran yang sama.
        $ulang = $this->kirimWizard('pendaftaran/simpan', $kirimA);
        $this->assertContains($ulang->response()->getStatusCode(), [200, 201]);
        $this->assertSame($a['id_pendaftaran'], $this->json($ulang)['id_pendaftaran']);
        $this->assertSame(1, $this->db->table('pendaftaran')->where('npsn', '99999911')->countAllResults());

        $kirimB                       = self::kirimanContoh();
        $kirimB['sekolah']            = $kirimA['sekolah'];
        $kirimB['atlet']              = [self::atletContoh('9900000005', [20]) + ['regu' => [20 => ['regu' => 'A', 'peran' => 'cadangan']]]];
        $kirimB['konfirmasi_susulan'] = [20];
        $b                            = $this->simpanWizard($kirimB);
        $this->assertTrue($b['rincian']['beregu'][0]['susulan']);

        // ---------- 2. Halaman publik diperkecil, cek dan bukti ----------
        $this->assertDiperkecil($this->halaman('/'));

        $cek = $this->halaman('cek-pendaftaran?kode=99999911');
        $this->assertDiperkecil($cek);
        $this->assertStringContainsString($a['id_pendaftaran'], (string) $cek->response()->getBody());

        $kode = (string) $this->db->table('pendaftaran')->where('id', $a['pid'])->get()->getRow()->kode_bukti;
        $pdf  = $this->get('bukti/' . $kode . '/' . $a['id_pendaftaran']);
        $this->assertStringStartsWith('%PDF', (string) $pdf->response()->getBody());

        // ---------- 3. Panel: bukti bayar, Lunas, gabung ----------
        $this->siapkanBerkas(BuktiBayarController::KOTAK, 'transfer.png', self::gambarPanel(1200, 900));
        $this->assertBerhasilDialihkan($this->kirimSebagaiAdmin('admin/pendaftaran/' . $a['pid'] . '/bukti-bayar'), 'admin/pendaftaran/' . $a['pid']);

        $this->assertBerhasilDialihkan($this->kirimSebagaiAdmin('admin/pendaftaran/' . $a['pid'] . '/lunas'), 'admin/pendaftaran/' . $a['pid']);
        $this->assertBerhasilDialihkan($this->kirimSebagaiAdmin('admin/pendaftaran/' . $b['pid'] . '/lunas'), 'admin/pendaftaran/' . $a['pid']);
        $this->assertSame(0, $this->db->table('pendaftaran')->where('id', $b['pid'])->countAllResults(), 'Susulan digabung (K109).');
        $this->assertSame(['2001', '2002', '2003', '2004', '2005'], array_column($this->db->table('atlet')->select('nomor_dada')->where('pendaftaran_id', $a['pid'])->orderBy('nomor_dada')->get()->getResultArray(), 'nomor_dada'));

        $this->assertDiperkecil($this->halaman('admin/pendaftaran/' . $a['pid']));
        $this->bukaSebagaiAdmin('admin/pendaftaran/' . $a['pid'] . '/ubah')->assertOK();

        // ---------- 4. Rekap: Excel dan PDF tidak diperkecil ----------
        $excel = $this->bukaSebagaiAdmin('admin/rekap/sekolah/excel');
        $this->assertSame(200, $excel->response()->getStatusCode());
        $rekapPdf = $this->bukaSebagaiAdmin('admin/rekap/keuangan/pdf');
        $this->assertStringStartsWith('%PDF', (string) $rekapPdf->response()->getBody());

        // ---------- 5. Hari lomba: daftar ulang, BIB, daftar peserta ----------
        $this->bukaSebagaiAdmin('admin/daftar-ulang?id=' . $a['id_pendaftaran'])->assertOK();
        $this->assertBerhasilDialihkan($this->kirimSebagaiAdmin('admin/daftar-ulang/' . $a['pid'] . '/hadir', ['semua' => '1']), 'admin/daftar-ulang?id=' . $a['id_pendaftaran'] . '#daftar-atlet');
        $this->assertSame(5, $this->db->table('atlet')->where('pendaftaran_id', $a['pid'])->where('hadir_pada IS NOT NULL', null, false)->countAllResults());
        $this->assertStringStartsWith('%PDF', (string) $this->bukaSebagaiAdmin('admin/nomor-atlet/cetak/pdf?ku=semua')->response()->getBody());
        $this->assertStringStartsWith('%PDF', (string) $this->bukaSebagaiAdmin('admin/daftar-peserta/' . $this->idInduk['kanga'] . '/pdf')->response()->getBody());

        // ---------- 6. Hasil: Final Lari 60 m putra, Terbitkan, halaman publik ----------
        $nomor = $this->idInduk['lari60putra'];
        $this->kirimSebagaiAdmin('admin/hasil/nomor/' . $nomor . '/babak', ['nama' => 'Final', 'final' => '1', 'isi' => 'semua']);
        $babak = (int) $this->db->table('babak')->where('nomor_lomba_id', $nomor)->get()->getRow()->id;
        $isian = [];

        foreach ($this->db->table('hasil')->where('babak_id', $babak)->orderBy('id')->get()->getResultArray() as $i => $h) {
            $isian[$h['id']] = ['prestasi' => ['9,87', '10,02', '9,95', '10,40'][$i] ?? '11,00', 'keterangan' => '', 'peringkat' => ''];
        }

        $this->assertBerhasilDialihkan($this->kirimSebagaiAdmin('admin/hasil/babak/' . $babak, ['hasil' => $isian]), 'admin/hasil/babak/' . $babak);
        $this->assertBerhasilDialihkan($this->kirimSebagaiAdmin('admin/hasil/nomor/' . $nomor . '/terbitkan'), 'admin/hasil/nomor/' . $nomor);

        $publik = $this->halaman('hasil');
        $this->assertDiperkecil($publik);
        $publik->assertSee('Juara I');
        $this->get('hasil/' . $nomor)->assertSee('9,87');

        // ---------- 7. Klasemen: satu sekolah, tiga piala, juara umum I ----------
        $klasemen = Klasemen::hitung((new HasilLomba())->juara(true));
        $this->assertSame(['SD KARANGAN MAJU'], array_column(array_values($klasemen)[0]['baris'], 'sekolah'));
        $this->assertSame([1, 1, 1, 1], [array_values($klasemen)[0]['baris'][0]['emas'], array_values($klasemen)[0]['baris'][0]['perak'], array_values($klasemen)[0]['baris'][0]['perunggu'], array_values($klasemen)[0]['baris'][0]['juara_umum']]);
        $this->assertDiperkecil($this->halaman('hasil/klasemen'));
        $this->halaman('hasil/klasemen')->assertSee('Juara umum I');
        $this->assertStringStartsWith('%PDF', (string) $this->get('hasil/klasemen/pdf')->response()->getBody());

        // ---------- 8. Hapus: NISN bebas lagi, hasil ikut terhapus ----------
        $this->assertBerhasilDialihkan($this->kirimSebagaiAdmin('admin/pendaftaran/' . $a['pid'] . '/hapus'), 'admin/pendaftaran');
        $this->assertSame(0, $this->db->table('hasil')->countAllResults());
        $this->assertSame([], (new HasilLomba())->juara(true));

        $lagi            = self::kirimanContoh();
        $lagi['sekolah'] = $kirimA['sekolah'];
        $lagi['atlet']   = [self::atletContoh('9900000001', [10])];
        $this->assertNotSame($a['id_pendaftaran'], $this->simpanWizard($lagi)['id_pendaftaran']);
    }

    /**
     * Kirim JSON ke wizard publik dengan token CSRF sah.
     *
     * @param array<string, mixed> $kiriman Id nomor lomba `indukContoh()`.
     */
    private function kirimWizard(string $alamat, array $kiriman): TestResponse
    {
        $token = bin2hex(random_bytes(32));
        Services::resetSingle('security');

        return $this->withSession([config('Security')->tokenName => $token])
            ->withHeaders(['X-CSRF-TOKEN' => $token, 'Content-Type' => 'application/json'])
            ->withBodyFormat('json')
            ->post($alamat, self::kirimanUntukBasisData($kiriman, $this->idInduk));
    }

    /**
     * @param array<string, mixed> $kiriman
     *
     * @return array<string, mixed>
     */
    private function simpanWizard(array $kiriman): array
    {
        $hasil = $this->kirimWizard('pendaftaran/simpan', $kiriman);
        $hasil->assertStatus(201);

        $isi        = $this->json($hasil);
        $isi['pid'] = (int) $this->db->table('pendaftaran')->where('id_pendaftaran', $isi['id_pendaftaran'])->get()->getRow()->id;

        return $isi;
    }

    /**
     * @return array<string, mixed>
     */
    private function json(TestResponse $hasil): array
    {
        return json_decode((string) $hasil->response()->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Buka halaman HTML. Jawaban bersama dari permintaan JSON sebelumnya
     * dibuang dulu, supaya filter `perkecilhtml` melihat jenis isi yang benar.
     */
    private function halaman(string $alamat): TestResponse
    {
        Services::resetSingle('response');
        $hasil = str_starts_with($alamat, 'admin') ? $this->bukaSebagaiAdmin($alamat) : $this->get($alamat);
        $hasil->assertOK();

        return $hasil;
    }

    /**
     * HTML diperkecil: tidak ada baris baru diikuti indentasi di antara tag.
     */
    private function assertDiperkecil(TestResponse $hasil): void
    {
        $this->assertStringStartsWith('text/html', $hasil->response()->getHeaderLine('Content-Type'));
        $this->assertStringNotContainsString("\n    <", (string) $hasil->response()->getBody());
    }
}
