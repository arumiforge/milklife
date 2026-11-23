<?php

namespace App\Controllers;

use App\Libraries\Hasil\Rekor;
use App\Libraries\IsiHalamanDepan;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * Halaman Rekor publik (`/rekor`, F11-06, K172 butir 3): rekor kejuaraan
 * yang berlaku per kelompok umur, nomor, spesifikasi alat, dan cara
 * pencatatan waktu. Selama sakelar `Rekor.tampil` mati (bawaan), halaman 404.
 */
class RekorController extends BaseController
{
    public function index(): string
    {
        if (! Rekor::tampil()) {
            throw PageNotFoundException::forPageNotFound();
        }

        return view('rekor/index', [
            'namaAcara' => (new IsiHalamanDepan())->acara()['nama'],
            'kelompok'  => (new Rekor())->daftar(true),
        ]);
    }
}
