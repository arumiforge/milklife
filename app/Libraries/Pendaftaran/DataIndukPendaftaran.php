<?php

namespace App\Libraries\Pendaftaran;

use App\Libraries\Jenjang;
use App\Models\AtletModel;
use App\Models\AtletNomorModel;
use App\Models\KelompokUmurModel;
use App\Models\NomorLombaModel;
use App\Models\PendaftaranBereguModel;
use App\Models\SekolahModel;

/**
 * Pengumpul data dari basis data dan pengaturan untuk `PemeriksaPendaftaran`
 * dan wizard pendaftaran. Bentuk lariknya ditulis di komentar
 * `PemeriksaPendaftaran`.
 *
 *     $data    = new DataIndukPendaftaran();
 *     $periksa = new PemeriksaPendaftaran(
 *         $data->induk(),
 *         $data->ada(PemeriksaPendaftaran::npsnKiriman($kiriman), PemeriksaPendaftaran::nisnKiriman($kiriman)),
 *         date('Y-m-d'),
 *     );
 *
 * Tidak `final`, supaya uji bisa menirukan kiriman lain yang tersimpan pada
 * saat yang sama.
 */
class DataIndukPendaftaran
{
    /**
     * @var array<string, mixed>|null
     */
    private ?array $induk = null;

    /**
     * Kelompok umur aktif, nomor lomba aktif pada kelompok umur aktif, dan
     * aturan dari pengaturan Pendaftaran. Dibaca sekali per objek.
     *
     * @return array{kelompok_umur: list<array<string, mixed>>, nomor_lomba: list<array<string, mixed>>, aturan: array<string, int|null>}
     */
    public function induk(): array
    {
        if ($this->induk !== null) {
            return $this->induk;
        }

        [$kelompokUmur, $nomorLomba] = self::baris(
            static fn (array $ku): bool => (int) $ku['aktif'] === 1,
            static fn (array $nomor): bool => (int) $nomor['aktif'] === 1 && (int) ($nomor['kelompok_umur_aktif'] ?? 0) === 1,
        );

        return $this->induk = [
            'kelompok_umur' => $kelompokUmur,
            'nomor_lomba'   => $nomorLomba,
            'aturan'        => self::aturan(),
        ];
    }

    /**
     * Kelompok umur dan nomor lomba yang sudah dimatikan tetapi masih dipakai
     * pendaftaran yang sedang diubah admin (K98). Bentuk barisnya sama dengan
     * `induk()`; hanya id yang diminta dan tidak aktif yang ikut. Nomor lomba
     * aktif milik kelompok umur yang dimatikan ikut dianggap tidak dibuka.
     *
     * @param list<int> $kelompokUmurId
     * @param list<int> $nomorLombaId
     *
     * @return array{kelompok_umur: list<array<string, mixed>>, nomor_lomba: list<array<string, mixed>>}
     */
    public function nonaktif(array $kelompokUmurId, array $nomorLombaId): array
    {
        $ku    = array_flip(array_map('intval', $kelompokUmurId));
        $nomor = array_flip(array_map('intval', $nomorLombaId));

        [$kelompokUmur, $nomorLomba] = self::baris(
            static fn (array $baris): bool => (int) $baris['aktif'] !== 1 && isset($ku[(int) $baris['id']]),
            static fn (array $baris): bool => ((int) $baris['aktif'] !== 1 || (int) ($baris['kelompok_umur_aktif'] ?? 0) !== 1) && isset($nomor[(int) $baris['id']]),
        );

        return ['kelompok_umur' => $kelompokUmur, 'nomor_lomba' => $nomorLomba];
    }

    /**
     * Baris kelompok umur dan nomor lomba yang lolos saringan, dalam bentuk induk.
     *
     * @param callable(array<string, mixed>): bool $ambilKu
     * @param callable(array<string, mixed>): bool $ambilNomor
     *
     * @return array{0: list<array<string, mixed>>, 1: list<array<string, mixed>>}
     */
    private static function baris(callable $ambilKu, callable $ambilNomor): array
    {
        $kelompokUmur = [];

        foreach (model(KelompokUmurModel::class)->daftarLengkap() as $ku) {
            if (! $ambilKu($ku)) {
                continue;
            }

            $kelompokUmur[] = [
                'id'                => (int) $ku['id'],
                'nama'              => (string) $ku['nama'],
                'tahun_lahir_awal'  => self::angka($ku['tahun_lahir_awal'] ?? null),
                'tahun_lahir_akhir' => self::angka($ku['tahun_lahir_akhir'] ?? null),
                'biaya_individu'    => self::angka($ku['biaya_individu'] ?? null),
                'jenjang'           => array_values($ku['jenjang']),
            ];
        }

        $nomorLomba = [];

        foreach (model(NomorLombaModel::class)->daftarLengkap() as $nomor) {
            if (! $ambilNomor($nomor)) {
                continue;
            }

            $nomorLomba[] = [
                'id'               => (int) $nomor['id'],
                'kelompok_umur_id' => (int) $nomor['kelompok_umur_id'],
                'nama'             => (string) $nomor['nama'],
                'jenis'            => (string) $nomor['jenis'],
                'kategori'         => (string) $nomor['kategori'],
                'jumlah_inti'      => self::angka($nomor['jumlah_inti']),
                'batas_anggota'    => self::angka($nomor['batas_anggota']),
                'jumlah_putra'     => self::angka($nomor['jumlah_putra']),
                'jumlah_putri'     => self::angka($nomor['jumlah_putri']),
                'biaya_beregu'     => self::angka($nomor['biaya_beregu']),
            ];
        }

        return [$kelompokUmur, $nomorLomba];
    }

