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
 * dibatasi rute. `admin/rekap/gabungan/excel` (K85) menggabungkan semuanya
 * dalam satu berkas, satu sheet per bagian.
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
        $excel = null;

        foreach (self::lembarRekap($jenis, $rekap) as $lembar) {
            $excel = $excel === null ? $lembar : $excel->tambahLembar($lembar);
        }

        return $excel->unduh((new RekapPdf($jenis, $this->status(), $rekap))->namaBerkas('xlsx'));
    }

    /**
     * Rekap gabungan (K85): satu berkas Excel berisi semua data, satu sheet
     * per bagian: Pendaftaran, Atlet, lalu tiap jenis rekap (dan Tim Tanpa
     * Pembayar). Tanpa saringan status; semua pendaftaran ikut.
     */
    public function semua(): DownloadResponse
    {
        $excel = PendaftaranController::lembarExcel()->tambahLembar(AtletController::lembarExcel());
        $data  = new Rekap();

        foreach (array_keys(Rekap::JENIS) as $jenis) {
            foreach (self::lembarRekap($jenis, $data->susun($jenis)) as $lembar) {
                $excel->tambahLembar($lembar);
            }
        }

        return $excel->unduh('Rekap_Gabungan_' . date('Ymd_His'));
    }

    /**
     * Lembar Excel satu jenis rekap: lembar utama (dengan baris jumlah bila
     * ada), lalu Tim Tanpa Pembayar bila rekap itu memilikinya.
     *
     * @param array<string, mixed> $rekap hasil `Rekap::susun()`
     *
     * @return list<EksporExcel>
     */
    private static function lembarRekap(string $jenis, array $rekap): array
    {
        $utama = (new EksporExcel(Rekap::JENIS[$jenis], $rekap['kolom']))->tambahBaris($rekap['baris']);

        if ($rekap['jumlah'] !== null) {
            $utama->tambahBaris([$rekap['jumlah']]);
        }

        $daftar = [$utama];

        if (isset($rekap['tim_tanpa_pembayar'])) {
            $daftar[] = (new EksporExcel('Tim Tanpa Pembayar', $rekap['tim_tanpa_pembayar']['kolom']))->tambahBaris($rekap['tim_tanpa_pembayar']['baris']);
        }

        return $daftar;
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
