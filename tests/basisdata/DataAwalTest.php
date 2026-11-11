<?php

use App\Commands\DataAwal;
use CodeIgniter\Test\StreamFilterTrait;
use Tests\Support\UjiBasisData;

/**
 * Perintah `php spark data:awal` (F2-01) dan nilai awal kelas pengaturan.
 *
 * @internal
 */
final class DataAwalTest extends UjiBasisData
{
    use StreamFilterTrait;

    public function testNilaiAwalPengaturanSebelumDataAwal(): void
    {
        $harapan = [
            'Acara.nama'                            => '',
            'Acara.seri'                            => null,
            'Acara.tahun'                           => null,
            'Acara.tempat'                          => '',
            'Acara.tanggal_mulai'                   => null,
            'Acara.tanggal_selesai'                 => null,
            'Pendaftaran.mulai'                     => null,
            'Pendaftaran.akhir'                     => null,
            'Pendaftaran.buka_manual'               => false,
            'Pendaftaran.buka_manual_sampai'        => null,
            'Pendaftaran.tanggal_patokan_usia'      => null,
            'Pendaftaran.tampilan_usia'             => 'tahun',
            'Pendaftaran.penanggung_jawab_minimal'  => 1,
            'Pendaftaran.penanggung_jawab_maksimal' => 5,
            'Pendaftaran.nomor_individu_maksimal'   => 2,
            'Pembayaran.bank'                       => '',
            'Pembayaran.nomor_rekening'             => '',
            'Pembayaran.pemilik_rekening'           => '',
            'Pembayaran.nomor_wa'                   => '',
            'Pembayaran.logo_bank'                  => '',
            'HalamanDepan.gambar_kepala'            => '',
            'HalamanDepan.logo_persegi'             => '',
            'HalamanDepan.logo_persegi_panjang'     => '',
            'HalamanDepan.logo_djarum'              => '',
            'HalamanDepan.judul'                    => '',
            'HalamanDepan.teks'                     => '',
            'HalamanDepan.footer'                   => '',
            'HalamanDepan.nomor_wa_tanya'           => '',
            'HalamanDepan.pesan_tanya'              => '',
            'HalamanDepan.pesan_bagikan'            => '',
        ];

        foreach ($harapan as $kunci => $nilai) {
            $this->assertSame($nilai, setting($kunci), $kunci);
        }

        // Templat pesan punya isi awal berbahasa Indonesia dengan semua penanda.
        $templat = setting('Pembayaran.templat_pesan');
        $this->assertIsString($templat);
        $this->assertStringStartsWith('Halo Admin', $templat);

        foreach (array_keys(Config\Pembayaran::PENANDA) as $penanda) {
            $this->assertStringContainsString($penanda, $templat);
        }
    }

    public function testMengisiDataAcaraDanPendaftaran(): void
    {
        $this->assertSame(EXIT_SUCCESS, $this->jalankan());

        $this->assertSame('MilkLife Athletics Challenge', setting('Acara.nama'));
        $this->assertSame(2, setting('Acara.seri'));
        $this->assertSame(2026, setting('Acara.tahun'));
        $this->assertSame('Supersoccer Arena, Kudus', setting('Acara.tempat'));
        $this->assertSame('2026-12-09', setting('Acara.tanggal_mulai'));
        $this->assertSame('2026-12-12', setting('Acara.tanggal_selesai'));
        $this->assertSame('2026-10-01 00:00:00', setting('Pendaftaran.mulai'));
        $this->assertSame('2026-11-30 23:59:00', setting('Pendaftaran.akhir'));
        $this->assertSame('2026-11-30', setting('Pendaftaran.tanggal_patokan_usia'));

        // Tersimpan di basis data (bukan hanya di memori) dengan tipe yang benar.
        Config\Services::resetSingle('settings');
        $this->assertSame(2, setting('Acara.seri'));
        $this->seeInDatabase('settings', ['class' => 'Config\Acara', 'key' => 'nama', 'value' => 'MilkLife Athletics Challenge']);

        // Yang tidak disebut flyer tetap bernilai awal.
        $this->assertFalse(setting('Pendaftaran.buka_manual'));
        $this->assertSame(2, setting('Pendaftaran.nomor_individu_maksimal'));
        $this->assertSame('', setting('Pembayaran.bank'));
    }

