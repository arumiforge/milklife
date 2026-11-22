<?php

namespace App\Controllers;

use App\Libraries\GambarOpsional;
use App\Libraries\Hasil\PapanVideotron;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Papan videotron (`/layar`, F9-13, K141): halaman layar penuh tanpa masuk
 * yang memutar hasil terbaru, juara umum, sedang berlangsung, dan jadwal
 * berikutnya. Isi digambar `public/aset/js/layar.js` dari JSON `/layar/data`,
 * yang diambil ulang setiap `SEGAR_DETIK`.
 *
 * - Hanya data terbit (`App\Libraries\Hasil\PapanVideotron`).
 * - Selama sakelar `Videotron.tampil` mati (bawaan), keduanya 404.
 * - `/layar/data` ditembolok di server `TEMBOLOK_DETIK` dan boleh ditembolok
 *   peramban/proksi sebentar; `ETag` membuat pengambilan ulang tanpa
 *   perubahan cukup dijawab 304.
 * - Latar gambar G9-06 (`aset/gambar/layar/`) hanya dipasang bila berkasnya ada.
 */
class LayarController extends BaseController
{
    public const SEGAR_DETIK = 30;

    public const TEMBOLOK_DETIK = 10;

    public const KUNCI_TEMBOLOK = 'layar-data';

    /**
     * Permintaan data paling banyak per alamat IP per menit (satu layar
     * meminta dua kali per menit).
     */
    public const BATAS_PERMINTAAN = 120;

    /**
     * Potongan latar G9-06: kunci atribut => berkas di `public/aset/gambar/layar/`.
     *
     * @var array<string, string>
     */
    public const GAMBAR_LATAR = [
        'dasar'      => 'latar-dasar.webp',
        'pita'       => 'pita-tepi.webp',
        'kiri-atas'  => 'hiasan-kiri-atas.webp',
        'kanan-bawah' => 'hiasan-kanan-bawah.webp',
    ];

    /**
     * Gambar juara umum G9-06: kunci atribut `data-gambar-*` => berkas di `public/aset/gambar/layar/`.
     *
     * @var array<string, string>
     */
    public const GAMBAR_JUARA = [
        'podium'          => 'podium.webp',
        'medali-emas'     => 'medali-emas.webp',
        'medali-perak'    => 'medali-perak.webp',
        'medali-perunggu' => 'medali-perunggu.webp',
    ];

    public function index(): ResponseInterface
    {
        $this->pastikanTampil();

        $gambar = [];

        foreach (self::GAMBAR_JUARA as $kunci => $berkas) {
            $alamat = GambarOpsional::alamat('layar/' . $berkas);

            if ($alamat !== null) {
                $gambar[$kunci] = $alamat;
            }
        }

        $latar = [];

        foreach (self::GAMBAR_LATAR as $kunci => $berkas) {
            $alamat = GambarOpsional::alamat('layar/' . $berkas);

            if ($alamat !== null) {
                $latar[$kunci] = $alamat;
            }
        }

        return $this->response
            ->setHeader('X-Robots-Tag', 'noindex')
            ->setBody(view('layar/index', [
                'data'   => $this->dataTembolok(),
                'latar'  => $latar,
                'gambar' => $gambar,
                'segar'  => self::SEGAR_DETIK,
            ]));
    }

    public function data(): ResponseInterface
    {
        $this->pastikanTampil();
        $this->response->setHeader('X-Robots-Tag', 'noindex');

        if (! service('throttler')->check('layar-' . hash('sha256', $this->request->getIPAddress()), self::BATAS_PERMINTAAN, MINUTE)) {
            return $this->response->setStatusCode(429)->setHeader('Retry-After', '60')
                ->setJSON(['pesan' => 'Terlalu banyak permintaan. Layar mencoba lagi sebentar lagi.']);
        }

        $data = $this->dataTembolok();
        $etag = '"' . $data['versi'] . '"';

        $this->response
            ->removeHeader('Cache-Control')
            ->setHeader('Cache-Control', 'public, max-age=' . self::TEMBOLOK_DETIK)
            ->setHeader('ETag', $etag);

        if (in_array($etag, array_map('trim', explode(',', $this->request->getHeaderLine('If-None-Match'))), true)) {
            return $this->response->setStatusCode(304)->setBody('');
        }

        return $this->response->setJSON($data);
    }

    /**
     * @return array<string, mixed>
     */
    private function dataTembolok(): array
    {
        $data = cache(self::KUNCI_TEMBOLOK);

        if (! is_array($data) || ! isset($data['versi'])) {
            $data = (new PapanVideotron())->data();
            cache()->save(self::KUNCI_TEMBOLOK, $data, self::TEMBOLOK_DETIK);
        }

        return $data;
    }

    private function pastikanTampil(): void
    {
        if (! PapanVideotron::tampil()) {
            throw PageNotFoundException::forPageNotFound();
        }
    }
}
