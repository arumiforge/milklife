<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\HariLomba\DaftarPeserta;
use App\Libraries\HariLomba\DaftarPesertaPdf;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Daftar peserta per nomor lomba untuk pemanggilan atlet dan juri (F8-03):
 * `admin/daftar-peserta`.
 *
 * - `index`: nomor lomba per kelompok umur, banyak peserta Lunas, dan tombol
 *   unduh PDF/Excel per nomor serta semua nomor satu kelompok umur.
 * - `unduh`: satu nomor lomba (`…/<id nomor>/pdf|excel`).
 * - `unduhKelompokUmur`: semua nomor satu kelompok umur
 *   (`…/kelompok-umur/<id>/pdf|excel`); PDF satu nomor per halaman, Excel
 *   satu lembar per nomor.
 */
class DaftarPesertaController extends BaseController
{
    public function index(): string
    {
        return view('admin/hari_lomba/daftar_peserta', [
            'kelompokUmur' => (new DaftarPeserta())->kelompokUmur(),
        ]);
    }

    public function unduh(int $nomorId, string $jenis): ResponseInterface
    {
        $daftar = (new DaftarPeserta())->susun($nomorId);

        if ($daftar === null) {
            throw PageNotFoundException::forPageNotFound('Nomor lomba tidak ditemukan.');
        }

        return $this->kirim([$daftar], $daftar['kelompok_umur'] . ' ' . $daftar['nama'], $jenis);
    }

    public function unduhKelompokUmur(int $kelompokUmurId, string $jenis): ResponseInterface
    {
        $nama = db_connect()->table('kelompok_umur')->select('nama')->where('id', $kelompokUmurId)->get()->getRow();

        if ($nama === null) {
            throw PageNotFoundException::forPageNotFound('Kelompok umur tidak ditemukan.');
        }

        @set_time_limit(300);

        return $this->kirim((new DaftarPeserta())->susunKelompokUmur($kelompokUmurId), (string) $nama->nama . ' Semua nomor', $jenis);
    }

    /**
     * @param list<array<string, mixed>> $semua
     */
    private function kirim(array $semua, string $judul, string $jenis): ResponseInterface
    {
        if ($jenis === 'excel') {
            return DaftarPeserta::excel($semua)->unduh(DaftarPesertaPdf::namaBerkas($judul, 'xlsx'));
        }

        return $this->response
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . DaftarPesertaPdf::namaBerkas($judul, 'pdf') . '"')
            ->setHeader('Cache-Control', 'no-store')
            ->setBody((new DaftarPesertaPdf($semua))->pdf());
    }
}
