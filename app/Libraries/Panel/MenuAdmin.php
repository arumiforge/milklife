<?php

namespace App\Libraries\Panel;

use App\Controllers\Admin\Pengaturan\BerandaPengaturanController;

/**
 * Menu panel admin, satu-satunya tempat butir menu ditulis (F9-14, F10-03).
 *
 * Dipakai `tata_letak/_menu_admin_tw.php` (tata letak `admin_tw`, Tailwind);
 * menu lama Bootstrap dilepas di F9-17.
 *
 * Bentuk menu (rencana Tahap 9 dan Tahap 10, "Panel admin"):
 * - sidebar berkelompok: Pendaftaran, Hari lomba, Hasil, Konten, Pengaturan,
 *   lalu daftar akhir (Pengecekan, Lihat halaman depan) di bagian bawahnya
 *   (K102). Kelompok dan submenu bisa dilipat (F10-03,
 *   `aset/js/menu-admin.js`). Di ponsel sidebar menjadi laci yang dibuka
 *   tombol menu di kepala halaman (K131, K133);
 * - menu bawah ponsel: butir bertanda `bawah`, tepat 5 butir: Dasbor,
 *   Pendaftaran, Atlet, Rekap, Pengaturan (K174 butir 3, menggantikan butir
 *   Menu K157). Sidebar tetap dibuka tombol menu di kepala halaman.
 *
 * Butir: `label`, `ikon` (nama ikon Lucide), `alamat`, pilihan `label_pendek`
 * (menu bawah), `bawah`, `aktif_juga` (alamat lain yang ikut menandai
 * butir), dan `anak` (submenu: daftar butir `label` dan `alamat`, tanpa
 * ikon). Butir baru cukup ditambahkan di kelompoknya di sini; ikon barunya
 * ditulis juga di penanda `ikon-sprite` di bawah lalu `npm run bangun`.
 *
 * ikon-sprite: layout-dashboard clipboard-check users user-x school chart-column
 * ikon-sprite: scan-qr-code printer list-checks calendar-clock list-ordered clipboard-pen
 * ikon-sprite: trophy medal monitor-play house circle-help images download settings
 * ikon-sprite: clipboard-list external-link chevron-down menu
 */
