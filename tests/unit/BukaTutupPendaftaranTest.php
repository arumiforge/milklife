<?php

use App\Libraries\BukaTutupPendaftaran;
use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Keadaan buka-tutup pendaftaran (rancangan 6.1), kelas murni (F2-09).
 *
 * @internal
 */
final class BukaTutupPendaftaranTest extends CIUnitTestCase
{
    private const MULAI = '2026-10-01 08:00:00';
    private const AKHIR = '2026-11-30 23:59:00';

    public function testBelumLengkapSelaluDisiapkan(): void
    {
        foreach (['2026-09-01 00:00:00', '2026-11-11 10:00:00', '2026-12-31 00:00:00'] as $sekarang) {
            $hasil = $this->hitung($sekarang, lengkap: false, bukaManual: true, bukaManualSampai: '2027-01-01 00:00:00');

            $this->assertSame(BukaTutupPendaftaran::DISIAPKAN, $hasil->keadaan(), $sekarang);
            $this->assertFalse($hasil->terbuka());
            $this->assertNull($hasil->batas());
            $this->assertSame('Pendaftaran sedang disiapkan.', $hasil->kalimat());
            $this->assertSame(['Pendaftaran', 'Segera dibuka'], $hasil->ringkas());
            $this->assertSame('Sedang disiapkan', $hasil->label());
        }
    }

