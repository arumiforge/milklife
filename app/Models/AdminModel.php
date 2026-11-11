<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Akun admin.
 *
 * - Nama pengguna selalu dinormalkan (huruf kecil, tanpa spasi di tepi)
 *   sebelum divalidasi dan disimpan.
 * - Kata sandi diberikan sebagai teks asli ke `insert()`/`update()`, divalidasi,
 *   lalu SELALU diubah menjadi hash oleh callback `hashSandi` sebelum masuk
 *   basis data. Tidak ada jalur lain untuk menyimpan kolom `sandi`.
 */
class AdminModel extends Model
{
    public const SANDI_MINIMAL = 8;

    /**
     * Batas bcrypt (algoritma `PASSWORD_DEFAULT` saat ini) adalah 72 bita.
     */
    public const SANDI_MAKSIMAL = 72;

    protected $table            = 'admin';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields    = ['nama', 'nama_pengguna', 'sandi'];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'dibuat_pada';
    protected $updatedField  = 'diubah_pada';

    protected $validationRules = [
        'nama' => [
            'label' => 'Nama',
            'rules' => ['required', 'max_length[100]'],
        ],
        'nama_pengguna' => [
            'label' => 'Nama pengguna',
            'rules' => [
                'required',
                'min_length[3]',
                'max_length[30]',
                'regex_match[/^[a-z0-9._]+$/]',
                'is_unique[admin.nama_pengguna]',
            ],
        ],
        'sandi' => [
            'label' => 'Kata sandi',
            'rules' => ['required', 'min_length[' . self::SANDI_MINIMAL . ']', 'max_length[' . self::SANDI_MAKSIMAL . ']'],
        ],
    ];

    protected $validationMessages = [
        'nama' => [
            'required'   => 'Nama belum diisi. Tulis nama admin, misalnya nama lengkap Anda.',
            'max_length' => 'Nama terlalu panjang. Tulis paling banyak 100 huruf.',
        ],
        'nama_pengguna' => [
            'required'    => 'Nama pengguna belum diisi.',
            'min_length'  => 'Nama pengguna terlalu pendek. Tulis paling sedikit 3 karakter.',
            'max_length'  => 'Nama pengguna terlalu panjang. Tulis paling banyak 30 karakter.',
            'regex_match' => 'Nama pengguna hanya boleh berisi huruf, angka, titik, dan garis bawah, tanpa spasi.',
            'is_unique'   => 'Nama pengguna ini sudah dipakai. Pilih nama pengguna lain.',
        ],
        'sandi' => [
            'required'   => 'Kata sandi belum diisi.',
            'min_length' => 'Kata sandi terlalu pendek. Buat kata sandi paling sedikit 8 karakter.',
            'max_length' => 'Kata sandi terlalu panjang. Buat kata sandi paling banyak 72 karakter.',
        ],
    ];

    protected $beforeInsert = ['hashSandi'];
    protected $beforeUpdate = ['hashSandi'];

    /**
     * Bentuk baku nama pengguna: huruf kecil, tanpa spasi di tepi.
     */
    public static function normalkanNamaPengguna(string $namaPengguna): string
    {
        return mb_strtolower(trim($namaPengguna));
    }

    /**
     * Cari admin lewat nama pengguna (dinormalkan dulu).
     *
     * @return array<string, mixed>|null
     */
    public function cariNamaPengguna(string $namaPengguna): ?array
    {
        $namaPengguna = self::normalkanNamaPengguna($namaPengguna);

        if ($namaPengguna === '') {
            return null;
        }

        return $this->where('nama_pengguna', $namaPengguna)->first();
    }

    /**
     * Ganti kata sandi seorang admin. Kata sandi diberikan sebagai teks asli
     * dan di-hash oleh model.
     */
    public function simpanSandi(int $id, string $sandi): bool
    {
        return $this->update($id, ['sandi' => $sandi]);
    }

    /**
     * Periksa isian tanpa menyimpan, dengan aturan yang sama seperti saat
     * menyimpan. Hanya kolom yang ada di `$data` yang diperiksa.
     *
     * @param array<string, string> $data
     *
     * @return array<string, string> pesan galat per kolom; kosong bila semua benar
     */
    public function periksaIsian(array $data, bool $abaikanNamaPenggunaDipakai = false): array
    {
        $data   = $this->normalkan($data);
        $aturan = array_intersect_key($this->validationRules, $data);

        if ($abaikanNamaPenggunaDipakai && isset($aturan['nama_pengguna'])) {
            $aturan['nama_pengguna']['rules'] = array_values(array_filter(
                $aturan['nama_pengguna']['rules'],
                static fn (string $aturan): bool => ! str_starts_with($aturan, 'is_unique'),
            ));
        }

        $validasi = service('validation', null, false);
        $validasi->setRules($aturan, $this->validationMessages);
        $validasi->run($data, null, $this->DBGroup);

        return $validasi->getErrors();
    }

    /**
     * Normalkan isian sebelum validasi (dipanggil `insert()` dan `update()`
     * sebelum memeriksa aturan).
     *
     * @param array<string, mixed>|object|null $row
     *
     * @return array<string, mixed>
     */
    protected function transformDataToArray($row, string $type): array
    {
        return $this->normalkan(parent::transformDataToArray($row, $type));
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function normalkan(array $data): array
    {
        if (isset($data['nama_pengguna']) && is_string($data['nama_pengguna'])) {
            $data['nama_pengguna'] = self::normalkanNamaPengguna($data['nama_pengguna']);
        }

        if (isset($data['nama']) && is_string($data['nama'])) {
            $data['nama'] = trim($data['nama']);
        }

        return $data;
    }

    /**
     * Callback sebelum simpan: ubah kata sandi teks asli menjadi hash.
     *
     * @param array{data: array<string, mixed>} $peristiwa
     *
     * @return array{data: array<string, mixed>}
     */
    protected function hashSandi(array $peristiwa): array
    {
        if (isset($peristiwa['data']['sandi'])) {
            $peristiwa['data']['sandi'] = password_hash((string) $peristiwa['data']['sandi'], PASSWORD_DEFAULT);
        }

        return $peristiwa;
    }
}