    /**
     * Data yang disematkan di halaman wizard (F4-03) sebagai JSON. Hanya data
     * yang boleh dilihat pengunjung: kelompok umur dan nomor aktif, biaya,
     * batas-batas, dan tanggal acara untuk usia.
     *
     *     [
     *         'jenjang'       => ['A' => 'SD/MI', …],
     *         'kabupaten'     => ['Blora', …],
     *         'kelompok_umur' => [['id' => 2, 'nama' => 'KU 10', 'tahun_lahir_awal' => 2016, 'tahun_lahir_akhir' => 2018,
     *                              'teks_tahun_lahir' => 'Lahir 2016–2018', 'biaya_individu' => 150000, 'jenjang' => ['A']]],
     *         'nomor_lomba'   => [['id' => 7, 'kelompok_umur_id' => 2, 'nama' => 'Lari 60 m', 'nama_lengkap' => 'Lari 60 m putra',
     *                              'jenis' => 'individu', 'kategori' => 'putra', 'kategori_label' => 'Putra',
     *                              'jumlah_inti' => null, 'batas_anggota' => null, 'jumlah_putra' => null,
     *                              'jumlah_putri' => null, 'biaya_beregu' => null]],
     *         'aturan'        => aturan(),
     *         'tampilan_usia' => 'tahun',            // atau 'tahun_bulan'
     *         'tanggal_acara' => '2026-12-09',       // Acara.tanggal_mulai, atau null
     *     ]
     *
     * @return array<string, mixed>
     */
    public function untukWizard(): array
    {
        $induk = $this->induk();

        return [
            'jenjang'       => Jenjang::semua(),
            'kabupaten'     => Kabupaten::semua(),
            'kelompok_umur' => array_map(static fn (array $ku): array => $ku + [
                'teks_tahun_lahir' => KelompokUmurModel::teksTahunLahir($ku['tahun_lahir_awal'], $ku['tahun_lahir_akhir']),
            ], $induk['kelompok_umur']),
            'nomor_lomba' => array_map(static fn (array $nomor): array => $nomor + [
                'nama_lengkap'   => NomorLombaModel::namaLengkap($nomor),
                'kategori_label' => NomorLombaModel::KATEGORI[$nomor['kategori']] ?? $nomor['kategori'],
            ], $induk['nomor_lomba']),
            'aturan'        => $induk['aturan'],
            'tampilan_usia' => setting('Pendaftaran.tampilan_usia') === 'tahun_bulan' ? 'tahun_bulan' : 'tahun',
            'tanggal_acara' => is_string($tanggal = setting('Acara.tanggal_mulai')) && $tanggal !== '' ? substr($tanggal, 0, 10) : null,
        ];
    }

    /**
     * Batas-batas dari pengaturan Pendaftaran.
     *
     * @return array{penanggung_jawab_minimal: int, penanggung_jawab_maksimal: int, nomor_individu_maksimal: int, atlet_per_nomor_maksimal: int|null}
     */
    public static function aturan(): array
    {
        $kuota = self::angka(setting('Pendaftaran.atlet_per_nomor_maksimal'));

        return [
            'penanggung_jawab_minimal'  => (int) setting('Pendaftaran.penanggung_jawab_minimal'),
            'penanggung_jawab_maksimal' => (int) setting('Pendaftaran.penanggung_jawab_maksimal'),
            'nomor_individu_maksimal'   => (int) setting('Pendaftaran.nomor_individu_maksimal'),
            'atlet_per_nomor_maksimal'  => $kuota !== null && $kuota > 0 ? $kuota : null,
        ];
    }

    /**
     * Data yang sudah ada untuk kiriman ini: sekolah referensi, NISN yang
     * sudah terdaftar, tim dan jumlah atlet per nomor individu milik NPSN ini.
     *
     * @param list<string> $nisn
     *
     * @return array{referensi: array<string, mixed>|null, nisn_terdaftar: list<string>, tim: array<int, int>, individu: array<int, int>}
     */
    public function ada(?string $npsn, array $nisn): array
    {
        return [
            'referensi'      => $npsn !== null ? model(SekolahModel::class)->cariNpsn($npsn) : null,
            'nisn_terdaftar' => model(AtletModel::class)->nisnTerdaftar($nisn),
        ] + $this->dataSekolah($npsn);
    }

    /**
     * Tim beregu (nomor => jumlah anggota) dan jumlah atlet per nomor
     * individu milik sebuah NPSN dari semua pendaftarannya. Kosong bila NPSN
     * kosong: pendaftaran tanpa NPSN berdiri sendiri (6.2).
     *
     * @return array{tim: array<int, int>, individu: array<int, int>}
     */
    public function dataSekolah(?string $npsn): array
    {
        if ($npsn === null) {
            return ['tim' => [], 'individu' => []];
        }

        $anggota = model(AtletNomorModel::class)->jumlahPerNomorUntukNpsn($npsn, 'beregu');
        $tim     = [];

        foreach (model(PendaftaranBereguModel::class)->nomorUntukNpsn($npsn) as $nomorId) {
            $tim[$nomorId] = $anggota[$nomorId] ?? 0;
        }

        return [
            'tim'      => $tim,
            'individu' => model(AtletNomorModel::class)->jumlahPerNomorUntukNpsn($npsn, 'individu'),
        ];
    }

    private static function angka(mixed $nilai): ?int
    {
        if ($nilai === null || $nilai === '' || ! is_numeric($nilai)) {
            return null;
        }

        return (int) $nilai;
    }
}
