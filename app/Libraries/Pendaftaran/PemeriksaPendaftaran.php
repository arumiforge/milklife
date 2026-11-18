<?php

namespace App\Libraries\Pendaftaran;

use App\Libraries\Pendaftaran\Pemeriksa\DaftarGalat;
use App\Libraries\Pendaftaran\Pemeriksa\IndukPeriksa;
use App\Libraries\Pendaftaran\Pemeriksa\MasukanPeriksa;
use App\Libraries\Pendaftaran\Pemeriksa\PeriksaAtlet;
use App\Libraries\Pendaftaran\Pemeriksa\PeriksaKuota;
use App\Libraries\Pendaftaran\Pemeriksa\PeriksaPenanggungJawab;
use App\Libraries\Pendaftaran\Pemeriksa\PeriksaRegu;
use App\Libraries\Pendaftaran\Pemeriksa\PeriksaSekolah;
use App\Libraries\Pendaftaran\Pemeriksa\PeriksaTim;
use App\Libraries\Pendaftaran\Pemeriksa\SusunHasil;

/**
 * Pemeriksaan seluruh kiriman wizard pendaftaran terhadap aturan rancangan
 * bagian 6 dan keputusan K23, K28, K36, K50, K51 (rencana Tahap 4, "Aturan
 * yang diperiksa server").
 *
 * Aturan murni: semua data dari basis data dan pengaturan diberikan lewat
 * konstruktor, dikumpulkan `DataIndukPendaftaran`. Waktu "hari ini" juga
 * diberikan, supaya bisa diuji pada tanggal berapa pun.
 *
 * ## Kiriman (JSON dari wizard)
 *
 *     [
 *         'sekolah' => ['jenjang' => 'A', 'kabupaten' => 'Kudus', 'npsn' => '20317000', 'nama' => ''],
 *         'penanggung_jawab' => [
 *             ['nama' => 'Ibu Contoh', 'nomor_wa' => '0812-3456-7890', 'foto' => 'data:image/jpeg;base64,…'],
 *         ],
 *         'atlet' => [
 *             ['nama' => 'Budi Contoh', 'nisn' => '0012345678', 'tanggal_lahir' => '2016-05-01',
 *              'jenis_kelamin' => 'L', 'nomor' => [12, 40],    // id nomor lomba
 *              'regu' => ['40' => ['regu' => 'A', 'peran' => 'inti']]], // per nomor beregu (K105)
 *         ],
 *         'konfirmasi_susulan' => ['40:A'],  // pilihan: regu susulan yang dikonfirmasi (K57), "nomor:regu"
 *         'turun_cadangan'     => ['40:17'], // pilihan: inti lama (id atlet) yang menjadi cadangan, "nomor:atlet"
 *     ]
 *
 * ## Regu (K105)
 *
 * Satu sekolah boleh punya beberapa regu per nomor beregu, berlabel huruf
 * (`Regu`). Setiap atlet di nomor beregu memilih regu dan peran (inti atau
 * cadangan). Atlet satu kiriman dengan nomor dan label yang sama membentuk
 * satu regu. Regu yang sudah dimiliki sekolah (NPSN sama, pendaftaran
 * sebelumnya) menjadi regu susulan: tanpa biaya regu, anggota lama dan baru
 * diperiksa bersama (`KelengkapanTim`). Inti lama boleh diturunkan menjadi
 * cadangan lewat `turun_cadangan` supaya atlet susulan bisa menjadi inti.
 *
 * ## Konfirmasi regu susulan (K57)
 *
 * Bila kiriman membawa kunci `konfirmasi_susulan` (list "nomor:regu"),
 * setiap regu susulan yang tidak ada di list ditolak dengan galat langkah 3,
 * jalur `konfirmasi.<nomor>:<regu>`.
 * Tanpa kunci itu tidak ada pemeriksaan konfirmasi, jadi pemanggil admin
 * (ubah pendaftaran, F6-04) tidak memintanya. Titik akhir publik wizard
 * memakai `wajibKonfirmasiSusulan()` supaya kunci itu selalu ada, termasuk
 * bila peramban tidak mengirimnya.
 *
 * ## Induk (`DataIndukPendaftaran::induk()`)
 *
 *     [
 *         'kelompok_umur' => [...],  // aktif: id, nama, tahun_lahir_awal, tahun_lahir_akhir, biaya_individu, jenjang
 *         'nomor_lomba'   => [...],  // aktif pada KU aktif: id, kelompok_umur_id, nama, jenis, kategori, kolom tim
 *         'aturan'        => ['penanggung_jawab_minimal' => 1, 'penanggung_jawab_maksimal' => 5,
 *                             'nomor_individu_maksimal' => 2, 'atlet_per_nomor_maksimal' => 2], // batas kuota boleh null
 *     ]
 *
 * ## Ada (`DataIndukPendaftaran::ada($npsn, $nisn)`)
 *
 *     [
 *         'referensi'      => ['npsn' => …, 'nama' => …, 'bentuk' => 'SD', 'jenjang' => 'SD', 'kabupaten' => 'Kudus'], // atau null
 *         'nisn_terdaftar' => ['0012345678'],      // NISN kiriman yang sudah ada di basis data
 *         'tim'            => [40 => ['A' => [['id' => 17, 'nama' => '…', 'jenis_kelamin' => 'L', 'peran' => 'inti'], …]]],
 *                                                  // nomor beregu => regu => anggota dari pendaftaran lain sekolah ini
 *         'regu_tersimpan' => ['40:A'],            // pilihan, admin: regu milik pendaftaran yang diubah
 *         'individu'       => [12 => 1],           // nomor individu => jumlah atlet sekolah ini
 *     ]
 *
 * ## Hasil `periksa()`
 *
 *     [
 *         'galat'   => [['langkah' => 3, 'jalur' => 'atlet.0.nisn', 'pesan' => '…']],  // [] bila sah
 *         'data'    => [...],   // siap disimpan `SimpanPendaftaran`, atau null bila ada galat
 *         'rincian' => [...],   // untuk langkah 4, atau null bila ada galat
 *     ]
 *
 * `data`: `sekolah` (jenjang, kabupaten, npsn atau null, nama_sekolah),
 * `penanggung_jawab` (nama, nomor_wa baku, foto = bita JPEG), `atlet` (nama
 * huruf besar, nisn, tanggal_lahir, jenis_kelamin, kelompok_umur_id, nomor:
 * list `{nomor_lomba_id, biaya}` dengan biaya `null` untuk beregu), `beregu`
 * (nomor_lomba_id, regu, biaya_tim), `turun_cadangan` (atlet_id,
 * nomor_lomba_id), `subtotal_individu`, `subtotal_beregu`, `total`. Nomor
 * beregu atlet juga membawa `regu` dan `peran`.
 *
 * `rincian`: `sekolah` (nama, npsn, kabupaten, jenjang, jenjang_label),
 * `individu` (nama, nisn, kelompok_umur, nomor, biaya; satu baris per atlet per
 * nomor individu), `beregu` (nomor, nomor_lomba_id, regu, regu_tunggal, biaya,
 * susulan, anggota, inti, cadangan, anggota_lama, turun; `regu_tunggal` = satu-
 * satunya regu sekolah di nomor itu, ditulis tanpa nama regu, K108),
 * `subtotal_individu`, `subtotal_beregu`, `total`.
 *
 * ## Letak kode
 *
 * Kelas ini pintu masuk; aturannya ada di `App\Libraries\Pendaftaran\Pemeriksa`
 * (F7-30), dijalankan berurutan sehingga urutan galat tetap:
 * `PeriksaSekolah` (langkah 1, SLB K28, usulan nama K99),
 * `PeriksaPenanggungJawab` (langkah 2), `PeriksaAtlet` (langkah 3, dengan
 * `PeriksaNisn`, `PeriksaKelompokUmur` K95/K98, `PeriksaNomor`, dan
 * `PeriksaRegu` K105–K107), `PeriksaTim` (kelengkapan regu, K57, K106, K108),
 * `PeriksaKuota` (K51), lalu `SusunHasil` (data dan rincian biaya). Bersama:
 * `IndukPeriksa`, `DaftarGalat`, dan `MasukanPeriksa`.
 */
