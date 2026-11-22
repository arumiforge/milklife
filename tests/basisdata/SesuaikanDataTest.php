<?php

use App\Commands\DataAwal;
use App\Commands\SesuaikanData;
use App\Libraries\Panel\PaketPendaftaran;
use App\Libraries\Pendaftaran\IdPendaftaran;
use CodeIgniter\Test\StreamFilterTrait;
use Tests\Support\UjiBasisData;

/**
 * Perintah `php spark data:sesuaikan` (F10-01): menyamakan data bawaan lama
 * dengan data bawaan terbaru tanpa menimpa isian admin, dan membuat zip backup
 * pendaftaran bernama `KU 8` bisa diimpor ke server `data:awal` lama. Semua
 * data karangan.
 *
 * @internal
 */
final class SesuaikanDataTest extends UjiBasisData
{
    use StreamFilterTrait;

    private string $zip = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->zip = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'milklife-sesuaikan-' . bin2hex(random_bytes(6)) . '.zip';
    }

    protected function tearDown(): void
    {
        @unlink($this->zip);

        parent::tearDown();
    }

    public function testMengubahDataBawaanLamaLaluTidakMengubahApaPunSaatDijalankanLagi(): void
    {
        $this->isiDataAwalLama();

        $this->assertSame(EXIT_SUCCESS, $this->jalankan());
        $keluaran = $this->getStreamFilterBuffer();

        $this->assertSame(['KU 8', 'KU 10', 'KU 12', 'KU 15', 'KU 18'], $this->namaKelompokUmur());
        $this->assertSame('6288215277227', setting('Pembayaran.nomor_wa'));
        $this->assertSame('6288215277227', setting('HalamanDepan.nomor_wa_tanya'));
        $this->assertSame(array_slice(DataAwal::TAUTAN, 2), $this->mediaSosial());

        $this->assertStringContainsString('Nama kelompok umur: KU 8 (Seneng Playon) menjadi KU 8.', $keluaran);
        $this->assertStringContainsString('nomor contoh menjadi 0882-1527-7227.', $keluaran);
        $this->assertStringContainsString('Media sosial Instagram: https://www.instagram.com/milklife.athletics menjadi https://www.instagram.com/milklife.id.', $keluaran);
        $this->assertStringContainsString('Data pendaftaran tidak diubah.', $keluaran);

        // Dijalankan lagi: semua langkah dilewati, data tetap.
        $sebelum = [$this->namaKelompokUmur(), $this->mediaSosial(), setting('Pembayaran.nomor_wa'), setting('HalamanDepan.nomor_wa_tanya')];
        $this->resetStreamFilterBuffer();

        $this->assertSame(EXIT_SUCCESS, $this->jalankan());
        $keluaran = $this->getStreamFilterBuffer();

        $this->assertSame($sebelum, [$this->namaKelompokUmur(), $this->mediaSosial(), setting('Pembayaran.nomor_wa'), setting('HalamanDepan.nomor_wa_tanya')]);
        $this->assertStringContainsString('tidak ada; data sudah sesuai.', $keluaran);
        $this->assertStringContainsString('Nama kelompok umur: KU 8 sudah ada.', $keluaran);
        $this->assertStringContainsString('Media sosial: tidak ada alamat bawaan lama.', $keluaran);
    }

    public function testIsianAdminTidakDitimpa(): void
    {
        $this->isiDataAwalLama();

        // Admin sudah mengganti nama KU 8, nomor WA, dan satu media sosial, serta menambah media sosial lain.
        $this->db->table('kelompok_umur')->where('nama', SesuaikanData::NAMA_KU_LAMA)->update(['nama' => 'KU 8 Ceria']);
        setting('Pembayaran.nomor_wa', '6281299990001');
        $this->db->table('tautan')->where('label', 'TikTok')->update(['nilai' => 'https://www.tiktok.com/@karangan']);
        $this->db->table('tautan')->insert(['jenis' => 'media_sosial', 'label' => 'Facebook', 'nilai' => 'https://www.facebook.com/karangan', 'urutan' => 9]);

        $this->assertSame(EXIT_SUCCESS, $this->jalankan());

        $this->assertSame(['KU 8 Ceria', 'KU 10', 'KU 12', 'KU 15', 'KU 18'], $this->namaKelompokUmur());
        $this->assertSame('6281299990001', setting('Pembayaran.nomor_wa'));
        $this->assertSame('6288215277227', setting('HalamanDepan.nomor_wa_tanya'), 'Nomor yang masih contoh tetap diubah.');
        $this->assertSame([
            ['media_sosial', 'Instagram', 'https://www.instagram.com/milklife.id'],
            ['media_sosial', 'TikTok', 'https://www.tiktok.com/@karangan'],
            ['media_sosial', 'YouTube', 'https://www.youtube.com/@milklife_id'],
            ['media_sosial', 'Facebook', 'https://www.facebook.com/karangan'],
        ], $this->mediaSosial());
        $this->assertStringContainsString('Nama kelompok umur: tidak ada KU 8 (Seneng Playon) (nama sudah diubah admin).', $this->getStreamFilterBuffer());
        $this->assertStringContainsString('Nomor WA konfirmasi pembayaran (Pengaturan > Pembayaran): bukan nomor contoh lama.', $this->getStreamFilterBuffer());
    }

    public function testNamaKu8TidakDiubahBilaKu8SudahAda(): void
    {
        $this->isiDataAwalLama();
        $this->db->table('kelompok_umur')->insert(['nama' => 'KU 8', 'urutan' => 9, 'aktif' => 0]);

        $this->assertSame(EXIT_SUCCESS, $this->jalankan());

        $this->assertContains(SesuaikanData::NAMA_KU_LAMA, $this->namaKelompokUmur());
        $this->assertStringContainsString('Nama kelompok umur: KU 8 sudah ada.', $this->getStreamFilterBuffer());
    }

    public function testBasisDataKosongTidakGagal(): void
    {
        $this->assertSame(EXIT_SUCCESS, $this->jalankan());

        $this->seeNumRecords(0, 'kelompok_umur', []);
        $this->seeNumRecords(0, 'tautan', []);
        $this->assertSame('', setting('Pembayaran.nomor_wa'));
        $this->assertStringContainsString('tidak ada; data sudah sesuai.', $this->getStreamFilterBuffer());
    }

    public function testKalimatJuaraUmumPerJenjangDiTanyaJawabDihapus(): void
    {
        // Jawaban bawaan lama (sebelum F11-02) dan jawaban yang sudah diubah admin.
        $waktu = date('Y-m-d H:i:s');
        $lama  = SesuaikanData::FAQ_JUARA_UMUM_LAMA . ' Satu sekolah (satu NPSN) dihitung satu kontingen.';
        $this->db->table('faq')->insertBatch([
            ['kategori' => 'hasil', 'pertanyaan' => 'Bagaimana juara umum dihitung?', 'jawaban' => $lama, 'urutan' => 1, 'aktif' => 1, 'dibuat_pada' => $waktu, 'diubah_pada' => $waktu],
            ['kategori' => 'hasil', 'pertanyaan' => 'Pertanyaan karangan', 'jawaban' => 'Jawaban karangan admin.', 'urutan' => 2, 'aktif' => 1, 'dibuat_pada' => $waktu, 'diubah_pada' => $waktu],
        ]);

        $this->assertSame(EXIT_SUCCESS, $this->jalankan());

        $this->assertSame(
            ['{cara_juara_umum} Satu sekolah (satu NPSN) dihitung satu kontingen.', 'Jawaban karangan admin.'],
            array_column($this->db->table('faq')->orderBy('urutan')->get()->getResultArray(), 'jawaban'),
        );
        $this->assertStringContainsString('Tanya-jawab juara umum: kalimat "per jenjang sekolah" dihapus dari 1 jawaban', $this->getStreamFilterBuffer());

        $this->resetStreamFilterBuffer();
        $this->assertSame(EXIT_SUCCESS, $this->jalankan());
        $this->assertStringContainsString('Tanya-jawab juara umum: tidak ada kalimat bawaan lama', $this->getStreamFilterBuffer());
    }

    public function testZipBackupKu8BisaDiimporSetelahDisesuaikan(): void
    {
        // Server lama yang membuat backup sudah memakai nama KU 8.
        DataAwal::isi($this->db);
        $id = $this->buatPendaftaranKu8();
        $this->assertSame(['pendaftaran' => 1, 'atlet' => 1, 'berkas' => 0], (new PaketPendaftaran())->ekspor($this->zip));

        // Server tujuan: data:awal versi lama, belum ada pendaftaran.
        $this->db->query('DELETE FROM pendaftaran');
        $this->db->table('kelompok_umur')->where('nama', 'KU 8')->update(['nama' => SesuaikanData::NAMA_KU_LAMA]);

        try {
            (new PaketPendaftaran())->impor($this->zip);
            $this->fail('Impor seharusnya ditolak.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Nama kelompok umur di zip tidak ditemukan di server ini: KU 8 (yang ada: KU 8 (Seneng Playon), KU 10, KU 12, KU 15, KU 18).', $e->getMessage());
            $this->assertStringContainsString('Samakan nama kelompok umur di Pengaturan > Kelompok umur, atau jalankan "php spark data:sesuaikan"', $e->getMessage());
            $this->assertStringNotContainsString('Nomor Lari 40 m', $e->getMessage(), 'Nomor lomba dari kelompok umur yang hilang tidak disebut satu per satu.');
        }

        $this->seeNumRecords(0, 'pendaftaran', []);

        $this->assertSame(EXIT_SUCCESS, $this->jalankan());
        $this->assertSame(['pendaftaran' => 1, 'atlet' => 1, 'berkas' => 0], (new PaketPendaftaran())->impor($this->zip));

        $atlet = $this->db->table('atlet a')->select('a.nama, k.nama AS ku, p.id_pendaftaran')
            ->join('kelompok_umur k', 'k.id = a.kelompok_umur_id')->join('pendaftaran p', 'p.id = a.pendaftaran_id')
            ->get()->getResultArray();
        $this->assertSame([['nama' => 'Atlet Karangan Delapan', 'ku' => 'KU 8', 'id_pendaftaran' => $id]], $atlet);
    }

    /**
     * Data awal seperti yang ditulis `data:awal` sebelum Tahap 10.
     */
    private function isiDataAwalLama(): void
    {
        DataAwal::isi($this->db);

        $this->db->table('kelompok_umur')->where('nama', 'KU 8')->update(['nama' => SesuaikanData::NAMA_KU_LAMA]);
        setting('Pembayaran.nomor_wa', SesuaikanData::WA_LAMA);
        setting('HalamanDepan.nomor_wa_tanya', SesuaikanData::WA_LAMA);

        foreach (SesuaikanData::MEDIA_SOSIAL_LAMA as $lama => [$label]) {
            $this->db->table('tautan')->where('label', $label)->update(['nilai' => $lama]);
        }
    }

    /**
     * Satu pendaftaran karangan dengan satu atlet KU 8 (Lari 40 m putra).
     */
    private function buatPendaftaranKu8(): string
    {
        $waktu = date('Y-m-d H:i:s');
        $ku    = (int) $this->db->table('kelompok_umur')->where('nama', 'KU 8')->get()->getRow()->id;
        $nomor = (int) $this->db->table('nomor_lomba')->where(['kelompok_umur_id' => $ku, 'nama' => 'Lari 40 m', 'kategori' => 'putra'])->get()->getRow()->id;

        $this->db->table('pendaftaran')->insert([
            'jenjang' => 'A', 'npsn' => '99999981', 'nama_sekolah' => 'SD KARANGAN DELAPAN', 'kabupaten' => 'Kudus',
            'subtotal_individu' => 25000, 'subtotal_beregu' => 0, 'total' => 25000, 'status' => 'menunggu',
            'dibuat_pada' => $waktu, 'diubah_pada' => $waktu,
        ]);
        $id            = (int) $this->db->insertID();
        $idPendaftaran = IdPendaftaran::buat(2, 'A', $id, 2026);
        $this->db->table('pendaftaran')->where('id', $id)->update(['id_pendaftaran' => $idPendaftaran]);
        $this->db->table('penanggung_jawab')->insert(['pendaftaran_id' => $id, 'nama' => 'Ibu Karangan', 'nomor_wa' => '6281200000081', 'dibuat_pada' => $waktu, 'diubah_pada' => $waktu]);
        $this->db->table('atlet')->insert([
            'pendaftaran_id' => $id, 'nama' => 'Atlet Karangan Delapan', 'nisn' => '9900800001', 'tanggal_lahir' => '2019-05-01',
            'jenis_kelamin' => 'L', 'kelompok_umur_id' => $ku, 'dibuat_pada' => $waktu, 'diubah_pada' => $waktu,
        ]);
        $this->db->table('atlet_nomor')->insert(['atlet_id' => (int) $this->db->insertID(), 'nomor_lomba_id' => $nomor, 'biaya' => 25000, 'dibuat_pada' => $waktu, 'diubah_pada' => $waktu]);

        return $idPendaftaran;
    }

    /**
     * @return list<string>
     */
    private function namaKelompokUmur(): array
    {
        return array_column($this->db->table('kelompok_umur')->select('nama')->orderBy('urutan')->orderBy('id')->get()->getResultArray(), 'nama');
    }

    /**
     * @return list<array{0: string, 1: string, 2: string}>
     */
    private function mediaSosial(): array
    {
        return array_map(
            static fn (array $b): array => [$b['jenis'], $b['label'], $b['nilai']],
            $this->db->table('tautan')->where('jenis', 'media_sosial')->orderBy('urutan')->orderBy('id')->get()->getResultArray(),
        );
    }

    private function jalankan(): int
    {
        return service('commands')->run('data:sesuaikan', []);
    }
}
