<?php

namespace App\Libraries\Panel;

/**
 * Menu panel admin, satu-satunya tempat butir menu ditulis (F9-14).
 *
 * Dipakai dua tata letak supaya isinya selalu sama:
 * - `tata_letak/_menu_admin_tw.php` (tata letak baru `admin_tw`, Tailwind);
 * - `tata_letak/_menu_admin.php` (tata letak lama `admin`, Bootstrap), selama
 *   masih ada halaman yang belum pindah.
 *
 * Bentuk menu (rencana Tahap 9, "Panel admin"):
 * - sidebar berkelompok: Pendaftaran, Hari lomba, Hasil, Konten, Pengaturan,
 *   lalu daftar akhir (Pengecekan, Lihat halaman depan) di bagian bawahnya
 *   (K102). Di ponsel sidebar menjadi laci yang dibuka tombol menu di kepala
 *   halaman (K131, K133);
 * - menu bawah ponsel: tepat 5 butir urusan pendaftaran bertanda `bawah`
 *   (Dasbor, Pendaftaran, Atlet, Ditolak, Rekap; K132, K133).
 *
 * Butir: `label`, `ikon` (nama ikon Lucide), `alamat`, pilihan `label_pendek`
 * (menu bawah), `bawah`, dan `aktif_juga` (alamat lain yang ikut menandai
 * butir). Butir baru cukup ditambahkan di kelompoknya di sini; ikon barunya
 * ditulis juga di penanda `ikon-sprite` di bawah lalu `npm run bangun`.
 *
 * ikon-sprite: layout-dashboard clipboard-check users user-x school chart-column
 * ikon-sprite: scan-qr-code printer list-checks calendar-clock list-ordered clipboard-pen
 * ikon-sprite: trophy medal monitor-play house circle-help images download settings
 * ikon-sprite: clipboard-list external-link
 */
final class MenuAdmin
{
    /**
     * Butir menu per kelompok, urut seperti di sidebar.
     *
     * @return array<string, list<array{label: string, ikon: string, alamat: string, label_pendek?: string, bawah?: bool, aktif_juga?: list<string>}>>
     */
    public static function kelompok(): array
    {
        return [
            'Pendaftaran' => [
                ['label' => 'Dasbor', 'ikon' => 'layout-dashboard', 'alamat' => 'admin/dasbor', 'bawah' => true],
                ['label' => 'Pendaftaran', 'ikon' => 'clipboard-check', 'alamat' => 'admin/pendaftaran', 'bawah' => true],
                ['label' => 'Atlet', 'ikon' => 'users', 'alamat' => 'admin/atlet', 'bawah' => true],
                // Atlet yang ditolak panitia, terpisah dari atlet terdaftar (K117).
                ['label' => 'Atlet ditolak', 'label_pendek' => 'Ditolak', 'ikon' => 'user-x', 'alamat' => 'admin/atlet-ditolak', 'bawah' => true],
                // Data sekolah (K97).
                ['label' => 'Sekolah', 'ikon' => 'school', 'alamat' => 'admin/sekolah'],
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
            // Hasil lomba (F8-04), klasemen (F8-05), papan videotron (F9-13).
            'Hasil' => [
                ['label' => 'Hasil lomba', 'ikon' => 'trophy', 'alamat' => 'admin/hasil'],
                ['label' => 'Klasemen', 'ikon' => 'medal', 'alamat' => 'admin/klasemen'],
                ['label' => 'Videotron', 'ikon' => 'monitor-play', 'alamat' => 'admin/pengaturan/videotron'],
            ],
            // Isi halaman publik; halamannya tetap di bawah Pengaturan (F2-05, F2-07, F9-04, F9-06).
            'Konten' => [
                ['label' => 'Halaman depan', 'ikon' => 'house', 'alamat' => 'admin/pengaturan/halaman-depan'],
                ['label' => 'Bantuan', 'ikon' => 'circle-help', 'alamat' => 'admin/pengaturan/bantuan'],
                ['label' => 'Galeri', 'ikon' => 'images', 'alamat' => 'admin/pengaturan/galeri'],
                ['label' => 'Unduhan', 'ikon' => 'download', 'alamat' => 'admin/pengaturan/unduhan'],
            ],
            // Halaman Akun dibuka dari daftar pengaturan, jadi ikut menandai Pengaturan.
            'Pengaturan' => [
                ['label' => 'Pengaturan', 'ikon' => 'settings', 'alamat' => 'admin/pengaturan', 'aktif_juga' => ['admin/akun']],
            ],
        ];
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
     * Lima butir menu bawah ponsel (K133).
     *
     * @return list<array{label: string, ikon: string, alamat: string, label_pendek?: string, bawah?: bool, aktif_juga?: list<string>}>
     */
    public static function bawah(): array
    {
        return array_values(array_filter(
            array_merge(...array_values(self::kelompok())),
            static fn (array $butir): bool => ! empty($butir['bawah']),
        ));
    }

    /**
     * Alamat butir yang sedang aktif, atau null.
     *
     * Di rincian pendaftaran dan turunannya, butir aktif mengikuti asal
     * halamannya (`AsalRincian`, K117). Selain itu dipilih butir yang
     * alamatnya (atau salah satu `aktif_juga`) sama dengan alamat sekarang
     * atau induknya; bila beberapa cocok, yang alamatnya paling panjang
     * menang, jadi `admin/pengaturan/galeri` menandai Galeri, bukan
     * Pengaturan.
     */
    public static function alamatAktif(): ?string
    {
        $asal = AsalRincian::menuAktif();

        if ($asal !== null) {
            return $asal;
        }

        $terbaik  = null;
        $panjang  = -1;
        $semua    = [...array_merge(...array_values(self::kelompok())), ...self::akhir()];

        foreach ($semua as $butir) {
            foreach ([$butir['alamat'], ...($butir['aktif_juga'] ?? [])] as $alamat) {
                if ((url_is($alamat) || url_is($alamat . '/*')) && strlen($alamat) > $panjang) {
                    $terbaik = $butir['alamat'];
                    $panjang = strlen($alamat);
                }
            }
        }

        return $terbaik;
    }
}
