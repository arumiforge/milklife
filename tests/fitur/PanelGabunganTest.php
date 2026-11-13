<?php

use App\Controllers\Admin\BuktiBayarController;
use App\Libraries\BuktiPendaftaran;
use App\Libraries\DataPendaftaran;
use App\Libraries\Panel\UbahPendaftaran;
use CodeIgniter\Config\Services;
use CodeIgniter\HTTP\DownloadResponse;
use CodeIgniter\Test\TestResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Support\KirimFormulir;
use Tests\Support\PanelDataContoh;
use Tests\Support\UjiBasisData;
use Tests\Support\UnggahanSementara;

/**
 * Uji gabungan Tahap 6 (F6-08) pada satu alur, dengan data karangan dan
 * gambar buatan GD:
 *
 * wizard (pendaftaran susulan dari NPSN yang sama dengan konfirmasi tim
 * susulan, K57) → panel (daftar dan Excel, rincian dan foto, unggah bukti
 * bayar, tandai Lunas dan nomor dada, ubah dengan K59 dan K32, hapus) → cek
 * dan bukti PDF membaca data yang sudah diubah → atlet dan rekap beserta
 * Excel/PDF-nya → dasbor.
 *
 * @internal
 */
final class PanelGabunganTest extends UjiBasisData
{
    use KirimFormulir;
    use PanelDataContoh;
    use UnggahanSementara;

    /**
     * @var list<string>
     */
    private array $berkasSementara = [];

    protected function setUp(): void
    {
        parent::setUp();
        Services::resetSingle('throttler');
        service('cache')->clean();
        $this->siapkanDataPanel();
        $this->pakaiFolderFotoSementara();
        $this->pakaiFolderBuktiBayarSementara();
    }

    protected function tearDown(): void
    {
        $this->bersihkanBerkasPanel();

        foreach ($this->berkasSementara as $berkas) {
            if (is_file($berkas)) {
                unlink($berkas);
            }
        }

        parent::tearDown();
    }

    // ---------------------------------------------------------------
    // Pembantu
    // ---------------------------------------------------------------

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
     * Simpan lewat wizard; hasilnya jawaban `pendaftaran/simpan` ditambah `pid` (pendaftaran.id).
     *
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

