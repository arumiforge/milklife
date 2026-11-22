<?php

use App\Libraries\Hasil\DataTabelSkor;
use App\Libraries\Hasil\UnduhanHasil;
use App\Libraries\Hasil\HasilLomba;
use App\Libraries\Panel\PaketPendaftaran;
use App\Libraries\Panel\StatusPembayaran;
use CodeIgniter\HTTP\DownloadResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\Support\KirimFormulir;
use Tests\Support\PanelDataContoh;
use Tests\Support\UjiBasisData;
use Tests\Support\UnggahanSementara;

/**
 * Kalkulator poin multi-lomba (F9-10, K140 butir 7b): pasang tabel bawaan,
 * jenis hasil Poin multi-lomba di Pengaturan > Nomor lomba, isian prestasi
 * per komponen, total dan peringkat, ubah baris dan impor Excel menghitung
 * ulang, halaman publik, unduhan, dan paket pindah server.
 *
 * @internal
 */
final class TabelSkorTest extends UjiBasisData
{
    use KirimFormulir;
    use PanelDataContoh;
    use UnggahanSementara;

    private const ALAMAT = 'admin/pengaturan/tabel-skor';

    protected function setUp(): void
    {
        parent::setUp();
        $this->siapkanDataPanel();
    }

    protected function tearDown(): void
    {
        // Uji halaman sesudahnya (tanpa migrasi ulang) mengharapkan belum ada pendaftaran.
        $this->db->table('pendaftaran')->where('id >', 0)->delete();
        $this->bersihkanBerkasPanel();
        parent::tearDown();
    }

    public function testHalamanHanyaUntukAdmin(): void
    {
        $this->assertBelumMasukDialihkan(self::ALAMAT);
        $this->assertBelumMasukDialihkan(self::ALAMAT . '/1/lari-60/putra');
        $this->assertBelumMasukDialihkan(self::ALAMAT . '/pasang', 'post');
        $this->assertBelumMasukDialihkan(self::ALAMAT . '/1/lari-60/putra/impor', 'post');
        $this->assertTokenCsrfWajib(self::ALAMAT . '/pasang');
    }

    public function testPasangTabelBawaanDariPengaturan(): void
    {
        $halaman = $this->bukaSebagaiAdmin(self::ALAMAT);
        $halaman->assertOK();
        $halaman->assertSee('Belum terpasang: Pancalomba SMA/SMK, Trilomba O2SN SMP.');

        $this->assertBerhasilDialihkan($this->kirimSebagaiAdmin(self::ALAMAT . '/pasang'), self::ALAMAT, 'Tabel bawaan dipasang: Pancalomba SMA/SMK, Trilomba O2SN SMP. Pilih tabelnya di Pengaturan > Nomor lomba untuk nomor Trilomba.');
        $this->assertSame(2, $this->db->table('tabel_skor')->countAllResults());
        $this->assertGreaterThan(15000, $this->db->table('tabel_skor_baris')->countAllResults());

        // Dipasang lagi: tidak ada yang berubah.
        $this->assertBerhasilDialihkan($this->kirimSebagaiAdmin(self::ALAMAT . '/pasang'), self::ALAMAT, 'Semua tabel bawaan sudah terpasang.');
        $this->assertSame(2, $this->db->table('tabel_skor')->countAllResults());

        $halaman = $this->bukaSebagaiAdmin(self::ALAMAT);
        $halaman->assertDontSee('Belum terpasang');
        $halaman->assertSee('Trilomba O2SN SMP');
        $halaman->assertSee('Lempar Lembing');

        $tri   = $this->idTabel('trilomba-o2sn-smp');
        $baris = $this->bukaSebagaiAdmin(self::ALAMAT . '/' . $tri . '/lari-60/putra');
        $baris->assertOK();
        $baris->assertSee('561 baris');
        $baris->assertSeeElement('#tabel-baris');
        $baris->assertSee('7,00');
    }

