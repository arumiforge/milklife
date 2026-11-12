<?php

namespace App\Libraries\Cadangan;

use Config\Cadangan;
use DateTimeImmutable;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use ZipArchive;

/**
 * Membuat satu berkas zip cadangan (K66) berisi:
 *
 * - `basisdata.sql`: hasil `mysqldump` satu transaksi (`--single-transaction`),
 *   jadi pendaftaran yang sedang disimpan tidak terpotong setengah;
 * - `writable/unggahan/…`: foto penanggung jawab dan bukti bayar;
 * - `public/unggahan/…`: gambar dan berkas unduhan unggahan admin.
 *
 * Jalur di dalam zip sama dengan jalur di folder proyek, jadi pemulihan cukup
 * menyalin kedua folder itu ke tempatnya. Setelah zip berhasil ditulis,
 * cadangan lama yang melewati masa simpan dihapus; bila gagal, tidak ada yang
 * dihapus.
 *
 * Sandi basis data ditulis ke berkas opsi sementara (`--defaults-extra-file`),
 * bukan ke baris perintah yang bisa dilihat proses lain, dan berkas itu
 * dihapus lagi setelah `mysqldump` selesai.
 *
 *     $hasil = (new PembuatCadangan(config('Cadangan'), $sambungan))->buat();
 *     // ['berkas' => 'D:\cadangan\cadangan-milklife-2026-11-12-0200.zip', 'dihapus' => [...]]
 */
final class PembuatCadangan
{
    /**
     * @param array<string, mixed>  $sambungan Satu grup dari `Config\Database` (hostname, port, username, password, database).
     * @param array<string, string> $folderSumber Jalur di dalam zip => folder asli. Folder yang belum ada dilewati.
     */
    public function __construct(
        private readonly Cadangan $pengaturan,
        private readonly array $sambungan,
        private readonly array $folderSumber = [
            'writable/unggahan' => WRITEPATH . 'unggahan',
            'public/unggahan'   => FCPATH . 'unggahan',
        ],
        private readonly string $folderProyek = ROOTPATH,
    ) {
    }

    /**
     * @return array{berkas: string, dihapus: list<string>}
     *
     * @throws RuntimeException Bila cadangan gagal dibuat; pesannya menyebut cara memperbaikinya.
     */
    public function buat(?DateTimeImmutable $sekarang = null): array
    {
        $sekarang ??= new DateTimeImmutable();
        $folder = $this->folderTujuan();

        if ($this->pengaturan->simpanHari < 1) {
            throw new RuntimeException('cadangan.simpanHari paling sedikit 1. Perbaiki di .env, lalu jalankan perintah ini lagi.');
        }

        $berkas = $folder . DIRECTORY_SEPARATOR . MasaSimpan::namaBerkas($sekarang);
        $sql    = $this->berkasSementara();

        try {
            $this->dump($sql);
            $this->tulisZip($berkas, $sql);
        } finally {
            @unlink($sql);
        }

        return ['berkas' => $berkas, 'dihapus' => $this->hapusYangKedaluwarsa($folder, $sekarang)];
    }

    /**
     * true bila `$folder` sama dengan `$induk` atau berada di dalamnya.
     * Keduanya jalur yang sudah lewat `realpath()`.
     */
    public static function diDalam(string $folder, string $induk): bool
    {
        $folder = rtrim(str_replace('\\', '/', $folder), '/');
        $induk  = rtrim(str_replace('\\', '/', $induk), '/');

        // Huruf besar-kecil jalur tidak dibedakan di Windows.
        if (DIRECTORY_SEPARATOR === '\\') {
            $folder = strtolower($folder);
            $induk  = strtolower($induk);
        }

        return $folder === $induk || str_starts_with($folder . '/', $induk . '/');
    }

    private function folderTujuan(): string
    {
        $isian = trim($this->pengaturan->folder);

        if ($isian === '') {
            throw new RuntimeException('cadangan.folder di .env belum diisi. Isi dengan folder di luar folder proyek, misalnya D:\cadangan-milklife, lalu jalankan perintah ini lagi.');
        }

        $folder = realpath($isian);

        if ($folder === false || ! is_dir($folder)) {
            throw new RuntimeException('Folder cadangan ' . $isian . ' tidak ditemukan. Buat folder itu dulu, atau perbaiki cadangan.folder di .env.');
        }

        $proyek = realpath($this->folderProyek);

        if ($proyek !== false && self::diDalam($folder, $proyek)) {
            throw new RuntimeException('Folder cadangan ' . $isian . ' berada di dalam folder proyek. Pilih folder di luar proyek, sebaiknya di diska lain, misalnya D:\cadangan-milklife.');
        }

        if (! is_writable($folder)) {
            throw new RuntimeException('Folder cadangan ' . $folder . ' tidak boleh ditulisi. Periksa izin folder itu, lalu jalankan perintah ini lagi.');
        }

        return $folder;
    }

