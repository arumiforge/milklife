<?php

namespace App\Libraries;

use CodeIgniter\HTTP\CURLRequest;
use Config\Turnstile as KonfigurasiTurnstile;
use Throwable;

/**
 * Pemeriksa token Cloudflare Turnstile (K122) tanpa pustaka tambahan: satu
 * panggilan `CURLRequest` ke siteverify.
 *
 * Token ditolak tanpa menghubungi Cloudflare bila kosong, bukan teks, atau
 * lebih dari 2048 huruf. Selain itu token dikirim bersama kunci rahasia dan
 * alamat IP pengunjung (`getIPAddress()`, sudah membaca `CF-Connecting-IP`,
 * K31) dengan batas waktu 10 detik. Token diterima hanya bila `success`
 * benar, `action` sama dengan tempatnya, dan `hostname` ada di daftar yang
 * diizinkan.
 *
 *     $turnstile = new Turnstile();
 *     if ($turnstile->aktif()) {
 *         $galat = $turnstile->periksa($token, Turnstile::AKSI_MASUK, $ip); // null bila lolos
 *     }
 *
 * Kunci rahasia tidak pernah ditulis ke log atau pesan.
 */
class Turnstile
{
    /**
     * `data-action` per tempat: huruf, angka, garis bawah, atau tanda hubung, paling banyak 32.
     */
    public const AKSI_PENDAFTARAN = 'kirim_pendaftaran';

    public const AKSI_CEK = 'cek_pendaftaran';

    public const AKSI_MASUK = 'masuk_admin';

    /**
     * Nama isian token di formulir dan di badan JSON wizard.
     */
    public const NAMA_ISIAN = 'cf-turnstile-response';

    public const PANJANG_TOKEN = 2048;

    public const PESAN_BELUM = 'Pemeriksaan keamanan belum selesai. Tunggu sampai kotak pemeriksaan keamanan di atas tombol bertanda centang hijau, lalu coba lagi.';

    public const PESAN_GAGAL = 'Pemeriksaan keamanan gagal atau sudah kedaluwarsa. Tunggu sampai kotak pemeriksaan keamanan bertanda centang hijau lagi, lalu coba lagi. Bila kotaknya tidak muncul, muat ulang halaman.';

    public const PESAN_TERPUTUS = 'Pemeriksaan keamanan belum bisa dihubungi. Periksa sambungan internet, tunggu sebentar, lalu coba lagi.';

    private KonfigurasiTurnstile $konfigurasi;

    private ?CURLRequest $http;

    public function __construct(?KonfigurasiTurnstile $konfigurasi = null, ?CURLRequest $http = null)
    {
        $this->konfigurasi = $konfigurasi ?? config(KonfigurasiTurnstile::class);
        $this->http        = $http;
    }

    /**
     * Turnstile hanya aktif bila kunci situs dan kunci rahasia sama-sama terisi.
     */
    public function aktif(): bool
    {
        return trim($this->konfigurasi->kunciSitus) !== '' && trim($this->konfigurasi->kunciRahasia) !== '';
    }

    public function kunciSitus(): string
    {
        return trim($this->konfigurasi->kunciSitus);
    }

    public function alamatSkrip(): string
    {
        return $this->konfigurasi->alamatSkrip;
    }

    /**
     * Data untuk widget di halaman, atau `null` bila Turnstile mati.
     *
     * @return array{kunci_situs: string, aksi: string, skrip: string}|null
     */
    public function untukHalaman(string $aksi): ?array
    {
        return $this->aktif() ? ['kunci_situs' => $this->kunciSitus(), 'aksi' => $aksi, 'skrip' => $this->alamatSkrip()] : null;
    }

    /**
     * Nama host yang diizinkan (huruf kecil).
     *
     * @return list<string>
     */
    public function namaHost(): array
    {
        $daftar = array_values(array_filter(array_map(
            static fn (string $h): string => strtolower(trim($h)),
            explode(',', $this->konfigurasi->namaHost),
        ), static fn (string $h): bool => $h !== ''));

        if ($daftar === []) {
            $host = parse_url((string) config('App')->baseURL, PHP_URL_HOST);

            if (is_string($host) && $host !== '') {
                $daftar[] = strtolower($host);
            }
        }

        return $daftar;
    }

    /**
     * Periksa token. `null` bila lolos (atau Turnstile mati); selain itu
     * pesan untuk pengunjung.
     */
    public function periksa(mixed $token, string $aksi, string $ip): ?string
    {
        if (! $this->aktif()) {
            return null;
        }

        if (! is_string($token) || trim($token) === '') {
            return self::PESAN_BELUM;
        }

        if (strlen($token) > self::PANJANG_TOKEN) {
            return self::PESAN_GAGAL;
        }

        try {
            $jawaban = ($this->http ?? service('curlrequest'))->post($this->konfigurasi->alamatPeriksa, [
                'form_params' => [
                    'secret'   => trim($this->konfigurasi->kunciRahasia),
                    'response' => $token,
                    'remoteip' => $ip,
                ],
                'timeout'         => $this->konfigurasi->batasWaktu,
                'connect_timeout' => $this->konfigurasi->batasWaktu,
                'http_errors'     => false,
            ]);
            $isi = json_decode($jawaban->getBody() ?? '', true);
        } catch (Throwable $e) {
            log_message('warning', 'Turnstile: siteverify tidak bisa dihubungi ({kelas}).', ['kelas' => $e::class]);

            return self::PESAN_TERPUTUS;
        }

        if (! is_array($isi)) {
            log_message('warning', 'Turnstile: jawaban siteverify tidak terbaca.');

            return self::PESAN_TERPUTUS;
        }

        if (($isi['success'] ?? false) !== true) {
            $kode = array_filter((array) ($isi['error-codes'] ?? []), 'is_string');
            log_message('info', 'Turnstile: token ditolak ({kode}).', ['kode' => implode(', ', $kode)]);

            return self::PESAN_GAGAL;
        }

        if (($isi['action'] ?? null) !== $aksi) {
            log_message('info', 'Turnstile: aksi tidak cocok untuk {aksi}.', ['aksi' => $aksi]);

            return self::PESAN_GAGAL;
        }

        $host = is_string($isi['hostname'] ?? null) ? strtolower($isi['hostname']) : '';

        if (! in_array($host, $this->namaHost(), true)) {
            log_message('info', 'Turnstile: nama host {host} tidak diizinkan.', ['host' => $host]);

            return self::PESAN_GAGAL;
        }

        return null;
    }
}
