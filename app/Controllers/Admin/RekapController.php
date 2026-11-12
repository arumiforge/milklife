<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Panel\EksporExcel;
use App\Libraries\Panel\Rekap;
use App\Libraries\Panel\RekapPdf;
use CodeIgniter\HTTP\DownloadResponse;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Rekap panitia (F6-06): `admin/rekap/<jenis>` dengan saringan
 * `?status=lunas|menunggu` (K60), JSON (`…/data`), Excel (`…/excel`), dan PDF
 * (`…/pdf`). Keempatnya memakai `Rekap::susun()` yang sama. Jenis yang sah
 * dibatasi rute.
 */
class RekapController extends BaseController
{
    public function index(string $jenis = 'nomor-lomba'): string
    {
        return view('admin/rekap/index', [
            'jenis'  => $jenis,
            'status' => $this->status(),
            'rekap'  => (new Rekap())->susun($jenis, $this->status()),
        ]);
    }

    public function data(string $jenis): ResponseInterface
    {
        return $this->response->setJSON((new Rekap())->susun($jenis, $this->status()));
    }

    public function excel(string $jenis): DownloadResponse
    {
        $rekap = (new Rekap())->susun($jenis, $this->status());
        $excel = (new EksporExcel(Rekap::JENIS[$jenis], $rekap['kolom']))->tambahBaris($rekap['baris']);

        if ($rekap['jumlah'] !== null) {
            $excel->tambahBaris([$rekap['jumlah']]);
        }

        return $excel->unduh((new RekapPdf($jenis, $this->status(), $rekap))->namaBerkas('xlsx'));
    }

    public function pdf(string $jenis): ResponseInterface
    {
        $pdf = new RekapPdf($jenis, $this->status(), (new Rekap())->susun($jenis, $this->status()));

        return $this->response
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $pdf->namaBerkas() . '"')
            ->setBody($pdf->pdf());
    }

    /**
     * Saringan status dari alamat; selain `lunas`/`menunggu` berarti semua.
     */
    private function status(): ?string
    {
        $status = $this->request->getGet('status');

        return is_string($status) && isset(Rekap::STATUS[$status]) ? $status : null;
    }
}
