<?php
/**
 * Navigasi pengaturan, ditampilkan di bagian atas isi setiap halaman
 * pengaturan (dan halaman Akun), tata letak `admin_tw`: tautan kembali ke
 * daftar pengaturan dan deretan tautan ke semua halaman pengaturan, dengan
 * halaman sekarang ditandai. Di ponsel deretannya bisa digeser mendatar;
 * halaman tidak ikut bergeser.
 *
 *     <div class="space-y-6 p-4 sm:p-6">
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
// Ikon dari `daftarHalaman()`; tulis juga di sini supaya masuk sprite.
// ikon-sprite: calendar-days door-open users trophy landmark app-window clock download phone circle-help images table award monitor user-cog
?>
<nav class="navigasi-pengaturan min-w-0 space-y-2" aria-label="Navigasi pengaturan">
    <a class="navigasi-pengaturan-kembali inline-flex min-h-11 items-center gap-1 text-sm font-semibold text-primary-700 hover:underline" href="<?= site_url('admin/pengaturan') ?>">
        <?= ikon('arrow-left', 'size-4') ?>Semua pengaturan
    </a>
    <ul class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:flex-wrap sm:overflow-visible sm:px-0">
        <?php foreach ($halamanPengaturan as $butir) : ?>
            <?php $aktif = url_is($butir['alamat']) || url_is($butir['alamat'] . '/*'); ?>
            <li class="shrink-0">
                <a class="navigasi-pengaturan-tautan inline-flex min-h-11 items-center gap-2 whitespace-nowrap rounded-lg border px-3 text-sm font-semibold <?= $aktif ? 'border-primary-700 bg-primary-700 text-white' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50 hover:text-primary-700' ?>" href="<?= site_url($butir['alamat']) ?>"<?= $aktif ? ' aria-current="page"' : '' ?>>
                    <?= ikon($butir['ikon'], 'size-4') ?><?= esc($butir['label']) ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
