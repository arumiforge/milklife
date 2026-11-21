<?php

namespace App\Libraries\HariLomba;

use CodeIgniter\HTTP\Response;
use Config\App;

/**
 * Tanggapan unduhan ZIP yang dibuat sambil dikirim (K137, `ZipAlir`).
 * Kepala tanggapan terkirim lebih dulu, lalu tiap berkas di dalam ZIP
 * dikirim begitu selesai dibuat oleh `$isi`.
 *
 *     return new UnduhanZipAlir('BIB_Semua.zip', static function (ZipAlir $zip): void {
 *         $zip->tambah('a.jpg', $jpg);
 *     });
 *
 * Di uji, isinya dibaca dengan `ob_start(); $tanggapan->sendBody(); ob_get_clean();`.
 */
final class UnduhanZipAlir extends Response
{
    /**
     * @var callable(ZipAlir): void
     */
    private $isi;

    /**
     * @param callable(ZipAlir): void $isi
     */
    public function __construct(string $namaBerkas, callable $isi)
    {
        parent::__construct(config(App::class));

        $this->isi = $isi;
        $this->setContentType('application/zip', '');
        $this->setHeader('Content-Disposition', 'attachment; filename="' . str_replace(['"', '\\', "\r", "\n"], '', $namaBerkas) . '"');
        $this->setHeader('Content-Transfer-Encoding', 'binary');
        // Nginx diminta tidak menahan isi supaya data langsung mengalir ke pengunduh.
        $this->setHeader('X-Accel-Buffering', 'no');
    }

    /**
     * @return $this
     */
    public function send()
    {
        // Seperti `DownloadResponse`: buang penyangga keluaran supaya data langsung terkirim.
        if (ENVIRONMENT !== 'testing') {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
        }

        return parent::send();
    }

    /**
     * @return $this
     */
    public function sendBody()
    {
        $kirim = ENVIRONMENT === 'testing'
            ? static function (string $bita): void {
                echo $bita;
            }
            : static function (string $bita): void {
                echo $bita;
                flush();
            };

        $zip = new ZipAlir($kirim);

        try {
            ($this->isi)($zip);
            $zip->selesai();
        } catch (\Throwable $e) {
            // Kepala sudah terkirim, jadi halaman galat tidak bisa ditampilkan;
            // ZIP terputus dan pengunduh melihat unduhan gagal.
            log_message('error', 'ZIP BIB terputus: {pesan}', ['pesan' => $e->getMessage()]);

            if (ENVIRONMENT === 'testing') {
                throw $e;
            }
        }

        return $this;
    }
}
