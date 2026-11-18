<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\HariLomba\CetakBib;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Cetak nomor atlet (BIB) massal (F8-03): `admin/nomor-atlet/cetak`.
 *
 * - `index`: pilih kelompok umur atau semua, dengan banyak atlet ber-BIB.
 * - `pdf`: `?ku=<id kelompok umur>` atau `?ku=semua`; dua BIB per A4.
 */
class CetakBibController extends BaseController
{
    public function index(): string
    {
        $kelompok = (new CetakBib())->kelompokUmur();

        return view('admin/hari_lomba/bib', [
            'kelompokUmur' => $kelompok,
            'jumlahSemua'  => array_sum(array_column($kelompok, 'atlet')),
        ]);
    }

    public function pdf(): ResponseInterface
    {
        $bib = new CetakBib();
        $ku  = (string) $this->request->getGet('ku');
        $id  = $ku === 'semua' || $ku === '' ? null : (int) $ku;

        if ($id !== null && $bib->namaKelompokUmur($id) === null) {
            throw PageNotFoundException::forPageNotFound('Kelompok umur tidak ditemukan.');
        }

        // Ratusan halaman bisa butuh waktu lebih lama dari batas bawaan.
        @set_time_limit(300);

        return $this->response
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $bib->namaBerkas($id) . '"')
            ->setHeader('Cache-Control', 'no-store')
            ->setBody($bib->pdf($id));
    }
}