    public function testLimaKelompokUmurTanpaBatasUsiaJenjangBiayaAwalan(): void
    {
        $this->jalankan();

        $baris = $this->db->table('kelompok_umur')->orderBy('urutan')->get()->getResultArray();

        $this->assertSame(['KU 8 (Seneng Playon)', 'KU 10', 'KU 12', 'KU 15', 'KU 18'], array_column($baris, 'nama'));
        $this->assertSame(['1', '2', '3', '4', '5'], array_column($baris, 'urutan'));

        foreach ($baris as $ku) {
            $this->assertNull($ku['usia_minimal'], $ku['nama']);
            $this->assertNull($ku['usia_maksimal'], $ku['nama']);
            $this->assertNull($ku['biaya_individu'], $ku['nama']);
            $this->assertSame('1', $ku['aktif'], $ku['nama']);
            $this->assertNotNull($ku['dibuat_pada']);
        }

        $this->seeNumRecords(0, 'kelompok_umur_jenjang', []);
        $this->seeNumRecords(0, 'awalan_nomor_dada', []);
    }

    public function testSemuaNomorLombaLampiran(): void
    {
        $this->jalankan();

        $this->seeNumRecords(69, 'nomor_lomba', []);
        $this->seeNumRecords(58, 'nomor_lomba', ['jenis' => 'individu']);
        $this->seeNumRecords(11, 'nomor_lomba', ['jenis' => 'beregu']);

        $harapan = [
            'KU 8 (Seneng Playon)' => [
                'individu' => ['Lari 40 m'],
                'beregu'   => [],
            ],
            'KU 10' => [
                'individu' => ['Lari 60 m', 'Frog Jump', 'Turbo Throw'],
                'beregu'   => [
                    "Kanga's Escape putra 4 100000", "Kanga's Escape putri 4 100000",
                    'Formula 1 putra 4 100000', 'Formula 1 putri 4 100000',
                    'Estafet 8 × 50 m mix 8 150000',
                ],
            ],
            'KU 12' => [
                'individu' => ['Lompat Jauh', 'Tolak Peluru', 'Lari 60 m', 'Lari 1000 m', 'Trilomba'],
                'beregu'   => ['Estafet 8 × 50 m mix 8 150000'],
            ],
            'KU 15' => [
                'individu' => ['Jalan Cepat 2000 m', 'Lari 80 m', 'Lari 400 m', 'Lari 800 m', 'Lari 1500 m', 'Lompat Jauh', 'Lompat Tinggi', 'Lempar Lembing', 'Lempar Cakram', 'Tolak Peluru'],
                'beregu'   => ['Estafet 5 × 80 m putra 5 100000', 'Estafet 5 × 80 m putri 5 100000'],
            ],
            'KU 18' => [
                'individu' => ['Jalan Cepat 3000 m', 'Lari 100 m', 'Lari 400 m', 'Lari 800 m', 'Lari 1500 m', 'Lompat Jauh', 'Lompat Tinggi', 'Lempar Lembing', 'Lempar Cakram', 'Tolak Peluru'],
                'beregu'   => ['Estafet 4 × 100 m putra 4 100000', 'Estafet 4 × 100 m putri 4 100000', 'Estafet 4 × 400 m mix 4 100000'],
            ],
        ];

        foreach ($harapan as $namaKu => $nomor) {
            $baris = $this->db->table('nomor_lomba n')
                ->select('n.*')
                ->join('kelompok_umur k', 'k.id = n.kelompok_umur_id')
                ->where('k.nama', $namaKu)
                ->orderBy('n.urutan')
                ->get()->getResultArray();

            $individu = array_values(array_filter($baris, static fn (array $b): bool => $b['jenis'] === 'individu'));
            $beregu   = array_values(array_filter($baris, static fn (array $b): bool => $b['jenis'] === 'beregu'));

            // Individu: setiap nomor dua kali, putra lalu putri, tanpa isian tim.
            $individuHarapan = [];

            foreach ($nomor['individu'] as $nama) {
                $individuHarapan[] = $nama . ' putra';
                $individuHarapan[] = $nama . ' putri';
            }

            $this->assertSame($individuHarapan, array_map(static fn (array $b): string => $b['nama'] . ' ' . $b['kategori'], $individu), $namaKu);

            foreach ($individu as $b) {
                foreach (['jumlah_inti', 'batas_anggota', 'jumlah_putra', 'jumlah_putri', 'biaya_beregu'] as $kolom) {
                    $this->assertNull($b[$kolom], "{$namaKu} {$b['nama']} {$kolom}");
                }
            }

            // Beregu: jumlah inti dan biaya dari flyer; batas anggota dan jumlah putra/putri kosong.
            $this->assertSame(
                $nomor['beregu'],
                array_map(static fn (array $b): string => "{$b['nama']} {$b['kategori']} {$b['jumlah_inti']} {$b['biaya_beregu']}", $beregu),
                $namaKu,
            );

            foreach ($beregu as $b) {
                $this->assertNull($b['batas_anggota']);
                $this->assertNull($b['jumlah_putra']);
                $this->assertNull($b['jumlah_putri']);
            }

            // Urutan berjalan dari 1 tanpa lompatan; semua aktif.
            $this->assertSame(range(1, count($baris)), array_map('intval', array_column($baris, 'urutan')), $namaKu);
            $this->assertSame([], array_diff(array_column($baris, 'aktif'), ['1']), $namaKu);
        }
    }

