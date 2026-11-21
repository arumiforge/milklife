<?php

namespace App\Libraries\HariLomba;

use RuntimeException;

/**
 * Penulis ZIP yang mengalir (K137): tiap berkas langsung dikirim lewat
 * `$tulis` begitu ditambahkan, jadi unduhan mulai sejak berkas pertama dan
 * tidak diputus Cloudflare (sekitar 100 detik tanpa data) walau ZIP semua
 * sekolah butuh beberapa menit. Tanpa berkas sementara dan tanpa memuat ZIP
 * utuh ke memori.
 *
 * Berkas disimpan tanpa dimampatkan (metode *stored*): JPG dan PDF sudah
 * termampatkan. Nama berkas UTF-8. Tanpa ZIP64, jadi ZIP paling besar 4 GB
 * dan 65.535 berkas (sekitar 14.000 BIB); lebih dari itu ditolak.
 *
 *     $zip = new ZipAlir(static function (string $bita): void { echo $bita; flush(); });
 *     $zip->tambah('SD Contoh - 20318001/2001 - SITI.jpg', $jpg);
 *     $zip->selesai();
 */
final class ZipAlir
{
    private const BATAS = 0xFFFFFFFF;

    /**
     * @var callable(string): void
     */
    private $tulis;

    private int $posisi = 0;

    /**
     * Isian direktori pusat, ditulis di akhir.
     */
    private string $direktori = '';

    private int $jumlah = 0;

    private int $waktuDos;

    private int $tanggalDos;

    /**
     * @param callable(string): void $tulis
     */
    public function __construct(callable $tulis, ?int $waktu = null)
    {
        $this->tulis = $tulis;
        $waktu       = getdate($waktu ?? time());

        $this->waktuDos   = ($waktu['hours'] << 11) | ($waktu['minutes'] << 5) | intdiv($waktu['seconds'], 2);
        $this->tanggalDos = (max(0, $waktu['year'] - 1980) << 9) | ($waktu['mon'] << 5) | $waktu['mday'];
    }

    /**
     * @throws RuntimeException Bila ZIP melebihi 4 GB atau 65.535 berkas.
     */
    public function tambah(string $nama, string $isi): void
    {
        $ukuran = strlen($isi);

        if ($this->jumlah >= 0xFFFF || $this->posisi + $ukuran + strlen($nama) + 30 > self::BATAS) {
            throw new RuntimeException('Berkas ZIP terlalu besar (lebih dari 4 GB atau 65.535 berkas). Unduh per sekolah atau per kelompok umur.');
        }

        $crc = (int) hexdec(hash('crc32b', $isi));
        // Versi 2.0, bit 11 = nama berkas UTF-8, metode 0 = stored.
        $umum = pack('vvvvvVVV', 20, 0x0800, 0, $this->waktuDos, $this->tanggalDos, $crc, $ukuran, $ukuran);

        ($this->tulis)("PK\x03\x04" . $umum . pack('vv', strlen($nama), 0) . $nama);
        ($this->tulis)($isi);

        $this->direktori .= "PK\x01\x02" . pack('v', 20) . $umum . pack('vvvvvVV', strlen($nama), 0, 0, 0, 0, 0, $this->posisi) . $nama;
        $this->posisi    += 30 + strlen($nama) + $ukuran;
        $this->jumlah++;
    }

    /**
     * Tulis direktori pusat dan penutup ZIP.
     */
    public function selesai(): void
    {
        ($this->tulis)($this->direktori . "PK\x05\x06" . pack('vvvvVVv', 0, 0, $this->jumlah, $this->jumlah, strlen($this->direktori), $this->posisi, 0));
        $this->direktori = '';
    }
}
