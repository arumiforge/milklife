<?php

namespace App\Controllers;

use App\Libraries\IsiHalamanDepan;
use App\Libraries\PesertaPublik;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Halaman Peserta publik (`/peserta`, F9-12): siswa dan official memastikan
 * namanya terdaftar dan melihat nomor atlet serta nomor lombanya.
 *
 * - Hanya pendaftaran Lunas; data dari `App\Libraries\PesertaPublik`, yang
 *   tidak pernah membaca NISN, tanggal lahir, foto, atau data official.
 * - `index` merender halaman pertama (dan halaman mana pun lewat `?halaman=`)
 *   di server, jadi tetap bekerja tanpa JavaScript. `data` menjawab JSON
 *   untuk pencarian langsung di `aset/js/peserta.js`.
 * - Keduanya dibatasi `throttler` per alamat IP, seperti Cek Pendaftaran.
 * - Selama sakelar `HalamanDepan.tampil_peserta` mati (bawaan), keduanya 404.
 * - Halaman memuat nama anak: mesin pencari diminta tidak mengindeksnya.
 */
class PesertaController extends BaseController
{
    /**
     * Batas permintaan (halaman dan JSON) per alamat IP per menit. Pencarian
     * langsung mengirim satu permintaan tiap jeda ketik, jadi lebih longgar
     * dari Cek Pendaftaran.
     */
    public const BATAS_PERMINTAAN = 60;

    public const PESAN_BATAS = 'Terlalu banyak pencarian dalam satu menit. Tunggu sebentar, lalu coba lagi.';

    /**
     * Sakelar tampil halaman Peserta dari Pengaturan > Halaman Depan.
     */
    public static function tampil(): bool
    {
        return (bool) setting('HalamanDepan.tampil_peserta');
    }

    public function index(): ResponseInterface
    {
        $this->pastikanTampil();

        $peserta = new PesertaPublik();
        $saring  = PesertaPublik::saringan($this->request->getGet());
        $lolos   = $this->lolosBatas();

        $this->response->setHeader('X-Robots-Tag', 'noindex');

        if (! $lolos) {
            $this->response->setStatusCode(429)->setHeader('Retry-After', '60');
        }

        return $this->response->setBody(view('peserta/index', [
            'namaAcara' => (new IsiHalamanDepan())->acara()['nama'],
            'saring'    => $saring,
            'pilihan'   => $peserta->pilihan(),
            'hasil'     => $lolos ? $peserta->cari($saring) : null,
        ]));
    }

    public function data(): ResponseInterface
    {
        $this->pastikanTampil();
        $this->response->setHeader('X-Robots-Tag', 'noindex');

        if (! $this->lolosBatas()) {
            return $this->response->setStatusCode(429)->setHeader('Retry-After', '60')
                ->setJSON(['pesan' => self::PESAN_BATAS]);
        }

        return $this->response->setJSON((new PesertaPublik())->cari(PesertaPublik::saringan($this->request->getGet())));
    }

    private function pastikanTampil(): void
    {
        if (! self::tampil()) {
            throw PageNotFoundException::forPageNotFound();
        }
    }

    private function lolosBatas(): bool
    {
        return service('throttler')->check('peserta-' . hash('sha256', $this->request->getIPAddress()), self::BATAS_PERMINTAAN, MINUTE);
    }
}
