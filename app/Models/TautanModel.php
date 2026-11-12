<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Kontak di halaman depan (tabel `tautan`): narahubung dan media sosial.
 *
 * - `jenis` `narahubung`: `label` nama narahubung, `nilai` nomor WA bentuk
 *   baku `628…` (`App\Libraries\NomorWa`).
 * - `jenis` `media_sosial`: `label` nama akun atau layanan, `nilai` alamat web
 *   `http`/`https` (`rapikanAlamatWeb()`).
 * - Urutan tampil per jenis: `urutan` kecil lebih dulu, lalu yang dibuat lebih dulu.
 *
 * Isian diperiksa di `KontakController`; model ini menyimpan dan membaca,
 * ditambah aturan alamat web yang dipakai juga oleh tautan jadwal.
 */
class TautanModel extends Model
{
    public const NARAHUBUNG   = 'narahubung';
    public const MEDIA_SOSIAL = 'media_sosial';

    /**
     * Jenis yang dikenal (sama dengan ENUM di migrasi) beserta labelnya.
     *
     * @var array<string, string>
     */
    public const JENIS = [
        self::NARAHUBUNG   => 'Narahubung',
        self::MEDIA_SOSIAL => 'Media sosial',
    ];

    /**
     * Urutan terbesar yang bisa diketik admin.
     */
    public const URUTAN_MAKSIMAL = 9999;

    /**
     * Panjang terpanjang tiap kolom teks, sama dengan migrasi.
     *
     * @var array<string, int>
     */
    public const PANJANG = [
        'label' => 100,
        'nilai' => 255,
    ];

    /**
     * Pesan untuk alamat web yang tidak sah, menyebut cara memperbaikinya.
     */
    public const PESAN_ALAMAT_WEB = 'Alamat web belum benar. Salin alamat lengkap dari peramban, misalnya https://www.instagram.com/milklife.';

    /**
     * Ikon Bootstrap Icons menurut nama situs media sosial (tanpa `www.`).
     *
     * @var array<string, string>
     */
    private const IKON_SITUS = [
        'instagram.com' => 'instagram',
        'facebook.com'  => 'facebook',
        'fb.com'        => 'facebook',
        'fb.me'         => 'facebook',
        'youtube.com'   => 'youtube',
        'youtu.be'      => 'youtube',
        'tiktok.com'    => 'tiktok',
        'x.com'         => 'twitter-x',
        'twitter.com'   => 'twitter-x',
        'threads.net'   => 'threads',
        'threads.com'   => 'threads',
        'wa.me'         => 'whatsapp',
        'whatsapp.com'  => 'whatsapp',
        't.me'          => 'telegram',
        'telegram.me'   => 'telegram',
        'linkedin.com'  => 'linkedin',
    ];

    protected $table            = 'tautan';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['jenis', 'label', 'nilai', 'urutan'];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'dibuat_pada';
    protected $updatedField  = 'diubah_pada';
    protected $deletedField  = '';

