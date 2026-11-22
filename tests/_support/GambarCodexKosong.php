<?php

namespace Tests\Support;

use App\Libraries\GambarOpsional;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Uji tidak bergantung pada gambar Codex yang sudah ada di `public/aset/gambar/`.
 *
 * Gambar G9 hanya dirender bila berkasnya ada (`GambarOpsional`, F9-19). Tanpa
 * sifat ini, hasil uji halaman berubah begitu PR gambar Codex digabung
 * (misalnya `<img>` ilustrasi tiba-tiba muncul di bagian pembuka). Sebelum
 * setiap uji, `GambarOpsional::$folder` diarahkan ke folder sementara yang
 * kosong di `writable/`, sehingga halaman dirender seperti sebelum gambar
 * diserahkan. Uji yang memeriksa gambar menaruh berkas tiruannya di folder itu
 * dengan `taruhGambarUji()`, tidak pernah di `public/`.
 *
 *     $this->taruhGambarUji('nomor/frog-jump.webp');   // jalur relatif `public/aset/gambar/`
 *
 * Sudah dipakai `UjiBasisData` dan `UjiHalaman`; dipanggil otomatis oleh
 * `CIUnitTestCase` lewat `setUpGambarCodexKosong()` dan `tearDownGambarCodexKosong()`.
 */
trait GambarCodexKosong
{
    private ?string $folderGambarUji = null;

    protected function setUpGambarCodexKosong(): void
    {
        $this->folderGambarUji = WRITEPATH . 'uji-gambar-' . bin2hex(random_bytes(4)) . '/';
        mkdir($this->folderGambarUji, 0777, true);

        GambarOpsional::$folder = $this->folderGambarUji;
    }

    protected function tearDownGambarCodexKosong(): void
    {
        GambarOpsional::$folder = null;

        if ($this->folderGambarUji !== null && is_dir($this->folderGambarUji)) {
            $isi = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->folderGambarUji, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST,
            );

            foreach ($isi as $berkas) {
                $berkas->isDir() ? rmdir($berkas->getPathname()) : unlink($berkas->getPathname());
            }

            rmdir($this->folderGambarUji);
        }

        $this->folderGambarUji = null;
    }

    /**
     * Taruh berkas gambar tiruan di folder gambar uji, misalnya `nomor/frog-jump.webp`.
     */
    protected function taruhGambarUji(string $jalur, string $isi = 'uji'): void
    {
        $berkas = (string) GambarOpsional::$folder . ltrim($jalur, '/');

        if (! is_dir(dirname($berkas))) {
            mkdir(dirname($berkas), 0777, true);
        }

        file_put_contents($berkas, $isi);
    }
}
