<?php

namespace App\Libraries\Cadangan;

use App\Libraries\Unggahan;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Encryption\Exceptions\EncryptionException;
use RuntimeException;

/**
 * Salinan cadangan harian ke Cloudflare R2 (F11-21, K187), diatur admin di
 * Pengaturan > Cadangan, bukan di `docker/.env`.
 *
 * Pengaturan disimpan di tabel `salinan_r2` (satu baris). Secret Access Key
 * dienkripsi dengan `encryption.key` dan tidak pernah ditampilkan lagi.
 * Objek di bucket bernama `<folder>/<nama berkas>`; folder bawaan = nama host
 * `app.baseURL`, jadi staging dan produksi bisa berbagi satu bucket tanpa
 * tercampur.
 *
 *     SalinanR2::simpan(['id_akun' => …, 'bucket' => …, 'kunci_id' => …, 'folder' => …], $rahasia);
 *     SalinanR2::periksa();          // tulis berkas uji, lalu baca daftar isi folder
 *     SalinanR2::kirim($jalurZip);   // dipanggil `cadangan:buat`
 */
final class SalinanR2
{
    public const TABEL = 'salinan_r2';

    /**
     * Nama berkas uji yang ditulis tombol Periksa sambungan, di dalam folder.
     */
    public const BERKAS_UJI = 'uji-sambungan.txt';

    /**
     * Lama alamat unduh dari daftar isi bucket berlaku (detik).
     */
    public const LAMA_UNDUH = 600;

    public const POLA_ID_AKUN = '/^[0-9a-f]{32}$/D';

    public const POLA_BUCKET = '/^[a-z0-9][a-z0-9-]{1,61}[a-z0-9]$/D';

    public const POLA_KUNCI_ID = '/^[A-Za-z0-9]{16,128}$/D';

    public const POLA_RAHASIA = '/^[A-Za-z0-9\/+=]{20,128}$/D';

    public const POLA_FOLDER = '/^[a-z0-9][a-z0-9._-]{0,62}$/D';

    /**
     * Pengaturan tersimpan tanpa rahasia, atau `null` bila belum diatur.
     *
     * @return array{id_akun: string, bucket: string, kunci_id: string, folder: string, kirim_waktu: string|null, kirim_berhasil: bool|null, kirim_pesan: string|null, diubah_pada: string|null}|null
     */
    public static function pengaturan(?BaseConnection $db = null): ?array
    {
        $baris = self::baris($db);

        if ($baris === null) {
            return null;
        }

        return [
            'id_akun'        => (string) $baris['id_akun'],
            'bucket'         => (string) $baris['bucket'],
            'kunci_id'       => (string) $baris['kunci_id'],
            'folder'         => (string) $baris['folder'],
            'kirim_waktu'    => $baris['kirim_waktu'] === null ? null : (string) $baris['kirim_waktu'],
            'kirim_berhasil' => $baris['kirim_berhasil'] === null ? null : (bool) $baris['kirim_berhasil'],
            'kirim_pesan'    => $baris['kirim_pesan'] === null ? null : (string) $baris['kirim_pesan'],
            'diubah_pada'    => $baris['diubah_pada'] === null ? null : (string) $baris['diubah_pada'],
        ];
    }

    /**
     * Folder bawaan di bucket: nama host `app.baseURL`, misalnya `mac2026.web.id`.
     */
    public static function folderBawaan(): string
    {
        $host = strtolower((string) parse_url((string) config('App')->baseURL, PHP_URL_HOST));

        return preg_match(self::POLA_FOLDER, $host) === 1 ? $host : 'milklife';
    }

    /**
     * ID akun dari isian admin. Alamat S3 API utuh
     * (`https://<id>.r2.cloudflarestorage.com/...`) juga diterima.
     */
    public static function rapikanIdAkun(string $isian): string
    {
        $isian = strtolower(trim($isian));

        if (preg_match('#^(?:https?://)?([0-9a-f]{32})\.(?:[a-z]+\.)?r2\.cloudflarestorage\.com(?:/.*)?$#', $isian, $cocok) === 1) {
            return $cocok[1];
        }

        return $isian;
    }

    /**
     * Klien dari isian (belum tersimpan) atau dari pengaturan tersimpan.
     * Tanpa `$rahasia`, Secret Access Key tersimpan yang dipakai.
     *
     * @param array{id_akun: string, bucket: string, kunci_id: string, folder?: string}|null $isian
     *
     * @throws RuntimeException Bila belum diatur atau rahasia tidak bisa dibuka.
     */
    public static function klien(?array $isian = null, ?string $rahasia = null): KlienR2
    {
        if ($isian === null || $rahasia === null || $rahasia === '') {
            $baris = self::baris();

            if ($baris === null) {
                throw new RuntimeException('Salinan ke Cloudflare R2 belum diatur. Isi di Pengaturan > Cadangan.');
            }

            $isian ??= $baris;
            $rahasia = self::buka((string) $baris['rahasia']);
        }

        return new KlienR2((string) $isian['id_akun'], (string) $isian['bucket'], (string) $isian['kunci_id'], $rahasia);
    }

