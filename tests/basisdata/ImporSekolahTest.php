<?php

use App\Commands\ImporSekolah;
use App\Models\SekolahModel;
use CodeIgniter\Test\StreamFilterTrait;
use Tests\Support\UjiBasisData;

/**
 * Perintah `php spark sekolah:impor` dan `SekolahModel::impor()`, dengan
 * berkas JSON karangan di `tests/_support/data/`.
 *
 * @internal
 */
final class ImporSekolahTest extends UjiBasisData
{
    use StreamFilterTrait;

    private const DATA = SUPPORTPATH . 'data/';

    public function testImporBerkasKecilBerhasil(): void
    {
        $kode = $this->jalankan(self::DATA . 'sekolah-contoh.json');

        $this->assertSame(EXIT_SUCCESS, $kode);
        $this->seeNumRecords(4, 'sekolah', []);

        $keluaran = $this->keluaran();
        $this->assertStringContainsString('Dibaca     : 4', $keluaran);
        $this->assertStringContainsString('Ditambah   : 4', $keluaran);
        $this->assertStringContainsString('Diperbarui : 0', $keluaran);
        $this->assertStringContainsString('Dilewati   : 0', $keluaran);
        $this->assertMatchesRegularExpression('/Blora\s+: 2/', $keluaran);
        $this->assertMatchesRegularExpression('/Kudus\s+: 1/', $keluaran);
        $this->assertMatchesRegularExpression('/Rekaan\s+: 1/', $keluaran);
        $this->assertStringContainsString('Jumlah sekolah di basis data sekarang: 4', $keluaran);
    }

    public function testKabupatenDisimpanTanpaAwalanDanDataDirapikan(): void
    {
        $this->jalankan(self::DATA . 'sekolah-contoh.json');

        $this->seeInDatabase('sekolah', ['npsn' => '99000001', 'kabupaten' => 'Blora', 'jenjang' => 'SD', 'bentuk' => 'SD']);
        $this->seeInDatabase('sekolah', ['npsn' => '99000002', 'kabupaten' => 'Kudus', 'jenjang' => 'SMP', 'status' => 'SWASTA']);
        $this->seeInDatabase('sekolah', ['npsn' => '99000003', 'kabupaten' => 'Rekaan', 'alamat' => null]);
        $this->seeInDatabase('sekolah', ['npsn' => '99000004', 'nama' => 'SMK KARANGAN EMPAT', 'jenjang' => 'SMA']);
        $this->dontSeeInDatabase('sekolah', ['kabupaten' => 'Kab. Blora']);

        $sekolah = model(SekolahModel::class)->cariNpsn('99000001');
        $this->assertNotNull($sekolah['dibuat_pada']);
        $this->assertNotNull($sekolah['diubah_pada']);
    }

    public function testImporUlangTidakMenggandakanDanMenghitungDiperbarui(): void
    {
        $this->jalankan(self::DATA . 'sekolah-contoh.json');
        $this->resetStreamFilterBuffer();

        $kode = $this->jalankan(self::DATA . 'sekolah-contoh.json');

        $this->assertSame(EXIT_SUCCESS, $kode);
        $this->seeNumRecords(4, 'sekolah', []);

        $keluaran = $this->keluaran();
        $this->assertStringContainsString('Ditambah   : 0', $keluaran);
        $this->assertStringContainsString('Diperbarui : 4', $keluaran);
    }

