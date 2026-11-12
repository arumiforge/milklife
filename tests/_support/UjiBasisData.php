<?php

namespace Tests\Support;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

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
}
