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
 */
abstract class UjiBasisData extends CIUnitTestCase
{
    use DatabaseTestTrait;

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
