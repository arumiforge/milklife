<?php

namespace App\Controllers;

use App\Libraries\IsiHalamanDepan;

/**
 * Halaman depan (rencana Tahap 3, "Halaman depan").
 *
 * Semua data dibaca lewat `IsiHalamanDepan`; tampilan `beranda.php` hanya
 * menyusun bagian dengan urutan K42 (pembuka → lomba → jadwal → unduhan →
 * kontak; footer di tata letak publik), ditambah carousel foto, angka acara,
 * dan hasil terbaru (F9-03).
 */
class BerandaController extends BaseController
{
    public function index(): string
    {
        $isi = new IsiHalamanDepan();

        return view('beranda', [
            'acara'          => $isi->acara(),
            'tampilan'       => $isi->tampilan(),
            'bukaTutup'      => $isi->bukaTutup(),
            'tombol'         => $isi->tombol(),
            'kelompokUmur'   => $isi->kelompokUmur(),
            'tampilkanBiaya' => $isi->tampilkanBiaya(),
            'jadwal'         => $isi->jadwal(),
            'unduhan'        => $isi->unduhan(),
            'narahubung'     => $isi->narahubung(),
            'mediaSosial'    => $isi->mediaSosial(),
            'waTanya'        => $isi->waTanya(),
            'waBagikan'      => $isi->waBagikan(),
            'carousel'       => $isi->carousel(),
            'angka'          => $isi->angkaAcara(),
            'hasilTerbaru'   => $isi->hasilTerbaru(),
        ]);
    }
}