    public function testPerubahanDiBerkasMemperbaruiBarisDanDibuatPadaTetap(): void
    {
        $model = model(SekolahModel::class);
        $model->impor(ImporSekolah::bacaBerkas(self::DATA . 'sekolah-contoh.json'));

        // Mundurkan cap waktu supaya perubahan terlihat tanpa menunggu.
        $this->db->table('sekolah')->update(['dibuat_pada' => '2026-01-01 08:00:00', 'diubah_pada' => '2026-01-01 08:00:00']);
        $idLama = $model->cariNpsn('99000001')['id'];

        $hasil = $model->impor([
            ['npsn' => '99000001', 'nama' => 'SD KARANGAN SATU BARU', 'jenjang' => 'SD', 'kabupaten_kota' => 'Kab. Jepara'],
            ['npsn' => '99000009', 'nama' => 'SD KARANGAN SEMBILAN', 'jenjang' => 'SD', 'kabupaten_kota' => 'Kab. Rembang'],
        ]);

        $this->assertSame(['dibaca' => 2, 'ditambah' => 1, 'diperbarui' => 1, 'dilewati' => 0], array_intersect_key($hasil, array_flip(['dibaca', 'ditambah', 'diperbarui', 'dilewati'])));
        $this->assertSame(['Jepara' => 1, 'Rembang' => 1], $hasil['per_kabupaten']);

        $sekolah = $model->cariNpsn('99000001');
        $this->assertSame($idLama, $sekolah['id']);
        $this->assertSame('SD KARANGAN SATU BARU', $sekolah['nama']);
        $this->assertSame('Jepara', $sekolah['kabupaten']);
        $this->assertNull($sekolah['bentuk']);
        $this->assertSame('2026-01-01 08:00:00', $sekolah['dibuat_pada']);
        $this->assertNotSame('2026-01-01 08:00:00', $sekolah['diubah_pada']);

        // Sekolah yang tidak ada di berkas kedua tidak dihapus.
        $this->seeNumRecords(5, 'sekolah', []);
        $this->seeInDatabase('sekolah', ['npsn' => '99000002', 'diubah_pada' => '2026-01-01 08:00:00']);
    }

    public function testSekolahYangDisuntingAdminTidakDitimpa(): void
    {
        $model = model(SekolahModel::class);
        $model->impor(ImporSekolah::bacaBerkas(self::DATA . 'sekolah-contoh.json'));

        // Admin memberi nama pembeda di panel (K97).
        $this->db->table('sekolah')->where('npsn', '99000001')
            ->update(['nama' => 'SD KARANGAN SATU JATI', 'disunting_pada' => '2026-11-14 09:00:00']);

        $hasil = $model->impor([
            ['npsn' => '99000001', 'nama' => 'SD KARANGAN SATU', 'jenjang' => 'SD', 'kabupaten_kota' => 'Kab. Jepara'],
            ['npsn' => '99000002', 'nama' => 'SD KARANGAN DUA LAGI', 'jenjang' => 'SD', 'kabupaten_kota' => 'Kab. Jepara'],
            ['npsn' => '99000009', 'nama' => 'SD KARANGAN SEMBILAN', 'jenjang' => 'SD', 'kabupaten_kota' => 'Kab. Rembang'],
        ]);

        $this->assertSame(
            ['dibaca' => 3, 'ditambah' => 1, 'diperbarui' => 1, 'dipertahankan' => 1, 'dilewati' => 0],
            array_intersect_key($hasil, array_flip(['dibaca', 'ditambah', 'diperbarui', 'dipertahankan', 'dilewati'])),
        );
        $this->seeInDatabase('sekolah', ['npsn' => '99000001', 'nama' => 'SD KARANGAN SATU JATI', 'disunting_pada' => '2026-11-14 09:00:00']);
        $this->seeInDatabase('sekolah', ['npsn' => '99000002', 'nama' => 'SD KARANGAN DUA LAGI', 'disunting_pada' => null]);

        // Perintah menyebut sekolah yang tidak diubah.
        $this->jalankan(self::DATA . 'sekolah-contoh.json');
        $this->assertStringContainsString('Tidak diubah karena sudah disunting admin: 1', $this->keluaran());
    }

    public function testBarisTidakSahDilewatiDanDihitung(): void
    {
        $kode = $this->jalankan(self::DATA . 'sekolah-tidak-sah.json');

        $this->assertSame(EXIT_SUCCESS, $kode);
        $this->seeNumRecords(2, 'sekolah', []);
        $this->seeInDatabase('sekolah', ['npsn' => '99000011', 'nama' => 'SD KARANGAN SEBELAS']);
        $this->seeInDatabase('sekolah', ['npsn' => '99000017', 'kabupaten' => 'Jepara']);

        $keluaran = $this->keluaran();
        $this->assertStringContainsString('Dibaca     : 9', $keluaran);
        $this->assertStringContainsString('Ditambah   : 2', $keluaran);
        $this->assertStringContainsString('Dilewati   : 7', $keluaran);
        $this->assertStringContainsString('data ke-2: NPSN "9900012" harus tepat 8 angka', $keluaran);
        $this->assertStringContainsString('data ke-3: NPSN "99A00013" harus tepat 8 angka', $keluaran);
        $this->assertStringContainsString('data ke-4: nama sekolah kosong', $keluaran);
        $this->assertStringContainsString('data ke-5: jenjang "PAUD" tidak dikenal', $keluaran);
        $this->assertStringContainsString('data ke-6: kabupaten kosong', $keluaran);
        $this->assertStringContainsString('data ke-7: NPSN 99000011 sudah muncul di data ke-1', $keluaran);
        $this->assertStringContainsString('data ke-8: bukan data sekolah', $keluaran);
    }

