<?php

use App\Libraries\Cadangan\KlienR2;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * `KlienR2` (F11-19, K185): tanda tangan AWS Signature Version 4 dicocokkan
 * dengan contoh resmi dokumentasi Amazon S3, lalu permintaan ke R2 diperiksa
 * lewat pengirim tiruan. Semua kunci karangan.
 *
 * @internal
 */
final class KlienR2Test extends CIUnitTestCase
{
    private const AKUN = '0123456789abcdef0123456789abcdef';

    private const RAHASIA_AWS = 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY';

    /**
     * @var list<array{metode: string, url: string, kepala: array<string, string>, berkas: string|null, isi: string|null}>
     */
    private array $permintaan = [];

    /**
     * @var array{status: int, isi: string}
     */
    private array $jawaban = ['status' => 200, 'isi' => ''];

    protected function setUp(): void
    {
        parent::setUp();
        $this->permintaan = [];
        KlienR2::gantiPengirim(function (string $metode, string $url, array $kepala, ?string $berkas, ?string $isi): array {
            $this->permintaan[] = ['metode' => $metode, 'url' => $url, 'kepala' => $kepala, 'berkas' => $berkas, 'isi' => $isi];

            return $this->jawaban;
        });
    }

    protected function tearDown(): void
    {
        KlienR2::gantiPengirim(null);
        parent::tearDown();
    }

    public function testTandaTanganSamaDenganContohResmiAmazonS3(): void
    {
        // Contoh "GET Bucket (List Objects)" di dokumentasi Signature Version 4 Amazon S3.
        $this->assertSame('34b48302e7b5fa45bde8084f4b7868a86f0a534bc59db6670ed5711ef69dc6f7', KlienR2::tandaTangan(
            self::RAHASIA_AWS,
            'GET',
            '/',
            ['max-keys' => '2', 'prefix' => 'J'],
            ['host' => 'examplebucket.s3.amazonaws.com', 'x-amz-content-sha256' => KlienR2::HASH_KOSONG, 'x-amz-date' => '20130524T000000Z'],
            KlienR2::HASH_KOSONG,
            '20130524T000000Z',
            'us-east-1',
        ));

        // Contoh alamat bertanda tangan (query string) di dokumentasi yang sama.
        $this->assertSame('aeeed9bbccd4d02ee5c0109b86d86835f995330da4c265957d157751f604d404', KlienR2::tandaTangan(
            self::RAHASIA_AWS,
            'GET',
            '/test.txt',
            [
                'X-Amz-Algorithm'     => 'AWS4-HMAC-SHA256',
                'X-Amz-Credential'    => 'AKIAIOSFODNN7EXAMPLE/20130524/us-east-1/s3/aws4_request',
                'X-Amz-Date'          => '20130524T000000Z',
                'X-Amz-Expires'       => '86400',
                'X-Amz-SignedHeaders' => 'host',
            ],
            ['host' => 'examplebucket.s3.amazonaws.com'],
            'UNSIGNED-PAYLOAD',
            '20130524T000000Z',
            'us-east-1',
        ));
    }

    public function testDaftarMembacaIsiBucket(): void
    {
        $this->jawaban = ['status' => 200, 'isi' => <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <ListBucketResult xmlns="http://s3.amazonaws.com/doc/2006-03-01/">
              <Name>cadangan-karangan</Name><Prefix>mac2026.web.id/</Prefix><KeyCount>2</KeyCount><MaxKeys>1000</MaxKeys><IsTruncated>false</IsTruncated>
              <Contents><Key>mac2026.web.id/cadangan-milklife-2026-11-25-0200.zip</Key><LastModified>2026-11-24T19:00:05.000Z</LastModified><Size>1048576</Size></Contents>
              <Contents><Key>mac2026.web.id/uji-sambungan.txt</Key><LastModified>2026-11-24T10:00:00.000Z</LastModified><Size>40</Size></Contents>
            </ListBucketResult>
            XML];

        $hasil = $this->klien()->daftar('mac2026.web.id/');

        $this->assertFalse($hasil['terpotong']);
        $this->assertSame([
            ['kunci' => 'mac2026.web.id/cadangan-milklife-2026-11-25-0200.zip', 'ukuran' => 1048576, 'waktu' => strtotime('2026-11-24T19:00:05Z')],
            ['kunci' => 'mac2026.web.id/uji-sambungan.txt', 'ukuran' => 40, 'waktu' => strtotime('2026-11-24T10:00:00Z')],
        ], $hasil['berkas']);

        $this->assertCount(1, $this->permintaan);
        $minta = $this->permintaan[0];
        $this->assertSame('GET', $minta['metode']);
        $this->assertSame('https://' . self::AKUN . '.r2.cloudflarestorage.com/cadangan-karangan?list-type=2&max-keys=1000&prefix=mac2026.web.id%2F', $minta['url']);
        $this->assertSame(KlienR2::HASH_KOSONG, $minta['kepala']['x-amz-content-sha256']);
        $this->assertMatchesRegularExpression('/^\d{8}T\d{6}Z$/', $minta['kepala']['x-amz-date']);
        $this->assertMatchesRegularExpression(
            '#^AWS4-HMAC-SHA256 Credential=kunciidkarangan0001/\d{8}/auto/s3/aws4_request, SignedHeaders=host;x-amz-content-sha256;x-amz-date, Signature=[0-9a-f]{64}$#',
            $minta['kepala']['authorization'],
        );

        // Tanda tangan dihitung ulang dari permintaan yang dikirim.
        preg_match('/Signature=([0-9a-f]{64})$/', $minta['kepala']['authorization'], $tanda);
        $this->assertSame($tanda[1], KlienR2::tandaTangan(
            'rahasiakarangan0000000000000000000000001',
            'GET',
            '/cadangan-karangan',
            ['list-type' => '2', 'max-keys' => '1000', 'prefix' => 'mac2026.web.id/'],
            ['host' => self::AKUN . '.r2.cloudflarestorage.com', 'x-amz-content-sha256' => KlienR2::HASH_KOSONG, 'x-amz-date' => $minta['kepala']['x-amz-date']],
            KlienR2::HASH_KOSONG,
            $minta['kepala']['x-amz-date'],
            'auto',
        ));
    }

