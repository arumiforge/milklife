<?php

namespace App\Controllers;

use App\Libraries\GambarOpsional;
use App\Libraries\IsiHalamanDepan;
use App\Libraries\Hasil\PapanVideotron;
use App\Libraries\Hasil\TampilanVideotron;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

/**
 * Videotron (F9-13, K141, F11-09, K173), tanpa masuk.
 *
 * - `/layar`: daftar tautan tampilan untuk operator videotron vendor, per
 *   hari dan sesi, klasemen, kelompok umur, dan nomor lomba
 *   (`App\Libraries\Hasil\TampilanVideotron::daftar()`).
 * - `/layar/<jenis>/…` (`seri`, `sesi`, `klasemen`, `kelompok-umur`,
 *   `nomor`): satu tampilan tetap layar penuh yang tidak berganti sendiri.
 *   Isinya dari JSON `/layar/data/<jenis>/…`, diambil ulang diam-diam setiap
 *   `SEGAR_DETIK` supaya hasil baru masuk tanpa memuat ulang.
 * - `/layar/putar`: papan putar lama (hasil terbaru, juara umum, sedang
 *   berlangsung, jadwal berikutnya bergantian) dari JSON `/layar/data`.
 *
 * Semua tampilan digambar `public/aset/js/layar.js`.
 *
 * - Hanya data terbit (`PapanVideotron`, `TampilanVideotron`).
 * - Selama sakelar `Videotron.tampil` mati (bawaan), semua alamat 404.
 * - JSON ditembolok di server `TEMBOLOK_DETIK` dan boleh ditembolok
 *   peramban/proksi sebentar; `ETag` membuat pengambilan ulang tanpa
 *   perubahan cukup dijawab 304. Batas `BATAS_PERMINTAAN` per alamat IP.
 * - Latar: gambar unggahan admin di Pengaturan > Videotron (K173), tanpa
 *   adegan Three.js; bila kosong, latar bawaan pilihan admin (K192): potongan
 *   G9-06 (`aset/gambar/layar/`) yang berkasnya ada tanpa gerakan (bawaan),
 *   gradien biru polos, atau potongan G9-06 dengan adegan Three.js.
 * - Logo acara di kanan kepala layar (`IsiHalamanDepan::logoBesar()`, K192).
 */
class LayarController extends BaseController
{
    public const SEGAR_DETIK = 30;

    public const TEMBOLOK_DETIK = 10;

    public const KUNCI_TEMBOLOK = 'layar-data';

    public const KUNCI_TEMBOLOK_DAFTAR = 'layar-daftar';

    /**
     * Awalan kunci tembolok tampilan tetap; diikuti md5 jenis dan argumennya.
     */
    public const AWALAN_TEMBOLOK_TAMPILAN = 'layar-tampilan-';

    /**
     * Permintaan data paling banyak per alamat IP per menit (satu layar
     * meminta dua kali per menit), dihitung bersama untuk semua tampilan.
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

    /**
     * Halaman daftar tampilan untuk operator videotron.
     */
    public function index(): ResponseInterface
    {
        $this->pastikanTampil();

        $daftar = cache(self::KUNCI_TEMBOLOK_DAFTAR);

        if (! is_array($daftar) || ! isset($daftar['hari'])) {
            $daftar = (new TampilanVideotron())->daftar();
            cache()->save(self::KUNCI_TEMBOLOK_DAFTAR, $daftar, self::TEMBOLOK_DETIK);
        }

        return $this->response
            ->setHeader('X-Robots-Tag', 'noindex')
            ->setBody(view('layar/daftar', [
                'daftar'    => $daftar,
                'namaAcara' => (new IsiHalamanDepan())->acara()['nama'],
                'sebutan'   => PapanVideotron::sebutanSeri(),
            ]));
    }

    /**
     * Papan putar lama (F9-13).
     */
    public function putar(): ResponseInterface
    {
        $this->pastikanTampil();

        return $this->halamanLayar($this->dataTembolok(), site_url('layar/data'));
    }

    public function data(): ResponseInterface
    {
        $this->pastikanTampil();

        return $this->jawabData(fn (): array => $this->dataTembolok());
    }

    /**
     * Satu tampilan tetap, misalnya `layar/seri/12/2` atau `layar/klasemen/A`.
     */
    public function tampil(string $jenis, string ...$argumen): ResponseInterface
    {
        $this->pastikanTampil();

        return $this->halamanLayar($this->dataTampilanTembolok($jenis, $argumen), site_url('layar/data/' . $jenis . '/' . implode('/', array_map('rawurlencode', $argumen))));
    }

    public function dataTampilan(string $jenis, string ...$argumen): ResponseInterface
    {
        $this->pastikanTampil();

        return $this->jawabData(fn (): array => $this->dataTampilanTembolok($jenis, $argumen));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function halamanLayar(array $data, string $sumber): ResponseInterface
    {
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

        // Latar unggahan admin (K173) menggantikan potongan G9-06 dan adegan Three.js.
        // Tanpa unggahan, latar bawaan pilihan admin (K192): sapuan kuas diam, biru polos, atau animasi lama.
        $unggahan    = PapanVideotron::latar();
        $latarBawaan = PapanVideotron::latarBawaan();

        if ($unggahan !== null) {
            $latar = ['dasar' => base_url($unggahan)];
        } elseif ($latarBawaan === 'polos') {
            $latar = [];
        }

        // Logo acara di kepala layar (K192).
        try {
            $logo = IsiHalamanDepan::logoBesar((new IsiHalamanDepan())->tampilan());
        } catch (Throwable) {
            $logo = IsiHalamanDepan::logoBesar([]);
        }

        return $this->response
            ->setHeader('X-Robots-Tag', 'noindex')
            ->setBody(view('layar/index', [
                'data'        => $data,
                'sumber'      => $sumber,
                'latar'       => $latar,
                'gambar'      => $gambar,
                'tanpaAdegan' => $unggahan !== null || $latarBawaan !== 'animasi',
                'logo'        => $logo['alamat'] ?? null,
                'segar'       => self::SEGAR_DETIK,
            ]));
    }

    /**
     * JSON data layar dengan batas permintaan, tembolok, dan `ETag`.
     *
     * @param callable(): array<string, mixed> $ambil
     */
    private function jawabData(callable $ambil): ResponseInterface
    {
        $this->response->setHeader('X-Robots-Tag', 'noindex');

        if (! service('throttler')->check('layar-' . hash('sha256', $this->request->getIPAddress()), self::BATAS_PERMINTAAN, MINUTE)) {
            return $this->response->setStatusCode(429)->setHeader('Retry-After', '60')
                ->setJSON(['pesan' => 'Terlalu banyak permintaan. Layar mencoba lagi sebentar lagi.']);
        }

        $data = $ambil();
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
     * Isi tampilan tetap dari tembolok; 404 bila alamatnya tidak sah.
     *
     * @param list<string> $argumen
     *
     * @return array<string, mixed>
     */
    private function dataTampilanTembolok(string $jenis, array $argumen): array
    {
        $kunci = self::AWALAN_TEMBOLOK_TAMPILAN . md5($jenis . '/' . implode('/', $argumen));
        $data  = cache($kunci);

        if (! is_array($data) || ! isset($data['versi'])) {
            $data = (new TampilanVideotron())->tampilan($jenis, $argumen);

            if ($data === null) {
                throw PageNotFoundException::forPageNotFound();
            }

            cache()->save($kunci, $data, self::TEMBOLOK_DETIK);
        }

        return $data;
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
