<?php

use App\Commands\DataAwal;
use App\Models\AwalanNomorDadaModel;
use App\Models\KelompokUmurJenjangModel;
use App\Models\KelompokUmurModel;
use Tests\Support\UjiBasisData;

/**
 * Model kelompok umur, jenjang, dan awalan nomor dada (F2-03): periksa
 * isian, simpan dalam satu transaksi, tabrakan tahun lahir, dan aturan hapus.
 *
 * @internal
 */
final class KelompokUmurModelTest extends UjiBasisData
{
    private KelompokUmurModel $model;

    protected function setUp(): void
    {
        parent::setUp();

        $this->model = model(KelompokUmurModel::class, false);
    }

    // ---------------------------------------------------------------
    // Periksa
    // ---------------------------------------------------------------

    public function testIsianLengkapMenjadiDataBersih(): void
    {
        [$data, $galat] = $this->model->periksa($this->isian([
            'nama'              => '  KU   10 ',
            'tahun_lahir_awal'  => '2016',
            'tahun_lahir_akhir' => '2018',
            'jenjang'           => ['c', 'A'],
            'biaya_individu'    => 'Rp 50.000',
            'awalan'            => '6, 2',
            'urutan'            => '7',
            'aktif'             => '1',
        ]));

        $this->assertSame([], $galat);
        $this->assertSame([
            'nama'              => 'KU 10',
            'tahun_lahir_awal'  => 2016,
            'tahun_lahir_akhir' => 2018,
            'jenjang'           => ['A', 'C'],
            'biaya_individu'    => 50000,
            'urutan'            => 7,
            'aktif'             => 1,
            'awalan'            => ['6', '2'],
        ], $data);
    }

    public function testBatasBiayaAwalanJenjangBolehKosong(): void
    {
        [$data, $galat] = $this->model->periksa($this->isian(['nama' => 'KU 10', 'aktif' => '']));

        $this->assertSame([], $galat);
        $this->assertNull($data['tahun_lahir_awal']);
        $this->assertNull($data['tahun_lahir_akhir']);
        $this->assertNull($data['biaya_individu']);
        $this->assertSame([], $data['jenjang']);
        $this->assertSame([], $data['awalan']);
        $this->assertSame(0, $data['aktif']);
    }

    public function testUrutanKosongMemakaiUrutanBerikutnyaAtauYangTersimpan(): void
    {
        $id = $this->simpan(['nama' => 'KU 10', 'urutan' => '4']);

        [$baru] = $this->model->periksa($this->isian(['nama' => 'KU 12', 'urutan' => '']));
        $this->assertSame(5, $baru['urutan']);

        [$lama] = $this->model->periksa($this->isian(['nama' => 'KU 10', 'urutan' => '']), $id);
        $this->assertSame(4, $lama['urutan']);
    }

    /**
     * @return iterable<string, array{0: array<string, mixed>, 1: string, 2: string}>
     */
    public static function contohIsianSalah(): iterable
    {
        yield 'nama kosong' => [['nama' => '  '], 'nama', 'Nama kelompok umur belum diisi.'];
        yield 'nama terlalu panjang' => [['nama' => str_repeat('K', 51)], 'nama', 'Nama kelompok umur terlalu panjang.'];
        yield 'tahun bukan angka' => [['tahun_lahir_awal' => 'dua ribu'], 'tahun_lahir_awal', 'Tahun lahir paling awal belum benar. Tulis tahun 4 angka antara 1950 dan '];
        yield 'tahun dua angka' => [['tahun_lahir_akhir' => '18'], 'tahun_lahir_akhir', 'Tahun lahir paling akhir belum benar.'];
        yield 'tahun terlalu lama' => [['tahun_lahir_awal' => '1949'], 'tahun_lahir_awal', 'Tahun lahir paling awal belum benar.'];
        yield 'tahun di masa depan' => [['tahun_lahir_akhir' => (string) ((int) date('Y') + 1)], 'tahun_lahir_akhir', 'Tahun lahir paling akhir belum benar.'];
        yield 'awal lebih besar' => [['tahun_lahir_awal' => '2018', 'tahun_lahir_akhir' => '2016'], 'tahun_lahir_awal', 'Tahun lahir paling awal tidak boleh lebih besar dari tahun lahir paling akhir.'];
        yield 'jenjang tidak dikenal' => [['jenjang' => ['A', 'D']], 'jenjang', 'Pilihan jenjang tidak dikenal. Centang jenjang dari daftar: SD/MI, SMP/MTs, SMA/SMK/MA.'];
        yield 'biaya berkoma' => [['biaya_individu' => '50.000,50'], 'biaya_individu', 'Biaya individu belum benar. Tulis angka rupiah tanpa koma'];
        yield 'biaya minus' => [['biaya_individu' => '-5000'], 'biaya_individu', 'Biaya individu belum benar.'];
        yield 'urutan huruf' => [['urutan' => 'satu'], 'urutan', 'Urutan belum benar.'];
        yield 'urutan terlalu besar' => [['urutan' => '1000'], 'urutan', 'Urutan belum benar.'];
        yield 'awalan nol' => [['awalan' => '0'], 'awalan', 'Awalan "0" belum benar.'];
        yield 'awalan ganda' => [['awalan' => '3, 3'], 'awalan', 'Awalan 3 tertulis lebih dari sekali.'];
    }

