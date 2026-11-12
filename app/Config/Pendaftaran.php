<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Pengaturan jadwal dan aturan pendaftaran (rancangan 6.1, 6.3–6.5).
 *
 * Tidak ada tanggal patokan usia (K37): kelompok umur ditentukan tahun lahir
 * (K36), dan usia hanya ditampilkan sebagai keterangan, dihitung pada tanggal
 * mulai acara (`Acara.tanggal_mulai`).
 *
 * Nilai di sini hanya nilai awal; baca dan tulis lewat
 * `setting('Pendaftaran.<kunci>')` (lihat `Config\Acara`). Waktu dalam WIB
 * (`app.appTimezone` = `Asia/Jakarta`).
 */
class Pendaftaran extends BaseConfig
{
    /**
     * Waktu pendaftaran dibuka, `Y-m-d H:i:s`.
     */
    public ?string $mulai = null;

    /**
     * Waktu pendaftaran ditutup, `Y-m-d H:i:s`.
     */
    public ?string $akhir = null;

    /**
     * Pendaftaran dibuka manual oleh admin, di luar jadwal.
     */
    public bool $buka_manual = false;

    /**
     * Batas waktu buka manual, `Y-m-d H:i:s`; wajib bila `buka_manual` aktif.
     */
    public ?string $buka_manual_sampai = null;

    /**
     * Tampilan usia sebagai keterangan: `tahun` atau `tahun_bulan`. Usia
     * dihitung pada tanggal mulai acara dan tidak menentukan kelompok umur.
     */
    public string $tampilan_usia = 'tahun';

    /**
     * Jumlah penanggung jawab paling sedikit per pendaftaran.
     */
    public int $penanggung_jawab_minimal = 1;

    /**
     * Jumlah penanggung jawab paling banyak per pendaftaran.
     */
    public int $penanggung_jawab_maksimal = 5;

    /**
     * Jumlah nomor individu paling banyak per atlet.
     */
    public int $nomor_individu_maksimal = 2;

    /**
     * Jumlah atlet satu sekolah pada satu nomor individu paling banyak (K51),
     * dihitung bersama pendaftaran sebelumnya dari NPSN yang sama.
     * `null` = tidak dibatasi; nilai awal tidak dibatasi (K53).
     */
    public ?int $atlet_per_nomor_maksimal = null;
}
