<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Hasil\HasilLomba;
use App\Libraries\Hasil\Klasemen;
use App\Libraries\Hasil\UnduhanKlasemen;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Klasemen sekolah di panel admin (F8-05, K124): `admin/klasemen`. Menghitung
 * juara Final semua nomor, termasuk yang belum terbit (diberi tanda), per
 * jenjang; unduh PDF dan Excel.
 */
class KlasemenController extends BaseController
{
    public function index(): string
    {
        $juara = (new HasilLomba())->juara();

        return view('admin/hasil/klasemen', [
            'klasemen'    => Klasemen::hitung($juara),
            'belumTerbit' => count(array_unique(array_column(array_filter($juara, static fn (array $j): bool => ! $j['terbit']), 'nomor_id'))),
        ]);
    }

    public function unduh(string $jenis): ResponseInterface
    {
        $unduhan = new UnduhanKlasemen(Klasemen::hitung((new HasilLomba())->juara()), false);

        if ($jenis === 'excel') {
            return $unduhan->excel()->unduh(UnduhanKlasemen::namaBerkas('xlsx'));
        }

        return $this->response
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . UnduhanKlasemen::namaBerkas('pdf') . '"')
            ->setHeader('Cache-Control', 'no-store')
            ->setBody($unduhan->pdf());
    }
}
