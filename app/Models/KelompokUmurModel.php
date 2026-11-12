<?php

namespace App\Models;

use App\Libraries\Jenjang;
use CodeIgniter\Model;
use RuntimeException;
use Throwable;

/**
 * Kelompok umur (tabel `kelompok_umur`) beserta jenjang yang boleh ikut
 * (`KelompokUmurJenjangModel`) dan awalan nomor dadanya (`AwalanNomorDadaModel`).
 *
 * Aturan (rancangan 6.4, 6.9; rencana Tahap 2 "Basis data dan model"):
 *
 * - Kelompok umur ditentukan tahun lahir (K36). `tahun_lahir_awal` adalah
 *   tahun lahir paling awal (atlet tertua), `tahun_lahir_akhir` paling akhir
 *   (atlet termuda). Keduanya boleh kosong; kosong berarti tidak dibatasi di
 *   sisi itu (KU 8: lahir 2019 ke atas).
 * - Rentang tahun lahir kelompok umur aktif tidak boleh tumpang tindih.
 *   Kelompok umur yang kedua batasnya masih kosong belum punya rentang, jadi
 *   tidak dibandingkan (pengaturannya dianggap belum lengkap oleh F2-09).
 * - Batas tahun lahir tidak bisa diubah sehingga atlet yang sudah terdaftar
 *   di kelompok umur itu keluar dari rentangnya (F6-07).
 * - Batas tahun lahir, jenjang, biaya individu, dan awalan boleh kosong saat
 *   disimpan supaya admin bisa mengisi bertahap; bila diisi harus sah.
 * - Kelompok umur hanya bisa dihapus bila belum punya nomor lomba dan atlet,
 *   dan semua awalannya belum pernah dipakai. Awalan dan jenjangnya ikut
 *   dihapus dalam satu transaksi.
 *
 * Alur halaman pengaturan: `periksa()` → (galat? tampilkan ulang) →
 * `simpanLengkap()`; hapus: `alasanTidakBisaDihapus()` → `hapusLengkap()`.
 */
class KelompokUmurModel extends Model
{
    /**
     * Tahun lahir paling kecil yang diterima formulir.
     */
    public const TAHUN_LAHIR_TERKECIL = 1950;

    public const NAMA_MAKSIMAL = 50;

    public const URUTAN_MAKSIMAL = 999;

    protected $table            = 'kelompok_umur';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['nama', 'tahun_lahir_awal', 'tahun_lahir_akhir', 'biaya_individu', 'urutan', 'aktif'];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'dibuat_pada';
    protected $updatedField  = 'diubah_pada';

    // ---------------------------------------------------------------
    // Aturan murni (tanpa basis data)
    // ---------------------------------------------------------------

    /**
     * Batas tahun lahir sebagai kalimat pendek.
     *
     *     teksTahunLahir(2016, 2018);  // 'Lahir 2016–2018'
     *     teksTahunLahir(2015, 2015);  // 'Lahir 2015'
     *     teksTahunLahir(2019, null);  // 'Lahir 2019 ke atas'
     *     teksTahunLahir(null, 2010);  // 'Lahir 2010 ke bawah'
     *     teksTahunLahir(null, null);  // ''
     */
    public static function teksTahunLahir(int|string|null $awal, int|string|null $akhir): string
    {
        $awal  = self::tahunAtauNull($awal);
        $akhir = self::tahunAtauNull($akhir);

        return match (true) {
            $awal === null && $akhir === null => '',
            $akhir === null                   => "Lahir {$awal} ke atas",
            $awal === null                    => "Lahir {$akhir} ke bawah",
            $awal === $akhir                  => "Lahir {$awal}",
            default                           => "Lahir {$awal}–{$akhir}",
        };
    }

    /**
     * Apakah dua rentang tahun lahir punya tahun yang sama. Batas kosong
     * berarti tidak terbatas di sisi itu. Rentang yang kedua batasnya kosong
     * belum punya rentang dan tidak bertabrakan dengan apa pun.
     */
    public static function rentangBertabrakan(
        int|string|null $awalA,
        int|string|null $akhirA,
        int|string|null $awalB,
        int|string|null $akhirB,
    ): bool {
        [$awalA, $akhirA, $awalB, $akhirB] = array_map(self::tahunAtauNull(...), [$awalA, $akhirA, $awalB, $akhirB]);

        if (($awalA === null && $akhirA === null) || ($awalB === null && $akhirB === null)) {
            return false;
        }

        return ($awalA ?? PHP_INT_MIN) <= ($akhirB ?? PHP_INT_MAX)
            && ($awalB ?? PHP_INT_MIN) <= ($akhirA ?? PHP_INT_MAX);
    }

