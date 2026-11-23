<?php

use App\Libraries\Hasil\JenisHasil;
use App\Libraries\Hasil\Rekor;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Aturan murni rekor kejuaraan (F11-06): siapa memecahkan rekor, kunci alat,
 * dan baris dari paket cadangan.
 *
 * @internal
 */
final class HasilRekorTest extends CIUnitTestCase
{
    public function testPemecahRekorWaktuDanJarak(): void
    {
        $baris = [
            self::baris(1, 950),
            self::baris(2, 910, 'DQ'),
            self::baris(3, null, 'DNS'),
            self::baris(4, 970),
        ];

        // Belum ada rekor: prestasi terbaik yang sah menjadi rekor pertama; DQ diabaikan.
        $this->assertSame([1], array_column(Rekor::pemecah($baris, null, JenisHasil::WAKTU), 'id'));
        // Lebih baik dari rekor 9,60.
        $this->assertSame([1], array_column(Rekor::pemecah($baris, 960, JenisHasil::WAKTU), 'id'));
        // Sama dengan rekor tidak memecahkan.
        $this->assertSame([], Rekor::pemecah($baris, 950, JenisHasil::WAKTU));

        // Jarak: makin besar makin baik; r tetap dinilai, NM tidak.
        $jarak = [self::baris(1, 300, 'r'), self::baris(2, 310), self::baris(3, 310), self::baris(4, null, 'NM')];
        $this->assertSame([2, 3], array_column(Rekor::pemecah($jarak, 305, JenisHasil::JARAK), 'id'), 'Prestasi sama-sama terbaik dicatat semua.');
        $this->assertSame([1], array_column(Rekor::pemecah([self::baris(1, 320, 'r')], 310, JenisHasil::JARAK), 'id'));
        $this->assertSame([], Rekor::pemecah([self::baris(1, null, 'NM')], null, JenisHasil::JARAK));
    }

    public function testKunciAlatDanCaraWaktu(): void
    {
        $this->assertSame('peluru 3 kg', Rekor::kunciAlat("  Peluru \t 3  KG "));
        $this->assertSame('', Rekor::kunciAlat(null));
        $this->assertSame('tangan', Rekor::caraWaktu(JenisHasil::WAKTU, true));
        $this->assertSame('elektronik', Rekor::caraWaktu(JenisHasil::WAKTU, false));
        $this->assertNull(Rekor::caraWaktu(JenisHasil::JARAK, true));
        $this->assertSame('2,45 m', Rekor::teksPrestasi(245, JenisHasil::JARAK, false));
        $this->assertSame('9,5', Rekor::teksPrestasi(941, JenisHasil::WAKTU, true));
    }

    public function testBarisImporMenolakYangRusak(): void
    {
        $sah = ['kelompok_umur' => 'KU 10', 'nomor' => 'Lari 60 m putra', 'kategori' => 'putra', 'jenis_hasil' => 'waktu', 'cara_waktu' => 'aneh', 'prestasi' => 950, 'atlet' => 'ATLET KARANGAN', 'sekolah' => 'SD KARANGAN', 'seri' => 'Seri 2', 'tanggal' => '2026-12-09'];

        $baris = Rekor::barisImpor($sah, 7);
        $this->assertSame([7, 'elektronik', 950, '2026-12-09'], [$baris['nomor_lomba_id'], $baris['cara_waktu'], $baris['prestasi'], $baris['tanggal']]);
        $this->assertNull(Rekor::barisImpor(['jenis_hasil' => 'jam'] + $sah, 7));
        $this->assertNull(Rekor::barisImpor(['prestasi' => '950'] + $sah, 7));
        $this->assertNull(Rekor::barisImpor('bukan larik', 7));
        $this->assertNull(Rekor::barisImpor(['jenis_hasil' => 'jarak', 'tanggal' => '9 Des'] + $sah, null)['cara_waktu']);
    }

    /**
     * @return array{id: int, prestasi: int|null, keterangan: string|null}
     */
    private static function baris(int $id, ?int $prestasi, ?string $keterangan = null): array
    {
        return ['id' => $id, 'prestasi' => $prestasi, 'keterangan' => $keterangan];
    }
}
