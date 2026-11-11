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
 * Butir baru ditambahkan bersama halamannya; ikon dari Bootstrap Icons
 * (nama tanpa awalan `bi-`). Label pendek supaya muat di menu bawah.
 *
 * @var CodeIgniter\View\View $this
 */

$menuAdmin = [
    ['label' => 'Dasbor', 'ikon' => 'speedometer2', 'alamat' => 'admin/dasbor'],
];
?>
<ul class="menu-admin">
    <?php foreach ($menuAdmin as $butir) : ?>
        <?php $aktif = url_is($butir['alamat']) || url_is($butir['alamat'] . '/*'); ?>
        <li>
            <a class="menu-admin-tautan" href="<?= site_url($butir['alamat']) ?>"<?= $aktif ? ' aria-current="page"' : '' ?>>
                <i class="bi bi-<?= esc($butir['ikon'], 'attr') ?>" aria-hidden="true"></i>
                <span class="menu-admin-label"><?= esc($butir['label']) ?></span>
            </a>
        </li>
    <?php endforeach; ?>
</ul>
