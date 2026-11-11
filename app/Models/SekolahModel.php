<?php

namespace App\Models;

use CodeIgniter\Model;
use RuntimeException;
use Throwable;

/**
 * Data referensi sekolah (tabel `sekolah`).
 *
 * Selain pencarian lewat NPSN, model ini memuat logika impor yang dipakai
 * perintah `php spark sekolah:impor`: memeriksa tiap data, merapikannya,
 * lalu menyimpannya per kelompok dalam transaksi.
 */
class SekolahModel extends Model
{
    /**
     * Jenjang yang dikenal.
     */
    public const JENJANG = ['SD', 'SMP', 'SMA'];

    /**
     * Banyak data yang disimpan dalam satu transaksi saat impor.
     */
    public const UKURAN_KELOMPOK = 500;

    /**
     * Banyak contoh data dilewati yang dicatat di hasil impor.
     */
    public const BATAS_CONTOH = 10;

    /**
     * Panjang terpanjang tiap kolom teks, sama dengan migrasi.
     *
     * @var array<string, int>
     */
    public const PANJANG = [
        'nama'           => 150,
        'bentuk'         => 20,
        'status'         => 10,
        'alamat'         => 200,
        'desa_kelurahan' => 60,
        'kecamatan'      => 60,
        'kabupaten'      => 40,
    ];

    protected $table            = 'sekolah';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'npsn',
        'nama',
        'bentuk',
        'jenjang',
        'status',
        'alamat',
        'desa_kelurahan',
        'kecamatan',
        'kabupaten',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'dibuat_pada';
    protected $updatedField  = 'diubah_pada';
    protected $deletedField  = '';

    /**
     * Cari sekolah lewat NPSN.
     *
     * Masukan yang bukan tepat 8 angka langsung ditolak (hasil null) tanpa
     * bertanya ke basis data.
     *
     * @return array<string, mixed>|null
     */
    public function cariNpsn(string $npsn): ?array
    {
        $npsn = trim($npsn);

        if (! self::npsnSah($npsn)) {
            return null;
        }

        return $this->where('npsn', $npsn)->first();
    }

    /**
     * NPSN sah bila tepat 8 angka.
     */
    public static function npsnSah(string $npsn): bool
    {
        return preg_match('/^[0-9]{8}$/D', $npsn) === 1;
    }

    /**
     * Buang awalan "Kab.", "Kabupaten", atau "Kota": "Kab. Blora" → "Blora".
     */
    public static function rapikanKabupaten(string $kabupaten): string
    {
        $kabupaten = trim($kabupaten);

        return trim((string) preg_replace('/^(kabupaten\b|kab\.|kab\b|kota\b)\s*/i', '', $kabupaten));
    }

    /**
     * Periksa dan rapikan satu data sekolah dari berkas impor.
     *
     * Kunci yang dibaca sama dengan `data/sekolah/sekolah.json`. Kunci kode
     * wilayah diabaikan.
     *
     * @return array{0: array<string, string|null>|null, 1: string|null}
     *                                                                    [data rapi, null] bila sah, [null, alasan] bila tidak sah
     */
    public static function periksaBaris(mixed $baris): array
    {
        if (! is_array($baris)) {
            return [null, 'bukan data sekolah (harus berupa { ... } dengan kunci npsn, nama, dan seterusnya)'];
        }

        $teks = static function (string $kunci) use ($baris): ?string {
            $nilai = $baris[$kunci] ?? null;

            if ($nilai === null || is_array($nilai) || is_object($nilai) || is_bool($nilai)) {
                return null;
            }

            $nilai = trim((string) $nilai);

            return $nilai === '' ? null : $nilai;
        };

        $data = [
            'npsn'           => $teks('npsn') ?? '',
            'nama'           => $teks('nama'),
            'bentuk'         => $teks('bentuk'),
            'jenjang'        => strtoupper($teks('jenjang') ?? ''),
            'status'         => $teks('status'),
            'alamat'         => $teks('alamat'),
            'desa_kelurahan' => $teks('desa_kelurahan'),
            'kecamatan'      => $teks('kecamatan'),
            'kabupaten'      => self::rapikanKabupaten($teks('kabupaten_kota') ?? ''),
        ];

        if (! self::npsnSah($data['npsn'])) {
            return [null, 'NPSN "' . $data['npsn'] . '" harus tepat 8 angka'];
        }

        if ($data['nama'] === null) {
            return [null, 'nama sekolah kosong'];
        }

        if (! in_array($data['jenjang'], self::JENJANG, true)) {
            return [null, 'jenjang "' . $data['jenjang'] . '" tidak dikenal (harus SD, SMP, atau SMA)'];
        }

        if ($data['kabupaten'] === '') {
            return [null, 'kabupaten kosong'];
        }

        foreach (self::PANJANG as $kolom => $panjang) {
            if ($data[$kolom] !== null && mb_strlen($data[$kolom]) > $panjang) {
                return [null, 'isian ' . $kolom . ' lebih dari ' . $panjang . ' huruf'];
            }
        }

        return [$data, null];
    }