    public function testTrilombaMenghitungPoinTotalDanPeringkat(): void
    {
        $nomor = $this->nomorTrilomba();
        $atlet = $this->tigaAtletKu12();

        // Formulir nomor lomba menampilkan pilihan tabel dan komponennya.
        $formulir = $this->bukaSebagaiAdmin('admin/pengaturan/nomor-lomba/' . $nomor . '/ubah');
        $formulir->assertOK();
        $formulir->assertSee('Poin multi-lomba');
        $formulir->assertSeeElement('#komponen-skor-' . $this->idTabel('trilomba-o2sn-smp'));

        $this->kirimSebagaiAdmin('admin/hasil/nomor/' . $nomor . '/babak', ['nama' => 'Final', 'final' => '1', 'isi' => 'semua']);
        $babak = (int) $this->db->table('babak')->where('nomor_lomba_id', $nomor)->get()->getRow()->id;
        $id    = $this->idHasil($babak);

        $halaman = $this->bukaSebagaiAdmin('admin/hasil/babak/' . $babak);
        $halaman->assertOK();
        $halaman->assertSee('Lari 60 m');
        $halaman->assertSee('Tolak Peluru');
        $halaman->assertSee('Total poin');

        // 60 m 7,00 = 882, lompat jauh 8,10 = 1086, tolak peluru 23,99 = 1350.
        $simpan = $this->kirimSebagaiAdmin('admin/hasil/babak/' . $babak, ['hasil' => [
            $id[$atlet[0]] => ['komponen' => ['lari-60' => '7,00', 'lompat-jauh' => '8,10', 'tolak-peluru' => '23,99'], 'keterangan' => ''],
            $id[$atlet[1]] => ['komponen' => ['lari-60' => '5,46', 'lompat-jauh' => '', 'tolak-peluru' => ''], 'keterangan' => ''],
            $id[$atlet[2]] => ['komponen' => ['lari-60' => '0,70', 'lompat-jauh' => '9,63', 'tolak-peluru' => '26,36'], 'keterangan' => ''],
        ]]);
        $this->assertBerhasilDialihkan($simpan, 'admin/hasil/babak/' . $babak);

        $this->assertSame([$atlet[0] => 3318, $atlet[1] => 1500, $atlet[2] => 3000], $this->prestasi($babak));
        $this->assertSame([$atlet[0] => 1, $atlet[2] => 2, $atlet[1] => 3], $this->peringkat($babak));

        $rincian = json_decode((string) $this->db->table('hasil')->where('id', $id[$atlet[0]])->get()->getRow()->rincian, true);
        $this->assertSame(['prestasi' => 700, 'poin' => 882, 'luar' => null], $rincian['lari-60']);

        // Prestasi di luar tabel: 0 poin dengan peringatan.
        $isi = $this->bukaSebagaiAdmin('admin/hasil/babak/' . $babak);
        $isi->assertSeeElement('#peringatan-luar-tabel');
        $isi->assertSee('882 poin');
        $isi->assertSee('di luar tabel');

        // Isian salah: tidak ada yang tersimpan.
        $salah = $this->kirimSebagaiAdmin('admin/hasil/babak/' . $babak, ['hasil' => [
            $id[$atlet[0]] => ['komponen' => ['lari-60' => 'tujuh', 'lompat-jauh' => '8,10', 'tolak-peluru' => '23,99'], 'keterangan' => ''],
        ]]);
        $this->assertGagalDialihkan($salah, 'admin/hasil/babak/' . $babak);
        $this->assertSame(3318, $this->prestasi($babak)[$atlet[0]]);

        // Jenis hasil dan tabelnya terkunci setelah ada prestasi.
        $ubah = $this->kirimSebagaiAdmin('admin/pengaturan/nomor-lomba/' . $nomor, $this->isianNomor($nomor, ['jenis_hasil' => 'poin']));
        $ubah->assertSee('sudah punya prestasi di hasil lomba');
        $ubah = $this->kirimSebagaiAdmin('admin/pengaturan/nomor-lomba/' . $nomor, $this->isianNomor($nomor, ['komponen_skor' => [$this->idTabel('trilomba-o2sn-smp') => ['lari-60']]]));
        $ubah->assertSee('tabel skor dan komponennya tidak bisa diganti');

        // Halaman publik dan unduhan menampilkan total dan rincian.
        $this->kirimSebagaiAdmin('admin/hasil/nomor/' . $nomor . '/terbitkan');
        $publik = $this->get('hasil/' . $nomor);
        $publik->assertOK();
        $publik->assertSee('3318 poin');
        $publik->assertSee('Lari 60 m 7,00 (882)');
        // Komponen kosong ditandai "–" dengan 0 poin (K143).
        $publik->assertSee('Lompat Jauh – (0)');

        $hasil   = new HasilLomba();
        $data    = $hasil->nomor($nomor);
        $unduhan = new UnduhanHasil($data, $hasil->semuaBabak($data));
        $this->assertStringContainsString('Lompat Jauh 8,10 (1086)', $unduhan->html(false));
        $this->assertStringContainsString('Tolak Peluru – (0)', $unduhan->html(false));
        $lembar = $unduhan->excel()->lembar()->getActiveSheet()->toArray();
        $this->assertContains('Poin Tolak Peluru', $lembar[0]);
        $this->assertContains('–', array_merge(...$lembar));

        // Klasemen tetap memakai juara dari Final.
        $this->assertSame([1, 2, 3], array_column($hasil->juara(true), 'peringkat'));
    }

