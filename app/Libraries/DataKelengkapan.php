<?php

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\I18n\Time;
use DateTimeInterface;

/**
 * Mengumpulkan potret data untuk `KelengkapanPengaturan` dan
 * `BukaTutupPendaftaran` dari pengaturan (`setting()`) dan tabel.
 *
 * Tabel dibaca lewat pembangun kueri (`db_connect()->table(...)`), bukan model
 * tugas lain (rencana Tahap 2, "Basis data dan model"). Kolom batas tahun
 * lahir dibaca dari `tahun_lahir_awal`/`tahun_lahir_akhir` (K36); selama
 * kolom itu belum ada, nilainya dianggap kosong.
 *
 * Pemakaian di pengendali, halaman depan (Tahap 3), dan wizard (Tahap 4):
 *
 *     $data        = new DataKelengkapan();
 *     $kelengkapan = $data->kelengkapan();   // KelengkapanPengaturan
 *     $pendaftaran = $data->bukaTutup();     // BukaTutupPendaftaran pada waktu sekarang (WIB)
 *
 *     if (! $pendaftaran->terbuka()) {
 *         return view('...', ['alasan' => $pendaftaran->kalimat()]);
 *     }
 *
 * Potret dibaca sekali per objek; buat objek baru untuk membaca ulang.
 */
final class DataKelengkapan
{
    private BaseConnection $db;

    /**
     * @var array<string, mixed>|null
     */
    private ?array $potret = null;

    private ?KelengkapanPengaturan $kelengkapan = null;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /**
     * Kelengkapan pengaturan saat ini.
     */
    public function kelengkapan(): KelengkapanPengaturan
    {
        return $this->kelengkapan ??= new KelengkapanPengaturan($this->potret());
    }

    /**
     * Keadaan pendaftaran pada `$sekarang` (bawaan: jam sekarang, zona waktu aplikasi).
     */
    public function bukaTutup(?DateTimeInterface $sekarang = null): BukaTutupPendaftaran
    {
        return new BukaTutupPendaftaran(
            lengkap: $this->kelengkapan()->lengkap(),
            mulai: self::teksAtauNull(setting('Pendaftaran.mulai')),
            akhir: self::teksAtauNull(setting('Pendaftaran.akhir')),
            bukaManual: (bool) setting('Pendaftaran.buka_manual'),
            bukaManualSampai: self::teksAtauNull(setting('Pendaftaran.buka_manual_sampai')),
            sekarang: $sekarang ?? Time::now(config('App')->appTimezone),
            zona: config('App')->appTimezone,
        );
    }

    /**
     * Potret data dalam bentuk yang diterima `KelengkapanPengaturan`.
     *
     * @return array<string, mixed>
     */
    public function potret(): array
    {
        return $this->potret ??= [
            'acara' => [
                'nama'            => setting('Acara.nama'),
                'seri'            => setting('Acara.seri'),
                'tahun'           => setting('Acara.tahun'),
                'tempat'          => setting('Acara.tempat'),
                'tanggal_mulai'   => setting('Acara.tanggal_mulai'),
                'tanggal_selesai' => setting('Acara.tanggal_selesai'),
            ],
            'pendaftaran' => [
                'mulai' => setting('Pendaftaran.mulai'),
                'akhir' => setting('Pendaftaran.akhir'),
            ],
            'pembayaran' => [
                'bank'             => setting('Pembayaran.bank'),
                'nomor_rekening'   => setting('Pembayaran.nomor_rekening'),
                'pemilik_rekening' => setting('Pembayaran.pemilik_rekening'),
                'nomor_wa'         => setting('Pembayaran.nomor_wa'),
            ],
        ] + $this->dataTabel();
    }

    /**
     * Kelompok umur (dengan jenjang dan awalannya) dan nomor lomba.
     *
     * Bila tabel belum bisa dibaca (basis data belum dimigrasi), dasbor tetap
     * terbuka dengan data kosong, sehingga semua butir tabel belum lengkap;
     * galatnya dicatat di log.
     *
     * @return array{kelompok_umur: list<array<string, mixed>>, nomor_lomba: list<array<string, mixed>>}
     */
    private function dataTabel(): array
    {
        try {
            $kelompokUmur = $this->db->table('kelompok_umur')
                ->orderBy('urutan')->orderBy('id')
                ->get()->getResultArray();

            $jenjang = [];

            foreach ($this->db->table('kelompok_umur_jenjang')->select('kelompok_umur_id, jenjang')->get()->getResultArray() as $baris) {
                $jenjang[(int) $baris['kelompok_umur_id']][] = $baris['jenjang'];
            }

            $awalan = [];

            foreach ($this->db->table('awalan_nomor_dada')->select('kelompok_umur_id, awalan')->orderBy('urutan')->orderBy('id')->get()->getResultArray() as $baris) {
                $awalan[(int) $baris['kelompok_umur_id']][] = $baris['awalan'];
            }

            $nomorLomba = $this->db->table('nomor_lomba')
                ->select('nomor_lomba.*')
                ->join('kelompok_umur', 'kelompok_umur.id = nomor_lomba.kelompok_umur_id')
                ->orderBy('kelompok_umur.urutan')->orderBy('kelompok_umur.id')
                ->orderBy('nomor_lomba.urutan')->orderBy('nomor_lomba.id')
                ->get()->getResultArray();
        } catch (DatabaseException $e) {
            log_message('error', 'Data kelengkapan pengaturan tidak terbaca: {pesan}', ['pesan' => $e->getMessage()]);

            return ['kelompok_umur' => [], 'nomor_lomba' => []];
        }

        return [
            'kelompok_umur' => array_map(static fn (array $ku): array => [
                'id'                => (int) $ku['id'],
                'nama'              => $ku['nama'],
                'aktif'             => (int) $ku['aktif'],
                'tahun_lahir_awal'  => $ku['tahun_lahir_awal'] ?? null,
                'tahun_lahir_akhir' => $ku['tahun_lahir_akhir'] ?? null,
                'biaya_individu'    => $ku['biaya_individu'],
                'jenjang'           => $jenjang[(int) $ku['id']] ?? [],
                'awalan'            => $awalan[(int) $ku['id']] ?? [],
            ], $kelompokUmur),
            'nomor_lomba' => $nomorLomba,
        ];
    }

    private static function teksAtauNull(mixed $nilai): ?string
    {
        return is_string($nilai) && trim($nilai) !== '' ? $nilai : null;
    }
}
