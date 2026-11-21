<?php

namespace App\Controllers;

use App\Libraries\IsiHalamanDepan;
use App\Models\GaleriModel;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * Halaman Galeri publik (`/galeri`, F9-06, K140 butir 11): kartu bersampul
 * yang membuka album Instagram atau Google Drive panitia di tab baru.
 *
 * - Hanya album aktif yang tautannya sudah diisi (`GaleriModel::publik()`).
 * - Selama sakelar `Galeri.tampil` mati (bawaan), halaman 404 dan butir menu
 *   Galeri tidak tampil (`App\Libraries\MenuPublik`).
 */
class GaleriController extends BaseController
{
    /**
     * Sakelar tampil halaman Galeri dari Pengaturan > Galeri.
     */
    public static function tampil(): bool
    {
        return (bool) setting('Galeri.tampil');
    }

    public function index(): string
    {
        if (! self::tampil()) {
            throw PageNotFoundException::forPageNotFound();
        }

        return view('galeri/index', [
            'namaAcara' => (new IsiHalamanDepan())->acara()['nama'],
            'album'     => model(GaleriModel::class)->publik(),
        ]);
    }
}