    /**
     * Semua kontak satu jenis menurut urutan tampil.
     *
     * @return list<array<string, mixed>>
     */
    public function daftarJenis(string $jenis): array
    {
        return $this->where('jenis', $jenis)
            ->orderBy('urutan', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    /**
     * Urutan untuk kontak baru supaya tampil paling akhir di jenisnya: urutan
     * terbesar ditambah satu (paling besar `URUTAN_MAKSIMAL`), atau 1 bila belum ada.
     *
     * @param int|null $kecuali `id` yang tidak ikut dihitung (kontak yang sedang diubah).
     */
    public function urutanBerikutnya(string $jenis, ?int $kecuali = null): int
    {
        $pembangun = $this->builder()->selectMax('urutan', 'terbesar')->where('jenis', $jenis);

        if ($kecuali !== null) {
            $pembangun->where('id !=', $kecuali);
        }

        $baris = $pembangun->get()->getRowArray();

        return min(self::URUTAN_MAKSIMAL, (int) ($baris['terbesar'] ?? 0) + 1);
    }

    /**
     * Apakah jenis dikenal.
     */
    public static function jenisSah(mixed $jenis): bool
    {
        return is_string($jenis) && array_key_exists($jenis, self::JENIS);
    }

    /**
     * Rapikan isian alamat web menjadi alamat `http`/`https` yang sah, atau
     * `null` bila tidak sah.
     *
     *     TautanModel::rapikanAlamatWeb('https://www.instagram.com/milklife');  // tetap
     *     TautanModel::rapikanAlamatWeb('instagram.com/milklife');              // 'https://instagram.com/milklife'
     *     TautanModel::rapikanAlamatWeb('HTTPS://zoom.us/j/123');               // 'https://zoom.us/j/123'
     *     TautanModel::rapikanAlamatWeb('javascript:alert(1)');                 // null
     *     TautanModel::rapikanAlamatWeb('ftp://contoh.id/berkas');              // null
     *     TautanModel::rapikanAlamatWeb('milklife');                            // null (bukan nama situs)
     *
     * Alamat tanpa awalan diberi `https://`, karena admin sering menyalin
     * alamat tanpa awalannya. Alamat harus punya nama situs bertitik
     * (`contoh.id`), tanpa spasi, tanpa nama pengguna dan kata sandi, dan
     * paling panjang 255 karakter.
     */
    public static function rapikanAlamatWeb(?string $isian): ?string
    {
        $alamat = trim((string) $isian);

        if ($alamat === '' || preg_match('/[\s\x00-\x1F\x7F]/u', $alamat) === 1) {
            return null;
        }

        if (str_starts_with($alamat, '//')) {
            $alamat = 'https:' . $alamat;
        } elseif (! str_contains($alamat, '://')) {
            // Awalan lain seperti `javascript:` atau `mailto:` ditolak, bukan
            // diberi `https://`. `contoh.id:8080` tetap dianggap nama situs.
            if (preg_match('/^[a-z][a-z0-9+.\-]*:(?!\d)/i', $alamat) === 1) {
                return null;
            }

            $alamat = 'https://' . $alamat;
        }

        $bagian = parse_url($alamat);

        if ($bagian === false || ! isset($bagian['scheme'], $bagian['host'])) {
            return null;
        }

        $skema = strtolower($bagian['scheme']);
        $situs = $bagian['host'];

        if (! in_array($skema, ['http', 'https'], true) || isset($bagian['user']) || isset($bagian['pass'])) {
            return null;
        }

        // Nama situs: bagian huruf/angka/tanda hubung dipisah titik, minimal dua bagian.
        if (preg_match('/^(?:[a-z0-9](?:[a-z0-9\-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9\-]*[a-z0-9])?$/i', $situs) !== 1) {
            return null;
        }

        $alamat = $skema . substr($alamat, strlen($bagian['scheme']));

        if (filter_var($alamat, FILTER_VALIDATE_URL) === false || strlen($alamat) > self::PANJANG['nilai']) {
            return null;
        }

        return $alamat;
    }

    /**
     * Ikon Bootstrap Icons (tanpa awalan `bi-`) untuk alamat media sosial,
     * menurut nama situsnya; `globe2` untuk situs lain.
     */
    public static function ikonMediaSosial(?string $alamat): string
    {
        $situs = strtolower((string) parse_url((string) $alamat, PHP_URL_HOST));
        $situs = preg_replace('/^(www\.|m\.)/', '', $situs) ?? $situs;

        foreach (self::IKON_SITUS as $nama => $ikon) {
            if ($situs === $nama || str_ends_with($situs, '.' . $nama)) {
                return $ikon;
            }
        }

        return 'globe2';
    }

    /**
     * Alamat tanpa `https://` dan `www.` untuk ditampilkan ringkas,
     * contoh `instagram.com/milklife`.
     */
    public static function alamatRingkas(?string $alamat): string
    {
        $ringkas = preg_replace('#^https?://(www\.)?#i', '', (string) $alamat) ?? (string) $alamat;

        return rtrim($ringkas, '/');
    }
}
