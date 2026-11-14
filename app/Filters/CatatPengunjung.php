<?php

namespace App\Filters;

use App\Libraries\Pengunjung;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

/**
 * Mencatat kunjungan halaman publik (K102) ke tabel `kunjungan` lewat
 * `App\Libraries\Pengunjung` (global *after*, lihat `Config\Filters`).
 *
 * Hanya `GET` halaman HTML yang berhasil dari peramban sungguhan; halaman
 * admin, JSON, PDF, dan bot dilewati (`Pengunjung::layakDicatat()`).
 * Kegagalan mencatat tidak pernah menggagalkan halaman, hanya dicatat di log.
 */
class CatatPengunjung implements FilterInterface
{
    /**
     * @param list<string>|null $arguments
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        return null;
    }

    /**
     * @param list<string>|null $arguments
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): ?ResponseInterface
    {
        if (! $request instanceof IncomingRequest) {
            return null;
        }

        $peramban = (string) $request->getUserAgent()->getAgentString();
        $jalur    = '/' . trim($request->getUri()->getRoutePath(), '/');
        $prefetch = str_contains(strtolower($request->getHeaderLine('Sec-Purpose') . $request->getHeaderLine('Purpose') . $request->getHeaderLine('X-Moz')), 'prefetch');

        if (! Pengunjung::layakDicatat($request->getMethod(), $jalur, $response->getStatusCode(), $response->getHeaderLine('Content-Type'), $peramban, $prefetch)) {
            return null;
        }

        try {
            (new Pengunjung())->catat($request->getIPAddress(), $peramban, $jalur);
        } catch (Throwable $e) {
            log_message('error', 'Kunjungan tidak tercatat: {pesan}', ['pesan' => $e->getMessage()]);
        }

        return null;
    }
}