    private function teks(TestResponse $hasil): string
    {
        return (string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $hasil->response()->getBody()), ENT_QUOTES | ENT_HTML5));
    }

    /**
     * @return list<list<mixed>>
     */
    private function bacaExcel(TestResponse $hasil): array
    {
        $tanggapan = $hasil->response();
        $this->assertInstanceOf(DownloadResponse::class, $tanggapan);

        ob_start();
        $tanggapan->sendBody();
        $isi = (string) ob_get_clean();

        $berkas = tempnam(sys_get_temp_dir(), 'milklife-gabungan-') . '.xlsx';
        file_put_contents($berkas, $isi);
        $this->berkasSementara[] = $berkas;

        $buku = IOFactory::load($berkas);

        try {
            return $buku->getActiveSheet()->toArray(null, false, false, false);
        } finally {
            $buku->disconnectWorksheets();
        }
    }

    /**
     * Isian formulir ubah seperti yang dikirim peramban (semua nilai teks).
     *
     * @return array<string, mixed>
     */
    private function isianUbah(int $id): array
    {
        $isian = (new UbahPendaftaran())->isian($id);

        return [
            'sekolah'          => $isian['sekolah'],
            'penanggung_jawab' => array_map(static fn (array $o): array => [
                'id' => (string) $o['id'], 'nama' => $o['nama'], 'nomor_wa' => $o['nomor_wa'], 'foto' => '',
            ], $isian['penanggung_jawab']),
            'atlet' => array_map(static fn (array $a): array => [
                'id'            => (string) $a['id'],
                'nama'          => $a['nama'],
                'nisn'          => $a['nisn'],
                'tanggal_lahir' => $a['tanggal_lahir'],
                'jenis_kelamin' => $a['jenis_kelamin'],
                'nomor'         => array_map('strval', $a['nomor']),
            ], $isian['atlet']),
        ];
    }

    /**
     * Nomor dada atlet satu pendaftaran menurut urutan simpan.
     *
     * @return list<string|null>
     */
    private function nomorDada(int $pid): array
    {
        return array_column($this->db->table('atlet')->select('nomor_dada')->where('pendaftaran_id', $pid)->orderBy('id')->get()->getResultArray(), 'nomor_dada');
    }

    private function statusPendaftaran(int $pid): string
    {
        return (string) $this->db->table('pendaftaran')->where('id', $pid)->get()->getRow()->status;
    }

    /**
     * @return array<string, mixed>
     */
    private function dataTabel(string $alamat, array $saringan = []): array
    {
        $hasil = $this->bukaSebagaiAdmin($alamat . '?' . http_build_query(['draw' => 1, 'start' => 0, 'length' => 25] + $saringan));
        $hasil->assertOK();

        return $this->json($hasil);
    }

    // ---------------------------------------------------------------
    // Alur
    // ---------------------------------------------------------------

    public function testWizardPanelCekRekapDasbor(): void
    {
        // ---------- 1. Wizard ----------
        // A: SD 99999911, tim Kanga's Escape baru (4 atlet) dan satu nomor individu.
        $kirimA            = self::kirimanContoh();
        $kirimA['sekolah'] = ['jenjang' => 'A', 'kabupaten' => 'Kudus', 'npsn' => '99999911', 'nama' => ''];
        $kirimA['atlet']   = [self::atletContoh('9900000001', [10, 20])];

        foreach ([2, 3, 4] as $n) {
            $kirimA['atlet'][] = self::atletContoh('990000000' . $n, [20]);
        }

        $a = $this->simpanWizard($kirimA);
        $this->assertSame(250000, $a['rincian']['total']);

        // B: susulan dari NPSN yang sama, tambahan anggota tim tanpa biaya tim.
        $kirimB            = self::kirimanContoh();
        $kirimB['sekolah'] = $kirimA['sekolah'];
        $kirimB['atlet']   = [self::atletContoh('9900000005', [20, 12])];

        // Tanpa konfirmasi tim susulan ditolak (K57).
        $tolak = $this->kirimWizard('pendaftaran/simpan', $kirimB);
        $tolak->assertStatus(422);
        $this->assertSame(['konfirmasi.' . $this->idInduk['kanga']], array_column($this->json($tolak)['galat'], 'jalur'));

        $kirimB['konfirmasi_susulan'] = [20];
        $b                            = $this->simpanWizard($kirimB);
        $this->assertSame(150000, $b['rincian']['total']);
        $this->assertTrue($b['rincian']['beregu'][0]['susulan']);

        // C: SMP lain, satu atlet putri.
        $kirimC                 = self::kirimanContoh();
        $kirimC['sekolah']      = ['jenjang' => 'B', 'kabupaten' => 'Jepara', 'npsn' => '99999913', 'nama' => ''];
        $kirimC['atlet']        = [self::atletContoh('9900000009', [40], 'P', '2012-05-01', 'dewi karangan')];
        $c                      = $this->simpanWizard($kirimC);
        $this->assertSame(200000, $c['rincian']['total']);

        // ---------- 2. Daftar pendaftaran dan Excel ----------
        $daftar = $this->dataTabel('admin/pendaftaran/data');
        $this->assertSame(3, $daftar['recordsTotal']);
        $this->assertSame(1, $this->dataTabel('admin/pendaftaran/data', ['kabupaten' => 'Jepara'])['recordsFiltered']);

        $excel = $this->bacaExcel($this->bukaSebagaiAdmin('admin/pendaftaran/excel?kabupaten=Kudus'));
        $this->assertCount(3, $excel, 'Judul dan dua pendaftaran Kudus.');
        $this->assertEqualsCanonicalizing([$a['id_pendaftaran'], $b['id_pendaftaran']], array_column(array_slice($excel, 1), 0));

        // ---------- 3. Rincian dan foto penanggung jawab ----------
        $rincian = $this->bukaSebagaiAdmin('admin/pendaftaran/' . $a['pid']);
        $rincian->assertOK();
        $teks = $this->teks($rincian);
        foreach (['SD KARANGAN MAJU', $a['id_pendaftaran'], 'BUDI KARANGAN 01', "Kanga's Escape putra", 'Ibu Karangan', 'Rp 250.000'] as $harap) {
            $this->assertStringContainsString($harap, $teks);
        }

        $pj   = (int) $this->db->table('penanggung_jawab')->where('pendaftaran_id', $a['pid'])->get()->getRow()->id;
        $foto = $this->bukaSebagaiAdmin('admin/pendaftaran/' . $a['pid'] . '/foto/' . $pj);
        $foto->assertOK();
        $this->assertStringStartsWith('image/jpeg', $foto->response()->getHeaderLine('Content-Type'));

        // ---------- 4. Bukti bayar ----------
        $this->siapkanBerkas(BuktiBayarController::KOTAK, 'transfer.png', self::gambarPanel(2000, 1000));
        $this->assertBerhasilDialihkan(
            $this->kirimSebagaiAdmin('admin/pendaftaran/' . $a['pid'] . '/bukti-bayar'),
            'admin/pendaftaran/' . $a['pid'],
            // Bukti pertama sudah ikut terkirim bersama pendaftaran (K90).
            'Bukti bayar tersimpan untuk pendaftaran ' . $a['id_pendaftaran'] . ' (2 dari 5 gambar).',
        );
        $buktiId = (int) $this->db->table('bukti_bayar')->where('pendaftaran_id', $a['pid'])->get()->getRow()->id;
        $this->bukaSebagaiAdmin('admin/pendaftaran/' . $a['pid'] . '/bukti-bayar/' . $buktiId)->assertOK();
        // Tiga bukti dari wizard (K90) ditambah satu unggahan admin.
        $this->assertCount(4, $this->berkasBuktiBayarTersimpan());

        // ---------- 5. Tandai Lunas dan nomor dada ----------
        foreach ([$a, $b, $c] as $p) {
            $this->assertBerhasilDialihkan($this->kirimSebagaiAdmin('admin/pendaftaran/' . $p['pid'] . '/lunas'), 'admin/pendaftaran/' . $p['pid']);
        }

        $this->assertSame(['2001', '2002', '2003', '2004'], $this->nomorDada($a['pid']));
        $this->assertSame(['2005'], $this->nomorDada($b['pid']));
        $this->assertSame(['4001'], $this->nomorDada($c['pid']));

        // ---------- 6. Ubah ----------
        // K59: B Lunas, tambahan anggota tim susulan; total tetap, atlet baru langsung bernomor dada.
        $isian            = $this->isianUbah($b['pid']);
        $isian['atlet'][] = [
            'id' => '', 'nama' => 'eko karangan 06', 'nisn' => '9900000006', 'tanggal_lahir' => '2017-02-03',
            'jenis_kelamin' => 'L', 'nomor' => [(string) $this->idInduk['kanga']],
        ];
        $this->assertBerhasilDialihkan(
            $this->kirimSebagaiAdmin('admin/pendaftaran/' . $b['pid'], $isian),
            'admin/pendaftaran/' . $b['pid'],
            'Perubahan pendaftaran ' . $b['id_pendaftaran'] . ' tersimpan. Total sekarang Rp 150.000. 1 atlet baru mendapat nomor dada.',
        );
        $this->assertSame('lunas', $this->statusPendaftaran($b['pid']));
        $this->assertSame(['2005', '2006'], $this->nomorDada($b['pid']));

        // K32: A Lunas, nama atlet diubah dan satu nomor individu ditambah; total naik, status kembali Menunggu.
        $isian                      = $this->isianUbah($a['pid']);
        $isian['atlet'][0]['nama']  = 'budi karangan ubah';
        $isian['atlet'][1]['nomor'] = [(string) $this->idInduk['kanga'], (string) $this->idInduk['frog']];

        $this->kirimSebagaiAdmin('admin/pendaftaran/' . $a['pid'], $isian)->assertSee('Tekan Simpan perubahan lagi');
        $this->assertSame('lunas', $this->statusPendaftaran($a['pid']));

        $this->assertBerhasilDialihkan(
            $this->kirimSebagaiAdmin('admin/pendaftaran/' . $a['pid'], $isian + ['setuju_menunggu' => '1']),
            'admin/pendaftaran/' . $a['pid'],
            'Perubahan pendaftaran ' . $a['id_pendaftaran'] . ' tersimpan. Total sekarang Rp 400.000. '
            . 'Status kembali menjadi Menunggu Konfirmasi Pembayaran; tandai Lunas lagi setelah selisihnya diurus.',
        );
        $this->assertSame('menunggu', $this->statusPendaftaran($a['pid']));
        $this->assertSame(['2001', '2002', '2003', '2004'], $this->nomorDada($a['pid']), 'Nomor dada tetap.');
        $this->assertCount(4, $this->berkasBuktiBayarTersimpan(), 'Bukti bayar tidak tersentuh oleh ubah.');

        // ---------- 7. Hapus, NISN bisa didaftarkan lagi, nomor dada tidak dipakai ulang ----------
        $this->assertBerhasilDialihkan(
            $this->kirimSebagaiAdmin('admin/pendaftaran/' . $c['pid'] . '/hapus'),
            'admin/pendaftaran',
            'Pendaftaran ' . $c['id_pendaftaran'] . ' (SMP KARANGAN JAYA) sudah dihapus beserta 1 atlet. NISN atletnya bisa didaftarkan lagi.',
        );
        $this->assertSame(0, $this->db->table('pendaftaran')->where('id', $c['pid'])->countAllResults());
        $this->assertCount(2, $this->berkasFoto(), 'Foto penanggung jawab C ikut terhapus.');

        $d = $this->simpanWizard($kirimC);
        $this->assertBerhasilDialihkan($this->kirimSebagaiAdmin('admin/pendaftaran/' . $d['pid'] . '/lunas'), 'admin/pendaftaran/' . $d['pid']);
        $this->assertSame(['4002'], $this->nomorDada($d['pid']));

        // ---------- 8. Cek dan bukti membaca data yang sudah diubah ----------
        $teks = $this->teks($this->get('cek-pendaftaran?kode=99999911'));
        foreach (['BUDI KARANGAN UBAH', 'EKO KARANGAN 06', 'Frog Jump putra', 'Rp 400.000', 'Rp 150.000', 'Rp 550.000', 'Lunas', 'Menunggu Konfirmasi Pembayaran'] as $harap) {
            $this->assertStringContainsString($harap, $teks);
        }
        $this->assertStringNotContainsString('BUDI KARANGAN 01', $teks);
        $this->assertStringNotContainsString('Rp 250.000', $teks);

        $html = (new BuktiPendaftaran((new DataPendaftaran())->cariId($a['id_pendaftaran'])))->html();
        $this->assertStringContainsString('BUDI KARANGAN UBAH', $html);
        $this->assertStringContainsString('Rp 400.000', $html);
        $this->assertStringContainsString('stempel stempel-menunggu', $html);

        $pdf = $this->get('bukti/' . (string) db_connect()->table('pendaftaran')->where('id_pendaftaran', $a['id_pendaftaran'])->get()->getRow()->kode_bukti . '/' . $a['id_pendaftaran']);
        $pdf->assertOK();
        $this->assertStringStartsWith('%PDF', (string) $pdf->response()->getBody());

        // ---------- 9. Atlet ----------
        $atlet = $this->dataTabel('admin/atlet/data');
        $this->assertSame(7, $atlet['recordsTotal']);
        $this->assertSame(3, $this->dataTabel('admin/atlet/data', ['status' => 'lunas'])['recordsFiltered']);
        $this->assertSame(6, $this->dataTabel('admin/atlet/data', ['nomor_lomba' => (string) $this->idInduk['kanga']])['recordsFiltered']);

        $excel = $this->bacaExcel($this->bukaSebagaiAdmin('admin/atlet/excel?status=menunggu'));
        $this->assertCount(5, $excel, 'Judul dan empat atlet A.');
        $this->assertContains('BUDI KARANGAN UBAH', array_column(array_slice($excel, 1), 1));

        // ---------- 10. Rekap ----------
        $rekap = fn (string $jenis, string $status = ''): array => $this->json($this->bukaSebagaiAdmin("admin/rekap/{$jenis}/data" . ($status === '' ? '' : '?status=' . $status)));

        $keuangan = $rekap('keuangan');
        $this->assertSame(750000, $keuangan['jumlah']['total']);
        $this->assertSame(350000, $keuangan['jumlah']['lunas']);

        $tim = $rekap('tim-beregu');
        $this->assertCount(1, $tim['baris'], 'Satu tim per sekolah per nomor, termasuk anggota susulan.');
        $this->assertSame(6, (int) $tim['baris'][0]['jumlah_anggota']);
        $this->assertSame(2, (int) $rekap('tim-beregu', 'lunas')['baris'][0]['jumlah_anggota']);

        $excel = $this->bacaExcel($this->bukaSebagaiAdmin('admin/rekap/sekolah/excel'));
        $this->assertSame('Jumlah', end($excel)[0]);
        $this->assertSame(750000, (int) end($excel)[7]);

        foreach (['nomor-lomba', 'tim-beregu', 'sekolah', 'kelompok-umur', 'keuangan'] as $jenis) {
            $this->bukaSebagaiAdmin("admin/rekap/{$jenis}")->assertOK();
            $this->assertStringStartsWith('%PDF', (string) $this->bukaSebagaiAdmin("admin/rekap/{$jenis}/pdf?status=lunas")->response()->getBody(), $jenis);
        }

        // ---------- 11. Dasbor ----------
        $xpath = $this->xpathDari($this->bukaSebagaiAdmin('admin/dasbor'));
        $angka = [];

        foreach ($xpath->query('//*[@id="angka-pendaftaran"]//*[@data-angka]') as $kartu) {
            $angka[$kartu->getAttribute('data-angka')] = trim($xpath->query('.//*[contains(@class, "nilai-angka")]', $kartu)->item(0)->textContent);
        }

        $this->assertSame([
            'pendaftaran' => '3',
            'sekolah'     => '2',
            'atlet'       => '7',
            'tim'         => '1',
            'tagihan'     => rupiah(750000),
            'lunas'       => rupiah(350000),
            'belum'       => rupiah(400000),
        ], $angka);
    }
}
