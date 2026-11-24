<?php

namespace App\Libraries\Cadangan;

use RuntimeException;
use SimpleXMLElement;

/**
 * Klien kecil API S3 Cloudflare R2 untuk salinan cadangan (F11-19, K185),
 * tanpa pustaka tambahan: tanda tangan AWS Signature Version 4, gaya jalur
 * (`https://<id-akun>.r2.cloudflarestorage.com/<bucket>/<kunci>`), wilayah
 * `auto`.
 *
 * Hanya tiga hal yang dipakai: menulis objek (`unggahBerkas()`,
 * `tulisTeks()`), membaca daftar objek (`daftar()`), dan alamat unduh
 * bertanda tangan yang berlaku sebentar (`alamatUnduh()`). Tidak ada perintah
 * hapus: masa simpan di bucket diatur aturan lifecycle di dasbor Cloudflare.
 *
 *     $r2 = new KlienR2($idAkun, 'cadangan-panitia', $kunciId, $rahasia);
 *     $r2->unggahBerkas('mac2026.web.id/cadangan-milklife-2026-11-25-0200.zip', $jalur);
 *     $r2->daftar('mac2026.web.id/'); // ['berkas' => [['kunci' => …, 'ukuran' => …, 'waktu' => …]], 'terpotong' => false]
 *
 * Pengiriman HTTP lewat cURL; uji menggantinya dengan `gantiPengirim()`.
 */
final class KlienR2
{
    public const WILAYAH = 'auto';

    /**
     * SHA-256 isi kosong (permintaan GET).
     */
    public const HASH_KOSONG = 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855';

    /**
     * Pengirim pengganti untuk uji:
     * `fn (string $metode, string $url, array $kepala, ?string $berkas, ?string $isi): array{status: int, isi: string}`.
     *
     * @var (callable(string, string, array<string, string>, string|null, string|null): array{status: int, isi: string})|null
     */
    private static $pengirimUji;

    public function __construct(
        private readonly string $idAkun,
        private readonly string $bucket,
        private readonly string $kunciId,
        private readonly string $rahasia,
    ) {
    }

    /**
     * Ganti pengiriman HTTP (uji). `null` kembali ke cURL.
     *
     * @param (callable(string, string, array<string, string>, string|null, string|null): array{status: int, isi: string})|null $pengirim
     */
    public static function gantiPengirim(?callable $pengirim): void
    {
        self::$pengirimUji = $pengirim;
    }

    public function host(): string
    {
        return $this->idAkun . '.r2.cloudflarestorage.com';
    }

    /**
     * Tulis berkas di disk sebagai objek `$kunci`. Berkas dialirkan dari disk,
     * tidak dimuat ke memori.
     *
     * @throws RuntimeException Bila R2 menolak atau tidak bisa dihubungi.
     */
    public function unggahBerkas(string $kunci, string $jalur): void
    {
        $hash = hash_file('sha256', $jalur);

        if ($hash === false) {
            throw new RuntimeException('Berkas ' . $jalur . ' tidak bisa dibaca untuk dikirim ke R2.');
        }

        $this->pastikanBerhasil($this->kirim('PUT', $this->jalur($kunci), [], $hash, $jalur, null, ['content-type' => 'application/zip']));
    }

    /**
     * Tulis teks pendek sebagai objek `$kunci` (berkas uji sambungan).
     *
     * @throws RuntimeException
     */
    public function tulisTeks(string $kunci, string $isi): void
    {
        $this->pastikanBerhasil($this->kirim('PUT', $this->jalur($kunci), [], hash('sha256', $isi), null, $isi, ['content-type' => 'text/plain; charset=utf-8']));
    }

    /**
     * Objek berawalan `$awalan`, urut nama (urutan dari R2).
     *
     * @return array{berkas: list<array{kunci: string, ukuran: int, waktu: int}>, terpotong: bool}
     *
     * @throws RuntimeException
     */
    public function daftar(string $awalan, int $maks = 1000): array
    {
        $jawaban = $this->kirim('GET', '/' . $this->bucket, ['list-type' => '2', 'max-keys' => (string) $maks, 'prefix' => $awalan], self::HASH_KOSONG);
        $this->pastikanBerhasil($jawaban);
        $xml = self::bacaXml($jawaban['isi']);

        if ($xml === null) {
            throw new RuntimeException('Jawaban daftar isi bucket dari R2 tidak bisa dibaca. Coba lagi beberapa saat lagi.');
        }

        $berkas = [];

        foreach ($xml->Contents as $isi) {
            $berkas[] = [
                'kunci'  => (string) $isi->Key,
                'ukuran' => (int) (string) $isi->Size,
                'waktu'  => (int) strtotime((string) $isi->LastModified),
            ];
        }

        return ['berkas' => $berkas, 'terpotong' => strtolower((string) $xml->IsTruncated) === 'true'];
    }

