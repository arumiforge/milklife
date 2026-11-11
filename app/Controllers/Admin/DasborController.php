<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\BukaTutupPendaftaran;
use App\Libraries\DataKelengkapan;

/**
 * Dasbor panel admin: keadaan pendaftaran, daftar centang kelengkapan
 * pengaturan (rancangan 6.1), dan keadaan kosong pendaftaran. Angka ringkasan
 * pendaftaran menyusul di Tahap 6.
 */
class DasborController extends BaseController
{
    public function index(): string
    {
        $data        = new DataKelengkapan();
        $kelengkapan = $data->kelengkapan();
        $pendaftaran = $data->bukaTutup();

        $mulai       = BukaTutupPendaftaran::bacaWaktu(setting('Pendaftaran.mulai'));
        $akhir       = BukaTutupPendaftaran::bacaWaktu(setting('Pendaftaran.akhir'));
        $manualSampai = BukaTutupPendaftaran::bacaWaktu(setting('Pendaftaran.buka_manual_sampai'));

        return view('admin/dasbor', [
            'butir'         => $kelengkapan->butir(),
            'jumlahLengkap' => $kelengkapan->jumlahLengkap(),
            'semuaLengkap'  => $kelengkapan->lengkap(),
            'keadaan'       => $pendaftaran->keadaan(),
            'labelKeadaan'  => $pendaftaran->label(),
            'kalimat'       => $pendaftaran->kalimat(),
            'lewatManual'   => $pendaftaran->lewatManual(),
            'jadwal'        => $mulai !== null && $akhir !== null
                ? tanggal_id($mulai, true) . ' sampai ' . tanggal_id($akhir, true)
                : '',
            'bukaManual'    => (bool) setting('Pendaftaran.buka_manual')
                ? ($manualSampai !== null ? 'Menyala sampai ' . tanggal_id($manualSampai, true) : 'Menyala, tetapi batas waktunya belum diisi')
                : 'Tidak dipakai',
            'gambarKepala'  => (string) setting('HalamanDepan.gambar_kepala'),
        ]);
    }
}