    public function testUbahBarisDanImporExcelMenghitungUlang(): void
    {
        $nomor = $this->nomorTrilomba();
        $atlet = $this->tigaAtletKu12();
        $tri   = $this->idTabel('trilomba-o2sn-smp');

        $this->kirimSebagaiAdmin('admin/hasil/nomor/' . $nomor . '/babak', ['nama' => 'Final', 'final' => '1', 'isi' => 'semua']);
        $babak = (int) $this->db->table('babak')->where('nomor_lomba_id', $nomor)->get()->getRow()->id;
        $id    = $this->idHasil($babak);
        $this->kirimSebagaiAdmin('admin/hasil/babak/' . $babak, ['hasil' => [
            $id[$atlet[0]] => ['komponen' => ['lari-60' => '7,00'], 'keterangan' => ''],
            $id[$atlet[1]] => ['komponen' => ['lari-60' => '7,01'], 'keterangan' => ''],
        ]]);
        $this->assertSame([$atlet[0] => 882, $atlet[1] => 879, $atlet[2] => null], $this->prestasi($babak));

        // Ubah satu baris: 7,01 menjadi 900 poin, peringkat ikut berubah.
        $alamat = self::ALAMAT . '/' . $tri . '/lari-60/putra';
        $this->assertBerhasilDialihkan($this->kirimSebagaiAdmin($alamat, ['prestasi' => '7,01', 'poin' => '900']), $alamat);
        $this->assertSame([$atlet[0] => 882, $atlet[1] => 900, $atlet[2] => null], $this->prestasi($babak));
        $this->assertSame([$atlet[1] => 1, $atlet[0] => 2, $atlet[2] => null], $this->peringkat($babak));

        $salah = $this->kirimSebagaiAdmin($alamat, ['prestasi' => '7 detik', 'poin' => 'banyak']);
        $this->assertGagalDialihkan($salah, $alamat . '#ubah-baris');

        // Hapus baris 7,00: prestasi 7,00 memakai baris yang lebih buruk (7,01 = 900).
        $this->assertBerhasilDialihkan($this->kirimSebagaiAdmin($alamat . '/hapus', ['prestasi' => '700']), $alamat);
        $this->assertSame(900, $this->prestasi($babak)[$atlet[0]]);

        // Impor Excel mengganti semua baris lari 60 m putra.
        $buku = new Spreadsheet();
        $buku->getActiveSheet()->fromArray([['Prestasi', 'Poin'], ['6,00', 1000], ['7,00', 500], ['8,00', 100]]);
        $jalur = tempnam(sys_get_temp_dir(), 'tabel-skor-') . '.xlsx';
        (new Xlsx($buku))->save($jalur);
        $this->siapkanBerkas('berkas', 'tabel.xlsx', (string) file_get_contents($jalur));
        unlink($jalur);

        $impor = $this->kirimSebagaiAdmin($alamat . '/impor');
        $this->assertBerhasilDialihkan($impor, $alamat, '3 baris dari Excel menggantikan tabel Lari 60 m putra. Poin hasil lomba yang memakai tabel ini dihitung ulang.');
        $this->assertSame(3, $this->db->table('tabel_skor_baris')->where(['tabel_skor_id' => $tri, 'komponen' => 'lari-60', 'kategori' => 'putra'])->countAllResults());
        // 7,00 = 500; 7,01 di antara 7,00 dan 8,00 → 100.
        $this->assertSame([$atlet[0] => 500, $atlet[1] => 100, $atlet[2] => null], $this->prestasi($babak));

        // Unduh Excel baris.
        $unduh = $this->bukaSebagaiAdmin($alamat . '/excel')->response();
        $this->assertInstanceOf(DownloadResponse::class, $unduh);
        ob_start();
        $unduh->sendBody();
        $jalur = tempnam(sys_get_temp_dir(), 'tabel-skor-') . '.xlsx';
        file_put_contents($jalur, (string) ob_get_clean());
        $sel = IOFactory::load($jalur)->getActiveSheet()->toArray();
        unlink($jalur);
        $this->assertSame(['6,00', 1000], [$sel[1][0], (int) $sel[1][1]]);

        // Tabel yang dipakai tidak bisa dihapus.
        $this->assertGagalDialihkan($this->kirimSebagaiAdmin(self::ALAMAT . '/' . $tri . '/hapus'), self::ALAMAT);
        $this->assertBerhasilDialihkan($this->kirimSebagaiAdmin(self::ALAMAT . '/' . $this->idTabel('pancalomba-sma-smk') . '/hapus'), self::ALAMAT);
    }