    public function testJadwalKosongDisiapkan(): void
    {
        $hasil = new BukaTutupPendaftaran(true, null, self::AKHIR, false, null, self::waktu('2026-11-11 10:00:00'));

        $this->assertSame(BukaTutupPendaftaran::DISIAPKAN, $hasil->keadaan());
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function batasJadwal(): array
    {
        return [
            'sedetik sebelum mulai' => ['2026-10-01 07:59:59', BukaTutupPendaftaran::BELUM_DIBUKA],
            'tepat saat mulai'      => ['2026-10-01 08:00:00', BukaTutupPendaftaran::TERBUKA],
            'di tengah jadwal'      => ['2026-11-11 10:00:00', BukaTutupPendaftaran::TERBUKA],
            'tepat saat akhir'      => ['2026-11-30 23:59:00', BukaTutupPendaftaran::TERBUKA],
            'detik terakhir akhir'  => ['2026-11-30 23:59:59', BukaTutupPendaftaran::TERBUKA],
            'sesudah menit akhir'   => ['2026-12-01 00:00:00', BukaTutupPendaftaran::DITUTUP],
        ];
    }

    #[DataProvider('batasJadwal')]
    public function testBatasWaktuTepat(string $sekarang, string $harapan): void
    {
        $this->assertSame($harapan, $this->hitung($sekarang)->keadaan());
    }

    public function testJadwalTeksKosongDisiapkan(): void
    {
        $hasil = new BukaTutupPendaftaran(true, '', '', true, '', self::waktu('2026-11-11 10:00:00'));

        $this->assertSame(BukaTutupPendaftaran::DISIAPKAN, $hasil->keadaan());
    }

    public function testBelumDibuka(): void
    {
        $hasil = $this->hitung('2026-09-15 12:00:00');

        $this->assertFalse($hasil->terbuka());
        $this->assertSame('Belum dibuka', $hasil->label());
        $this->assertSame('Pendaftaran dibuka 1 Oktober 2026 pukul 08.00.', $hasil->kalimat());
        $this->assertSame(['Dibuka', '1 Oktober'], $hasil->ringkas());
        $this->assertSame('2026-10-01 08:00:00', $hasil->batas()?->format('Y-m-d H:i:s'));
    }

    public function testBelumDibukaTengahMalamTanpaJam(): void
    {
        $hasil = $this->hitung('2026-09-15 12:00:00', mulai: '2026-10-01 00:00:00');

        $this->assertSame('Pendaftaran dibuka 1 Oktober 2026.', $hasil->kalimat());
    }

    public function testTerbukaMenurutJadwal(): void
    {
        $hasil = $this->hitung('2026-11-11 10:00:00');

        $this->assertTrue($hasil->terbuka());
        $this->assertFalse($hasil->lewatManual());
        $this->assertSame('Terbuka', $hasil->label());
        $this->assertSame('Pendaftaran dibuka sampai 30 November 2026 pukul 23.59.', $hasil->kalimat());
        $this->assertSame(['Daftar s.d.', '30 November'], $hasil->ringkas());
        $this->assertSame('2026-11-30 23:59:59', $hasil->batas()?->format('Y-m-d H:i:s'));
    }

    public function testDitutup(): void
    {
        $hasil = $this->hitung('2026-12-05 09:00:00');

        $this->assertFalse($hasil->terbuka());
        $this->assertSame('Ditutup', $hasil->label());
        $this->assertSame('Pendaftaran sudah ditutup pada 30 November 2026 pukul 23.59.', $hasil->kalimat());
        $this->assertSame(['Pendaftaran', 'Ditutup'], $hasil->ringkas());
    }

    // ---------- Buka manual ----------

    public function testBukaManualSesudahJadwalBerlakuSampaiBatasnya(): void
    {
        $sampai = '2026-12-03 12:00:00';

        $terbuka = $this->hitung('2026-12-03 12:00:59', bukaManual: true, bukaManualSampai: $sampai);
        $this->assertTrue($terbuka->terbuka());
        $this->assertTrue($terbuka->lewatManual());
        $this->assertSame('Pendaftaran dibuka sampai 3 Desember 2026 pukul 12.00.', $terbuka->kalimat());

        $lewat = $this->hitung('2026-12-03 12:01:00', bukaManual: true, bukaManualSampai: $sampai);
        $this->assertSame(BukaTutupPendaftaran::DITUTUP, $lewat->keadaan());
        $this->assertFalse($lewat->lewatManual());
        $this->assertSame('Pendaftaran sudah ditutup pada 3 Desember 2026 pukul 12.00.', $lewat->kalimat());
    }

    public function testBukaManualSebelumJadwal(): void
    {
        $hasil = $this->hitung('2026-09-20 10:00:00', bukaManual: true, bukaManualSampai: '2026-09-25 17:00:00');

        $this->assertTrue($hasil->terbuka());
        $this->assertTrue($hasil->lewatManual());

        // Buka manual yang lewat sebelum jadwal mulai: kembali menunggu jadwal.
        $lewat = $this->hitung('2026-09-26 10:00:00', bukaManual: true, bukaManualSampai: '2026-09-25 17:00:00');
        $this->assertSame(BukaTutupPendaftaran::BELUM_DIBUKA, $lewat->keadaan());
        $this->assertSame('Pendaftaran dibuka 1 Oktober 2026 pukul 08.00.', $lewat->kalimat());
    }

    public function testBukaManualDanJadwalBerlakuSampaiYangTerakhir(): void
    {
        $lebihLama = $this->hitung('2026-11-11 10:00:00', bukaManual: true, bukaManualSampai: '2026-12-05 17:00:00');
        $this->assertFalse($lebihLama->lewatManual());
        $this->assertSame('Pendaftaran dibuka sampai 5 Desember 2026 pukul 17.00.', $lebihLama->kalimat());

        $lebihPendek = $this->hitung('2026-11-11 10:00:00', bukaManual: true, bukaManualSampai: '2026-11-12 17:00:00');
        $this->assertSame('Pendaftaran dibuka sampai 30 November 2026 pukul 23.59.', $lebihPendek->kalimat());

        // Sesudah keduanya lewat, waktu tutup yang disebut adalah yang terakhir.
        $tutup = $this->hitung('2026-12-06 00:00:00', bukaManual: true, bukaManualSampai: '2026-12-05 17:00:00');
        $this->assertSame('Pendaftaran sudah ditutup pada 5 Desember 2026 pukul 17.00.', $tutup->kalimat());
    }

    public function testBukaManualTanpaBatasTidakBerlaku(): void
    {
        foreach ([null, '', 'bukan tanggal'] as $sampai) {
            $hasil = $this->hitung('2026-12-05 09:00:00', bukaManual: true, bukaManualSampai: $sampai);

            $this->assertSame(BukaTutupPendaftaran::DITUTUP, $hasil->keadaan());
        }
    }

    public function testBatasManualDiabaikanBilaBukaManualMati(): void
    {
        $hasil = $this->hitung('2026-12-05 09:00:00', bukaManual: false, bukaManualSampai: '2026-12-31 00:00:00');

        $this->assertSame(BukaTutupPendaftaran::DITUTUP, $hasil->keadaan());
        $this->assertSame('Pendaftaran sudah ditutup pada 30 November 2026 pukul 23.59.', $hasil->kalimat());
    }

    // ---------- Zona waktu dan masukan ----------

    public function testWaktuSekarangZonaLainDibandingkanBenar(): void
    {
        // 2026-11-30 16:59:59 UTC = 23.59.59 WIB: masih terbuka.
        $utc = new DateTimeZone('UTC');

        $terbuka = new BukaTutupPendaftaran(true, self::MULAI, self::AKHIR, false, null, new DateTimeImmutable('2026-11-30 16:59:59', $utc));
        $tutup   = new BukaTutupPendaftaran(true, self::MULAI, self::AKHIR, false, null, new DateTimeImmutable('2026-11-30 17:00:00', $utc));

        $this->assertTrue($terbuka->terbuka());
        $this->assertSame(BukaTutupPendaftaran::DITUTUP, $tutup->keadaan());
    }

    public function testMenerimaObjekTanggalDanFormatTanpaDetik(): void
    {
        $hasil = new BukaTutupPendaftaran(
            true,
            self::waktu(self::MULAI),
            '2026-11-30 23:59',
            false,
            null,
            self::waktu('2026-11-30 23:59:30'),
        );

        $this->assertTrue($hasil->terbuka());
    }

    public function testBacaWaktu(): void
    {
        $this->assertSame('2026-10-01 00:00:00', BukaTutupPendaftaran::bacaWaktu('2026-10-01')?->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-01 08:30:00', BukaTutupPendaftaran::bacaWaktu('2026-10-01 08:30')?->format('Y-m-d H:i:s'));
        $this->assertSame('Asia/Jakarta', BukaTutupPendaftaran::bacaWaktu('2026-10-01 08:30:00')?->getTimezone()->getName());
        $this->assertNull(BukaTutupPendaftaran::bacaWaktu(null));
        $this->assertNull(BukaTutupPendaftaran::bacaWaktu(''));
        $this->assertNull(BukaTutupPendaftaran::bacaWaktu('2026-02-30'));
        $this->assertNull(BukaTutupPendaftaran::bacaWaktu('30/11/2026'));
        $this->assertNull(BukaTutupPendaftaran::bacaWaktu(20261130));
    }

    // ---------- Bantuan ----------

    private function hitung(
        string $sekarang,
        bool $lengkap = true,
        ?string $mulai = self::MULAI,
        ?string $akhir = self::AKHIR,
        bool $bukaManual = false,
        ?string $bukaManualSampai = null,
    ): BukaTutupPendaftaran {
        return new BukaTutupPendaftaran($lengkap, $mulai, $akhir, $bukaManual, $bukaManualSampai, self::waktu($sekarang));
    }

    private static function waktu(string $teks): DateTimeImmutable
    {
        return new DateTimeImmutable($teks, new DateTimeZone('Asia/Jakarta'));
    }
}
