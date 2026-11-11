<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Penjaga halaman admin.
 *
 * - Belum masuk: dialihkan ke `/admin/masuk`. Untuk permintaan `GET`, alamat
 *   yang tadi dibuka disimpan di sesi (`admin_tujuan`) supaya setelah masuk
 *   admin kembali ke sana.
 * - Sudah masuk dan membuka `/admin/masuk`: dialihkan ke `/admin/dasbor`.
 * - Semua jawaban halaman admin dikirim dengan `Cache-Control: no-store`,
 *   supaya tombol kembali di peramban tidak menampilkan halaman admin
 *   setelah keluar.
 *
 * Penjaga hanya memeriksa sesi (ada `admin_id`), tidak membaca basis data.
 */
class AdminFilter implements FilterInterface
{
    public const KUNCI_TUJUAN = 'admin_tujuan';

    private const HALAMAN_MASUK = 'admin/masuk';

    /**
     * @param list<string>|null $arguments
     */
    public function before(RequestInterface $request, $arguments = null): ?RedirectResponse
    {
        $jalur        = $request instanceof IncomingRequest ? trim($request->getPath(), '/') : '';
        $sudahMasuk   = self::sudahMasuk();
        $halamanMasuk = $jalur === self::HALAMAN_MASUK;

        if ($halamanMasuk) {
            return $sudahMasuk ? redirect()->to('admin/dasbor') : null;
        }

        if ($sudahMasuk) {
            return null;
        }

        if ($request instanceof IncomingRequest && $request->getMethod() === 'GET') {
            $kueri  = $request->getUri()->getQuery();
            $tujuan = self::tujuanAman($jalur . ($kueri === '' ? '' : '?' . $kueri));

            if ($tujuan !== null) {
                session()->set(self::KUNCI_TUJUAN, $tujuan);
            }
        }

        return redirect()->to(self::HALAMAN_MASUK)
            ->with('pesan_masuk', 'Silakan masuk dulu untuk membuka halaman admin.');
    }

    /**
     * @param list<string>|null $arguments
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): ResponseInterface
    {
        $response->removeHeader('Cache-Control');
        $response->setHeader('Cache-Control', ['no-store', 'no-cache', 'must-revalidate', 'max-age=0']);
        $response->setHeader('Pragma', 'no-cache');

        return $response;
    }

    /**
     * Apakah sesi ini milik admin yang sudah masuk.
     */
    public static function sudahMasuk(): bool
    {
        $id = session('admin_id');

        return is_int($id) && $id > 0;
    }

    /**
     * Saring alamat tujuan setelah masuk. Hanya jalur internal di bawah
     * `admin` (tanpa skema, tanpa nama host, tanpa `//`, `\`, atau `..`) yang
     * diterima; selain itu `null`.
     */
    public static function tujuanAman(mixed $tujuan): ?string
    {
        if (! is_string($tujuan) || $tujuan === '' || strlen($tujuan) > 500) {
            return null;
        }

        $pola = '#\Aadmin(/[A-Za-z0-9._~\-/]*)?(\?[A-Za-z0-9._~\-=&%+\[\]]*)?\z#';

        if (preg_match($pola, $tujuan) !== 1 || str_contains($tujuan, '//') || str_contains($tujuan, '..')) {
            return null;
        }

        $jalur = rtrim(explode('?', $tujuan, 2)[0], '/');

        if (in_array($jalur, [self::HALAMAN_MASUK, 'admin/keluar'], true)) {
            return null;
        }

        return $tujuan;
    }
}