    public function testImporBerisiBarisSalahTidakMengganti(): void
    {
        (new DataTabelSkor())->pasangBawaan();
        $tri    = $this->idTabel('trilomba-o2sn-smp');
        $alamat = self::ALAMAT . '/' . $tri . '/lari-60/putra';

        $this->siapkanBerkas('berkas', 'tabel.csv', "Prestasi,Poin\n6.00,1000\ntujuh,5\n");
        $gagal = $this->kirimSebagaiAdmin($alamat . '/impor');
        $this->assertGagalDialihkan($gagal, $alamat . '#impor');
        $this->assertSame(561, $this->db->table('tabel_skor_baris')->where(['tabel_skor_id' => $tri, 'komponen' => 'lari-60', 'kategori' => 'putra'])->countAllResults());

        $halaman = $this->bukaSebagaiAdmin($alamat, ['galat_impor' => ['Baris 3: Prestasi "tujuh" belum benar.']]);
        $halaman->assertSeeElement('#galat-impor');
    }

    public function testTambahTabelBaru(): void
    {
        $hasil = $this->kirimSebagaiAdmin(self::ALAMAT, ['nama' => 'Dwilomba Karangan', 'komponen_nama' => ['Lari 50 m', 'Lompat Jauh', ''], 'komponen_jenis' => ['waktu', 'jarak', 'jarak']]);
        $tabel = $this->db->table('tabel_skor')->where('nama', 'Dwilomba Karangan')->get()->getRowArray();
        $this->assertIsArray($tabel);
        $this->assertBerhasilDialihkan($hasil, self::ALAMAT . '#tabel-' . $tabel['id']);
        $this->assertSame([['kode' => 'lari-50-m', 'nama' => 'Lari 50 m', 'jenis' => 'waktu'], ['kode' => 'lompat-jauh', 'nama' => 'Lompat Jauh', 'jenis' => 'jarak']], json_decode($tabel['komponen'], true));

        $alamat = self::ALAMAT . '/' . $tabel['id'] . '/lari-50-m/putri';
        $this->bukaSebagaiAdmin($alamat)->assertSeeElement('#baris-kosong');
        $this->assertBerhasilDialihkan($this->kirimSebagaiAdmin($alamat, ['prestasi' => '8,5', 'poin' => '700']), $alamat);
        $this->assertSame([[850, 700]], (new DataTabelSkor())->baris((int) $tabel['id'], 'lari-50-m', 'putri'));

        $kosong = $this->kirimSebagaiAdmin(self::ALAMAT, ['nama' => '', 'komponen_nama' => ['', ''], 'komponen_jenis' => ['waktu', 'jarak']]);
        $this->assertGagalDialihkan($kosong, self::ALAMAT . '#tambah-tabel');
    }


