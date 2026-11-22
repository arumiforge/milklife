<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Hasil\HasilLomba;
use App\Libraries\Hasil\Klasemen;
use App\Libraries\Hasil\MetodeKlasemen;
use App\Libraries\Hasil\UnduhanKlasemen;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Klasemen sekolah di panel admin (F8-05, K124): `admin/klasemen`. Menghitung
 * juara Final semua nomor, termasuk yang belum terbit (diberi tanda), per
 * kelompok umur (F11-02), dengan metode dan gabungan kelompok umur dari
 * Pengaturan > Hasil (F9-11); unduh PDF dan Excel.
 */
class KlasemenController extends BaseController
{
    public function index(): string
    {
        // Sekali baca peringkat 1–8 Final (sama dengan `HasilLomba::klasemen()`).
        $juara  = (new HasilLomba())->juara(false, Klasemen::PERINGKAT_POIN);
        $metode = MetodeKlasemen::metode();
        $poin    = MetodeKlasemen::poin();
        $susunan = MetodeKlasemen::susunan();

        return view('admin/hasil/klasemen', [
            'klasemen'    => Klasemen::hitung($juara, $metode, $poin, $susunan),
            'metode'      => $metode,
            'keterangan'  => Klasemen::teksSusunan($susunan) . ' ' . Klasemen::keterangan($metode, $poin),
            'belumTerbit' => count(array_unique(array_column(array_filter($juara, static fn (array $j): bool => ! $j['terbit'] && ($metode === Klasemen::POIN || $j['peringkat'] <= 3)), 'nomor_id'))),
        ]);
    }

    public function unduh(string $jenis): ResponseInterface
    {
        $unduhan = new UnduhanKlasemen((new HasilLomba())->klasemen(), false);

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