    /**
     * Alamat unduh bertanda tangan untuk objek `$kunci`, berlaku `$detik` detik.
     * Peramban mengunduh langsung dari R2, tanpa lewat server web.
     */
    public function alamatUnduh(string $kunci, int $detik = 600, ?int $sekarang = null): string
    {
        $waktu   = gmdate('Ymd\THis\Z', $sekarang ?? time());
        $lingkup = substr($waktu, 0, 8) . '/' . self::WILAYAH . '/s3/aws4_request';
        $jalur   = $this->jalur($kunci);
        $kueri   = [
            'X-Amz-Algorithm'     => 'AWS4-HMAC-SHA256',
            'X-Amz-Credential'    => $this->kunciId . '/' . $lingkup,
            'X-Amz-Date'          => $waktu,
            'X-Amz-Expires'       => (string) $detik,
            'X-Amz-SignedHeaders' => 'host',
        ];
        $tanda   = self::tandaTangan($this->rahasia, 'GET', $jalur, $kueri, ['host' => $this->host()], 'UNSIGNED-PAYLOAD', $waktu, self::WILAYAH);

        return 'https://' . $this->host() . self::jalurKanonis($jalur) . '?' . self::kueriKanonis($kueri) . '&X-Amz-Signature=' . $tanda;
    }

    /**
     * Tanda tangan AWS Signature Version 4 (layanan `s3`), dalam heksadesimal.
     *
     * @param array<string, string> $kueri  Parameter kueri, belum disandikan.
     * @param array<string, string> $kepala Kepala yang ditandatangani, nama huruf kecil, termasuk `host`.
     */
    public static function tandaTangan(string $rahasia, string $metode, string $jalur, array $kueri, array $kepala, string $hashIsi, string $waktuAmz, string $wilayah): string
    {
        ksort($kepala);
        $kepalaKanonis = '';

        foreach ($kepala as $nama => $nilai) {
            $kepalaKanonis .= $nama . ':' . trim($nilai) . "\n";
        }

        $permintaan = implode("\n", [
            $metode,
            self::jalurKanonis($jalur),
            self::kueriKanonis($kueri),
            $kepalaKanonis,
            implode(';', array_keys($kepala)),
            $hashIsi,
        ]);
        $tanggal = substr($waktuAmz, 0, 8);
        $teks    = implode("\n", ['AWS4-HMAC-SHA256', $waktuAmz, $tanggal . '/' . $wilayah . '/s3/aws4_request', hash('sha256', $permintaan)]);

        $kunci = hash_hmac('sha256', $tanggal, 'AWS4' . $rahasia, true);
        $kunci = hash_hmac('sha256', $wilayah, $kunci, true);
        $kunci = hash_hmac('sha256', 's3', $kunci, true);
        $kunci = hash_hmac('sha256', 'aws4_request', $kunci, true);

        return hash_hmac('sha256', $teks, $kunci);
    }

    /**
     * @param array<string, string> $kueri
     */
    public static function kueriKanonis(array $kueri): string
    {
        ksort($kueri, SORT_STRING);
        $bagian = [];

        foreach ($kueri as $nama => $nilai) {
            $bagian[] = rawurlencode((string) $nama) . '=' . rawurlencode($nilai);
        }

        return implode('&', $bagian);
    }

    private static function jalurKanonis(string $jalur): string
    {
        return implode('/', array_map(rawurlencode(...), explode('/', $jalur)));
    }

    private function jalur(string $kunci): string
    {
        return '/' . $this->bucket . '/' . ltrim($kunci, '/');
    }

