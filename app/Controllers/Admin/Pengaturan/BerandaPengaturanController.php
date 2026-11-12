<?php

namespace App\Controllers\Admin\Pengaturan;

use App\Controllers\BaseController;
use App\Libraries\DataKelengkapan;

/**
 * Halaman daftar pengaturan: `GET /admin/pengaturan`.
 *
 * Menampilkan semua halaman pengaturan dan halaman Akun sebagai kartu. Daftar
 * halaman ditulis sekali di `daftarHalaman()` dan dipakai juga oleh navigasi
 * pengaturan (`admin/pengaturan/_navigasi.php`). Tanda lengkap/belum (F2-09)
 * diisi di `index()` dari `KelengkapanPengaturan`, dicocokkan lewat `kunci`;
 * halaman tanpa butir kelengkapan (Halaman Depan, Jadwal, Unduhan, Kontak,
 * Akun) tidak diberi tanda.
 *
 * ## Pola halaman pengaturan (rencana Tahap 2, "Pola halaman")
 *
 * Contoh pengendali satu formulir, `App\Controllers\Admin\Pengaturan\AcaraController`
 * (rute `GET` → `index`, `POST` → `simpan`):
 *
 *     public function index(): string
 *     {
 *         // 1. Tampilkan formulir berisi nilai tersimpan.
 *         return $this->tampilkan(['nama' => setting('Acara.nama'), ...]);
 *     }
 *
 *     public function simpan(): ResponseInterface|string
 *     {
 *         $isian = [
 *             'nama' => trim((string) $this->request->getPost('nama')),
 *             ...
 *         ];
 *
 *         // 2. Isian salah: tampilkan ulang formulir (status 200, tanpa
 *         //    pengalihan) dengan isian yang diketik dan pesan per kotak.
 *         if (! $this->validateData($isian, ['nama' => [
 *             'rules'  => 'required|max_length[100]',
 *             'errors' => ['required' => 'Nama acara belum diisi. Tulis nama acara, misalnya MilkLife Athletics Challenge.'],
 *         ]])) {
 *             return $this->tampilkan($isian, $this->validator->getErrors());
 *         }
 *
 *         // 3. Berhasil: simpan, lalu alihkan dengan pesan kilat `pesan_sukses`.
 *         setting('Acara.nama', $isian['nama']);
 *
 *         return redirect()->to('admin/pengaturan/acara')->with('pesan_sukses', 'Data acara tersimpan.');
 *     }
 *
 *     private function tampilkan(array $isian, array $galat = []): string
 *     {
 *         return view('admin/pengaturan/acara', ['isian' => $isian, 'galat' => $galat]);
 *     }
 *
 * Tampilannya:
 *
 *     <?= $this->extend('tata_letak/admin') ?>
 *     <?= $this->section('judul') ?>Data Acara – Admin MilkLife<?= $this->endSection() ?>
 *     <?= $this->section('judul_halaman') ?>Data Acara<?= $this->endSection() ?>
 *     <?= $this->section('isi') ?>
 *     <div class="container-fluid px-3 px-lg-4">
 *         <?= $this->include('admin/pengaturan/_navigasi') ?>
 *         <?= view('admin/_galat_isian', ['galat' => $galat], ['saveData' => false]) ?>
 *         <form action="<?= site_url('admin/pengaturan/acara') ?>" method="post" novalidate> <?= csrf_field() ?> … </form>
 *     </div>
 *     <?= $this->endSection() ?>
 *
 * - Pesan kilat (`pesan_sukses`, `pesan_galat`) ditampilkan tata letak lewat
 *   `admin/_pesan.php`; halaman tidak menampilkannya sendiri.
 * - Gagal karena keadaan data (misalnya hapus yang tidak diizinkan): alihkan
 *   dengan `pesan_galat` yang menyebut sebab dan jalan keluarnya.
 * - Hapus: formulir `POST` dengan `csrf_field()` dan
 *   `data-konfirmasi="<kalimat>"`; skrip `aset/js/konfirmasi.js` dimuat di
 *   bagian `skrip`. Contoh di komentar kepala `public/aset/js/konfirmasi.js`.
 * - Uji: `Tests\Support\KirimFormulir` (contoh di komentar kelasnya) dan
 *   `tests/fitur/BerandaPengaturanPolaHalamanTest.php`.
 */