    public function testContohDilewatiDibatasi(): void
    {
        $daftar = array_fill(0, SekolahModel::BATAS_CONTOH + 5, ['npsn' => 'salah']);

        $hasil = model(SekolahModel::class)->impor($daftar);

        $this->assertSame(SekolahModel::BATAS_CONTOH + 5, $hasil['dilewati']);
        $this->assertCount(SekolahModel::BATAS_CONTOH, $hasil['contoh_dilewati']);
    }

    public function testImporPerKelompokMenghitungDenganBenar(): void
    {
        $model  = model(SekolahModel::class);
        $daftar = ImporSekolah::bacaBerkas(self::DATA . 'sekolah-contoh.json');
        $model->impor([$daftar[0], $daftar[1]]);

        // Kelompok berisi 3: kelompok pertama campuran lama dan baru.
        $hasil = $model->impor([...$daftar, ...$this->sekolahBaru(5)], 3);

        $this->assertSame(9, $hasil['dibaca']);
        $this->assertSame(7, $hasil['ditambah']);
        $this->assertSame(2, $hasil['diperbarui']);
        $this->seeNumRecords(9, 'sekolah', []);
    }

    public function testBerkasTidakAdaDitolak(): void
    {
        $kode = $this->jalankan(self::DATA . 'sekolah-tidak-ada.json');

        $this->assertSame(EXIT_ERROR, $kode);
        $this->assertNotSame(0, $kode);
        $this->assertStringContainsString('tidak ditemukan', $this->keluaran());
        $this->assertStringContainsString('Periksa nama dan letak berkasnya', $this->keluaran());
        $this->seeNumRecords(0, 'sekolah', []);
    }

    public function testBerkasJsonRusakDitolak(): void
    {
        $kode = $this->jalankan(self::DATA . 'sekolah-rusak.json');

        $this->assertSame(EXIT_ERROR, $kode);
        $this->assertStringContainsString('bukan JSON yang benar', $this->keluaran());
        $this->seeNumRecords(0, 'sekolah', []);
    }

    public function testBerkasBukanDaftarDitolak(): void
    {
        $kode = $this->jalankan(self::DATA . 'sekolah-bukan-daftar.json');

        $this->assertSame(EXIT_ERROR, $kode);
        $this->assertStringContainsString('harus berupa daftar sekolah', $this->keluaran());
        $this->seeNumRecords(0, 'sekolah', []);
    }

    public function testTabelBelumDibuatDitolakDenganPesanPerbaikan(): void
    {
        $this->db->query('DROP TABLE sekolah');

        $kode = $this->jalankan(self::DATA . 'sekolah-contoh.json');

        $this->assertSame(EXIT_ERROR, $kode);
        $this->assertStringContainsString('php spark migrate --all', $this->keluaran());
    }

    public function testLewatHelperCommand(): void
    {
        command('sekolah:impor "' . self::DATA . 'sekolah-contoh.json"');

        $this->seeNumRecords(4, 'sekolah', []);
        $this->assertStringContainsString('Ditambah   : 4', $this->keluaran());
    }

    private function jalankan(string $berkas): int
    {
        return service('commands')->run('sekolah:impor', [$berkas]);
    }

    private function keluaran(): string
    {
        return $this->getStreamFilterBuffer();
    }

    /**
     * @return list<array<string, string>>
     */
    private function sekolahBaru(int $jumlah): array
    {
        $daftar = [];

        for ($i = 1; $i <= $jumlah; $i++) {
            $daftar[] = [
                'npsn'           => sprintf('9900010%d', $i),
                'nama'           => 'SD KARANGAN TAMBAHAN ' . $i,
                'jenjang'        => 'SD',
                'kabupaten_kota' => 'Kab. Pati',
            ];
        }

        return $daftar;
    }
}
