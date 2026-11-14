<?php
/**
 * Daftar menu admin, satu-satunya tempat menu ini ditulis.
 *
 * Dipakai dua kali oleh `tata_letak/admin.php`: di menu samping (layar lebar)
 * dan di menu bawah (ponsel). Markupnya sama; bentuknya diatur dari wadahnya
 * di `aset/css/admin.css` (`.menu-samping …` dan `.menu-bawah …`).
 *
 * Butir aktif ditentukan dari alamat sekarang: alamat butir itu sendiri atau
 * halaman di bawahnya (`admin/pendaftaran/…` mengaktifkan "Pendaftaran").
 * Alamat lain yang ikut mengaktifkan butir ditulis di `aktif_juga`
 * (halaman Akun dibuka dari daftar pengaturan, jadi ikut menandai "Pengaturan").
 * Butir baru ditambahkan bersama halamannya; ikon dari Bootstrap Icons
 * (nama tanpa awalan `bi-`). Label pendek supaya muat di menu bawah.
 *
 * @var CodeIgniter\View\View $this
 */

$menuAdmin = [
    ['label' => 'Dasbor', 'ikon' => 'speedometer2', 'alamat' => 'admin/dasbor'],
    // Panel data (Tahap 6, F6-01).
    ['label' => 'Pendaftaran', 'ikon' => 'clipboard-check', 'alamat' => 'admin/pendaftaran'],
    ['label' => 'Atlet', 'ikon' => 'people', 'alamat' => 'admin/atlet'],
    ['label' => 'Rekap', 'ikon' => 'bar-chart-line', 'alamat' => 'admin/rekap'],
    // Data sekolah (K97).
    ['label' => 'Sekolah', 'ikon' => 'building', 'alamat' => 'admin/sekolah'],
    ['label' => 'Pengaturan', 'ikon' => 'gear', 'alamat' => 'admin/pengaturan', 'aktif_juga' => ['admin/akun']],
];
?>
<ul class="menu-admin">
    <?php foreach ($menuAdmin as $butir) : ?>
        <?php
        $aktif = false;

        foreach ([$butir['alamat'], ...($butir['aktif_juga'] ?? [])] as $alamat) {
            $aktif = $aktif || url_is($alamat) || url_is($alamat . '/*');
        }
        ?>
        <li>
            <a class="menu-admin-tautan" href="<?= site_url($butir['alamat']) ?>"<?= $aktif ? ' aria-current="page"' : '' ?>>
                <i class="bi bi-<?= esc($butir['ikon'], 'attr') ?>" aria-hidden="true"></i>
                <span class="menu-admin-label"><?= esc($butir['label']) ?></span>
            </a>
        </li>
    <?php endforeach; ?>
</ul>
