<?php
/**
 * Navigasi pengaturan, ditampilkan di bagian atas isi setiap halaman
 * pengaturan (dan halaman Akun), tata letak `admin_tw`: deretan tautan ke
 * semua halaman pengaturan, dengan halaman sekarang ditandai. Jalan kembali
 * ke daftar pengaturan lewat jejak navigasi (F10-08). Di ponsel deretannya bisa digeser mendatar;
 * halaman tidak ikut bergeser.
 *
 *     <div class="space-y-4 p-4 sm:px-6 sm:py-4">
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
// ikon-sprite: calendar-days door-open users trophy landmark app-window clock download phone circle-help images table award monitor database-backup user-cog
?>
<nav class="navigasi-pengaturan min-w-0" aria-label="Navigasi pengaturan">
    <ul class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1 sm:mx-0 sm:flex-wrap lg:gap-1.5 sm:overflow-visible sm:px-0">
        <?php foreach ($halamanPengaturan as $butir) : ?>
            <?php $aktif = url_is($butir['alamat']) || url_is($butir['alamat'] . '/*'); ?>
            <li class="shrink-0">
                <a class="navigasi-pengaturan-tautan inline-flex min-h-11 items-center gap-2 whitespace-nowrap rounded-lg border px-3 text-sm lg:pointer-fine:min-h-9 lg:pointer-fine:gap-1.5 lg:pointer-fine:px-2.5 font-semibold <?= $aktif ? 'border-primary-700 bg-primary-700 text-white' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50 hover:text-primary-700' ?>" href="<?= site_url($butir['alamat']) ?>"<?= $aktif ? ' aria-current="page"' : '' ?>>
                    <?= ikon($butir['ikon'], 'size-4') ?><?= esc($butir['label']) ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
