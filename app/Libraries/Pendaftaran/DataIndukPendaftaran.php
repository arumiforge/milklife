<?php

namespace App\Libraries\Pendaftaran;

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

        $kelompokUmur = [];

        foreach (model(KelompokUmurModel::class)->daftarLengkap() as $ku) {
            if ((int) $ku['aktif'] !== 1) {
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
            if ((int) $nomor['aktif'] !== 1 || (int) ($nomor['kelompok_umur_aktif'] ?? 0) !== 1) {
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

        return $this->induk = [
            'kelompok_umur' => $kelompokUmur,
            'nomor_lomba'   => $nomorLomba,
            'aturan'        => self::aturan(),
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