    public function testRingkasanDiLayar(): void
    {
        $this->jalankan();
        $keluaran = $this->getStreamFilterBuffer();

        $this->assertStringContainsString('Data awal tersimpan.', $keluaran);
        $this->assertStringContainsString('MilkLife Athletics Challenge Seri 2 – 2026', $keluaran);
        $this->assertStringContainsString('Supersoccer Arena, Kudus, 9–12 Desember 2026', $keluaran);
        $this->assertStringContainsString('1 Oktober 2026 pukul 00.00 sampai 30 November 2026 pukul 23.59', $keluaran);
        $this->assertStringContainsString('Kelompok umur: 5', $keluaran);
        $this->assertStringContainsString('Nomor lomba  : 69 (58 individu, 11 beregu)', $keluaran);
        $this->assertMatchesRegularExpression('/KU 10\s+:\s+6 nomor individu, 5 nomor beregu/', $keluaran);
    }

    public function testMenolakBilaDijalankanLagi(): void
    {
        $this->assertSame(EXIT_SUCCESS, $this->jalankan());
        $this->resetStreamFilterBuffer();

        $this->assertSame(EXIT_ERROR, $this->jalankan());

        $this->assertStringContainsString('Data awal tidak diisi: sudah ada 5 kelompok umur di basis data.', $this->getStreamFilterBuffer());
        $this->seeNumRecords(5, 'kelompok_umur', []);
        $this->seeNumRecords(69, 'nomor_lomba', []);
    }

    public function testMenolakBilaAdminSudahMengisiKelompokUmur(): void
    {
        $this->db->table('kelompok_umur')->insert(['nama' => 'KU Karangan']);

        $this->assertSame(EXIT_ERROR, $this->jalankan());
        $this->assertStringContainsString('sudah ada 1 kelompok umur', $this->getStreamFilterBuffer());
        $this->seeNumRecords(1, 'kelompok_umur', []);
        $this->seeNumRecords(0, 'nomor_lomba', []);
        $this->assertSame('', setting('Acara.nama'));
    }

    public function testMenolakBilaPengaturanAcaraSudahTerisi(): void
    {
        setting('Acara.tempat', 'Stadion Karangan');

        $this->assertSame(EXIT_ERROR, $this->jalankan());
        $this->assertStringContainsString('pengaturan Acara.tempat sudah terisi', $this->getStreamFilterBuffer());
        $this->assertSame('Stadion Karangan', setting('Acara.tempat'));
        $this->assertSame('', setting('Acara.nama'));
        $this->seeNumRecords(0, 'kelompok_umur', []);
    }

    public function testMenolakBilaTanggalPendaftaranSudahTerisi(): void
    {
        setting('Pendaftaran.akhir', '2026-11-25 12:00:00');

        $this->assertSame(EXIT_ERROR, $this->jalankan());
        $this->assertSame('2026-11-25 12:00:00', setting('Pendaftaran.akhir'));
        $this->seeNumRecords(0, 'kelompok_umur', []);
    }

    public function testPengaturanLainYangTerisiTidakMenghalangi(): void
    {
        // Rekening atau halaman depan yang sudah diisi tidak ditimpa data awal.
        setting('Pembayaran.bank', 'Bank Karangan');

        $this->assertSame(EXIT_SUCCESS, $this->jalankan());
        $this->assertSame('Bank Karangan', setting('Pembayaran.bank'));
    }

    public function testGagalDiTengahTidakMenyimpanApaPun(): void
    {
        // Tabel nomor_lomba dibuang supaya penyimpanan gagal setelah kelompok umur pertama.
        $this->db->query('SET FOREIGN_KEY_CHECKS = 0');
        $this->db->query('RENAME TABLE nomor_lomba TO nomor_lomba_cadangan');

        try {
            DataAwal::isi($this->db);
            $this->fail('Penyimpanan seharusnya gagal.');
        } catch (Throwable) {
            // Lanjut ke pemeriksaan.
        } finally {
            $this->db->query('RENAME TABLE nomor_lomba_cadangan TO nomor_lomba');
            $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
        }

        $this->seeNumRecords(0, 'kelompok_umur', []);
        $this->assertSame('', setting('Acara.nama'));
        $this->seeNumRecords(0, 'settings', []);
    }

    private function jalankan(): int
    {
        return service('commands')->run('data:awal', []);
    }
}
