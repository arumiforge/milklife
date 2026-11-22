<?php

namespace App\Controllers\Admin\Pengaturan;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Sakelar halaman Jadwal pertandingan publik (F9-07): `POST
 * /admin/pengaturan/halaman-depan/jadwal`, dari kartu "Halaman Jadwal
 * pertandingan" di Pengaturan > Halaman depan (`admin/pengaturan/_halaman_jadwal.php`).
 *
 * Menyimpan `HalamanDepan.tampil_jadwal`. Dipisah dari formulir isi halaman
 * depan supaya menyalakan atau mematikan halaman tidak ikut menyimpan isian lain.
 */
class HalamanJadwalController extends BaseController
{
    public function simpan(): RedirectResponse
    {
        // Kotak centang: tidak terkirim sama sekali bila tidak dicentang.
        $tampil = $this->request->getPost('tampil_jadwal') === '1';

        setting('HalamanDepan.tampil_jadwal', $tampil);

        $kembali = $this->request->getPost('kembali') === 'jadwal-pertandingan'
            ? site_url('admin/jadwal-pertandingan') . '#sakelar-jadwal'
            : site_url('admin/pengaturan/halaman-depan') . '#halaman-jadwal';

        return redirect()->to($kembali)
            ->with('pesan_sukses', $tampil ? 'Halaman Jadwal pertandingan sekarang tampil untuk umum.' : 'Halaman Jadwal pertandingan disembunyikan.');
    }
}
