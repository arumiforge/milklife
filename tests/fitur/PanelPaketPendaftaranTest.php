<?php

use App\Libraries\Panel\BerkasBuktiBayar;
use App\Libraries\Panel\PaketPendaftaran;
use App\Libraries\Pendaftaran\FotoPenanggungJawab;
use Tests\Support\KirimFormulir;
use Tests\Support\PanelDataContoh;
use Tests\Support\UjiBasisData;

/**
 * Ekspor dan impor data pendaftaran untuk pindah server (K76):
 * `PaketPendaftaran`, tombol ekspor di panel, dan `php spark pendaftaran:impor`.
 *
 * @internal
 */
final class PanelPaketPendaftaranTest extends UjiBasisData
{
    use KirimFormulir;
    use PanelDataContoh;

    private string $zip = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->siapkanDataPanel();
        $this->zip = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'milklife-paket-' . bin2hex(random_bytes(6)) . '.zip';
    }

    protected function tearDown(): void
    {
        @unlink($this->zip);
        $this->bersihkanBerkasPanel();
        parent::tearDown();
    }

    public function testEksporLaluImporKeServerKosongSamaPersis(): void
    {
        $a = $this->buatPendaftaranPanel(['foto' => true, 'atlet' => array_merge([$this->atletPanel(['nomor' => ['lari60putra', 'frog']])], $this->timPanel('kanga', 4))]);
        $b = $this->buatPendaftaranPanel(['sekolah' => ['nama' => 'SMP KARANGAN JAYA', 'npsn' => null]]);
        $c = $this->buatPendaftaranPanel(['sekolah' => ['nama' => 'SD KARANGAN HILANG', 'npsn' => '99999919']]);
        $this->buatBuktiBayarPanel($a['id'], 2);
        $this->kirimSebagaiAdmin('admin/pendaftaran/' . $a['id'] . '/lunas');
        $this->kirimSebagaiAdmin('admin/pendaftaran/' . $c['id'] . '/lunas');
        $this->kirimSebagaiAdmin('admin/pendaftaran/' . $c['id'] . '/hapus');

        $sebelum = $this->potret();
        $awalan  = $this->awalan();
        $this->assertNotEmpty($sebelum['bukti']);

        $this->assertSame(['pendaftaran' => 2, 'atlet' => 7, 'berkas' => 3], (new PaketPendaftaran())->ekspor($this->zip));

        // Server baru: tanpa pendaftaran, berkas, dan nomor dada yang pernah dibagikan.
        $this->db->query('DELETE FROM pendaftaran');
        $this->db->query('ALTER TABLE pendaftaran AUTO_INCREMENT = 1');
        $this->db->query('UPDATE awalan_nomor_dada SET nomor_terakhir = 0');
        $this->hapusBerkas();

        $this->assertSame(['pendaftaran' => 2, 'atlet' => 7, 'berkas' => 3], (new PaketPendaftaran())->impor($this->zip));

        $this->assertSame($sebelum, $this->potret());
        // Nomor dada pendaftaran yang dihapus di server lama tetap tidak dipakai ulang.
        $this->assertSame($awalan, $this->awalan());

        foreach ($sebelum['berkas'] as $penuh) {
            $this->assertFileExists($penuh);
        }

        // Pendaftaran baru melanjutkan nomor urut ID server lama (melewati yang dihapus).
        $baru = $this->buatPendaftaranPanel(['sekolah' => ['nama' => 'SD KARANGAN BARU', 'npsn' => '99999920']]);
        $this->assertSame($c['id'] + 1, $baru['id']);

        // Impor kedua ditolak karena server sudah punya pendaftaran.
        $this->expectExceptionMessage('Server ini sudah punya 3 pendaftaran.');
        (new PaketPendaftaran())->impor($this->zip);
    }

    public function testImporDitolakBilaNomorLombaTidakAdaDanTidakAdaYangTersimpan(): void
    {
        $this->buatPendaftaranPanel(['foto' => true]);
        (new PaketPendaftaran())->ekspor($this->zip);
        $this->db->query('DELETE FROM pendaftaran');
        $this->hapusBerkas();
        $this->db->table('nomor_lomba')->where('nama', 'Frog Jump')->update(['nama' => 'Frog Jump Baru']);

        try {
            (new PaketPendaftaran())->impor($this->zip);
            $this->fail('Impor seharusnya ditolak.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Tidak ditemukan: Nomor Frog Jump putra (KU 10)', $e->getMessage());
        }

        $this->seeNumRecords(0, 'pendaftaran', []);
        $this->assertSame([], glob(FotoPenanggungJawab::folderDasar() . FotoPenanggungJawab::FOLDER . '/*.jpg') ?: []);
    }

    public function testImporMenolakBerkasYangBukanEkspor(): void
    {
        $zip = new ZipArchive();
        $zip->open($this->zip, ZipArchive::CREATE);
        $zip->addFromString('data.json', '{"versi": 99}');
        $zip->close();

        $this->expectExceptionMessage('bukan ekspor pendaftaran yang dikenal');
        (new PaketPendaftaran())->impor($this->zip);
    }

    public function testTombolEksporMengunduhZipDanPerintahImpor(): void
    {
        $this->buatPendaftaranPanel();

        $hasil = $this->bukaSebagaiAdmin('admin/pendaftaran/ekspor');
        $hasil->assertOK();
        $this->assertStringContainsString('Ekspor_Pendaftaran_', $hasil->response()->getHeaderLine('Content-Disposition'));
        file_put_contents($this->zip, (string) $hasil->response()->getBody());

        $this->db->query('DELETE FROM pendaftaran');
        $this->assertSame(EXIT_SUCCESS, service('commands')->run('pendaftaran:impor', [$this->zip]));
        $this->seeNumRecords(1, 'pendaftaran', []);

        $this->assertSame(EXIT_ERROR, service('commands')->run('pendaftaran:impor', []));
        $this->assertBelumMasukDialihkan('admin/pendaftaran/ekspor');
    }

    /**
     * Isi data pendaftaran tanpa id baris anak (yang boleh berubah), dengan nama sebagai pengganti id induk.
     *
     * @return array<string, mixed>
     */
    private function potret(): array
    {
        $baris = fn (string $sql): array => $this->db->query($sql)->getResultArray();

        return [
            'pendaftaran' => $baris('SELECT id, id_pendaftaran, jenjang, npsn, nama_sekolah, kabupaten, subtotal_individu, subtotal_beregu, total, status, waktu_lunas, dibuat_pada FROM pendaftaran ORDER BY id'),
            'pj'          => $baris('SELECT p.id_pendaftaran, j.nama, j.nomor_wa, j.foto FROM penanggung_jawab j JOIN pendaftaran p ON p.id = j.pendaftaran_id ORDER BY j.id'),
            'atlet'       => $baris('SELECT p.id_pendaftaran, a.nama, a.nisn, a.tanggal_lahir, a.jenis_kelamin, k.nama ku, a.nomor_dada FROM atlet a JOIN pendaftaran p ON p.id = a.pendaftaran_id JOIN kelompok_umur k ON k.id = a.kelompok_umur_id ORDER BY a.id'),
            'nomor'       => $baris('SELECT a.nisn, n.nama, n.kategori, an.biaya FROM atlet_nomor an JOIN atlet a ON a.id = an.atlet_id JOIN nomor_lomba n ON n.id = an.nomor_lomba_id ORDER BY a.nisn, n.id'),
            'beregu'      => $baris('SELECT p.id_pendaftaran, n.nama, n.kategori, b.biaya_tim FROM pendaftaran_beregu b JOIN pendaftaran p ON p.id = b.pendaftaran_id JOIN nomor_lomba n ON n.id = b.nomor_lomba_id ORDER BY b.id'),
            'bukti'       => $baris('SELECT p.id_pendaftaran, b.berkas, b.ukuran FROM bukti_bayar b JOIN pendaftaran p ON p.id = b.pendaftaran_id ORDER BY b.id'),
            'berkas'      => array_merge(
                array_map(static fn (array $r): string => FotoPenanggungJawab::folderDasar() . $r['foto'], $baris('SELECT foto FROM penanggung_jawab WHERE foto IS NOT NULL ORDER BY id')),
                array_map(static fn (array $r): string => BerkasBuktiBayar::folderDasar() . $r['berkas'], $baris('SELECT berkas FROM bukti_bayar ORDER BY id')),
            ),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function awalan(): array
    {
        return array_column($this->db->table('awalan_nomor_dada')->select('awalan, nomor_terakhir')->get()->getResultArray(), 'nomor_terakhir', 'awalan');
    }

    private function hapusBerkas(): void
    {
        foreach ([FotoPenanggungJawab::folderDasar() . FotoPenanggungJawab::FOLDER, BerkasBuktiBayar::folderDasar() . BerkasBuktiBayar::FOLDER] as $folder) {
            foreach (glob($folder . '/*.jpg') ?: [] as $berkas) {
                unlink($berkas);
            }
        }
    }
}
