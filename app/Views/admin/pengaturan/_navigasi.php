<?php
/**
 * Navigasi pengaturan, ditampilkan di bagian atas isi setiap halaman
 * pengaturan (dan halaman Akun): tautan kembali ke daftar pengaturan dan
 * deretan tautan ke semua halaman pengaturan, dengan halaman sekarang ditandai.
 * Di ponsel deretannya bisa digeser mendatar; halaman tidak ikut bergeser.
 *
 * Cara memakai, di dalam bagian `isi`, sebagai isi pertama `container-fluid`:
 *
 *     <div class="container-fluid px-3 px-lg-4">
 *         <?= $this->include('admin/pengaturan/_navigasi') ?>
 *         …
 *     </div>
 *
 * Tidak perlu data. Halaman yang ditandai ditentukan dari alamat sekarang:
 * alamat halaman itu sendiri atau di bawahnya (`…/kelompok-umur/3/ubah`
 * menandai "Kelompok Umur"). Daftar halaman dari
 * `BerandaPengaturanController::daftarHalaman()`.
 *
 * @var CodeIgniter\View\View $this
 */

use App\Controllers\Admin\Pengaturan\BerandaPengaturanController;

$halamanPengaturan = BerandaPengaturanController::daftarHalaman();
?>
<nav class="navigasi-pengaturan mb-4" aria-label="Navigasi pengaturan">
    <a class="navigasi-pengaturan-kembali tautan-kembali" href="<?= site_url('admin/pengaturan') ?>">
        <i class="bi bi-arrow-left" aria-hidden="true"></i>
        Semua pengaturan
    </a>
    <ul class="navigasi-pengaturan-daftar">
        <?php foreach ($halamanPengaturan as $butir) : ?>
            <?php $aktif = url_is($butir['alamat']) || url_is($butir['alamat'] . '/*'); ?>
            <li>
                <a class="navigasi-pengaturan-tautan" href="<?= site_url($butir['alamat']) ?>"<?= $aktif ? ' aria-current="page"' : '' ?>>
                    <i class="bi bi-<?= esc($butir['ikon'], 'attr') ?>" aria-hidden="true"></i>
                    <?= esc($butir['label']) ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