    /**
     * Impor daftar sekolah ke tabel `sekolah`.
     *
     * Aman diulang: NPSN yang sudah ada diperbarui (nama, alamat, dan
     * seterusnya; `dibuat_pada` tetap), NPSN baru ditambahkan, dan sekolah
     * yang tidak ada di daftar tidak dihapus. Data tidak sah dan NPSN yang
     * muncul lebih dari sekali dilewati dan dihitung; yang pertama dipakai.
     *
     * @param iterable<mixed> $daftar Isi berkas JSON yang sudah diurai
     *
     * @return array{
     *     dibaca: int,
     *     ditambah: int,
     *     diperbarui: int,
     *     dilewati: int,
     *     contoh_dilewati: list<string>,
     *     per_kabupaten: array<string, int>
     * }
     *
     * @throws RuntimeException Bila basis data gagal menyimpan
     */
    public function impor(iterable $daftar, int $ukuranKelompok = self::UKURAN_KELOMPOK): array
    {
        $hasil = [
            'dibaca'          => 0,
            'ditambah'        => 0,
            'diperbarui'      => 0,
            'dilewati'        => 0,
            'contoh_dilewati' => [],
            'per_kabupaten'   => [],
        ];

        $waktu    = date('Y-m-d H:i:s');
        $terlihat = [];
        $kelompok = [];

        foreach ($daftar as $baris) {
            $hasil['dibaca']++;
            $urutan = $hasil['dibaca'];

            [$data, $alasan] = self::periksaBaris($baris);

            if ($data !== null && isset($terlihat[$data['npsn']])) {
                $alasan = 'NPSN ' . $data['npsn'] . ' sudah muncul di data ke-' . $terlihat[$data['npsn']];
                $data   = null;
            }

            if ($data === null) {
                $hasil['dilewati']++;

                if (count($hasil['contoh_dilewati']) < self::BATAS_CONTOH) {
                    $hasil['contoh_dilewati'][] = 'data ke-' . $urutan . ': ' . $alasan;
                }

                continue;
            }

            $terlihat[$data['npsn']] = $urutan;
            $hasil['per_kabupaten'][$data['kabupaten']] = ($hasil['per_kabupaten'][$data['kabupaten']] ?? 0) + 1;

            $data['dibuat_pada'] = $waktu;
            $data['diubah_pada'] = $waktu;
            $kelompok[]          = $data;

            if (count($kelompok) >= $ukuranKelompok) {
                $this->simpanKelompok($kelompok, $hasil);
                $kelompok = [];
            }
        }

        if ($kelompok !== []) {
            $this->simpanKelompok($kelompok, $hasil);
        }

        return $hasil;
    }

    /**
     * Simpan satu kelompok data dalam satu transaksi.
     *
     * NPSN yang sudah ada diperiksa dulu supaya "ditambah" dan "diperbarui"
     * terhitung benar, lalu semuanya ditulis dengan satu
     * `INSERT ... ON DUPLICATE KEY UPDATE`.
     *
     * @param list<array<string, string|null>> $kelompok
     * @param array<string, mixed>             $hasil
     */
    private function simpanKelompok(array $kelompok, array &$hasil): void
    {
        $npsn = array_column($kelompok, 'npsn');

        // Kolom yang diperbarui bila NPSN sudah ada; dibuat_pada tidak termasuk.
        $diperbarui = [...array_diff($this->allowedFields, ['npsn']), $this->updatedField];

        $this->db->transBegin();

        try {
            $ada = $this->db->table($this->table)->select('npsn')->whereIn('npsn', $npsn)->get();

            if ($ada === false) {
                throw new RuntimeException('Gagal membaca NPSN yang sudah tersimpan.');
            }

            $jumlahAda = count($ada->getResultArray());

            $tertulis = $this->db->table($this->table)
                ->setData($kelompok)
                ->updateFields($diperbarui)
                ->upsertBatch(null, null, count($kelompok));

            if ($tertulis === false) {
                throw new RuntimeException('Gagal menyimpan data sekolah.');
            }

            $this->db->transCommit();
        } catch (Throwable $e) {
            $this->db->transRollback();

            throw new RuntimeException($e->getMessage(), 0, $e);
        }

        $hasil['ditambah'] += count($kelompok) - $jumlahAda;
        $hasil['diperbarui'] += $jumlahAda;
    }
}