    public function testTetapTersimpanWalauKoneksiPernahMenggagalkanTransaksi(): void
    {
        $data = new DataTabelSkor();
        $id   = $data->buatTabel('Dwilomba Rekaan', [['nama' => 'Lari 50 m', 'jenis' => 'waktu']])['id'];

        $this->tinggalkanStatusTransaksiGagal();
        $this->assertSame([], $data->simpanBaris($id, 'lari-50-m', 'putra', '8,5', '700'));
        $this->assertSame([[850, 700]], $data->baris($id, 'lari-50-m', 'putra'));

        $this->tinggalkanStatusTransaksiGagal();
        $data->gantiBaris($id, 'lari-50-m', 'putra', [[800, 900], [900, 600]]);
        $this->assertSame([[800, 900], [900, 600]], $data->baris($id, 'lari-50-m', 'putra'));

        $this->tinggalkanStatusTransaksiGagal();
        $this->assertTrue($data->hapusBaris($id, 'lari-50-m', 'putra', 900));
        $this->assertSame([[800, 900]], $data->baris($id, 'lari-50-m', 'putra'));

        $this->tinggalkanStatusTransaksiGagal();
        $baru = $data->simpanData([
            'kode'     => 'ekalomba-rekaan',
            'nama'     => 'Ekalomba Rekaan',
            'komponen' => [['kode' => 'lari-50-m', 'nama' => 'Lari 50 m', 'jenis' => 'waktu', 'baris' => ['putri' => [[850, 650]]]]],
        ]);
        $this->assertSame([[850, 650]], $data->baris($baru, 'lari-50-m', 'putri'));
    }

    public function testPaketPindahServerMembawaTabelDanRincian(): void
    {
        $nomor = $this->nomorTrilomba();
        $atlet = $this->tigaAtletKu12();
        $tri   = $this->idTabel('trilomba-o2sn-smp');
        (new DataTabelSkor())->simpanBaris($tri, 'lari-60', 'putra', '7,00', '999');

        $this->kirimSebagaiAdmin('admin/hasil/nomor/' . $nomor . '/babak', ['nama' => 'Final', 'final' => '1', 'isi' => 'semua']);
        $babak = (int) $this->db->table('babak')->where('nomor_lomba_id', $nomor)->get()->getRow()->id;
        $id    = $this->idHasil($babak);
        $this->kirimSebagaiAdmin('admin/hasil/babak/' . $babak, ['hasil' => [$id[$atlet[0]] => ['komponen' => ['lari-60' => '7,00', 'lompat-jauh' => '8,10'], 'keterangan' => '']]]);

        $zip = tempnam(sys_get_temp_dir(), 'paket-') . '.zip';
        (new PaketPendaftaran())->ekspor($zip);

        // Server baru: data induk sama, tabel bawaan belum diubah admin.
        $this->db->table('hasil')->emptyTable();
        $this->db->table('babak')->emptyTable();
        $this->db->table('nomor_lomba')->where('id', $nomor)->update(['jenis_hasil' => 'poin', 'tabel_skor_id' => null, 'komponen_skor' => null]);
        $this->db->table('tabel_skor_baris')->emptyTable();
        $this->db->table('tabel_skor')->emptyTable();
        foreach (['atlet_nomor', 'atlet', 'penanggung_jawab', 'pendaftaran'] as $tabel) {
            $this->db->table($tabel)->emptyTable();
        }

        (new PaketPendaftaran())->impor($zip);
        unlink($zip);

        $baru = $this->db->table('nomor_lomba')->where('id', $nomor)->get()->getRowArray();
        $this->assertSame('multi', $baru['jenis_hasil']);
        $this->assertSame((int) $this->idTabel('trilomba-o2sn-smp'), (int) $baru['tabel_skor_id']);
        $this->assertSame([[546, 1500]], array_slice((new DataTabelSkor())->baris((int) $baru['tabel_skor_id'], 'lari-60', 'putra'), 0, 1));
        $this->assertContains([700, 999], (new DataTabelSkor())->baris((int) $baru['tabel_skor_id'], 'lari-60', 'putra'));

        $h = $this->db->table('hasil')->where('prestasi IS NOT NULL', null, false)->get()->getRowArray();
        $this->assertSame(999 + 1086, (int) $h['prestasi']);
        $this->assertSame(['prestasi' => 810, 'poin' => 1086, 'luar' => null], json_decode((string) $h['rincian'], true)['lompat-jauh']);
    }