    /**
     * @param array<string, mixed> $isian
     */
    #[PHPUnit\Framework\Attributes\DataProvider('contohIsianSalah')]
    public function testIsianSalahDitolak(array $isian, string $kotak, string $awalPesan): void
    {
        [, $galat] = $this->model->periksa($this->isian($isian + ['nama' => 'KU 10']));

        $this->assertArrayHasKey($kotak, $galat);
        $this->assertStringStartsWith($awalPesan, $galat[$kotak]);
    }

    public function testNamaKembarDitolakKecualiDirinyaSendiri(): void
    {
        $id = $this->simpan(['nama' => 'KU 10']);

        [, $galat] = $this->model->periksa($this->isian(['nama' => 'ku 10']));
        $this->assertSame('Nama "ku 10" sudah dipakai kelompok umur lain. Pakai nama lain, misalnya tambahkan keterangan di belakangnya.', $galat['nama']);

        [, $galat] = $this->model->periksa($this->isian(['nama' => 'KU 10']), $id);
        $this->assertArrayNotHasKey('nama', $galat);
    }

    public function testAwalanMilikKelompokUmurLainDitolak(): void
    {
        $this->simpan(['nama' => 'KU 10', 'awalan' => '2']);

        [, $galat] = $this->model->periksa($this->isian(['nama' => 'KU 12', 'awalan' => '3, 2']));

        $this->assertSame('Awalan 2 sudah dipakai KU 10. Satu awalan hanya untuk satu kelompok umur; pilih angka lain yang belum dipakai.', $galat['awalan']);
    }

    public function testAwalanTerpakaiTidakBolehDihapus(): void
    {
        $id = $this->simpan(['nama' => 'KU 10', 'awalan' => '2, 6']);
        $this->db->table('awalan_nomor_dada')->where('awalan', '2')->update(['nomor_terakhir' => 45]);

        [, $galat] = $this->model->periksa($this->isian(['nama' => 'KU 10', 'awalan' => '6']), $id);
        $this->assertSame('Awalan 2 sudah dipakai untuk nomor dada, jadi tidak bisa dihapus. Tulis lagi angka 2 di kotak awalan.', $galat['awalan']);

        // Awalan yang belum dipakai boleh dihapus; yang terpakai boleh pindah urutan.
        [, $galat] = $this->model->periksa($this->isian(['nama' => 'KU 10', 'awalan' => '7, 2']), $id);
        $this->assertSame([], $galat);
    }

    // ---------------------------------------------------------------
    // Tabrakan tahun lahir
    // ---------------------------------------------------------------

    public function testDataAwalTidakSalingBertabrakan(): void
    {
        DataAwal::isi($this->db);

        foreach ($this->model->findAll() as $ku) {
            [, $galat] = $this->model->periksa($this->isian([
                'nama'              => $ku['nama'],
                'tahun_lahir_awal'  => (string) $ku['tahun_lahir_awal'],
                'tahun_lahir_akhir' => (string) $ku['tahun_lahir_akhir'],
                'jenjang'           => model(KelompokUmurJenjangModel::class)->kodeUntuk((int) $ku['id']),
                'awalan'            => '',
            ]), (int) $ku['id']);

            $this->assertSame([], $galat, $ku['nama']);
        }
    }

