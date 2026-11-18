<?php

use App\Libraries\Panel\BerkasBuktiBayar;
use App\Libraries\Panel\PaketPendaftaran;
use App\Libraries\Pendaftaran\FotoPenanggungJawab;
use CodeIgniter\HTTP\DownloadResponse;
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
        $this->bersihkanSementara();
        $this->zip = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'milklife-paket-' . bin2hex(random_bytes(6)) . '.zip';
    }

    protected function tearDown(): void
    {
        @unlink($this->zip);
        $this->bersihkanSementara();
        $this->bersihkanBerkasPanel();
        parent::tearDown();
    }

    public function testEksporLaluImporKeServerKosongSamaPersis(): void
    {
        $a = $this->buatPendaftaranPanel(['foto' => true, 'atlet' => array_merge([$this->atletPanel(['nomor' => ['lari60putra', 'frog']])], $this->timPanel('kanga', 4))]);
        $b = $this->buatPendaftaranPanel(['sekolah' => ['nama' => 'SMP KARANGAN JAYA', 'npsn' => null]]);
        $c = $this->buatPendaftaranPanel(['sekolah' => ['nama' => 'SD KARANGAN HILANG', 'npsn' => '99999919']]);
        $this->buatBuktiBayarPanel($a['id'], 2);
        // Kunci kirim wizard (K118) ikut pindah; $b dari halaman lama tanpa kunci.
        $this->db->table('pendaftaran')->where('id', $a['id'])->update(['kunci_kirim' => str_repeat('a1', 16)]);
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

    public function testUsulanNamaDanSekolahDisuntingIkutPindah(): void
    {
        $this->db->table('sekolah')->insertBatch([
            ['npsn' => '99100001', 'nama' => 'MIS KARANGAN', 'jenjang' => 'SD', 'kabupaten' => 'Kudus', 'desa_kelurahan' => 'Rekaan', 'disunting_pada' => null],
            ['npsn' => '99100002', 'nama' => 'MIS KARANGAN KHAYALAN', 'jenjang' => 'SD', 'kabupaten' => 'Kudus', 'desa_kelurahan' => 'Khayalan', 'disunting_pada' => '2026-11-14 09:00:00'],
        ]);
        $p = $this->buatPendaftaranPanel(['foto' => true, 'sekolah' => ['npsn' => '99100001', 'nama' => 'MIS KARANGAN REKAAN', 'jenjang' => 'A', 'kabupaten' => 'Kudus']]);
        $this->db->table('usulan_nama_sekolah')->insertBatch([
            ['npsn' => '99100001', 'pendaftaran_id' => $p['id'], 'nama_lama' => 'MIS KARANGAN', 'nama_usulan' => 'MIS KARANGAN REKAAN', 'status' => 'menunggu', 'nama_disetujui' => null, 'dibuat_pada' => '2026-11-14 10:00:00'],
            ['npsn' => '99100002', 'pendaftaran_id' => null, 'nama_lama' => 'MIS KARANGAN', 'nama_usulan' => 'MIS KARANGAN KHAYALAN', 'status' => 'disetujui', 'nama_disetujui' => 'MIS KARANGAN KHAYALAN', 'dibuat_pada' => '2026-11-14 08:00:00'],
        ]);

        (new PaketPendaftaran())->ekspor($this->zip);

        // Server baru: sekolah dari impor resmi (nama umum), tanpa pendaftaran dan usulan.
        $this->db->query('DELETE FROM pendaftaran');
        $this->db->query('DELETE FROM usulan_nama_sekolah');
        $this->db->table('sekolah')->where('npsn', '99100002')->update(['nama' => 'MIS KARANGAN', 'disunting_pada' => null]);
        $this->hapusBerkas();

        (new PaketPendaftaran())->impor($this->zip);

        // K97: nama pembeda admin kembali; K99: usulan tetap bisa diputuskan di server baru.
        $this->seeInDatabase('sekolah', ['npsn' => '99100002', 'nama' => 'MIS KARANGAN KHAYALAN', 'disunting_pada' => '2026-11-14 09:00:00']);
        $this->seeInDatabase('usulan_nama_sekolah', ['npsn' => '99100001', 'pendaftaran_id' => $p['id'], 'status' => 'menunggu', 'nama_usulan' => 'MIS KARANGAN REKAAN']);
        $this->seeInDatabase('usulan_nama_sekolah', ['npsn' => '99100002', 'status' => 'disetujui']);
        $this->seeInDatabase('pendaftaran', ['id' => $p['id'], 'nama_sekolah' => 'MIS KARANGAN REKAAN']);
    }

    public function testIdLamaPendaftaranGabunganIkutPindah(): void
    {
        $awal  = $this->buatPendaftaranPanel(['status' => 'lunas']);
        $susul = $this->buatPendaftaranPanel(['atlet' => [$this->atletPanel()]]);
        // Kunci kirim (K118) tidak menghalangi penggabungan; kunci tujuan tetap.
        $this->db->table('pendaftaran')->where('id', $awal['id'])->update(['kunci_kirim' => str_repeat('b2', 16)]);
        $this->db->table('pendaftaran')->where('id', $susul['id'])->update(['kunci_kirim' => str_repeat('c3', 16)]);
        $alamatLama = App\Libraries\BuktiPendaftaran::alamat(['id' => $susul['id'], 'id_pendaftaran' => $susul['id_pendaftaran']]);
        $this->kirimSebagaiAdmin('admin/pendaftaran/' . $susul['id'] . '/lunas');
        $catatan = $this->db->table('pendaftaran_gabungan')->select('pendaftaran_id, id_pendaftaran_lama, kode_bukti_lama')->get()->getResultArray();
        $this->assertCount(1, $catatan);
        $this->assertSame([str_repeat('b2', 16)], array_column($this->db->table('pendaftaran')->select('kunci_kirim')->get()->getResultArray(), 'kunci_kirim'));

        (new PaketPendaftaran())->ekspor($this->zip);
        $this->db->query('DELETE FROM pendaftaran');
        $this->hapusBerkas();
        (new PaketPendaftaran())->impor($this->zip);

        // K109: ID dan kode bukti lama tetap mengarah ke pendaftaran gabungan.
        $this->assertSame($catatan, $this->db->table('pendaftaran_gabungan')->select('pendaftaran_id, id_pendaftaran_lama, kode_bukti_lama')->get()->getResultArray());
        $this->assertStringEndsWith('/' . $catatan[0]['kode_bukti_lama'] . '/' . $susul['id_pendaftaran'], $alamatLama);
        $this->assertSame($awal['id_pendaftaran'], (new App\Libraries\DataPendaftaran())->cariId($susul['id_pendaftaran'])['id_pendaftaran']);
        $this->seeInDatabase('pendaftaran', ['id' => $awal['id'], 'kunci_kirim' => str_repeat('b2', 16)]);
    }

    public function testAtletDitolakIkutPindah(): void
    {
        $p = $this->buatPendaftaranPanel(['foto' => true, 'atlet' => [
            $this->atletPanel(),
            $this->atletPanel(['ku' => 'ku12', 'tanggal_lahir' => '2016-05-01', 'nomor' => ['lari60putraKu12']]),
        ]]);
        $hasil = (new App\Libraries\Panel\TolakAtlet())->tolak($p['id'], [$p['atlet'][1]], 'Tahun lahir tidak sesuai kelompok umur.', 'Admin Uji');
        $this->assertTrue($hasil['tersimpan']);

        $kolom   = 'pendaftaran_id, nama, nisn, tanggal_lahir, jenis_kelamin, kelompok_umur, nomor_lomba, biaya_individu, kelompok_umur_sesuai_id, kelompok_umur_sesuai, alasan, total_sebelum, total_sesudah, ditolak_oleh, dibuat_pada';
        $catatan = $this->db->table('atlet_ditolak')->select($kolom)->get()->getResultArray();
        $this->assertCount(1, $catatan);

        (new PaketPendaftaran())->ekspor($this->zip);
        $this->db->query('DELETE FROM pendaftaran');
        $this->hapusBerkas();
        (new PaketPendaftaran())->impor($this->zip);

        // K116: catatan tetap ada, kelompok umur yang sesuai dicocokkan menurut nama.
        $this->assertSame($catatan, $this->db->table('atlet_ditolak')->select($kolom)->get()->getResultArray());
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

    public function testEksporMenghapusBerkasSementaraLebihDariSatuJam(): void
    {
        $folder   = PaketPendaftaran::FOLDER_SEMENTARA;
        $sekarang = time();
        $lama     = PaketPendaftaran::jalurSementara();
        $baru     = PaketPendaftaran::jalurSementara();
        $lain     = $folder . '/bukan-backup.txt';

        $this->assertStringStartsWith($folder . '/Backup_', $lama);
        $this->assertNotSame($lama, $baru, 'Nama berkas sementara harus acak.');

        foreach ([$lama, $baru, $lain] as $berkas) {
            file_put_contents($berkas, 'isi');
        }
        touch($lama, $sekarang - PaketPendaftaran::UMUR_SEMENTARA - 1);
        touch($baru, $sekarang - PaketPendaftaran::UMUR_SEMENTARA + 60);
        touch($lain, $sekarang - 10 * PaketPendaftaran::UMUR_SEMENTARA);

        $this->buatPendaftaranPanel();
        $this->bukaSebagaiAdmin('admin/pendaftaran/ekspor')->assertStatus(200);

        $this->assertFileDoesNotExist($lama, 'Zip lebih dari 1 jam harus dihapus.');
        $this->assertFileExists($baru, 'Zip yang belum 1 jam (mungkin sedang diunduh) tidak boleh dihapus.');
        $this->assertFileExists($lain, 'Hanya zip Backup yang dihapus.');
        $this->assertCount(2, glob($folder . '/Backup_*.zip') ?: []);
    }

    public function testHapusSementaraLama(): void
    {
        $folder = PaketPendaftaran::FOLDER_SEMENTARA;
        @mkdir($folder, 0700, true);
        file_put_contents($folder . '/Backup_a.zip', 'a');
        touch($folder . '/Backup_a.zip', 1_000_000);

        $this->assertSame(0, PaketPendaftaran::hapusSementaraLama($folder, 1_000_000 + PaketPendaftaran::UMUR_SEMENTARA));
        $this->assertSame(1, PaketPendaftaran::hapusSementaraLama($folder, 1_000_001 + PaketPendaftaran::UMUR_SEMENTARA));
        $this->assertFileDoesNotExist($folder . '/Backup_a.zip');
    }

    public function testTombolEksporMengunduhZipDanPerintahImpor(): void
    {
        $this->buatPendaftaranPanel();

        $hasil = $this->bukaSebagaiAdmin('admin/pendaftaran/ekspor');
        $hasil->assertStatus(200);

        // F7-25: dikirim dari berkas di `writable/cache/ekspor/`, tanpa dimuat ke memori.
        $jawaban = $hasil->response();
        $this->assertInstanceOf(DownloadResponse::class, $jawaban);
        $this->assertSame('', (string) $jawaban->getBody(), 'Isi zip tidak boleh dimuat ke badan jawaban.');
        $jawaban->buildHeaders(); // dipanggil `send()` sebelum isi dikirim
        $this->assertStringStartsWith('application/zip', $jawaban->getHeaderLine('Content-Type'));
        $this->assertMatchesRegularExpression('/attachment; filename="Backup_Pendaftaran_\d{8}_\d{6}\.zip"/', $jawaban->getHeaderLine('Content-Disposition'));
        $sementara = glob(PaketPendaftaran::FOLDER_SEMENTARA . '/Backup_*.zip') ?: [];
        $this->assertCount(1, $sementara);
        $this->assertSame((string) filesize($sementara[0]), $jawaban->getHeaderLine('Content-Length'));

        ob_start();
        $jawaban->sendBody();
        file_put_contents($this->zip, (string) ob_get_clean());
        $this->assertSame(md5_file($sementara[0]), md5_file($this->zip));

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
            'pendaftaran' => $baris('SELECT id, id_pendaftaran, kode_bukti, kunci_kirim, jenjang, npsn, nama_sekolah, kabupaten, subtotal_individu, subtotal_beregu, total, status, waktu_lunas, dibuat_pada FROM pendaftaran ORDER BY id'),
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

    private function bersihkanSementara(): void
    {
        foreach (glob(PaketPendaftaran::FOLDER_SEMENTARA . '/*') ?: [] as $berkas) {
            @unlink($berkas);
        }
    }
}
