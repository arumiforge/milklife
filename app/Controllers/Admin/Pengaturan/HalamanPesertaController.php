<?php

namespace App\Controllers\Admin\Pengaturan;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Sakelar halaman Peserta publik (F9-12): `POST
 * /admin/pengaturan/halaman-depan/peserta`, dari kartu "Halaman Peserta" di
 * Pengaturan > Halaman Depan (`admin/pengaturan/_halaman_peserta.php`).
 *
 * Menyimpan `HalamanDepan.tampil_peserta`. Dipisah dari formulir isi halaman
 * depan supaya menyalakan atau mematikan halaman tidak ikut menyimpan isian
 * lain.
 */
class HalamanPesertaController extends BaseController
{
    public function simpan(): RedirectResponse
    {
        // Kotak centang: tidak terkirim sama sekali bila tidak dicentang.
        $tampil = $this->request->getPost('tampil_peserta') === '1';

        setting('HalamanDepan.tampil_peserta', $tampil);

        return redirect()->to(site_url('admin/pengaturan/halaman-depan') . '#halaman-peserta')
            ->with('pesan_sukses', $tampil ? 'Halaman Peserta sekarang tampil untuk umum.' : 'Halaman Peserta disembunyikan.');
    }
}