    public function testRentangBertabrakanDenganKelompokUmurAktifDitolak(): void
    {
        DataAwal::isi($this->db);

        [, $galat] = $this->model->periksa($this->isian([
            'nama'              => 'KU 11',
            'tahun_lahir_awal'  => '2015',
            'tahun_lahir_akhir' => '2016',
            'jenjang'           => ['A'],
        ]));

        $this->assertSame(
            'Tahun lahir ini bertabrakan dengan KU 10 (lahir 2016–2018) untuk jenjang SD/MI. Dalam satu jenjang, satu tahun lahir hanya boleh masuk satu kelompok umur aktif. Ubah tahunnya, hapus centang jenjang yang sama, atau matikan salah satu kelompok umur.',
            $galat['tahun_lahir_awal'],
        );

        // Batas kosong = tidak terbatas: "lahir 2020 ke atas" menabrak KU 8 (2019 ke atas).
        [, $galat] = $this->model->periksa($this->isian(['nama' => 'KU 6', 'tahun_lahir_awal' => '2020', 'jenjang' => ['A']]));
        $this->assertStringStartsWith('Tahun lahir ini bertabrakan dengan KU 8 (Seneng Playon) (lahir 2019 ke atas) untuk jenjang SD/MI.', $galat['tahun_lahir_awal']);
    }

    public function testRentangBeririsanBolehUntukJenjangLain(): void
    {
        // K95: KU 12 (SD) diperlebar sampai 2012, beririsan dengan KU 15 (SMP, 2011–2014).
        DataAwal::isi($this->db);
        $id = (int) $this->model->where('nama', 'KU 12')->first()['id'];

        [, $galat] = $this->model->periksa($this->isian(['nama' => 'KU 12', 'tahun_lahir_awal' => '2012', 'tahun_lahir_akhir' => '2015', 'jenjang' => ['A']]), $id);
        $this->assertSame([], $galat);

        // Mencentang SMP juga membuat keduanya menerima SMP 2012–2014: ditolak.
        [, $galat] = $this->model->periksa($this->isian(['nama' => 'KU 12', 'tahun_lahir_awal' => '2012', 'tahun_lahir_akhir' => '2015', 'jenjang' => ['A', 'B']]), $id);
        $this->assertStringStartsWith('Tahun lahir ini bertabrakan dengan KU 15 (lahir 2011–2014) untuk jenjang SMP/MTs.', $galat['tahun_lahir_awal']);
    }

    public function testKelompokUmurTanpaJenjangTidakDibandingkan(): void
    {
        DataAwal::isi($this->db);

        // Belum dicentang jenjangnya, jadi belum menerima atlet.
        [, $galat] = $this->model->periksa($this->isian(['nama' => 'KU 10 Baru', 'tahun_lahir_awal' => '2016', 'tahun_lahir_akhir' => '2018']));
        $this->assertSame([], $galat);
    }

    public function testKelompokUmurMatiBolehBertabrakan(): void
    {
        DataAwal::isi($this->db);

        // Kelompok umur baru yang mati tidak diperiksa.
        [, $galat] = $this->model->periksa($this->isian(['nama' => 'KU 10 Lama', 'tahun_lahir_awal' => '2016', 'tahun_lahir_akhir' => '2018', 'jenjang' => ['A'], 'aktif' => '']));
        $this->assertSame([], $galat);

        // Kelompok umur aktif tidak dibandingkan dengan yang mati.
        $this->db->table('kelompok_umur')->where('nama', 'KU 10')->update(['aktif' => 0]);
        [, $galat] = $this->model->periksa($this->isian(['nama' => 'KU 10 Baru', 'tahun_lahir_awal' => '2016', 'tahun_lahir_akhir' => '2018', 'jenjang' => ['A']]));
        $this->assertSame([], $galat);
    }

    public function testKelompokUmurTanpaBatasTidakDibandingkan(): void
    {
        $this->simpan(['nama' => 'KU Belum Diisi', 'jenjang' => ['A']]);

        [, $galat] = $this->model->periksa($this->isian(['nama' => 'KU 10', 'tahun_lahir_awal' => '2016', 'tahun_lahir_akhir' => '2018', 'jenjang' => ['A']]));
        $this->assertSame([], $galat);
    }

    // ---------------------------------------------------------------
    // Simpan
    // ---------------------------------------------------------------

    public function testSimpanBaruBesertaJenjangDanAwalan(): void
    {
        $id = $this->simpan([
            'nama'              => 'KU 10',
            'tahun_lahir_awal'  => '2016',
            'tahun_lahir_akhir' => '2018',
            'jenjang'           => ['A', 'B'],
            'biaya_individu'    => '50000',
            'awalan'            => '6, 2',
        ]);

        $this->seeInDatabase('kelompok_umur', ['id' => $id, 'nama' => 'KU 10', 'tahun_lahir_awal' => 2016, 'tahun_lahir_akhir' => 2018, 'biaya_individu' => 50000, 'aktif' => 1]);
        $this->assertSame(['A', 'B'], model(KelompokUmurJenjangModel::class, false)->kodeUntuk($id));
        $this->assertSame(
            [['awalan' => '6', 'urutan' => '1', 'nomor_terakhir' => '0'], ['awalan' => '2', 'urutan' => '2', 'nomor_terakhir' => '0']],
            $this->awalan($id),
        );
    }