class BerandaPengaturanController extends BaseController
{
    public function index(): string
    {
        $kelengkapan = (new DataKelengkapan())->kelengkapan();
        $halaman     = array_map(static function (array $butir) use ($kelengkapan): array {
            $nilai = $kelengkapan->untukHalaman($butir['kunci']);

            if ($nilai !== null) {
                $butir['lengkap']                = $nilai['lengkap'];
                $butir['keterangan_kelengkapan'] = $nilai['lengkap'] ? '' : $nilai['keterangan'];
            }

            return $butir;
        }, self::daftarHalaman());

        return view('admin/pengaturan/index', [
            'kelompok' => self::kelompokkan($halaman),
        ]);
    }

    /**
     * Semua halaman pengaturan dan halaman Akun, berurutan seperti tampil di
     * daftar dan navigasi.
     *
     * - `kunci`: nama pendek yang tetap, untuk dicocokkan (F2-09, uji).
     * - `label`: judul kartu dan butir navigasi.
     * - `ikon`: nama ikon Bootstrap Icons tanpa awalan `bi-`.
     * - `alamat`: jalur untuk `site_url()`.
     * - `keterangan`: satu kalimat pendek tentang isinya.
     * - `kelompok`: judul kelompok kartu di halaman daftar.
     * - `lengkap`: `null` (tanpa butir kelengkapan), `true`, atau `false`; diisi `index()`.
     * - `keterangan_kelengkapan`: kalimat apa yang belum lengkap; diisi `index()`.
     *
     * @return list<array{kunci: string, label: string, ikon: string, alamat: string, keterangan: string, kelompok: string, lengkap: bool|null, keterangan_kelengkapan: string}>
     */
    public static function daftarHalaman(): array
    {
        $wajib  = 'Wajib diisi sebelum pendaftaran dibuka';
        $depan  = 'Isi halaman depan';
        $sendiri = 'Akun Anda';

        $daftar = [
            ['acara', 'Data Acara', 'calendar-event', 'admin/pengaturan/acara', 'Nama, seri, tahun, tempat, dan tanggal acara.', $wajib],
            ['pendaftaran', 'Pendaftaran', 'door-open', 'admin/pengaturan/pendaftaran', 'Jadwal buka dan tutup, buka manual, tampilan usia, dan batas-batas pendaftaran.', $wajib],
            ['kelompok-umur', 'Kelompok Umur', 'people', 'admin/pengaturan/kelompok-umur', 'Batas tahun lahir, jenjang sekolah yang boleh ikut, biaya, dan awalan nomor dada.', $wajib],
            ['nomor-lomba', 'Nomor Lomba', 'trophy', 'admin/pengaturan/nomor-lomba', 'Nomor individu dan beregu tiap kelompok umur, beserta biaya beregu.', $wajib],
            ['pembayaran', 'Pembayaran', 'bank', 'admin/pengaturan/pembayaran', 'Rekening, nomor WA konfirmasi, dan isi pesan pembayaran.', $wajib],
            ['halaman-depan', 'Halaman Depan', 'window', 'admin/pengaturan/halaman-depan', 'Gambar kepala, logo, judul, teks, dan pesan WA.', $depan],
            ['jadwal', 'Jadwal', 'clock', 'admin/pengaturan/jadwal', 'Sosialisasi, technical meeting, dan jadwal lain.', $depan],
            ['unduhan', 'Unduhan', 'download', 'admin/pengaturan/unduhan', 'Flyer, juknis, dan berkas lain untuk diunduh sekolah.', $depan],
            ['kontak', 'Kontak', 'telephone', 'admin/pengaturan/kontak', 'Narahubung dan media sosial.', $depan],
            ['akun', 'Akun', 'person-gear', 'admin/akun', 'Ganti nama, nama pengguna, dan kata sandi Anda.', $sendiri],
        ];

        return array_map(static fn (array $butir): array => [
            'kunci'                  => $butir[0],
            'label'                  => $butir[1],
            'ikon'                   => $butir[2],
            'alamat'                 => $butir[3],
            'keterangan'             => $butir[4],
            'kelompok'               => $butir[5],
            'lengkap'                => null,
            'keterangan_kelengkapan' => '',
        ], $daftar);
    }

    /**
     * Kelompokkan butir menurut `kelompok`, urutan tetap seperti daftarnya.
     *
     * @param list<array<string, mixed>> $halaman
     *
     * @return array<string, list<array<string, mixed>>>
     */
    private static function kelompokkan(array $halaman): array
    {
        $kelompok = [];

        foreach ($halaman as $butir) {
            $kelompok[$butir['kelompok']][] = $butir;
        }

        return $kelompok;
    }
}
