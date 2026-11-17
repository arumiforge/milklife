<?php

namespace Tests\Support;

use CodeIgniter\HTTP\URI;
use CodeIgniter\Test\Mock\MockCURLRequest;
use RuntimeException;

/**
 * Tiruan siteverify Cloudflare Turnstile untuk uji, tanpa panggilan jaringan
 * (K122). Jawabannya meniru kunci uji resmi Cloudflare:
 *
 * - kunci rahasia `RAHASIA_LOLOS` (1x…AA): selalu lolos,
 * - kunci rahasia `RAHASIA_GAGAL` (2x…AA): selalu gagal,
 * - kunci rahasia `RAHASIA_TERPAKAI` (3x…AA): token sudah terpakai.
 *
 * Saat lolos, `hostname` diisi dari `$namaHost` (bawaan `example.com`, seperti
 * kunci uji asli) dan `action` dari `$aksi`; uji mengisi `$aksi` dengan aksi
 * tempatnya, karena kunci produksi mengembalikan aksi widget. Setiap panggilan dicatat di `$kiriman` (isian form yang
 * dikirim) beserta pilihan cURL-nya di `$pilihan`.
 */
final class TiruanSiteverify extends MockCURLRequest
{
    /**
     * Kunci situs uji resmi Cloudflare yang selalu lolos.
     */
    public const SITUS_LOLOS = '1x00000000000000000000AA';

    public const RAHASIA_LOLOS = '1x0000000000000000000000000000000AA';

    public const RAHASIA_GAGAL = '2x0000000000000000000000000000000AA';

    public const RAHASIA_TERPAKAI = '3x0000000000000000000000000000000AA';

    /**
     * Token tiruan yang dibuat widget dengan kunci situs uji.
     */
    public const TOKEN = 'XXXX.DUMMY.TOKEN.XXXX';

    public string $namaHost = 'example.com';

    public string $aksi = '';

    public bool $putus = false;

    /**
     * @var list<array<string, string>>
     */
    public array $kiriman = [];

    /**
     * @var list<array<int, mixed>>
     */
    public array $pilihan = [];

    public function __construct()
    {
        parent::__construct(config('App'), new URI('https://challenges.cloudflare.com/'), service('response'));
    }

    /**
     * @param array<int, mixed> $curlOptions
     */
    protected function sendRequest(array $curlOptions = []): string
    {
        $this->response = clone $this->responseOrig;
        $this->pilihan[] = $curlOptions;

        parse_str((string) ($curlOptions[CURLOPT_POSTFIELDS] ?? ''), $isi);
        /** @var array<string, string> $isi */
        $this->kiriman[] = $isi;

        if ($this->putus) {
            throw new RuntimeException('Waktu habis (tiruan).');
        }

        $jawaban = match ($isi['secret'] ?? '') {
            // Kunci uji asli menjawab tanpa `action` (diperiksa 17 November 2026);
            // `$aksi` kosong meniru itu.
            self::RAHASIA_LOLOS => ['challenge_ts' => '2026-11-17T00:00:00.000Z', 'error-codes' => [], 'hostname' => $this->namaHost, 'metadata' => ['result_with_testing_key' => true], 'success' => true]
                + ($this->aksi === '' ? [] : ['action' => $this->aksi]),
            self::RAHASIA_TERPAKAI => ['success' => false, 'error-codes' => ['timeout-or-duplicate']],
            default => ['success' => false, 'error-codes' => ['invalid-input-response']],
        };

        return "HTTP/1.1 200 OK\r\nContent-Type: application/json\r\n\r\n" . json_encode($jawaban);
    }
}
