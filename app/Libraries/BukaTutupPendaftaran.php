<?php

namespace App\Libraries;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

/**
 * Keadaan pendaftaran saat ini: sedang disiapkan, belum dibuka, terbuka, atau
 * ditutup (rancangan 6.1), beserta kalimat ramah untuk ditampilkan.
 *
 * Kelas murni: tidak membaca basis data, pengaturan, jam, atau permintaan
 * HTTP. Waktu sekarang selalu diberikan pemanggil, supaya aturan bisa diuji
 * pada detik berapa pun.
 *
 * ## Aturan
 *
 * 1. Pengaturan belum lengkap → `disiapkan`, apa pun jadwalnya.
 * 2. Buka manual menyala dan batasnya belum lewat → `terbuka`, walau di luar
 *    jadwal. Buka manual tanpa batas yang terbaca tidak berlaku.
 * 3. Waktu sekarang di antara mulai dan akhir pendaftaran → `terbuka`.
 * 4. Waktu sekarang sebelum mulai → `belum_dibuka`.
 * 5. Selain itu → `ditutup`.
 *
 * Batas waktu dibaca dengan ketelitian menit, sama dengan formulir
 * pengaturan: mulai `08.00` berarti terbuka sejak 08.00.00, akhir `23.59`
 * berarti masih terbuka sampai 23.59.59 dan tertutup pada 00.00.00. Waktu
 * tersimpan dibaca di zona waktu aplikasi (WIB) kecuali diberi zona lain.
 *
 * ## Pemakaian
 *
 * Paling mudah lewat `DataKelengkapan`, yang membaca pengaturan dan jam:
 *
 *     $pendaftaran = (new DataKelengkapan())->bukaTutup();
 *
 * Atau langsung, misalnya di uji:
 *
 *     $pendaftaran = new BukaTutupPendaftaran(
 *         lengkap: $kelengkapan->lengkap(),
 *         mulai: '2026-10-01 00:00:00',          // Pendaftaran.mulai
 *         akhir: '2026-11-30 23:59:00',          // Pendaftaran.akhir
 *         bukaManual: false,                     // Pendaftaran.buka_manual
 *         bukaManualSampai: null,                // Pendaftaran.buka_manual_sampai
 *         sekarang: new DateTimeImmutable('2026-11-11 10:00', new DateTimeZone('Asia/Jakarta')),
 *     );
 *
 *     $pendaftaran->keadaan();     // 'terbuka' (bandingkan dengan konstanta TERBUKA dan lainnya)
 *     $pendaftaran->terbuka();     // true: wizard pendaftaran boleh dipakai (Tahap 4)
 *     $pendaftaran->kalimat();     // 'Pendaftaran dibuka sampai 30 November 2026 pukul 23.59.'
 *     $pendaftaran->label();       // 'Terbuka' (untuk lencana)
 *     $pendaftaran->batas();       // waktu penting keadaan ini (lihat metodenya), atau null
 *     $pendaftaran->lewatManual(); // true bila terbuka hanya karena buka manual
 *
 * Halaman depan (Tahap 3) menampilkan `kalimat()`; wizard (Tahap 4) menolak
 * bila `terbuka()` false dan menampilkan `kalimat()` sebagai alasannya.
 */
final class BukaTutupPendaftaran
{
    public const DISIAPKAN    = 'disiapkan';
    public const BELUM_DIBUKA = 'belum_dibuka';
    public const TERBUKA      = 'terbuka';
    public const DITUTUP      = 'ditutup';

    /**
     * Zona waktu bawaan untuk membaca waktu tersimpan (sama dengan `Config\App::$appTimezone`).
     */
    public const ZONA = 'Asia/Jakarta';

    private const LABEL = [
        self::DISIAPKAN    => 'Sedang disiapkan',
        self::BELUM_DIBUKA => 'Belum dibuka',
        self::TERBUKA      => 'Terbuka',
        self::DITUTUP      => 'Ditutup',
    ];

    private string $keadaan;

    private ?DateTimeImmutable $batas = null;

    private bool $lewatManual = false;

    /**
     * @param bool                          $lengkap          Hasil `KelengkapanPengaturan::lengkap()`.
     * @param DateTimeInterface|string|null $mulai            Waktu buka pendaftaran (`Y-m-d H:i:s`).
     * @param DateTimeInterface|string|null $akhir            Waktu tutup pendaftaran.
     * @param bool                          $bukaManual       Admin membuka pendaftaran secara manual.
     * @param DateTimeInterface|string|null $bukaManualSampai Batas buka manual.
     * @param DateTimeInterface             $sekarang         Waktu sekarang.
     * @param string                        $zona             Zona waktu untuk membaca teks waktu.
     */
    public function __construct(
        bool $lengkap,
        DateTimeInterface|string|null $mulai,
        DateTimeInterface|string|null $akhir,
        bool $bukaManual,
        DateTimeInterface|string|null $bukaManualSampai,
        DateTimeInterface $sekarang,
        string $zona = self::ZONA,
    ) {
        $zonaWaktu = new DateTimeZone($zona);
        $sekarang  = DateTimeImmutable::createFromInterface($sekarang);
        $awal      = self::bacaWaktu($mulai, $zonaWaktu);
        $tutup     = self::akhirMenit(self::bacaWaktu($akhir, $zonaWaktu));
        $manual    = $bukaManual ? self::akhirMenit(self::bacaWaktu($bukaManualSampai, $zonaWaktu)) : null;

        if (! $lengkap || $awal === null || $tutup === null) {
            $this->keadaan = self::DISIAPKAN;

            return;
        }

        $manualBerlaku = $manual !== null && $sekarang <= $manual;
        $jadwalBerlaku = $sekarang >= $awal && $sekarang <= $tutup;

        if ($manualBerlaku || $jadwalBerlaku) {
            $this->keadaan     = self::TERBUKA;
            $this->lewatManual = $manualBerlaku && ! $jadwalBerlaku;
            // Bila keduanya berlaku, pendaftaran terbuka sampai batas yang paling akhir.
            $this->batas = match (true) {
                $manualBerlaku && $jadwalBerlaku => max($manual, $tutup),
                $manualBerlaku                   => $manual,
                default                          => $tutup,
            };

            return;
        }

        if ($sekarang < $awal) {
            $this->keadaan = self::BELUM_DIBUKA;
            $this->batas   = $awal;

            return;
        }

        $this->keadaan = self::DITUTUP;
        // Saat tutup yang terakhir lewat: jadwal, atau buka manual yang sudah habis.
        $this->batas = $manual !== null ? max($manual, $tutup) : $tutup;
    }

