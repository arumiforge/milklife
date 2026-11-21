<?php

namespace Tests\Support;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Throwable;

/**
 * Kelas dasar uji yang memakai basis data uji (grup `tests`, MySQL).
 *
 * Sebelum setiap uji, semua migrasi digulung balik lalu dijalankan lagi untuk
 * semua namespace (sama dengan `php spark migrate --all`), jadi setiap uji
 * mulai dari tabel kosong, termasuk tabel `settings` dari `codeigniter4/settings`.
 *
 * Pemakaian:
 *
 *     final class SekolahModelTest extends UjiBasisData
 *     {
 *         public function testCariNpsn(): void { ... $this->db ... }
 *     }
 *
 * Kelas turunan boleh mengisi `$seed` untuk menjalankan seeder.
 *
 * Pengaturan (`setting()`) dibaca dari tabel `settings` yang kosong di awal
 * setiap uji, tanpa sisa dari uji sebelumnya (`PengaturanBersih`). Untuk
 * mengirim formulir admin, tambahkan `use KirimFormulir;` (sudah termasuk
 * `FeatureTestTrait`).
 *
 * Sesi admin (`UjiHalaman::SESI_ADMIN`) diterima tanpa akun sungguhan lewat
 * tiruan `SesiAdminUji`; uji yang memeriksa sidik kata sandi sungguhan
 * memanggil `pakaiSesiAdminAsli()`.
 */
abstract class UjiBasisData extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use PengaturanBersih;
    use SesiAdminUji;

    /**
     * Grup sambungan di `app/Config/Database.php`.
     *
     * @var string
     */
    protected $DBGroup = 'tests';

    /**
     * Jalankan migrasi sebelum uji.
     *
     * @var bool
     */
    protected $migrate = true;

    /**
     * Ulangi migrasi untuk setiap uji, supaya data antar-uji tidak tercampur.
     *
     * @var bool
     */
    protected $migrateOnce = false;

    /**
     * Gulung balik semua migrasi dulu sebelum menjalankannya lagi.
     *
     * @var bool
     */
    protected $refresh = true;

    /**
     * null = semua namespace (aplikasi dan pustaka), seperti `migrate --all`.
     *
     * @var list<string>|string|null
     */
    protected $namespace;

    /**
     * Seragamkan baris `information_schema.COLUMNS` MySQL 8 dan MariaDB,
     * supaya uji struktur tabel lulus di keduanya:
     *
     * - `tipe`: lebar tampilan bilangan bulat dibuang (`int(10) unsigned` →
     *   `int unsigned`), kecuali `tinyint(1)` tanpa `unsigned`, yang juga
     *   ditulis MySQL 8.
     * - `awal`: MariaDB menulis nilai awal kosong sebagai teks `NULL`, teks
     *   bertanda kutip (`'menunggu'`), dan `current_timestamp()`; diubah
     *   menjadi null, `menunggu`, dan `CURRENT_TIMESTAMP` seperti MySQL 8.
     *
     * @param list<array<string, mixed>> $baris
     *
     * @return list<array<string, mixed>>
     */
    protected static function bakukanKolom(array $baris): array
    {
        foreach ($baris as $i => $b) {
            if (isset($b['tipe'])) {
                $baris[$i]['tipe'] = preg_replace_callback(
                    '/^(tinyint|smallint|mediumint|int|bigint)\((\d+)\)(.*)$/s',
                    static fn (array $m): string => $m[1] === 'tinyint' && $m[2] === '1' && ! str_contains($m[3], 'unsigned') ? $m[0] : $m[1] . $m[3],
                    (string) $b['tipe'],
                );
            }

            if (array_key_exists('awal', $b) && is_string($b['awal'])) {
                $awal = $b['awal'];

                if ($awal === 'NULL') {
                    $awal = null;
                } elseif (preg_match("/^'(.*)'\$/s", $awal, $m) === 1) {
                    $awal = str_replace("''", "'", $m[1]);
                } elseif (strtolower($awal) === 'current_timestamp()') {
                    $awal = 'CURRENT_TIMESTAMP';
                }

                $baris[$i]['awal'] = $awal;
            }
        }

        return $baris;
    }

    /**
     * Tinggalkan status transaksi gagal di koneksi bersama, seperti sisa
     * kegagalan uji lain atau permintaan sebelumnya di proses yang sama:
     * dalam mode ketat CodeIgniter tidak mengembalikannya saat transaksi baru
     * dimulai, sehingga `transStart()`/`transComplete()` ikut membatalkan
     * (#129). Kode yang memakai `transBegin()`/`transCommit()` tetap menyimpan.
     */
    protected function tinggalkanStatusTransaksiGagal(): void
    {
        $this->db->transStart();

        try {
            $this->db->query('SELECT * FROM tabel_yang_tidak_ada');
        } catch (Throwable) {
            // Kueri gagal memang disengaja.
        }

        $this->db->transComplete();
        $this->assertFalse($this->db->transStatus(), 'Prasyarat: status transaksi gagal tertinggal.');
    }
}