    /**
     * Simpan pengaturan. `$rahasia` kosong = pakai rahasia yang sudah tersimpan.
     *
     * @param array{id_akun: string, bucket: string, kunci_id: string, folder: string} $isian Sudah diperiksa.
     *
     * @throws RuntimeException Bila `encryption.key` belum diisi.
     */
    public static function simpan(array $isian, ?string $rahasia): void
    {
        $db    = db_connect();
        $lama  = self::baris($db);
        $tutup = $rahasia === null || $rahasia === '' ? (string) ($lama['rahasia'] ?? '') : self::tutup($rahasia);
        $data  = [
            'id_akun'     => $isian['id_akun'],
            'bucket'      => $isian['bucket'],
            'kunci_id'    => $isian['kunci_id'],
            'rahasia'     => $tutup,
            'folder'      => $isian['folder'],
            'diubah_pada' => date('Y-m-d H:i:s'),
        ];

        if ($lama === null) {
            $db->table(self::TABEL)->insert(['id' => 1] + $data);
        } else {
            $db->table(self::TABEL)->where('id', 1)->update($data);
        }
    }

    public static function hapus(): void
    {
        db_connect()->table(self::TABEL)->where('id', 1)->delete();
    }

    /**
     * Tulis berkas uji ke folder, lalu baca daftar isi folder itu.
     *
     * @return array{berkas: list<array{kunci: string, ukuran: int, waktu: int}>, terpotong: bool}
     *
     * @throws RuntimeException Dengan pesan yang menyebut cara memperbaikinya.
     */
    public static function periksa(KlienR2 $klien, string $folder): array
    {
        $klien->tulisTeks($folder . '/' . self::BERKAS_UJI, 'Uji sambungan MilkLife ' . date('Y-m-d H:i:s') . "\n");

        return $klien->daftar($folder . '/');
    }

    /**
     * Isi folder di bucket untuk Pengaturan > Cadangan, terbaru di atas, dengan alamat unduh.
     *
     * @return array{berkas: list<array{nama: string, ukuran: int, waktu: int, unduh: string}>, terpotong: bool}
     *
     * @throws RuntimeException
     */
    public static function isiBucket(): array
    {
        $pengaturan = self::pengaturan();

        if ($pengaturan === null) {
            return ['berkas' => [], 'terpotong' => false];
        }

        $klien  = self::klien();
        $awalan = $pengaturan['folder'] . '/';
        $daftar = $klien->daftar($awalan);
        $berkas = [];

        foreach ($daftar['berkas'] as $objek) {
            $berkas[] = [
                'nama'   => substr($objek['kunci'], strlen($awalan)),
                'ukuran' => $objek['ukuran'],
                'waktu'  => $objek['waktu'],
                'unduh'  => $klien->alamatUnduh($objek['kunci'], self::LAMA_UNDUH),
            ];
        }

        usort($berkas, static fn (array $a, array $b): int => [$b['waktu'], $b['nama']] <=> [$a['waktu'], $a['nama']]);

        return ['berkas' => $berkas, 'terpotong' => $daftar['terpotong']];
    }

    /**
     * Kirim satu berkas cadangan ke folder di bucket, lalu catat hasilnya.
     * Tidak melakukan apa pun bila salinan belum diatur.
     *
     * @return string|null Kunci objek di bucket, atau `null` bila belum diatur.
     *
     * @throws RuntimeException Bila kiriman gagal (hasilnya sudah dicatat).
     */
    public static function kirim(string $jalur): ?string
    {
        $pengaturan = self::pengaturan();

        if ($pengaturan === null) {
            return null;
        }

        $kunci = $pengaturan['folder'] . '/' . basename($jalur);

        try {
            self::klien()->unggahBerkas($kunci, $jalur);
        } catch (RuntimeException $e) {
            self::catatKiriman(false, basename($jalur) . ': ' . $e->getMessage());

            throw $e;
        }

        self::catatKiriman(true, basename($jalur) . ' (' . Unggahan::ukuranTeks((int) filesize($jalur)) . ')');

        return $pengaturan['bucket'] . '/' . $kunci;
    }

    private static function catatKiriman(bool $berhasil, string $pesan): void
    {
        db_connect()->table(self::TABEL)->where('id', 1)->update([
            'kirim_waktu'    => date('Y-m-d H:i:s'),
            'kirim_berhasil' => $berhasil ? 1 : 0,
            'kirim_pesan'    => mb_substr($pesan, 0, 500),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function baris(?BaseConnection $db = null): ?array
    {
        $baris = ($db ?? db_connect())->table(self::TABEL)->where('id', 1)->get()->getRowArray();

        return is_array($baris) ? $baris : null;
    }

    private static function tutup(string $rahasia): string
    {
        try {
            return base64_encode(service('encrypter')->encrypt($rahasia));
        } catch (EncryptionException) {
            throw new RuntimeException('Kunci enkripsi web (encryption.key di .env) belum diisi, jadi Secret Access Key tidak bisa disimpan dengan aman. Minta pengelola server mengisinya, lalu simpan lagi.');
        }
    }

    private static function buka(string $tertutup): string
    {
        $mentah = base64_decode($tertutup, true);

        try {
            if ($mentah === false || $mentah === '') {
                throw new EncryptionException('kosong');
            }

            return (string) service('encrypter')->decrypt($mentah);
        } catch (EncryptionException) {
            throw new RuntimeException('Secret Access Key yang tersimpan tidak bisa dibuka, mungkin karena encryption.key di .env berubah. Isi ulang Secret Access Key di Pengaturan > Cadangan.');
        }
    }
}