    public function testUnggahBerkasMengalirkanBerkasDenganHashIsinya(): void
    {
        $berkas = tempnam(sys_get_temp_dir(), 'milklife-r2-');
        file_put_contents($berkas, 'isi zip karangan');

        try {
            $this->klien()->unggahBerkas('mac2026.web.id/cadangan-milklife-2026-11-25-0200.zip', $berkas);
        } finally {
            @unlink($berkas);
        }

        $minta = $this->permintaan[0];
        $this->assertSame('PUT', $minta['metode']);
        $this->assertSame('https://' . self::AKUN . '.r2.cloudflarestorage.com/cadangan-karangan/mac2026.web.id/cadangan-milklife-2026-11-25-0200.zip', $minta['url']);
        $this->assertSame($berkas, $minta['berkas'], 'Berkas dialirkan dari disk, tidak dimuat ke memori.');
        $this->assertNull($minta['isi']);
        $this->assertSame(hash('sha256', 'isi zip karangan'), $minta['kepala']['x-amz-content-sha256']);
        $this->assertSame('application/zip', $minta['kepala']['content-type']);
    }

    public function testAlamatUnduhBertandaTanganBerlakuSebentar(): void
    {
        $waktu  = (int) strtotime('2026-11-25T03:00:00Z');
        $alamat = $this->klien()->alamatUnduh('mac2026.web.id/cadangan-milklife-2026-11-25-0200.zip', 600, $waktu);

        $this->assertStringStartsWith('https://' . self::AKUN . '.r2.cloudflarestorage.com/cadangan-karangan/mac2026.web.id/cadangan-milklife-2026-11-25-0200.zip?X-Amz-Algorithm=AWS4-HMAC-SHA256&X-Amz-Credential=kunciidkarangan0001%2F20261125%2Fauto%2Fs3%2Faws4_request&X-Amz-Date=20261125T030000Z&X-Amz-Expires=600&X-Amz-SignedHeaders=host&X-Amz-Signature=', $alamat);
        $this->assertMatchesRegularExpression('/X-Amz-Signature=[0-9a-f]{64}$/', $alamat);
        $this->assertSame([], $this->permintaan, 'Alamat unduh dibuat tanpa menghubungi R2.');
    }

    /**
     * @return iterable<string, array{int, string, string}>
     */
    public static function jawabanGagal(): iterable
    {
        yield 'kunci salah' => [403, 'SignatureDoesNotMatch', 'Periksa Access Key ID dan Secret Access Key'];
        yield 'kunci tidak dikenal' => [403, 'InvalidAccessKeyId', 'Periksa Access Key ID dan Secret Access Key'];
        yield 'bucket tidak ada' => [404, 'NoSuchBucket', 'Bucket "cadangan-karangan" tidak ada di akun Cloudflare ini'];
        yield 'token bucket lain' => [403, 'AccessDenied', 'Object Read & Write untuk bucket itu'];
        yield 'gangguan R2' => [500, 'InternalError', 'Cloudflare R2 menjawab 500 InternalError'];
    }

    /**
     * @dataProvider jawabanGagal
     */
    public function testJawabanGagalMenjadiPesanYangBisaDiikuti(int $status, string $kode, string $pesan): void
    {
        $this->jawaban = ['status' => $status, 'isi' => '<?xml version="1.0" encoding="UTF-8"?><Error><Code>' . $kode . '</Code><Message>karangan</Message></Error>'];

        try {
            $this->klien()->tulisTeks('mac2026.web.id/uji-sambungan.txt', 'uji');
            $this->fail('Jawaban gagal harus menjadi galat.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString($pesan, $e->getMessage());
        }
    }

    private function klien(): KlienR2
    {
        return new KlienR2(self::AKUN, 'cadangan-karangan', 'kunciidkarangan0001', 'rahasiakarangan0000000000000000000000001');
    }
}