final class MenuAdmin
{
    /**
     * Butir menu per kelompok, urut seperti di sidebar.
     *
     * @return array<string, list<array{label: string, ikon: string, alamat: string, label_pendek?: string, bawah?: bool, aktif_juga?: list<string>, anak?: list<array{label: string, alamat: string}>}>>
     */
    public static function kelompok(): array
    {
        $kelompok = [
            'Pendaftaran' => [
                ['label' => 'Dasbor', 'ikon' => 'layout-dashboard', 'alamat' => 'admin/dasbor', 'bawah' => true],
                ['label' => 'Pendaftaran', 'ikon' => 'clipboard-check', 'alamat' => 'admin/pendaftaran', 'bawah' => true, 'anak' => [
                    ['label' => 'Riwayat pendaftaran', 'alamat' => 'admin/riwayat'],
                ]],
                ['label' => 'Atlet', 'ikon' => 'users', 'alamat' => 'admin/atlet', 'bawah' => true, 'anak' => [
                    ['label' => 'Urutkan nomor atlet (BIB)', 'alamat' => 'admin/atlet/nomor-dada'],
                ]],
                // Atlet yang ditolak panitia, terpisah dari atlet terdaftar (K117).
                // Hanya di sidebar, tidak di menu bawah (K157, K174).
                ['label' => 'Atlet ditolak', 'label_pendek' => 'Ditolak', 'ikon' => 'user-x', 'alamat' => 'admin/atlet-ditolak'],
                // Data sekolah (K97).
                ['label' => 'Sekolah', 'ikon' => 'school', 'alamat' => 'admin/sekolah', 'anak' => [
                    ['label' => 'Usulan nama sekolah', 'alamat' => 'admin/sekolah/usulan'],
                    ['label' => 'Tambahkan nama desa', 'alamat' => 'admin/sekolah/nama-desa'],
                ]],
                ['label' => 'Rekap', 'ikon' => 'chart-column', 'alamat' => 'admin/rekap', 'bawah' => true],
            ],
            // Hari lomba (F8-03), jadwal pertandingan (F9-07), seri dan lintasan (F9-08), lembar juri (F9-09).
            'Hari lomba' => [
                ['label' => 'Daftar ulang', 'ikon' => 'scan-qr-code', 'alamat' => 'admin/daftar-ulang'],
                ['label' => 'Cetak BIB', 'ikon' => 'printer', 'alamat' => 'admin/nomor-atlet/cetak', 'aktif_juga' => ['admin/nomor-atlet']],
                ['label' => 'Daftar peserta', 'ikon' => 'list-checks', 'alamat' => 'admin/daftar-peserta'],
                ['label' => 'Jadwal pertandingan', 'ikon' => 'calendar-clock', 'alamat' => 'admin/jadwal-pertandingan'],
                ['label' => 'Seri dan lintasan', 'ikon' => 'list-ordered', 'alamat' => 'admin/seri'],
                ['label' => 'Lembar juri', 'ikon' => 'clipboard-pen', 'alamat' => 'admin/juri'],
            ],
            // Hasil lomba (F8-04), klasemen (F8-05), rekor (F11-06), papan videotron (F9-13).
            'Hasil' => [
                ['label' => 'Hasil lomba', 'ikon' => 'trophy', 'alamat' => 'admin/hasil'],
                ['label' => 'Klasemen', 'ikon' => 'medal', 'alamat' => 'admin/klasemen'],
                ['label' => 'Rekor', 'ikon' => 'timer', 'alamat' => 'admin/rekor'],
                ['label' => 'Videotron', 'ikon' => 'monitor-play', 'alamat' => 'admin/pengaturan/videotron'],
            ],
            // Isi halaman publik; halamannya tetap di bawah Pengaturan (F2-05, F2-07, F9-04, F9-06).
            'Konten' => [
                ['label' => 'Halaman depan', 'ikon' => 'house', 'alamat' => 'admin/pengaturan/halaman-depan'],
                ['label' => 'Bantuan', 'ikon' => 'circle-help', 'alamat' => 'admin/pengaturan/bantuan'],
                ['label' => 'Galeri', 'ikon' => 'images', 'alamat' => 'admin/pengaturan/galeri'],
                ['label' => 'Unduhan', 'ikon' => 'download', 'alamat' => 'admin/pengaturan/unduhan'],
            ],
        ];

        return $kelompok + [
            // Submenu: semua halaman pengaturan dan Akun yang belum ada di kelompok lain (F10-03).
            'Pengaturan' => [
                ['label' => 'Pengaturan', 'ikon' => 'settings', 'alamat' => 'admin/pengaturan', 'bawah' => true, 'anak' => self::anakPengaturan($kelompok)],
            ],
        ];
    }

    /**
     * Halaman dari `BerandaPengaturanController::daftarHalaman()` yang belum
     * menjadi butir di kelompok lain, supaya satu halaman tidak tampil dua
     * kali di sidebar.
     *
     * @param array<string, list<array<string, mixed>>> $kelompok
     *
     * @return list<array{label: string, alamat: string}>
     */
    private static function anakPengaturan(array $kelompok): array
    {
        $sudahAda = array_column(array_merge(...array_values($kelompok)), 'alamat');
        $anak     = [];

        foreach (BerandaPengaturanController::daftarHalaman() as $halaman) {
            if (! in_array($halaman['alamat'], $sudahAda, true)) {
                $anak[] = ['label' => $halaman['label'], 'alamat' => $halaman['alamat']];
            }
        }

        return $anak;
    }