    private function dump(string $tujuan): void
    {
        $opsi = $this->berkasSementara();
        $log  = $this->berkasSementara();

        try {
            if (@file_put_contents($opsi, $this->isiBerkasOpsi()) === false) {
                throw new RuntimeException('Berkas sementara untuk mysqldump gagal ditulis di ' . sys_get_temp_dir() . '. Periksa sisa ruang diska.');
            }

            @chmod($opsi, 0600);

            // --defaults-extra-file wajib menjadi opsi pertama.
            $perintah = [
                $this->pengaturan->mysqldump,
                '--defaults-extra-file=' . $opsi,
                '--single-transaction',
                '--no-tablespaces',
                '--default-character-set=utf8mb4',
                '--result-file=' . $tujuan,
                (string) ($this->sambungan['database'] ?? ''),
            ];

            $proses = @proc_open($perintah, [0 => ['pipe', 'r'], 1 => ['file', $log, 'w'], 2 => ['file', $log, 'a']], $pipa);

            if ($proses === false) {
                throw new RuntimeException($this->pesanMysqldump(''));
            }

            fclose($pipa[0]);
            $kode = proc_close($proses);

            if ($kode !== 0 || ! is_file($tujuan) || filesize($tujuan) === 0) {
                throw new RuntimeException($this->pesanMysqldump(trim((string) @file_get_contents($log))));
            }
        } finally {
            @unlink($opsi);
            @unlink($log);
        }
    }

    private function pesanMysqldump(string $keluaran): string
    {
        return 'mysqldump (' . $this->pengaturan->mysqldump . ') gagal mencadangkan basis data.'
            . ($keluaran === '' ? '' : ' Pesannya: ' . $keluaran . '.')
            . ' Periksa cadangan.mysqldump (jalur mysqldump.exe di Laragon) dan isian database.default di .env, lalu jalankan perintah ini lagi.';
    }

    private function isiBerkasOpsi(): string
    {
        $baris = ['[client]'];

        foreach (['host' => 'hostname', 'port' => 'port', 'user' => 'username', 'password' => 'password'] as $opsi => $kunci) {
            $nilai = (string) ($this->sambungan[$kunci] ?? '');

            if ($nilai !== '') {
                $baris[] = $opsi . '="' . addcslashes($nilai, '"\\') . '"';
            }
        }

        return implode("\n", $baris) . "\n";
    }

    private function tulisZip(string $berkas, string $sql): void
    {
        $zip = new ZipArchive();

        if ($zip->open($berkas, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Berkas cadangan ' . $berkas . ' gagal dibuat. Periksa izin folder cadangan.');
        }

        $zip->addFile($sql, 'basisdata.sql');

        foreach ($this->folderSumber as $dalamZip => $folder) {
            if (! is_dir($folder)) {
                continue;
            }

            $zip->addEmptyDir($dalamZip);
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST,
            );

            foreach ($iterator as $isi) {
                /** @var SplFileInfo $isi */
                $nama = $dalamZip . '/' . str_replace('\\', '/', substr($isi->getPathname(), strlen(rtrim($folder, '/\\')) + 1));
                $isi->isDir() ? $zip->addEmptyDir($nama) : $zip->addFile($isi->getPathname(), $nama);
            }
        }

        // Isi berkas baru dibaca dan ditulis saat close(); diska penuh terlihat di sini.
        if (! $zip->close()) {
            @unlink($berkas);

            throw new RuntimeException('Berkas cadangan ' . $berkas . ' gagal ditulis. Periksa sisa ruang diska folder cadangan, lalu jalankan perintah ini lagi.');
        }
    }

    /**
     * @return list<string> Nama berkas yang dihapus.
     */
    private function hapusYangKedaluwarsa(string $folder, DateTimeImmutable $sekarang): array
    {
        $nama    = array_map('basename', glob($folder . DIRECTORY_SEPARATOR . 'cadangan-milklife-*.zip') ?: []);
        $dihapus = [];

        foreach (MasaSimpan::kedaluwarsa($nama, $sekarang, $this->pengaturan->simpanHari) as $berkas) {
            if (@unlink($folder . DIRECTORY_SEPARATOR . $berkas)) {
                $dihapus[] = $berkas;
            }
        }

        return $dihapus;
    }

    private function berkasSementara(): string
    {
        $berkas = tempnam(sys_get_temp_dir(), 'milklife-');

        if ($berkas === false) {
            throw new RuntimeException('Berkas sementara gagal dibuat di ' . sys_get_temp_dir() . '. Periksa sisa ruang diska.');
        }

        return $berkas;
    }
}
