<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Security\Exceptions\SecurityException;
use CodeIgniter\Security\Security;

/**
 * Pemeriksaan CSRF dengan pesan galat yang terlihat (F7-24, K118, analisis B1).
 *
 * Sama dengan filter `csrf` bawaan CodeIgniter, tetapi kiriman yang ditolak di
 * lingkungan yang mengalihkan (`Config\Security::$redirect`, aktif di
 * produksi) tidak lagi kembali diam-diam dengan pesan kilat `error` yang tidak
 * ditampilkan siapa pun:
 *
 * - Kiriman biasa (formulir) dialihkan kembali ke halaman asal dengan pesan
 *   kilat `pesan_galat` (lihat `admin/_pesan` dan `tata_letak/_pesan_publik`)
 *   dan isian lama (`withInput()`), kecuali kata sandi dan token.
 * - Kiriman JSON atau AJAX (wizard pendaftaran) mendapat jawaban JSON 403
 *   `{status: 'kedaluwarsa', pesan, csrf}`. `csrf` berisi token yang berlaku,
 *   jadi wizard bisa langsung mencoba lagi tanpa memuat ulang halaman.
 *
 * Halaman asal dipilih seperti `BatasUnggahFilter::alamatKembali()`: hanya
 * alamat di situs sendiri.
 *
 * Di luar produksi kiriman yang ditolak tetap menampilkan halaman galat 403
 * bawaan supaya mudah dikenali saat pengembangan.
 */
class CsrfFilter implements FilterInterface
{
    public const PESAN = 'Halaman ini sudah terlalu lama terbuka atau dibuka di tab lain. Muat ulang halaman, lalu ulangi.';

    /**
     * Isian yang tidak ikut dikembalikan ke formulir.
     */
    private const ISIAN_RAHASIA = '/sandi|password|csrf/i';

    /**
     * @param list<string>|null $arguments
     *
     * @return ResponseInterface|null
     *
     * @throws SecurityException
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! $request instanceof IncomingRequest) {
            return null;
        }

        /** @var Security $security */
        $security = service('security');

        try {
            $security->verify($request);
        } catch (SecurityException $e) {
            if (! $security->shouldRedirect()) {
                throw $e;
            }

            if (self::mintaJson($request)) {
                return service('response')
                    ->setStatusCode(403)
                    ->setJSON(['status' => 'kedaluwarsa', 'pesan' => self::PESAN, 'csrf' => csrf_hash()]);
            }

            $alihkan = redirect()->to(BatasUnggahFilter::alamatKembali($request))->withInput();
            self::buangIsianRahasia();

            return $alihkan->with('pesan_galat', self::PESAN);
        }

        return null;
    }

    /**
     * @param list<string>|null $arguments
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): ?ResponseInterface
    {
        return null;
    }

    /**
     * Apakah peminta mengharapkan jawaban JSON (wizard pendaftaran memakai
     * `fetch` dengan `X-Requested-With` dan `Accept: application/json`).
     */
    public static function mintaJson(IncomingRequest $request): bool
    {
        if ($request->isAJAX()) {
            return true;
        }

        $jenis = strtolower($request->getHeaderLine('Content-Type') . ' ' . $request->getHeaderLine('Accept'));

        return str_contains($jenis, 'application/json');
    }

    /**
     * Hapus kata sandi dan token dari isian lama yang disimpan `withInput()`.
     */
    private static function buangIsianRahasia(): void
    {
        $sesi = service('session');
        $lama = $sesi->getFlashdata('_ci_old_input');

        if (! is_array($lama)) {
            return;
        }

        foreach (['get', 'post'] as $bagian) {
            if (! isset($lama[$bagian]) || ! is_array($lama[$bagian])) {
                continue;
            }

            foreach (array_keys($lama[$bagian]) as $kunci) {
                if (preg_match(self::ISIAN_RAHASIA, (string) $kunci) === 1) {
                    unset($lama[$bagian][$kunci]);
                }
            }
        }

        $sesi->setFlashdata('_ci_old_input', $lama);
    }
}
