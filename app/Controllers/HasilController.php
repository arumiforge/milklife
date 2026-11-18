<?php

namespace App\Controllers;

use App\Libraries\Hasil\HasilLomba;
use App\Libraries\IsiHalamanDepan;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * Hasil lomba publik (F8-04, K124 butir 7; asumsi 8 rencana Tahap 8).
 *
 * - `hasil`: nomor yang hasilnya sudah diterbitkan admin, per kelompok umur,
 *   dengan juara I–III babak Final.
 * - `hasil/<id>`: semua babak satu nomor yang sudah terbit. Nomor yang belum
 *   terbit (atau ditarik kembali) menampilkan halaman tidak ditemukan.
 *
 * Berbeda dengan `App\Controllers\Admin\HasilController` (panel admin).
 */
class HasilController extends BaseController
{
    public function index(): string
    {
        $hasil = new HasilLomba();

        return view('hasil/index', [
            'namaAcara' => (new IsiHalamanDepan())->acara()['nama'],
            'kelompok'  => $hasil->daftarNomor(true),
            'juara'     => $hasil->juara(true),
        ]);
    }

    public function nomor(int $id): string
    {
        $hasil = new HasilLomba();
        $nomor = $hasil->nomor($id);

        if ($nomor === null || $nomor['hasil_terbit_pada'] === null) {
            throw PageNotFoundException::forPageNotFound('Hasil nomor lomba ini belum diterbitkan.');
        }

        return view('hasil/nomor', [
            'namaAcara' => (new IsiHalamanDepan())->acara()['nama'],
            'nomor'     => $nomor,
            'babak'     => $hasil->susunan($nomor),
        ]);
    }
}
