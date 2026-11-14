<?php
/**
 * Daftar menu admin, satu-satunya tempat menu ini ditulis.
 *
 * Dipakai dua kali oleh `tata_letak/admin.php`: di menu samping (layar lebar)
 * dan di menu bawah (ponsel). Markupnya sama; bentuknya diatur dari wadahnya
 * di `aset/css/admin.css` (`.menu-samping …` dan `.menu-bawah …`).
 *
 * Menu samping (`$tempatMenu = 'samping'`) punya daftar kedua yang menempel
 * di bagian bawah menu (K102): Pengecekan, lalu Halaman depan (tab baru).
 * Di ponsel keduanya tampil sebagai ikon di kepala halaman, karena menu
 * bawah sudah penuh.
 *
 * Butir aktif ditentukan dari alamat sekarang: alamat butir itu sendiri atau
 * halaman di bawahnya (`admin/pendaftaran/…` mengaktifkan "Pendaftaran").
 * Alamat lain yang ikut mengaktifkan butir ditulis di `aktif_juga`
 * (halaman Akun dibuka dari daftar pengaturan, jadi ikut menandai "Pengaturan").
 * Butir baru ditambahkan bersama halamannya; ikon dari Bootstrap Icons
 * (nama tanpa awalan `bi-`). Label pendek supaya muat di menu bawah.
 *
 * @var CodeIgniter\View\View $this
 * @var string|null           $tempatMenu `samping` atau kosong (menu bawah)
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

// Bagian bawah menu samping (K102).
$menuAkhir = [
    ['label' => 'Pengecekan', 'ikon' => 'clipboard2-pulse', 'alamat' => 'admin/pengecekan'],
];

$butirAktif = static function (array $butir): bool {
    foreach ([$butir['alamat'], ...($butir['aktif_juga'] ?? [])] as $alamat) {
        if (url_is($alamat) || url_is($alamat . '/*')) {
            return true;
        }
    }

    return false;
};
?>
<ul class="menu-admin">
    <?php foreach ($menuAdmin as $butir) : ?>
        <li>
            <a class="menu-admin-tautan" href="<?= site_url($butir['alamat']) ?>"<?= $butirAktif($butir) ? ' aria-current="page"' : '' ?>>
                <i class="bi bi-<?= esc($butir['ikon'], 'attr') ?>" aria-hidden="true"></i>
                <span class="menu-admin-label"><?= esc($butir['label']) ?></span>
            </a>
        </li>
    <?php endforeach; ?>
</ul>
<?php if (($tempatMenu ?? '') === 'samping') : ?>
<ul class="menu-admin menu-admin-akhir">
    <?php foreach ($menuAkhir as $butir) : ?>
        <li>
            <a class="menu-admin-tautan" href="<?= site_url($butir['alamat']) ?>"<?= $butirAktif($butir) ? ' aria-current="page"' : '' ?>>
                <i class="bi bi-<?= esc($butir['ikon'], 'attr') ?>" aria-hidden="true"></i>
                <span class="menu-admin-label"><?= esc($butir['label']) ?></span>
            </a>
        </li>
    <?php endforeach; ?>
    <li>
        <a class="menu-admin-tautan tombol-halaman-depan" href="<?= site_url('/') ?>" target="_blank" rel="noopener" title="Buka halaman depan di tab baru">
            <i class="bi bi-house-door" aria-hidden="true"></i>
            <span class="menu-admin-label">Halaman depan</span>
            <i class="bi bi-box-arrow-up-right ms-auto small" aria-hidden="true"></i>
            <span class="visually-hidden">(tab baru)</span>
        </a>
    </li>
</ul>
<?php endif ?>