    /**
     * Daftar akhir sidebar (K102). Tautan halaman depan publik (tab baru)
     * ditulis di view karena bentuknya lain.
     *
     * @return list<array{label: string, ikon: string, alamat: string}>
     */
    public static function akhir(): array
    {
        return [
            ['label' => 'Pengecekan', 'ikon' => 'clipboard-list', 'alamat' => 'admin/pengecekan'],
        ];
    }

    /**
     * Butir menu bawah ponsel bertanda `bawah` (susunan K174 butir 3, gaya
     * butir aktif K133): Dasbor, Pendaftaran, Atlet, Rekap, Pengaturan.
     *
     * @return list<array{label: string, ikon: string, alamat: string, label_pendek?: string, bawah?: bool, aktif_juga?: list<string>, anak?: list<array{label: string, alamat: string}>}>
     */
    public static function bawah(): array
    {
        return array_values(array_filter(
            array_merge(...array_values(self::kelompok())),
            static fn (array $butir): bool => ! empty($butir['bawah']),
        ));
    }

    /**
     * Apakah butir ini, atau salah satu anaknya, sedang aktif.
     *
     * @param array{alamat: string, anak?: list<array{label: string, alamat: string}>} $butir
     */
    public static function memuat(array $butir, ?string $aktif): bool
    {
        return $aktif !== null && in_array($aktif, [$butir['alamat'], ...array_column($butir['anak'] ?? [], 'alamat')], true);
    }

    /**
     * Apakah butir menu bawah ini sedang aktif. Pengaturan ikut menyala di
     * semua halaman yang punya pil navigasi Pengaturan (`admin/pengaturan/…`
     * dan Akun), termasuk yang di sidebar ada di kelompok Konten atau Hasil,
     * sejalan dengan jejaknya (`halamanPengaturan()`).
     *
     * @param array{alamat: string, anak?: list<array{label: string, alamat: string}>} $butir
     */
    public static function aktifDiBawah(array $butir, ?string $aktif): bool
    {
        if ($butir['alamat'] === 'admin/pengaturan') {
            return url_is('admin/pengaturan') || self::halamanPengaturan() !== null;
        }

        return self::memuat($butir, $aktif);
    }

    /**
     * Alamat butir (atau anak butir) yang sedang aktif, atau null.
     *
     * Di rincian pendaftaran dan turunannya, butir aktif mengikuti asal
     * halamannya (`AsalRincian`, K117). Selain itu dipilih butir yang
     * alamatnya (atau salah satu `aktif_juga`) sama dengan alamat sekarang
     * atau induknya; bila beberapa cocok, yang alamatnya paling panjang
     * menang, jadi `admin/pengaturan/galeri` menandai Galeri, bukan
     * Pengaturan, dan `admin/sekolah/usulan` menandai anak Usulan, bukan
     * Sekolah.
     */
    public static function alamatAktif(): ?string
    {
        $asal = AsalRincian::menuAktif();

        if ($asal !== null) {
            return $asal;
        }

        $terbaik = null;
        $panjang = -1;

        foreach (self::semuaButir() as $butir) {
            foreach ([$butir['alamat'], ...($butir['aktif_juga'] ?? [])] as $alamat) {
                if ((url_is($alamat) || url_is($alamat . '/*')) && strlen($alamat) > $panjang) {
                    $terbaik = $butir['alamat'];
                    $panjang = strlen($alamat);
                }
            }
        }

        return $terbaik;
    }

