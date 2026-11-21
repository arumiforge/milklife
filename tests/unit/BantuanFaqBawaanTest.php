<?php

use App\Libraries\Bantuan\FaqBawaan;
use App\Libraries\Bantuan\JawabanFaq;
use App\Models\FaqModel;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Tanya-jawab bawaan halaman Bantuan (F9-04): jumlah, kategori, panjang, dan
 * isian otomatis yang dipakai.
 *
 * @internal
 */
final class BantuanFaqBawaanTest extends CIUnitTestCase
{
    public function testEmpatPuluhSampaiEnamPuluhTanyaJawabDiSemuaKategori(): void
    {
        $jumlah = count(FaqBawaan::DAFTAR);
        $this->assertGreaterThanOrEqual(40, $jumlah);
        $this->assertLessThanOrEqual(60, $jumlah);

        $perKategori = array_count_values(array_column(FaqBawaan::DAFTAR, 0));
        $this->assertSame(array_keys(FaqModel::KATEGORI), array_keys($perKategori), 'Semua kategori terisi, berurutan seperti KATEGORI.');

        foreach ($perKategori as $kategori => $banyak) {
            $this->assertGreaterThanOrEqual(5, $banyak, "Kategori {$kategori} terlalu sedikit.");
        }
    }

    public function testPertanyaanUnikDanPanjangSesuaiKolom(): void
    {
        $pertanyaan = array_column(FaqBawaan::DAFTAR, 1);
        $this->assertSame($pertanyaan, array_values(array_unique($pertanyaan)));

        foreach (FaqBawaan::baris('2026-11-21 10:00:00') as $baris) {
            [$data, $galat] = (new FaqModel())->periksa([
                'kategori'   => $baris['kategori'],
                'pertanyaan' => $baris['pertanyaan'],
                'jawaban'    => $baris['jawaban'],
                'urutan'     => (string) $baris['urutan'],
                'aktif'      => '1',
            ]);
            $this->assertSame([], $galat, $baris['pertanyaan']);
            $this->assertSame($baris['pertanyaan'], $data['pertanyaan'], 'Pertanyaan sudah satu baris rapi.');
            $this->assertSame($baris['jawaban'], $data['jawaban'], 'Jawaban sudah rapi.');
            $this->assertStringNotContainsString('            ', $baris['jawaban'], 'Jorok heredoc terbuang: ' . $baris['pertanyaan']);
            $this->assertSame([], JawabanFaq::isianTidakDikenal($baris['jawaban']), $baris['pertanyaan']);
        }
    }

    public function testPertanyaanSederhanaYangDimintaPemilikAda(): void
    {
        $pertanyaan = implode("\n", array_column(FaqBawaan::DAFTAR, 1));

        foreach (['Apakah harus punya akun', 'Foto ofisial dari HP boleh?', 'Salah ketik nama atlet', 'Bukti transfer hilang', 'lahir 2016, ikut KU 10 atau KU 12?'] as $teks) {
            $this->assertStringContainsString($teks, $pertanyaan);
        }
    }

    public function testUrutanPerKategoriMulaiDariSatu(): void
    {
        $baris = FaqBawaan::baris('2026-11-21 10:00:00');
        $urut  = [];

        foreach ($baris as $b) {
            $urut[$b['kategori']][] = $b['urutan'];
            $this->assertSame(1, $b['aktif']);
            $this->assertSame('2026-11-21 10:00:00', $b['dibuat_pada']);
        }

        foreach ($urut as $kategori => $daftar) {
            $this->assertSame(range(1, count($daftar)), $daftar, $kategori);
        }
    }

    public function testTanggalDanRekeningMemakaiIsianOtomatisBukanAngkaTetap(): void
    {
        $semua = implode("\n", array_column(FaqBawaan::DAFTAR, 2));

        // Nilai pengaturan yang bisa diubah admin tidak ditulis mati.
        foreach (['30 November', '1 Oktober', '9–12 Desember', 'Supersoccer', '0123456789', 'Rp 25.000', 'Rp 100.000'] as $tetap) {
            $this->assertStringNotContainsString($tetap, $semua);
        }

        foreach (['{tutup_pendaftaran}', '{buka_pendaftaran}', '{rekening}', '{daftar_kelompok_umur}', '{daftar_biaya}', '{tanggal_lomba}'] as $isian) {
            $this->assertStringContainsString($isian, $semua);
        }
    }
}