    /**
     * Tahun terbesar yang diterima formulir: tahun berjalan.
     */
    public static function tahunLahirTerbesar(): int
    {
        return (int) date('Y');
    }

    private static function tahunAtauNull(int|string|null $nilai): ?int
    {
        if ($nilai === null || $nilai === '') {
            return null;
        }

        return (int) $nilai;
    }

    // ---------------------------------------------------------------
    // Baca
    // ---------------------------------------------------------------

    /**
     * Semua kelompok umur berurutan (`urutan`, lalu `id`), masing-masing
     * dengan tambahan:
     *
     * - `jenjang`: daftar kode jenjang yang boleh ikut,
     * - `awalan`: baris awalan nomor dada berurutan,
     * - `jumlah_nomor_lomba`, `jumlah_atlet`: pemakaian, untuk aturan hapus.
     *
     * @return list<array<string, mixed>>
     */
    public function daftarLengkap(): array
    {
        $jenjang = model(KelompokUmurJenjangModel::class)->kodePerKelompokUmur();
        $awalan  = model(AwalanNomorDadaModel::class)->perKelompokUmur();
        $nomor   = $this->hitungPerKelompokUmur('nomor_lomba');
        $atlet   = $this->hitungPerKelompokUmur('atlet');

        return array_map(static function (array $ku) use ($jenjang, $awalan, $nomor, $atlet): array {
            $id = (int) $ku['id'];

            return $ku + [
                'jenjang'            => $jenjang[$id] ?? [],
                'awalan'             => $awalan[$id] ?? [],
                'jumlah_nomor_lomba' => $nomor[$id] ?? 0,
                'jumlah_atlet'       => $atlet[$id] ?? 0,
            ];
        }, $this->orderBy('urutan')->orderBy('id')->findAll());
    }

    /**
     * Satu kelompok umur dengan `jenjang` dan `awalan` (lihat `daftarLengkap()`),
     * atau `null` bila tidak ada.
     *
     * @return array<string, mixed>|null
     */
    public function cariLengkap(int $id): ?array
    {
        $ku = $this->find($id);

        if ($ku === null) {
            return null;
        }

        return $ku + [
            'jenjang' => model(KelompokUmurJenjangModel::class)->kodeUntuk($id),
            'awalan'  => model(AwalanNomorDadaModel::class)->milikKelompokUmur($id),
        ];
    }

    /**
     * `urutan` untuk kelompok umur baru: satu lebih besar dari yang terbesar.
     */
    public function urutanBerikutnya(): int
    {
        $baris = $this->selectMax('urutan', 'terbesar')->get()->getRowArray();

        return min(self::URUTAN_MAKSIMAL, (int) ($baris['terbesar'] ?? 0) + 1);
    }

    // ---------------------------------------------------------------
    // Periksa dan simpan
    // ---------------------------------------------------------------