    /**
     * Jejak navigasi bawaan untuk halaman yang tidak menulis jejaknya
     * sendiri (F10-03), untuk `admin/_jejak_tw`: butir induk (bila halaman
     * ini anak submenu), butir menu yang aktif, lalu judul halaman. Bila
     * halaman ini halaman butir itu sendiri, butir terakhir memakai label
     * butir menu (atau label pil Pengaturan), bukan judul halaman. Contoh: `admin/pengaturan/jadwal/3/ubah` menjadi Pengaturan ›
     * Agenda › Ubah Agenda. Halaman pengaturan selalu lewat Pengaturan
     * (`halamanPengaturan()`). Butir "Dasbor" di depan ditambahkan
     * `admin/_jejak_tw`.
     *
     * @param string $judul Judul halaman, teks biasa.
     *
     * @return list<array{label: string, alamat?: string}>
     */
    public static function jejak(string $judul): array
    {
        [$jejak, $kini] = self::halamanPengaturan() ?? self::rantaiMenu();

        if ($kini !== null && ! url_is($kini['alamat'])) {
            $jejak[] = $kini;
        } elseif ($kini !== null) {
            // Halaman butir itu sendiri: label menu atau pil, bukan judul halaman,
            // supaya jejak sama dengan sidebar dan pil navigasi (K174 butir 2).
            $judul = $kini['label'];
        }

        if ($judul !== '' && $jejak !== [] && mb_strtolower(end($jejak)['label']) === mb_strtolower($judul)) {
            array_pop($jejak);
        }

        if ($judul !== '') {
            $jejak[] = ['label' => $judul];
        }

        return $jejak;
    }

    /**
     * Rantai menu untuk jejak: butir induk (bila halaman ini anak submenu)
     * dan butir yang aktif (`kini`), atau `kini` null bila tidak ada butir
     * yang aktif.
     *
     * @return array{0: list<array{label: string, alamat: string}>, 1: array{label: string, alamat: string}|null}
     */
    private static function rantaiMenu(): array
    {
        $aktif = self::alamatAktif();

        foreach ([...array_merge(...array_values(self::kelompok())), ...self::akhir()] as $butir) {
            if (! self::memuat($butir, $aktif)) {
                continue;
            }

            $jejak = [];
            $kini  = ['label' => $butir['label'], 'alamat' => $butir['alamat']];

            foreach ($butir['anak'] ?? [] as $anak) {
                if ($anak['alamat'] === $aktif) {
                    $jejak[] = $kini;
                    $kini    = ['label' => $anak['label'], 'alamat' => $anak['alamat']];
                }
            }

            return [$jejak, $kini];
        }

        return [[], null];
    }

    /**
     * Rantai jejak halaman yang punya pil navigasi Pengaturan
     * (`admin/pengaturan/_navigasi`): selalu Pengaturan lalu label pilnya,
     * juga untuk halaman yang di sidebar ada di kelompok lain (Konten,
     * Videotron, Akun), supaya jejak sejalan dengan pil yang ditandai
     * (F11-10, K174 butir 2). Null bila alamat ini bukan halaman pengaturan.
     *
     * @return array{0: list<array{label: string, alamat: string}>, 1: array{label: string, alamat: string}}|null
     */
    private static function halamanPengaturan(): ?array
    {
        $kini = null;

        foreach (BerandaPengaturanController::daftarHalaman() as $halaman) {
            if ((url_is($halaman['alamat']) || url_is($halaman['alamat'] . '/*')) && strlen($halaman['alamat']) > strlen($kini['alamat'] ?? '')) {
                $kini = ['label' => $halaman['label'], 'alamat' => $halaman['alamat']];
            }
        }

        return $kini === null ? null : [[['label' => 'Pengaturan', 'alamat' => 'admin/pengaturan']], $kini];
    }

    /**
     * Semua butir dan anaknya, lalu daftar akhir, dalam satu daftar datar.
     *
     * @return list<array{label: string, alamat: string, aktif_juga?: list<string>}>
     */
    private static function semuaButir(): array
    {
        $semua = [];

        foreach ([...array_merge(...array_values(self::kelompok())), ...self::akhir()] as $butir) {
            $semua[] = $butir;

            foreach ($butir['anak'] ?? [] as $anak) {
                $semua[] = $anak;
            }
        }

        return $semua;
    }
}
