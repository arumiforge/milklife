<?php

namespace Tests\Support;

use CodeIgniter\Config\Factories;
use CodeIgniter\Config\Services;
use Config\Turnstile;

/**
 * Menyalakan Cloudflare Turnstile di uji dengan kunci uji resmi Cloudflare
 * dan tiruan siteverify (`TiruanSiteverify`), tanpa panggilan jaringan.
 *
 *     $tiruan = $this->nyalakanTurnstile(TiruanSiteverify::RAHASIA_LOLOS, 'masuk_admin');
 *
 * Panggil `matikanTurnstile()` di `tearDown()`.
 */
trait TurnstileUji
{
    protected function nyalakanTurnstile(string $rahasia, string $aksi, string $namaHost = 'example.com'): TiruanSiteverify
    {
        $konfigurasi               = new Turnstile();
        $konfigurasi->kunciSitus   = TiruanSiteverify::SITUS_LOLOS;
        $konfigurasi->kunciRahasia = $rahasia;
        $konfigurasi->namaHost     = 'example.com, localhost';
        Factories::injectMock('config', Turnstile::class, $konfigurasi);

        $tiruan           = new TiruanSiteverify();
        $tiruan->aksi     = $aksi;
        $tiruan->namaHost = $namaHost;
        Services::injectMock('curlrequest', $tiruan);

        return $tiruan;
    }

    protected function matikanTurnstile(): void
    {
        Factories::reset('config');
        Services::resetSingle('curlrequest');
    }
}