    /**
     * Periksa isian formulir kelompok umur.
     *
     * Isian (teks seperti dari formulir): `nama`, `tahun_lahir_awal`,
     * `tahun_lahir_akhir`, `jenjang` (larik kode), `biaya_individu`, `awalan`
     * (misalnya `4, 6`), `urutan`, `aktif` (`'1'` bila dicentang).
     *
     * Hasil: `[data bersih, galat]`. Galat berkunci nama kotak, pesannya
     * menyebut cara memperbaikinya. Bila galat kosong, data bersih siap untuk
     * `simpanLengkap()`: kolom `kelompok_umur` ditambah `jenjang` (list kode)
     * dan `awalan` (list angka, urut sesuai ketikan).
     *
     * @param array<string, mixed> $isian
     *
     * @return array{0: array<string, mixed>, 1: array<string, string>}
     */
    public function periksa(array $isian, ?int $id = null): array
    {
        $teks  = static fn (string $kunci): string => is_string($isian[$kunci] ?? null) ? trim($isian[$kunci]) : '';
        $galat = [];
        $data  = [];

        // Nama
        $data['nama'] = (string) preg_replace('/\s+/u', ' ', $teks('nama'));

        if ($data['nama'] === '') {
            $galat['nama'] = 'Nama kelompok umur belum diisi. Tulis namanya, misalnya KU 10.';
        } elseif (mb_strlen($data['nama']) > self::NAMA_MAKSIMAL) {
            $galat['nama'] = 'Nama kelompok umur terlalu panjang. Persingkat menjadi paling banyak ' . self::NAMA_MAKSIMAL . ' huruf.';
        } else {
            $kembar = $this->builder()->where('nama', $data['nama']);

            if ($id !== null) {
                $kembar->where('id !=', $id);
            }

            if ($kembar->countAllResults() > 0) {
                $galat['nama'] = 'Nama "' . $data['nama'] . '" sudah dipakai kelompok umur lain. Pakai nama lain, misalnya tambahkan keterangan di belakangnya.';
            }
        }

        // Tahun lahir
        $terbesar = self::tahunLahirTerbesar();
        $label    = ['tahun_lahir_awal' => 'Tahun lahir paling awal', 'tahun_lahir_akhir' => 'Tahun lahir paling akhir'];

        foreach ($label as $kunci => $sebutan) {
            $nilai        = $teks($kunci);
            $data[$kunci] = null;

            if ($nilai === '') {
                continue;
            }

            if (preg_match('/^\d{4}$/D', $nilai) !== 1 || (int) $nilai < self::TAHUN_LAHIR_TERKECIL || (int) $nilai > $terbesar) {
                $galat[$kunci] = $sebutan . ' belum benar. Tulis tahun 4 angka antara ' . self::TAHUN_LAHIR_TERKECIL . ' dan ' . $terbesar . ', misalnya 2016, atau kosongkan bila tidak dibatasi.';

                continue;
            }

            $data[$kunci] = (int) $nilai;
        }

        if ($data['tahun_lahir_awal'] !== null && $data['tahun_lahir_akhir'] !== null && $data['tahun_lahir_awal'] > $data['tahun_lahir_akhir']) {
            $galat['tahun_lahir_awal'] = 'Tahun lahir paling awal tidak boleh lebih besar dari tahun lahir paling akhir. Contoh KU 10: lahir 2016 sampai 2018.';
        }

        // Atlet yang sudah terdaftar tidak boleh keluar dari rentangnya (F6-07).
        if ($id !== null && ! isset($galat['tahun_lahir_awal']) && ! isset($galat['tahun_lahir_akhir'])) {
            $rentang = $this->db->table('atlet')
                ->select('MIN(YEAR(tanggal_lahir)) AS tertua, MAX(YEAR(tanggal_lahir)) AS termuda')
                ->where('kelompok_umur_id', $id)
                ->get()->getRowArray();

            if ($rentang['tertua'] !== null && $data['tahun_lahir_awal'] !== null && $data['tahun_lahir_awal'] > (int) $rentang['tertua']) {
                $galat['tahun_lahir_awal'] = "Sudah ada atlet terdaftar di kelompok umur ini yang lahir tahun {$rentang['tertua']}. Tulis {$rentang['tertua']} atau lebih kecil, atau kosongkan.";
            }

            if ($rentang['termuda'] !== null && $data['tahun_lahir_akhir'] !== null && $data['tahun_lahir_akhir'] < (int) $rentang['termuda']) {
                $galat['tahun_lahir_akhir'] = "Sudah ada atlet terdaftar di kelompok umur ini yang lahir tahun {$rentang['termuda']}. Tulis {$rentang['termuda']} atau lebih besar, atau kosongkan.";
            }
        }

        // Jenjang
        $jenjang = $isian['jenjang'] ?? [];
        $jenjang = is_array($jenjang) ? $jenjang : [$jenjang];

        foreach ($jenjang as $kode) {
            if (! Jenjang::sah(is_string($kode) ? strtoupper(trim($kode)) : $kode)) {
                $galat['jenjang'] = 'Pilihan jenjang tidak dikenal. Centang jenjang dari daftar: ' . Jenjang::gabungLabel(Jenjang::kode()) . '.';

                break;
            }
        }

        $data['jenjang'] = Jenjang::rapikan($jenjang);

        // Biaya individu
        $biaya                  = $teks('biaya_individu');
        $data['biaya_individu'] = null;

        if ($biaya !== '') {
            $data['biaya_individu'] = angka_rupiah($biaya);

            if ($data['biaya_individu'] === null) {
                $galat['biaya_individu'] = 'Biaya individu belum benar. Tulis angka rupiah tanpa koma, misalnya 50000, atau kosongkan bila belum ditentukan.';
            }
        }

        // Urutan
        $urutan = $teks('urutan');

        if ($urutan === '') {
            $data['urutan'] = $id === null ? $this->urutanBerikutnya() : (int) ($this->find($id)['urutan'] ?? 0);
        } elseif (preg_match('/^\d{1,3}$/D', $urutan) === 1) {
            $data['urutan'] = (int) $urutan;
        } else {
            $galat['urutan'] = 'Urutan belum benar. Tulis angka bulat 0 sampai ' . self::URUTAN_MAKSIMAL . '; angka kecil tampil lebih dulu.';
        }

        // Aktif
        $data['aktif'] = in_array($isian['aktif'] ?? '', ['1', 1, true, 'on'], true) ? 1 : 0;

        // Awalan nomor dada
        [$data['awalan'], $galatAwalan] = AwalanNomorDadaModel::urai(is_string($isian['awalan'] ?? null) ? $isian['awalan'] : '');

        $galatAwalan ??= $this->periksaAwalan($data['awalan'], $id);

        if ($galatAwalan !== null) {
            $galat['awalan'] = $galatAwalan;
        }

        // Rentang tahun lahir kelompok umur aktif tidak boleh tumpang tindih.
        if ($data['aktif'] === 1 && ! isset($galat['tahun_lahir_awal']) && ! isset($galat['tahun_lahir_akhir'])) {
            $tabrakan = $this->cariTabrakan($data['tahun_lahir_awal'], $data['tahun_lahir_akhir'], $id);

            if ($tabrakan !== null) {
                $galat['tahun_lahir_awal'] = 'Tahun lahir ini bertabrakan dengan ' . $tabrakan['nama'] . ' ('
                    . mb_strtolower(self::teksTahunLahir($tabrakan['tahun_lahir_awal'], $tabrakan['tahun_lahir_akhir']))
                    . '). Satu tahun lahir hanya boleh masuk satu kelompok umur aktif. Ubah tahunnya, atau matikan salah satu kelompok umur.';
            }
        }

        // Ringkasan galat mengikuti urutan kotak di formulir.
        $urutanKotak = array_flip(['nama', 'tahun_lahir_awal', 'tahun_lahir_akhir', 'jenjang', 'biaya_individu', 'awalan', 'urutan']);
        uksort($galat, static fn (string $a, string $b): int => ($urutanKotak[$a] ?? 99) <=> ($urutanKotak[$b] ?? 99));

        return [$data, $galat];
    }

