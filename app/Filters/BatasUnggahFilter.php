<?php

namespace App\Filters;

use App\Libraries\Unggahan;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Penjaga kiriman yang melebihi batas `post_max_size` PHP.
 *
 * Bila isi kiriman `POST` lebih besar dari `post_max_size`, PHP membuang
 * seluruh isian dan berkasnya, termasuk token CSRF. Tanpa penjaga ini,
 * pemeriksaan CSRF menolak kiriman itu dengan halaman galat umum. Penjaga ini
 * dijalankan sebelum `csrf` (lihat `Config\Filters::$globals`) dan mengalihkan
 * pengunjung kembali ke halaman asal dengan pesan kilat `pesan_galat` yang
 * menyebut batasnya dan cara memperbaikinya.
 *
 * Kiriman dianggap kebesaran bila `CONTENT_LENGTH` di atas `post_max_size`
 * dan isian serta berkasnya kosong. Kiriman lain tidak disentuh, jadi CSRF
 * tetap memeriksa semuanya seperti biasa.
 *
 * Halaman asal dipilih dengan urutan:
 * 1. alamat halaman terakhir yang dibuka di situs ini (sesi `_ci_previous_url`,
 *    sama seperti `redirect()->back()`);
 * 2. kepala `Referer`;
 * 3. cadangan: dasbor admin untuk kiriman ke `admin/…`, halaman depan untuk
 *    yang lain.
 * Alamat 1 dan 2 hanya dipakai bila masih di situs sendiri (diawali
 * `base_url()`), supaya penjaga ini tidak bisa dipakai mengalihkan ke situs lain.
 *
 * Pesan kilat ditampilkan tata letak admin (`admin/_pesan`). Tata letak publik
 * belum menampilkan pesan kilat (Tahap 4).
 */
class BatasUnggahFilter implements FilterInterface
{
    /**
     * @param list<string>|null $arguments
     */
    public function before(RequestInterface $request, $arguments = null): ?RedirectResponse
    {
        if (! $request instanceof IncomingRequest || $request->getMethod() !== 'POST') {
            return null;
        }

        $batas = self::bitaDariIni((string) ini_get('post_max_size'));

        if (! self::kebesaran($request, $batas)) {
            return null;
        }

        return redirect()->to(self::alamatKembali($request))->with('pesan_galat', self::pesan($batas));
    }

    /**
     * @param list<string>|null $arguments
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): ?ResponseInterface
    {
        return null;
    }

    /**
     * Apakah kiriman ini dibuang PHP karena melebihi `$batas` bita.
     * Batas `0` berarti tanpa batas.
     */
    public static function kebesaran(IncomingRequest $request, int $batas): bool
    {
        if ($batas <= 0) {
            return false;
        }

        $panjang = $request->getServer('CONTENT_LENGTH');

        if (! is_string($panjang) && ! is_int($panjang)) {
            return false;
        }

        $panjang = trim((string) $panjang);

        if (preg_match('/\A\d{1,18}\z/', $panjang) !== 1 || (int) $panjang <= $batas) {
            return false;
        }

        return $request->getPost() === [] && $request->getFiles() === [];
    }

    /**
     * Nilai ukuran dari `php.ini` (`12M`, `512K`, `1G`, `8388608`) dalam bita.
     */
    public static function bitaDariIni(string $nilai): int
    {
        $nilai = strtoupper(trim($nilai));

        if (preg_match('/\A(\d+)\s*([KMG]?)B?\z/', $nilai, $cocok) !== 1) {
            return 0;
        }

        $pengali = match ($cocok[2]) {
            'K'     => 1024,
            'M'     => 1024 ** 2,
            'G'     => 1024 ** 3,
            default => 1,
        };

        return (int) $cocok[1] * $pengali;
    }

    /**
     * Pesan untuk pengunjung, menyebut batas dan cara memperbaikinya.
     */
    public static function pesan(int $batas): string
    {
        return 'Kiriman tadi tidak tersimpan karena ukurannya lebih dari batas ' . Unggahan::ukuranTeks($batas)
            . ' untuk sekali kirim. Perkecil dulu ukuran berkasnya (misalnya kompres gambar atau PDF),'
            . ' atau unggah berkas satu per satu, lalu kirim lagi.';
    }

    /**
     * Alamat halaman asal yang aman (masih di situs ini).
     */
    public static function alamatKembali(IncomingRequest $request): string
    {
        $calon = [
            session()->get('_ci_previous_url'),
            $request->getHeaderLine('Referer'),
        ];

        foreach ($calon as $alamat) {
            if (self::alamatSendiri($alamat)) {
                return $alamat;
            }
        }

        $jalur = trim($request->getPath(), '/');

        return $jalur === 'admin' || str_starts_with($jalur, 'admin/')
            ? site_url('admin/dasbor')
            : site_url('/');
    }

    /**
     * Apakah `$alamat` mengarah ke situs ini (diawali `base_url()`), tanpa
     * karakter kendali, garis miring terbalik, atau `..`.
     */
    public static function alamatSendiri(mixed $alamat): bool
    {
        if (! is_string($alamat) || $alamat === '' || strlen($alamat) > 2000) {
            return false;
        }

        if (preg_match('/[\x00-\x20\x7F\\\\]/', $alamat) === 1 || str_contains($alamat, '..')) {
            return false;
        }

        $dasar = rtrim(base_url(), '/') . '/';

        return str_starts_with($alamat, $dasar) || $alamat === rtrim($dasar, '/');
    }
}
