<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\DataKelengkapan;
use App\Libraries\Panel\Rekap;
use App\Libraries\Panel\RingkasanDasbor;
use App\Models\UsulanNamaSekolahModel;

/**
 * Dasbor panel admin (K102): kartu ringkasan data (`Rekap::ringkasan()` dan
 * `RingkasanDasbor::angka()`), grafik (`RingkasanDasbor::grafik()`), dan
 * keadaan kosong pendaftaran. Keadaan pendaftaran, kelengkapan pengaturan,
 * dan gambar kepala ada di Pengecekan (`PengecekanController`); dasbor hanya
 * menampilkan lencana keadaan dan peringatan bila pengaturan belum lengkap.
 */
class DasborController extends BaseController
{
    public function index(): string
    {
        $data        = new DataKelengkapan();
        $kelengkapan = $data->kelengkapan();
        $pendaftaran = $data->bukaTutup();
        $ringkasan   = new RingkasanDasbor();

        return view('admin/dasbor', [
            'angka'         => (new Rekap())->ringkasan(),
            'tambahan'      => $ringkasan->angka(),
            'grafik'        => $ringkasan->grafik(),
            'keadaan'       => $pendaftaran->keadaan(),
            'labelKeadaan'  => $pendaftaran->label(),
            'kalimat'       => $pendaftaran->kalimat(),
            'semuaLengkap'  => $kelengkapan->lengkap(),
            'jumlahLengkap' => $kelengkapan->jumlahLengkap(),
            'jumlahButir'   => count($kelengkapan->butir()),
            'usulanNama'    => model(UsulanNamaSekolahModel::class)->jumlahMenunggu(),
        ]);
    }
}