    /**
     * Simpan kelompok umur beserta jenjang dan awalannya dalam satu transaksi.
     * `$data` dari `periksa()` tanpa galat.
     *
     * @param array<string, mixed> $data
     *
     * @return int id kelompok umur
     *
     * @throws RuntimeException Bila gagal; tidak ada yang tersimpan.
     */
    public function simpanLengkap(array $data, ?int $id = null): int
    {
        $kolom = array_intersect_key($data, array_flip($this->allowedFields));

        $this->db->transBegin();

        try {
            if ($id === null) {
                $id = $this->insert($kolom, true);

                if ($id === false) {
                    throw new RuntimeException('Kelompok umur gagal disimpan.');
                }

                $id = (int) $id;
            } elseif ($this->update($id, $kolom) === false) {
                throw new RuntimeException('Kelompok umur gagal disimpan.');
            }

            model(KelompokUmurJenjangModel::class)->sinkronkan($id, $data['jenjang'] ?? []);
            model(AwalanNomorDadaModel::class)->sinkronkan($id, $data['awalan'] ?? []);

            $this->db->transCommit();
        } catch (Throwable $e) {
            $this->db->transRollback();

            throw new RuntimeException($e->getMessage(), 0, $e);
        }

        return $id;
    }

    // ---------------------------------------------------------------
    // Hapus
    // ---------------------------------------------------------------