    public function testUbahMenyamakanJenjangDanAwalan(): void
    {
        $id = $this->simpan(['nama' => 'KU 10', 'jenjang' => ['A', 'B'], 'awalan' => '2, 6']);
        $this->db->table('awalan_nomor_dada')->where('awalan', '2')->update(['nomor_terakhir' => 999]);
        $idJenjangA = $this->db->table('kelompok_umur_jenjang')->where('jenjang', 'A')->get()->getRow()->id;

        $this->simpan(['nama' => 'KU 10', 'jenjang' => ['A', 'C'], 'awalan' => '7, 2'], $id);

        $this->assertSame(['A', 'C'], model(KelompokUmurJenjangModel::class, false)->kodeUntuk($id));
        $this->seeInDatabase('kelompok_umur_jenjang', ['id' => $idJenjangA, 'jenjang' => 'A']);

        // 6 dihapus, 7 baru di depan, 2 tetap dengan nomor terakhirnya.
        $this->assertSame(
            [['awalan' => '7', 'urutan' => '1', 'nomor_terakhir' => '0'], ['awalan' => '2', 'urutan' => '2', 'nomor_terakhir' => '999']],
            $this->awalan($id),
        );
    }

    public function testGagalDiTengahTidakMenyimpanApaPun(): void
    {
        $lain = $this->simpan(['nama' => 'KU 12', 'awalan' => '3']);

        [$data] = $this->model->periksa($this->isian(['nama' => 'KU 10', 'jenjang' => ['A'], 'awalan' => '2']));
        // Awalan 3 milik KU 12 diselipkan melewati pemeriksaan: indeks unik menolak.
        $data['awalan'] = ['2', '3'];

        try {
            $this->model->simpanLengkap($data);
            $this->fail('Penyimpanan seharusnya gagal.');
        } catch (RuntimeException) {
            // Lanjut ke pemeriksaan.
        }

        $this->seeNumRecords(1, 'kelompok_umur', []);
        $this->seeNumRecords(0, 'kelompok_umur_jenjang', []);
        $this->assertSame([['awalan' => '3', 'urutan' => '1', 'nomor_terakhir' => '0']], $this->awalan($lain));
    }

    public function testAwalanTerpakaiTidakPernahDihapusSinkronisasi(): void
    {
        $id = $this->simpan(['nama' => 'KU 10', 'awalan' => '2']);
        $this->db->table('awalan_nomor_dada')->update(['nomor_terakhir' => 1]);

        $this->expectException(RuntimeException::class);
        model(AwalanNomorDadaModel::class, false)->sinkronkan($id, []);
    }

    public function testDaftarLengkapBerurutanDenganPemakaian(): void
    {
        DataAwal::isi($this->db);
        $this->simpan(['nama' => 'KU 6', 'urutan' => '0', 'jenjang' => ['A'], 'aktif' => '']);

        $daftar = $this->model->daftarLengkap();

        $this->assertSame(['KU 6', 'KU 8 (Seneng Playon)', 'KU 10', 'KU 12', 'KU 15', 'KU 18'], array_column($daftar, 'nama'));
        $this->assertSame(['A'], $daftar[0]['jenjang']);
        $this->assertSame([], $daftar[0]['awalan']);
        $this->assertSame(0, $daftar[0]['jumlah_nomor_lomba']);
        $this->assertSame('2', $daftar[2]['awalan'][0]['awalan']);
        $this->assertSame(11, $daftar[2]['jumlah_nomor_lomba']);
        $this->assertSame(0, $daftar[2]['jumlah_atlet']);
        $this->assertSame(6, $this->model->urutanBerikutnya());
    }

    // ---------------------------------------------------------------
    // Hapus
    // ---------------------------------------------------------------

