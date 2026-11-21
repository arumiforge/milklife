<?php

namespace App\Libraries\HariLomba;

use App\Libraries\Unggahan;
use CodeIgniter\HTTP\Files\UploadedFile;
use RuntimeException;

/**
 * Tampilan nomor atlet (BIB) cetak (K137): gambar latar penuh, kepala, dan
 * warna tulisan. Dipakai bersama oleh PDF (`CetakBib`) dan gambar JPG
 * (`GambarBib`), jadi keduanya selalu sama.
 *
 * - Satu latar untuk semua BIB. Latar unggahan dipotong tengah supaya pas
 *   ukuran BIB (setengah A4 mendatar, 210 × 148,5 mm) lalu disimpan sebagai
 *   JPG 2480 × 1754 piksel (300 dpi) di `unggahan/bib/`.
 * - Tanpa latar: kepala selalu dicetak dan tulisan gelap, seperti BIB F8-03.
 * - Dengan latar: admin memilih kepala dicetak atau tidak (`Bib.tampilkan_kepala`)
 *   dan warna tulisan gelap atau terang (`Bib.warna_teks`).
 *
 *     $tampilan = TampilanBib::dariPengaturan();
 *     $tampilan->latarJpg();        // isi JPG latar atau null
 *     $tampilan->kepala;            // bool
 *     $tampilan->warna['nomor'];    // '#000000'
 */
final class TampilanBib
{
    /**
     * Ukuran satu BIB dalam piksel pada 300 dpi (210 × 148,5 mm).
     */
    public const LEBAR = 2480;

    public const TINGGI = 1754;

    /**
     * Unggahan latar paling besar 5 MB (batas unggah server 10 MB).
     */
    public const BATAS_LATAR = 5 * 1024 * 1024;

    private const FOLDER = 'bib';

    /**
     * Warna tulisan per bagian BIB.
     */
    private const WARNA = [
        'gelap' => [
            'acara'   => '#021F7B',
            'seri'    => '#5B6475',
            'garis'   => '#021F7B',
            'nomor'   => '#000000',
            'nama'    => '#1B2433',
            'sekolah' => '#33415A',
        ],
        'terang' => [
            'acara'   => '#FFFFFF',
            'seri'    => '#FFFFFF',
            'garis'   => '#FFFFFF',
            'nomor'   => '#FFFFFF',
            'nama'    => '#FFFFFF',
            'sekolah' => '#FFFFFF',
        ],
    ];

    public const PILIHAN_WARNA = ['gelap' => 'Gelap (hitam)', 'terang' => 'Terang (putih)'];

    /**
     * @param array<string, string> $warna
     */
    private function __construct(
        public readonly ?string $latar,
        public readonly bool $kepala,
        public readonly string $namaWarna,
        public readonly array $warna,
    ) {
    }

    public static function dariPengaturan(Unggahan $unggahan = new Unggahan()): self
    {
        $jalur = trim((string) setting('Bib.latar'));
        $latar = $jalur !== '' && $unggahan->ada($jalur) ? $jalur : null;

        return self::buat($latar, (bool) setting('Bib.tampilkan_kepala'), (string) setting('Bib.warna_teks'));
    }

    /**
     * Tanpa latar, kepala selalu dicetak dan tulisan gelap.
     */
    public static function buat(?string $latar, bool $kepala, string $warna): self
    {
        $warna = $latar !== null && isset(self::WARNA[$warna]) ? $warna : 'gelap';

        return new self($latar, $latar === null || $kepala, $warna, self::WARNA[$warna]);
    }

    /**
     * Isi berkas JPG latar, atau `null` bila tanpa latar.
     */
    public function latarJpg(Unggahan $unggahan = new Unggahan()): ?string
    {
        $penuh = $this->latar === null ? null : $unggahan->jalurPenuh($this->latar);
        $isi   = $penuh === null ? false : @file_get_contents($penuh);

        return $isi === false || $isi === '' ? null : $isi;
    }

    /**
     * Simpan pengaturan dari formulir halaman Cetak BIB. Latar baru (bila
     * dipilih) diperiksa, dipotong, dan disimpan lebih dulu; latar lama
     * dihapus setelah yang baru tersimpan.
     *
     * @throws RuntimeException Dengan pesan ramah bila gambar ditolak; tidak ada yang disimpan.
     */
    public static function simpan(?UploadedFile $berkas, bool $hapusLatar, bool $kepala, string $warna, Unggahan $unggahan = new Unggahan()): void
    {
        if (! isset(self::WARNA[$warna])) {
            throw new RuntimeException('Pilih warna tulisan Gelap atau Terang.');
        }

        $lama = trim((string) setting('Bib.latar'));
        $baru = null;

        if (Unggahan::dipilih($berkas)) {
            $pesan = $unggahan->periksaGambar($berkas, self::BATAS_LATAR);

            if ($pesan !== null) {
                throw new RuntimeException($pesan);
            }

            $baru = $unggahan->simpanJpg(self::potong((string) $berkas->getTempName()), self::FOLDER, 90);
        }

        setting('Bib.tampilkan_kepala', $kepala);
        setting('Bib.warna_teks', $warna);

        if ($baru !== null || $hapusLatar) {
            setting('Bib.latar', $baru ?? '');

            if ($lama !== '' && $lama !== $baru) {
                $unggahan->hapus($lama);
            }
        }
    }

    /**
     * Gambar dipotong tengah (seperti `object-fit: cover`) supaya pas
     * `LEBAR` × `TINGGI`. Arah foto kamera (EXIF) diterapkan, dan bagian
     * transparan PNG/WEBP menjadi putih.
     *
     * @throws RuntimeException Bila gambar tidak bisa dibaca.
     */
    public static function potong(string $sumber): \GdImage
    {
        $info = @getimagesize($sumber);

        if ($info === false) {
            throw new RuntimeException('Gambar latar tidak bisa dibaca. Simpan ulang sebagai JPG atau PNG, lalu unggah lagi.');
        }

        Unggahan::cukupkanMemori(($info[0] * $info[1] + self::LEBAR * self::TINGGI) * 5);
        $asli = @imagecreatefromstring((string) file_get_contents($sumber));

        if ($asli === false) {
            throw new RuntimeException('Gambar latar tidak bisa dibaca. Simpan ulang sebagai JPG atau PNG, lalu unggah lagi.');
        }

        if ($info[2] === IMAGETYPE_JPEG) {
            $asli = Unggahan::putarSesuaiExif($asli, $sumber);
        }

        $lebar  = imagesx($asli);
        $tinggi = imagesy($asli);
        $skala  = max(self::LEBAR / $lebar, self::TINGGI / $tinggi);
        $ambilL = (int) round(self::LEBAR / $skala);
        $ambilT = (int) round(self::TINGGI / $skala);

        $hasil = imagecreatetruecolor(self::LEBAR, self::TINGGI);
        imagefill($hasil, 0, 0, imagecolorallocate($hasil, 255, 255, 255));
        imagecopyresampled($hasil, $asli, 0, 0, (int) (($lebar - $ambilL) / 2), (int) (($tinggi - $ambilT) / 2), self::LEBAR, self::TINGGI, $ambilL, $ambilT);
        imagedestroy($asli);

        return $hasil;
    }
}