final class PemeriksaPendaftaran
{
    public const NAMA_SEKOLAH_MAKSIMAL = 150;
    public const NAMA_MAKSIMAL        = 100;
    public const TAHUN_LAHIR_TERKECIL = 1950;

    /**
     * Jenjang di data referensi sekolah → kode jenjang pendaftaran.
     */
    public const JENJANG_REFERENSI = ['SD' => 'A', 'SMP' => 'B', 'SMA' => 'C'];

    /**
     * Kunci kiriman berisi id nomor beregu yang tim susulannya sudah
     * dikonfirmasi official (K57).
     */
    public const KUNCI_KONFIRMASI = 'konfirmasi_susulan';

    /**
     * Kunci kiriman berisi inti lama ("nomor:id atlet") yang menjadi
     * cadangan karena atlet susulan menjadi inti (K105).
     */
    public const KUNCI_TURUN = 'turun_cadangan';

    private readonly IndukPeriksa $induk;

    /**
     * @param array<string, mixed> $induk    Bila admin mengubah pendaftaran, boleh ditambah
     *                                       `kelompok_umur_nonaktif` dan `nomor_lomba_nonaktif`
     *                                       (`DataIndukPendaftaran::nonaktif()`).
     * @param array<string, mixed> $ada
     * @param array<int, array{kelompok_umur_id: int, nomor: list<int>}> $simpanan
     *                                       Atlet tersimpan per id (hanya saat admin mengubah
     *                                       pendaftaran): kelompok umur dan nomor lomba yang sudah
     *                                       dimatikan tetap diterima untuk atlet yang sudah memakainya
     *                                       (K98), tidak untuk atlet atau nomor baru.
     * @param bool $longgar                   Admin mengubah pendaftaran: regu yang sudah terdaftar boleh
     *                                       kurang inti (`KelengkapanTim`, K105).
     */
    public function __construct(
        array $induk,
        private readonly array $ada,
        private readonly string $hariIni,
        private readonly array $simpanan = [],
        private readonly bool $longgar = false,
    ) {
        $this->induk = new IndukPeriksa($induk);
    }