    /**
     * Pasang tabel bawaan dan jadikan Lari 60 m putra KU 12 nomor Trilomba
     * berjenis poin multi-lomba lewat Pengaturan > Nomor lomba.
     */
    private function nomorTrilomba(): int
    {
        (new DataTabelSkor())->pasangBawaan();
        $nomor = $this->idInduk['lari60putraKu12'];

        $hasil = $this->kirimSebagaiAdmin('admin/pengaturan/nomor-lomba/' . $nomor, $this->isianNomor($nomor, ['nama' => 'Trilomba']));
        $this->assertTrue($hasil->isRedirect(), 'Nomor Trilomba tersimpan.');
        $this->assertSame('multi', $this->db->table('nomor_lomba')->where('id', $nomor)->get()->getRow()->jenis_hasil);

        return $nomor;
    }

    /**
     * @param array<string, mixed> $ubah
     *
     * @return array<string, mixed>
     */
    private function isianNomor(int $nomor, array $ubah = []): array
    {
        $n = $this->db->table('nomor_lomba')->where('id', $nomor)->get()->getRowArray();

        return $ubah + [
            'kelompok_umur_id' => $n['kelompok_umur_id'],
            'nama'             => $n['nama'],
            'jenis'            => 'individu',
            'kategori'         => 'putra',
            'jenis_hasil'      => 'multi',
            'tabel_skor_id'    => (string) $this->idTabel('trilomba-o2sn-smp'),
            'urutan'           => (string) $n['urutan'],
            'aktif'            => '1',
        ];
    }

    /**
     * @return list<int> id atlet
     */
    private function tigaAtletKu12(): array
    {
        $atlet = [];

        for ($i = 0; $i < 3; $i++) {
            $atlet[] = $this->atletPanel(['ku' => 'ku12', 'tanggal_lahir' => '2015-03-0' . ($i + 1), 'nomor' => ['lari60putraKu12']]);
        }

        $p = $this->buatPendaftaranPanel(['atlet' => $atlet]);
        (new StatusPembayaran())->tandaiLunas($p['id']);

        return $p['atlet'];
    }

    private function idTabel(string $kode): int
    {
        return (int) $this->db->table('tabel_skor')->where('kode', $kode)->get()->getRow()->id;
    }

    /**
     * @return array<int, int> id atlet => id hasil
     */
    private function idHasil(int $babak): array
    {
        return array_map('intval', array_column($this->db->table('hasil')->where('babak_id', $babak)->get()->getResultArray(), 'id', 'atlet_id'));
    }

    /**
     * @return array<int, int|null> id atlet => total poin
     */
    private function prestasi(int $babak): array
    {
        $hasil = [];

        foreach ($this->db->table('hasil')->where('babak_id', $babak)->orderBy('atlet_id')->get()->getResultArray() as $h) {
            $hasil[(int) $h['atlet_id']] = $h['prestasi'] === null ? null : (int) $h['prestasi'];
        }

        return $hasil;
    }

    /**
     * @return array<int, int|null> id atlet => peringkat, urut peringkat
     */
    private function peringkat(int $babak): array
    {
        $hasil = [];

        foreach ($this->db->table('hasil')->where('babak_id', $babak)->orderBy('peringkat IS NULL', '', false)->orderBy('peringkat')->orderBy('atlet_id')->get()->getResultArray() as $h) {
            $hasil[(int) $h['atlet_id']] = $h['peringkat'] === null ? null : (int) $h['peringkat'];
        }

        return $hasil;
    }
}