    /**
     * @param array<string, string> $kueri
     * @param array<string, string> $kepalaTambahan Kepala yang dikirim tanpa ditandatangani.
     *
     * @return array{status: int, isi: string}
     */
    private function kirim(string $metode, string $jalur, array $kueri, string $hashIsi, ?string $berkas = null, ?string $isi = null, array $kepalaTambahan = []): array
    {
        $waktu  = gmdate('Ymd\THis\Z');
        $kepala = ['host' => $this->host(), 'x-amz-content-sha256' => $hashIsi, 'x-amz-date' => $waktu];
        $tanda  = self::tandaTangan($this->rahasia, $metode, $jalur, $kueri, $kepala, $hashIsi, $waktu, self::WILAYAH);

        $kepala['authorization'] = sprintf(
            'AWS4-HMAC-SHA256 Credential=%s/%s/%s/s3/aws4_request, SignedHeaders=host;x-amz-content-sha256;x-amz-date, Signature=%s',
            $this->kunciId,
            substr($waktu, 0, 8),
            self::WILAYAH,
            $tanda,
        );
        unset($kepala['host']);

        $url = 'https://' . $this->host() . self::jalurKanonis($jalur) . ($kueri === [] ? '' : '?' . self::kueriKanonis($kueri));

        return (self::$pengirimUji ?? self::kirimCurl(...))($metode, $url, $kepala + $kepalaTambahan, $berkas, $isi);
    }

    /**
     * @param array<string, string> $kepala
     *
     * @return array{status: int, isi: string}
     */
    private static function kirimCurl(string $metode, string $url, array $kepala, ?string $berkas, ?string $isi): array
    {
        // Tanpa "Expect: 100-continue": isi langsung dikirim.
        $baris = ['Expect:'];

        foreach ($kepala as $nama => $nilai) {
            $baris[] = $nama . ': ' . $nilai;
        }

        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST  => $metode,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $baris,
            CURLOPT_CONNECTTIMEOUT => 15,
            // Kiriman besar boleh lama, asal tidak macet: putus bila di bawah 1 KB/detik selama 60 detik.
            CURLOPT_LOW_SPEED_LIMIT => 1024,
            CURLOPT_LOW_SPEED_TIME  => 60,
            CURLOPT_TIMEOUT         => $berkas === null ? 30 : 0,
        ]);
        $pegangan = null;

        if ($berkas !== null) {
            $pegangan = fopen($berkas, 'rb');

            if ($pegangan === false) {
                throw new RuntimeException('Berkas ' . $berkas . ' tidak bisa dibuka untuk dikirim ke R2.');
            }

            curl_setopt_array($curl, [
                CURLOPT_UPLOAD     => true,
                CURLOPT_INFILE     => $pegangan,
                CURLOPT_INFILESIZE => (int) filesize($berkas),
            ]);
        } elseif ($isi !== null) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $isi);
        }

        $jawaban = curl_exec($curl);
        $status  = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $galat   = curl_error($curl);

        if (is_resource($pegangan)) {
            fclose($pegangan);
        }

        if ($jawaban === false || $status === 0) {
            throw new RuntimeException('Tidak bisa tersambung ke Cloudflare R2 (' . $galat . '). Periksa ID akun, lalu coba lagi. Bila tetap gagal, server mungkin tidak bisa membuka internet.');
        }

        return ['status' => $status, 'isi' => (string) $jawaban];
    }

    /**
     * @param array{status: int, isi: string} $jawaban
     *
     * @throws RuntimeException Dengan pesan yang menyebut cara memperbaikinya.
     */
    private function pastikanBerhasil(array $jawaban): void
    {
        if ($jawaban['status'] >= 200 && $jawaban['status'] < 300) {
            return;
        }

        $xml  = self::bacaXml($jawaban['isi']);
        $kode = $xml === null ? '' : (string) $xml->Code;

        throw new RuntimeException(match (true) {
            in_array($kode, ['InvalidAccessKeyId', 'SignatureDoesNotMatch', 'InvalidArgument', 'Unauthorized'], true)
                => 'R2 menolak kunci token (' . $kode . '). Periksa Access Key ID dan Secret Access Key; salin ulang dari halaman token di dasbor Cloudflare.',
            $kode === 'NoSuchBucket'
                => 'Bucket "' . $this->bucket . '" tidak ada di akun Cloudflare ini. Periksa nama bucket dan ID akun.',
            $kode === 'AccessDenied' || $jawaban['status'] === 403
                => 'Token tidak punya izin untuk bucket "' . $this->bucket . '". Buat token dengan izin Object Read & Write untuk bucket itu.',
            default
                => 'Cloudflare R2 menjawab ' . $jawaban['status'] . ($kode === '' ? '' : ' ' . $kode) . '. Coba lagi beberapa saat lagi.',
        });
    }

    private static function bacaXml(string $isi): ?SimpleXMLElement
    {
        if (trim($isi) === '') {
            return null;
        }

        $lama = libxml_use_internal_errors(true);
        $xml  = simplexml_load_string($isi, SimpleXMLElement::class, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($lama);

        return $xml === false ? null : $xml;
    }
}