    /**
     * Apakah sekolah di data referensi berbentuk SLB (K28).
     *
     * @param array<string, mixed> $sekolah
     */
    public static function slb(array $sekolah): bool
    {
        return PeriksaSekolah::slb($sekolah);
    }

    /**
     * Pesan penolakan SLB, dipakai juga pencarian NPSN.
     */
    public static function pesanSlb(string $nama = ''): string
    {
        return PeriksaSekolah::pesanSlb($nama);
    }

    /**
     * NPSN kiriman yang sudah dirapikan (8 angka), atau `null`.
     *
     * @param array<string, mixed> $kiriman
     */
    public static function npsnKiriman(array $kiriman): ?string
    {
        // Spasi di mana pun dibuang, sama dengan PeriksaSekolah: "2031 7000"
        // harus dibaca 20317000 juga saat memeriksa susulan, kuota, kunci, dan SLB.
        $npsn = preg_replace('/\s+/', '', MasukanPeriksa::teks($kiriman['sekolah']['npsn'] ?? null)) ?? '';

        return preg_match('/^\d{8}$/D', $npsn) === 1 ? $npsn : null;
    }

    /**
     * NISN kiriman yang berbentuk sah (10 angka), untuk ditanyakan ke basis data.
     *
     * @param array<string, mixed> $kiriman
     *
     * @return list<string>
     */
    public static function nisnKiriman(array $kiriman): array
    {
        $hasil = [];

        foreach (MasukanPeriksa::daftar($kiriman['atlet'] ?? null) as $atlet) {
            $nisn = trim(MasukanPeriksa::teks(is_array($atlet) ? ($atlet['nisn'] ?? null) : null));

            if (preg_match('/^\d{10}$/D', $nisn) === 1) {
                $hasil[$nisn] = $nisn;
            }
        }

        return array_values($hasil);
    }