    /**
     * Salah satu konstanta `DISIAPKAN`, `BELUM_DIBUKA`, `TERBUKA`, `DITUTUP`.
     */
    public function keadaan(): string
    {
        return $this->keadaan;
    }

    /**
     * Apakah sekolah boleh mendaftar sekarang.
     */
    public function terbuka(): bool
    {
        return $this->keadaan === self::TERBUKA;
    }

    /**
     * Apakah pendaftaran terbuka hanya karena buka manual (di luar jadwal).
     */
    public function lewatManual(): bool
    {
        return $this->lewatManual;
    }

    /**
     * Waktu penting keadaan ini: saat dibuka (`belum_dibuka`), batas terbuka
     * (`terbuka`, sampai 59 detik menit terakhir), saat ditutup (`ditutup`),
     * atau `null` (`disiapkan`).
     */
    public function batas(): ?DateTimeImmutable
    {
        return $this->batas;
    }

    /**
     * Label pendek untuk lencana, contoh "Belum dibuka".
     */
    public function label(): string
    {
        return self::LABEL[$this->keadaan];
    }

    /**
     * Kalimat ramah untuk sekolah dan admin.
     *
     * - "Pendaftaran sedang disiapkan."
     * - "Pendaftaran dibuka 1 Oktober 2026." (jam disebut bila bukan 00.00)
     * - "Pendaftaran dibuka sampai 30 November 2026 pukul 23.59."
     * - "Pendaftaran sudah ditutup pada 30 November 2026 pukul 23.59."
     */
    public function kalimat(): string
    {
        return match ($this->keadaan) {
            self::DISIAPKAN    => 'Pendaftaran sedang disiapkan.',
            self::BELUM_DIBUKA => 'Pendaftaran dibuka ' . self::tulisWaktu($this->batas) . '.',
            self::TERBUKA      => 'Pendaftaran dibuka sampai ' . self::tulisWaktu($this->batas) . '.',
            default            => 'Pendaftaran sudah ditutup pada ' . self::tulisWaktu($this->batas) . '.',
        };
    }

    /**
     * Ringkasan dua baris untuk kartu info halaman depan (K82), tanpa tahun
     * dan jam supaya pendek:
     *
     * - disiapkan:    ["Pendaftaran", "Segera dibuka"]
     * - belum_dibuka: ["Dibuka", "1 Oktober"]
     * - terbuka:      ["Daftar s.d.", "30 November"]
     * - ditutup:      ["Pendaftaran", "Ditutup"]
     *
     * Kalimat lengkapnya tetap dari `kalimat()`.
     *
     * @return array{0: string, 1: string}
     */
    public function ringkas(): array
    {
        $tanggal = $this->batas === null ? '' : (int) $this->batas->format('j') . ' ' . _aplikasi_nama_bulan((int) $this->batas->format('n'));

        return match ($this->keadaan) {
            self::DISIAPKAN    => ['Pendaftaran', 'Segera dibuka'],
            self::BELUM_DIBUKA => ['Dibuka', $tanggal],
            self::TERBUKA      => ['Daftar s.d.', $tanggal],
            default            => ['Pendaftaran', 'Ditutup'],
        };
    }

    /**
     * Baca waktu tersimpan (`Y-m-d`, `Y-m-d H:i`, `Y-m-d H:i:s`, atau
     * objek tanggal). Nilai kosong atau tidak sah menghasilkan `null`.
     * Dipakai juga `KelengkapanPengaturan`.
     */
    public static function bacaWaktu(mixed $nilai, ?DateTimeZone $zona = null): ?DateTimeImmutable
    {
        if ($nilai instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($nilai);
        }

        if (! is_string($nilai) || trim($nilai) === '') {
            return null;
        }

        $zona ??= new DateTimeZone(self::ZONA);

        foreach (['!Y-m-d H:i:s', '!Y-m-d H:i', '!Y-m-d'] as $format) {
            $waktu = DateTimeImmutable::createFromFormat($format, trim($nilai), $zona);
            $galat = DateTimeImmutable::getLastErrors();

            if ($waktu !== false && ($galat === false || ($galat['warning_count'] === 0 && $galat['error_count'] === 0))) {
                return $waktu;
            }
        }

        return null;
    }

    /**
     * Detik terakhir menit itu: 23.59 → 23.59.59.
     */
    private static function akhirMenit(?DateTimeImmutable $waktu): ?DateTimeImmutable
    {
        return $waktu?->setTime((int) $waktu->format('H'), (int) $waktu->format('i'), 59);
    }

    /**
     * "1 Oktober 2026" bila pukul 00.00, selain itu "30 November 2026 pukul 23.59".
     */
    private static function tulisWaktu(DateTimeImmutable $waktu): string
    {
        return tanggal_id($waktu, $waktu->format('H:i') !== '00:00');
    }
}