    public function testHapusBesertaAwalanDanJenjang(): void
    {
        $id   = $this->simpan(['nama' => 'KU 10', 'jenjang' => ['A', 'B'], 'awalan' => '2, 6']);
        $lain = $this->simpan(['nama' => 'KU 12', 'jenjang' => ['B'], 'awalan' => '3']);

        $this->assertNull($this->model->alasanTidakBisaDihapus($this->model->find($id)));
        $this->model->hapusLengkap($id);

        $this->dontSeeInDatabase('kelompok_umur', ['id' => $id]);
        $this->seeNumRecords(0, 'awalan_nomor_dada', ['kelompok_umur_id' => $id]);
        $this->seeNumRecords(0, 'kelompok_umur_jenjang', ['kelompok_umur_id' => $id]);
        $this->seeNumRecords(1, 'awalan_nomor_dada', ['kelompok_umur_id' => $lain]);
        $this->seeNumRecords(1, 'kelompok_umur_jenjang', ['kelompok_umur_id' => $lain]);
    }

    public function testTidakBisaDihapusBilaPunyaNomorLomba(): void
    {
        DataAwal::isi($this->db);
        $ku = $this->model->where('nama', 'KU 10')->first();

        $this->assertSame(
            'KU 10 masih punya 11 nomor lomba, jadi tidak bisa dihapus. Hapus dulu nomor lombanya di halaman Nomor Lomba, atau matikan kelompok umur ini supaya tidak muncul di pendaftaran.',
            $this->model->alasanTidakBisaDihapus($ku),
        );
    }

    public function testTidakBisaDihapusBilaAwalanSudahDipakai(): void
    {
        $id = $this->simpan(['nama' => 'KU 10', 'awalan' => '2, 6']);
        $this->db->table('awalan_nomor_dada')->where('awalan', '6')->update(['nomor_terakhir' => 3]);

        $this->assertSame(
            'Awalan nomor dada 6 milik KU 10 sudah dipakai, jadi kelompok umur ini tidak bisa dihapus. Matikan saja supaya tidak muncul di pendaftaran.',
            $this->model->alasanTidakBisaDihapus($this->model->find($id)),
        );
    }

    public function testTidakBisaDihapusBilaSudahDipakaiAtlet(): void
    {
        $id = $this->simpan(['nama' => 'KU 10']);
        $this->buatAtlet($id, '9900000001');
        $this->buatAtlet($id, '9900000002');

        $this->assertSame(
            'KU 10 sudah dipakai 2 atlet, jadi tidak bisa dihapus. Matikan saja supaya tidak muncul di pendaftaran.',
            $this->model->alasanTidakBisaDihapus($this->model->find($id)),
        );
    }

    // ---------------------------------------------------------------
    // Bantuan
    // ---------------------------------------------------------------

    /**
     * Isian formulir lengkap dengan nilai kosong, ditimpa `$isi`.
     *
     * @param array<string, mixed> $isi
     *
     * @return array<string, mixed>
     */
    private function isian(array $isi = []): array
    {
        return $isi + [
            'nama'              => '',
            'tahun_lahir_awal'  => '',
            'tahun_lahir_akhir' => '',
            'jenjang'           => [],
            'biaya_individu'    => '',
            'awalan'            => '',
            'urutan'            => '',
            'aktif'             => '1',
        ];
    }

    /**
     * Periksa lalu simpan; gagal bila isian salah.
     *
     * @param array<string, mixed> $isi
     */
    private function simpan(array $isi, ?int $id = null): int
    {
        [$data, $galat] = $this->model->periksa($this->isian($isi), $id);
        $this->assertSame([], $galat);

        return $this->model->simpanLengkap($data, $id);
    }

    /**
     * @return list<array{awalan: string, urutan: string, nomor_terakhir: string}>
     */
    private function awalan(int $id): array
    {
        return $this->db->table('awalan_nomor_dada')
            ->select('awalan, urutan, nomor_terakhir')
            ->where('kelompok_umur_id', $id)
            ->orderBy('urutan')
            ->get()->getResultArray();
    }

    private function buatAtlet(int $kelompokUmurId, string $nisn): void
    {
        $this->db->table('pendaftaran')->insert([
            'jenjang'           => 'A',
            'npsn'              => '99999901',
            'nama_sekolah'      => 'SD KARANGAN SATU',
            'kabupaten'         => 'Kudus',
            'subtotal_individu' => 0,
            'subtotal_beregu'   => 0,
            'total'             => 0,
        ]);
        $this->db->table('atlet')->insert([
            'pendaftaran_id'   => $this->db->insertID(),
            'nama'             => 'ATLET KARANGAN ' . $nisn,
            'nisn'             => $nisn,
            'tanggal_lahir'    => '2016-05-17',
            'jenis_kelamin'    => 'L',
            'kelompok_umur_id' => $kelompokUmurId,
        ]);
    }
}
