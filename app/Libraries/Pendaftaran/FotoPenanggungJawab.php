<?php

namespace App\Libraries\Pendaftaran;

use RuntimeException;

/**
 * Foto penanggung jawab (rancangan 6.3, 7, 13; K50).
 *
 * Peramban memperkecil foto (sisi terpanjang 800 piksel, JPEG) dan
 * mengirimnya sebagai data URL `data:image/jpeg;base64,…`. Server hanya
 * menerima JPEG yang terbaca, tidak lebih besar dari `SISI_MAKSIMAL` dan
 * `BATAS_BITA`, lalu menyimpannya ulang lewat GD (data tambahan berkas, misalnya
 * lokasi kamera, ikut terbuang).
 *
 * Berkas disimpan di luar folder publik: `WRITEPATH . 'unggahan/penanggung-jawab/<acak>.jpg'`.
 * Yang disimpan di `penanggung_jawab.foto` jalur relatifnya terhadap
 * `folderDasar()`, contoh `penanggung-jawab/3f9c….jpg`. Penyajiannya ke admin
 * dikerjakan Tahap 6 lewat `jalurPenuh()`.
 *
 *     $isi = FotoPenanggungJawab::baca($dataUrl);   // bita JPEG, atau null bila ditolak
 *     $jalur = (new FotoPenanggungJawab())->simpan($isi);
 *
 * Di uji, folder dasar diganti dengan `gantiFolderDasar()`.
 */
final class FotoPenanggungJawab
{
    public const SISI_MAKSIMAL = 800;

    public const BATAS_BITA = 500 * 1024;

    public const FOLDER = 'penanggung-jawab';

    private const AWALAN_DATA = 'data:image/jpeg;base64,';

    private static ?string $folderDasar = null;

    public static function gantiFolderDasar(?string $folder): void
    {
        self::$folderDasar = $folder === null ? null : rtrim($folder, '/\\') . DIRECTORY_SEPARATOR;
    }

    /**
     * Folder induk semua unggahan di luar `public/`.
     */
    public static function folderDasar(): string
    {
        return self::$folderDasar ?? WRITEPATH . 'unggahan' . DIRECTORY_SEPARATOR;
    }

    /**
     * Isi JPEG dari data URL bila sah, atau `null`.
     */
    public static function baca(mixed $dataUrl): ?string
    {
        if (! is_string($dataUrl) || ! str_starts_with($dataUrl, self::AWALAN_DATA)) {
            return null;
        }

        $base64 = substr($dataUrl, strlen(self::AWALAN_DATA));

        // Teks base64 lebih panjang sepertiga dari isinya; tolak sebelum dibuka.
        if (strlen($base64) > (int) ceil(self::BATAS_BITA * 4 / 3) + 4) {
            return null;
        }

        $isi = base64_decode($base64, true);

        if ($isi === false || $isi === '' || strlen($isi) > self::BATAS_BITA) {
            return null;
        }

        $info = @getimagesizefromstring($isi);

        if ($info === false || $info[2] !== IMAGETYPE_JPEG || $info[0] < 1 || $info[1] < 1
            || max($info[0], $info[1]) > self::SISI_MAKSIMAL) {
            return null;
        }

        return $isi;
    }

    /**
     * Simpan ulang JPEG ke folder penanggung jawab; hasilnya jalur relatif.
     *
     * @throws RuntimeException bila gambar tidak bisa dibaca atau ditulis
     */
    public function simpan(string $isi): string
    {
        $gambar = @imagecreatefromstring($isi);

        if ($gambar === false) {
            throw new RuntimeException('Foto penanggung jawab tidak bisa dibaca.');
        }

        $folder = self::folderDasar() . self::FOLDER;

        if (! is_dir($folder) && ! mkdir($folder, 0775, true) && ! is_dir($folder)) {
            throw new RuntimeException('Folder foto penanggung jawab tidak bisa dibuat.');
        }

        $jalur = self::FOLDER . '/' . bin2hex(random_bytes(16)) . '.jpg';

        try {
            if (! imagejpeg($gambar, self::folderDasar() . $jalur, 85)) {
                throw new RuntimeException('Foto penanggung jawab tidak bisa disimpan.');
            }
        } finally {
            imagedestroy($gambar);
        }

        return $jalur;
    }

    /**
     * Jalur lengkap di disk, atau `null` bila jalurnya tidak sah.
     */
    public function jalurPenuh(?string $jalur): ?string
    {
        if ($jalur === null || preg_match('#^' . self::FOLDER . '/[0-9a-f]{32}\.jpg$#D', $jalur) !== 1) {
            return null;
        }

        return self::folderDasar() . $jalur;
    }

    public function hapus(?string $jalur): bool
    {
        $penuh = $this->jalurPenuh($jalur);

        return $penuh !== null && is_file($penuh) && unlink($penuh);
    }
}
