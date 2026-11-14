<?php

use App\Filters\AdminFilter;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Session;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Aturan murni penjaga halaman admin: tujuan setelah masuk dan lama sesi.
 *
 * @internal
 */
final class AdminFilterTest extends CIUnitTestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function tujuanDiterima(): array
    {
        return [
            'akar admin'    => ['admin'],
            'dasbor'        => ['admin/dasbor'],
            'halaman dalam' => ['admin/pengaturan/kelompok-umur'],
            'dengan kueri'  => ['admin/pendaftaran?halaman=2&cari=SD%20Karangan'],
            'rincian'       => ['admin/pendaftaran/5'],
            'rekap'         => ['admin/rekap/sekolah?status=lunas'],
        ];
    }

    #[DataProvider('tujuanDiterima')]
    public function testTujuanInternalDiterima(string $tujuan): void
    {
        $this->assertSame($tujuan, AdminFilter::tujuanAman($tujuan));
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function tujuanDitolak(): array
    {
        return [
            'bukan teks'         => [['admin/dasbor']],
            'kosong'             => [''],
            'alamat luar'        => ['https://contoh.invalid/admin'],
            'tanpa skema'        => ['//contoh.invalid/admin'],
            'garis miring ganda' => ['admin//contoh.invalid'],
            'garis miring balik' => ['admin/\\contoh.invalid'],
            'naik folder'        => ['admin/../beranda'],
            'luar admin'         => ['beranda'],
            'mirip admin'        => ['administrasi'],
            'awalan garis'       => ['/admin/dasbor'],
            'halaman masuk'      => ['admin/masuk'],
            'halaman keluar'     => ['admin/keluar'],
            'baris baru'         => ["admin/dasbor\r\nLocation: https://contoh.invalid"],
            'terlalu panjang'    => ['admin/' . str_repeat('a', 600)],
            // Bukan halaman: JSON tabel, unduhan, dan berkas.
            'data tabel'         => ['admin/pendaftaran/data?draw=3&start=25'],
            'unduh excel'        => ['admin/atlet/excel?status=lunas'],
            'unduh pdf'          => ['admin/rekap/sekolah/pdf'],
            'backup data'        => ['admin/pendaftaran/ekspor'],
            'foto'               => ['admin/pendaftaran/5/foto/2'],
            'bukti bayar'        => ['admin/pendaftaran/5/bukti-bayar/3'],
        ];
    }

    #[DataProvider('tujuanDitolak')]
    public function testTujuanLainDitolak(mixed $tujuan): void
    {
        $this->assertNull(AdminFilter::tujuanAman($tujuan));
    }

    public function testSesiAdminBerakhirSesuaiKonfigurasi(): void
    {
        // Sesi admin berakhir otomatis setelah 2 jam tanpa mekanisme sendiri:
        // berkas sesi dan kukinya mengikuti `session.expiration`.
        $this->assertSame(7200, config(Session::class)->expiration);
    }
}