    /**
     * Kiriman wizard publik dengan kunci `konfirmasi_susulan` yang pasti ada
     * dan rapi (list "nomor:regu"), supaya pemeriksaan konfirmasi regu
     * susulan (K57) selalu berjalan. Isian yang bukan "nomor:regu"
     * diabaikan; kunci yang hilang menjadi list kosong.
     *
     * @param array<string, mixed> $kiriman
     *
     * @return array<string, mixed>
     */
    public static function wajibKonfirmasiSusulan(array $kiriman): array
    {
        $kiriman[self::KUNCI_KONFIRMASI] = self::idKonfirmasi($kiriman[self::KUNCI_KONFIRMASI] ?? null);

        return $kiriman;
    }

    /**
     * Periksa kiriman.
     *
     * @param array<string, mixed> $kiriman     Kunci `konfirmasi_susulan` bersifat pilihan (lihat komentar kelas).
     * @param bool                 $periksaFoto false = foto hanya diperiksa ada atau tidak
     *                                          (dipakai bila isi foto tidak ikut dikirim)
     *
     * @return array{galat: list<array{langkah: int, jalur: string, pesan: string}>, data: array<string, mixed>|null, rincian: array<string, mixed>|null}
     */
    public function periksa(array $kiriman, bool $periksaFoto = true): array
    {
        $galat         = new DaftarGalat();
        $reguTersimpan = $this->ada['regu_tersimpan'] ?? [];

        $konfirmasi = array_key_exists(self::KUNCI_KONFIRMASI, $kiriman) ? self::idKonfirmasi($kiriman[self::KUNCI_KONFIRMASI]) : null;

        $sekolah         = (new PeriksaSekolah($this->ada['referensi'] ?? null, $galat))->periksa(is_array($kiriman['sekolah'] ?? null) ? $kiriman['sekolah'] : []);
        $regu            = new PeriksaRegu($this->induk, $sekolah['npsn'] !== null ? ($this->ada['tim'] ?? []) : [], $reguTersimpan, $galat);
        $penanggungJawab = (new PeriksaPenanggungJawab($this->induk, $galat))->periksa(MasukanPeriksa::daftar($kiriman['penanggung_jawab'] ?? null), $periksaFoto);
        [$atlet, $individu, $anggota] = (new PeriksaAtlet($this->induk, $this->ada, $this->hariIni, $this->simpanan, $regu, $galat))
            ->periksa(MasukanPeriksa::daftar($kiriman['atlet'] ?? null), $sekolah['jenjang']);

        [$beregu, $turun] = (new PeriksaTim($this->induk, $regu, $this->longgar, $galat))->periksa($anggota, $konfirmasi, $kiriman[self::KUNCI_TURUN] ?? null, $reguTersimpan);
        (new PeriksaKuota($this->induk, $galat))->periksa($individu, $sekolah['npsn'], $this->ada['individu'] ?? []);

        if (! $galat->kosong()) {
            return ['galat' => $galat->semua(), 'data' => null, 'rincian' => null];
        }

        return (new SusunHasil($this->induk))->susun($sekolah, $penanggungJawab, $atlet, $beregu, $turun);
    }

    /**
     * @return list<string> kunci "nomor:regu"
     */
    private static function idKonfirmasi(mixed $nilai): array
    {
        $hasil = [];

        foreach (MasukanPeriksa::daftar($nilai) as $kunci) {
            $pecah = Regu::pecah($kunci);

            if ($pecah !== null) {
                $hasil[$kunci] = $kunci;
            }
        }

        return array_values($hasil);
    }
}
