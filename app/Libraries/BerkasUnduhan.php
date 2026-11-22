<?php

namespace App\Libraries;

/**
 * Aturan tampil berkas unduhan publik (F10-09): nama berkas saat disimpan
 * dan jenis yang bisa dilihat dulu di peramban.
 *
 * Dipakai `beranda/_daftar_unduhan` (tombol "Lihat" dan "Unduh") dan
 * `App\Controllers\UnduhanController::lihat()` (pratinjau `inline`).
 */
final class BerkasUnduhan
{
    /**
     * Jenis berkas (`Unggahan::jenis()`) yang bisa dilihat di peramban =>
     * jenis isi (MIME) yang dikirim rute pratinjau.
     *
     * @var array<string, string>
     */
    public const JENIS_LIHAT = [
        'PDF'  => 'application/pdf',
        'JPG'  => 'image/jpeg',
        'JPEG' => 'image/jpeg',
        'PNG'  => 'image/png',
    ];

    /**
     * Bentuk pratinjau: `pdf` (dalam `<iframe>`), `gambar` (dalam `<img>`),
     * atau `null` bila berkas hanya bisa diunduh (DOC, XLS, …).
     */
    public static function pratinjau(string $jenis): ?string
    {
        $jenis = strtoupper($jenis);

        if (! isset(self::JENIS_LIHAT[$jenis])) {
            return null;
        }

        return $jenis === 'PDF' ? 'pdf' : 'gambar';
    }

    /**
     * Apakah ada berkas yang bisa dilihat dulu, untuk memuat modal dan
     * `unduhan-pratinjau.js` hanya bila perlu.
     *
     * @param list<array{jenis: string}> $unduhan `IsiHalamanDepan::unduhan()`
     */
    public static function adaPratinjau(array $unduhan): bool
    {
        foreach ($unduhan as $berkas) {
            if (self::pratinjau($berkas['jenis']) !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * Alamat rute pratinjau untuk satu berkas unduhan.
     */
    public static function alamatLihat(int $id): string
    {
        return site_url('unduhan/' . $id . '/lihat');
    }

    /**
     * Nama berkas saat disimpan: judul tanpa tanda yang dilarang sistem
     * berkas, ditambah ekstensinya (alamat berkas sendiri berupa nama acak).
     */
    public static function namaSimpan(string $judul, string $jenis): string
    {
        $nama = trim((string) preg_replace('/[\\\\\/:*?"<>|\x00-\x1F]+/u', '-', $judul), ' .-');

        return ($nama === '' ? 'unduhan' : $nama) . ($jenis === '' ? '' : '.' . strtolower($jenis));
    }
}