    /**
     * Kalimat mengapa kelompok umur ini tidak bisa dihapus beserta jalan
     * keluarnya, atau `null` bila boleh dihapus.
     *
     * @param array<string, mixed> $ku Baris kelompok umur (minimal `id` dan `nama`).
     */
    public function alasanTidakBisaDihapus(array $ku): ?string
    {
        $id      = (int) $ku['id'];
        $nama    = (string) $ku['nama'];
        $matikan = ' Matikan saja supaya tidak muncul di pendaftaran.';

        $atlet = $this->db->table('atlet')->where('kelompok_umur_id', $id)->countAllResults();

        if ($atlet > 0) {
            return "{$nama} sudah dipakai {$atlet} atlet, jadi tidak bisa dihapus." . $matikan;
        }

        $terpakai = array_column(
            $this->db->table('awalan_nomor_dada')->select('awalan')
                ->where('kelompok_umur_id', $id)->where('nomor_terakhir >', 0)
                ->orderBy('urutan')->get()->getResultArray(),
            'awalan',
        );

        if ($terpakai !== []) {
            return 'Awalan nomor dada ' . implode(', ', $terpakai) . " milik {$nama} sudah dipakai, jadi kelompok umur ini tidak bisa dihapus." . $matikan;
        }

        $nomor = $this->db->table('nomor_lomba')->where('kelompok_umur_id', $id)->countAllResults();

        if ($nomor > 0) {
            return "{$nama} masih punya {$nomor} nomor lomba, jadi tidak bisa dihapus. Hapus dulu nomor lombanya di halaman Nomor Lomba, atau matikan kelompok umur ini supaya tidak muncul di pendaftaran.";
        }

        return null;
    }

    /**
     * Hapus kelompok umur beserta awalan dan jenjangnya dalam satu transaksi.
     * Pemanggil sudah memastikan `alasanTidakBisaDihapus()` bernilai `null`.
     *
     * @throws RuntimeException Bila gagal; tidak ada yang terhapus.
     */
    public function hapusLengkap(int $id): void
    {
        $this->db->transBegin();

        try {
            if (
                $this->db->table('awalan_nomor_dada')->where('kelompok_umur_id', $id)->delete() === false
                || $this->db->table('kelompok_umur_jenjang')->where('kelompok_umur_id', $id)->delete() === false
                || $this->delete($id) === false
            ) {
                throw new RuntimeException('Kelompok umur gagal dihapus.');
            }

            $this->db->transCommit();
        } catch (Throwable $e) {
            $this->db->transRollback();

            throw new RuntimeException($e->getMessage(), 0, $e);
        }
    }

    // ---------------------------------------------------------------
    // Bantuan
    // ---------------------------------------------------------------

    /**
     * Pesan galat awalan, atau `null` bila sah: awalan kelompok umur lain
     * tidak boleh dipakai, dan awalan sendiri yang sudah dipakai tidak boleh
     * dihapus.
     *
     * @param list<string> $awalan
     */
    private function periksaAwalan(array $awalan, ?int $id): ?string
    {
        $milikLain = model(AwalanNomorDadaModel::class)->dipakaiKelompokUmurLain($awalan, $id);

        if ($milikLain !== []) {
            $kode = (string) array_key_first($milikLain);

            return 'Awalan ' . $kode . ' sudah dipakai ' . $milikLain[$kode] . '. Satu awalan hanya untuk satu kelompok umur; pilih angka lain yang belum dipakai.';
        }

        if ($id === null) {
            return null;
        }

        foreach (model(AwalanNomorDadaModel::class)->milikKelompokUmur($id) as $baris) {
            if ((int) $baris['nomor_terakhir'] > 0 && ! in_array((string) $baris['awalan'], $awalan, true)) {
                return 'Awalan ' . $baris['awalan'] . ' sudah dipakai untuk nomor dada, jadi tidak bisa dihapus. Tulis lagi angka ' . $baris['awalan'] . ' di kotak awalan.';
            }
        }

        return null;
    }

    /**
     * Kelompok umur aktif lain yang rentang tahun lahirnya bertabrakan.
     *
     * @return array<string, mixed>|null
     */
    private function cariTabrakan(?int $awal, ?int $akhir, ?int $kecualiId): ?array
    {
        if ($awal === null && $akhir === null) {
            return null;
        }

        $kueri = $this->builder()->where('aktif', 1)->orderBy('urutan')->orderBy('id');

        if ($kecualiId !== null) {
            $kueri->where('id !=', $kecualiId);
        }

        foreach ($kueri->get()->getResultArray() as $lain) {
            if (self::rentangBertabrakan($awal, $akhir, $lain['tahun_lahir_awal'], $lain['tahun_lahir_akhir'])) {
                return $lain;
            }
        }

        return null;
    }

    /**
     * Banyak baris per kelompok umur di tabel lain: id => jumlah.
     *
     * @return array<int, int>
     */
    private function hitungPerKelompokUmur(string $tabel): array
    {
        $hasil = [];

        foreach ($this->db->table($tabel)->select('kelompok_umur_id, COUNT(*) AS jumlah')->groupBy('kelompok_umur_id')->get()->getResultArray() as $baris) {
            $hasil[(int) $baris['kelompok_umur_id']] = (int) $baris['jumlah'];
        }

        return $hasil;
    }
}
