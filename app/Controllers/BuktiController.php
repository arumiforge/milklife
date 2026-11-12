<?php

namespace App\Controllers;

use App\Libraries\BuktiPendaftaran;
use App\Libraries\DataPendaftaran;
use App\Libraries\IsiHalamanDepan;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Bukti pendaftaran PDF (`/bukti/{id-pendaftaran}`, rancangan 8).
 *
 * Tidak dibatasi kelengkapan pengaturan: pendaftaran yang sudah tersimpan
 * tetap bisa dicetak. Setiap permintaan dihitung pembatas laju per alamat IP
 * (rancangan 13). PDF dikirim langsung, tidak disimpan di disk.
 */
class BuktiController extends BaseController
{
    /**
     * Batas permintaan bukti per alamat IP dalam satu jendela waktu.
     */
    public const BATAS_PERMINTAAN = 10;

    /**
     * Lama jendela pembatasan, dalam detik.
     */
    public const JENDELA_DETIK = 60;

    public function index(string $id): ResponseInterface
    {
        $pembatas = service('throttler');
        if (! $pembatas->check('bukti-' . hash('sha256', $this->request->getIPAddress()), self::BATAS_PERMINTAAN, self::JENDELA_DETIK)) {
            $detik = max(1, $pembatas->getTokenTime());

            return $this->response
                ->setStatusCode(429)
                ->setHeader('Retry-After', (string) $detik)
                ->setBody(view('bukti/terlalu_sering', [
                    'namaAcara' => (new IsiHalamanDepan())->acara()['nama'],
                    'detik'     => $detik,
                ]));
        }

        $pendaftaran = preg_match('/^MAC[0-9A-Za-z]{1,17}$/', $id) === 1 ? (new DataPendaftaran())->cariId($id) : null;

        if ($pendaftaran === null) {
            throw PageNotFoundException::forPageNotFound('Bukti pendaftaran dengan ID ini tidak ditemukan. Periksa lagi ID pendaftaran Anda, atau cari lewat halaman Cek Pendaftaran.');
        }

        $bukti = new BuktiPendaftaran($pendaftaran);

        return $this->response
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $bukti->namaBerkas() . '"')
            ->setHeader('Cache-Control', 'no-store')
            ->setBody($bukti->pdf());
    }
}
