<?php
/**
 * Daftar semua halaman pengaturan dan halaman Akun, dikelompokkan, dalam
 * kartu berikon dan berketerangan singkat, tata letak `admin_tw`. Data dari
 * `BerandaPengaturanController::index()`.
 *
 * Tanda lengkap/belum (F2-09) dibaca dari `lengkap` tiap butir: `true` →
 * "Lengkap", `false` → "Belum lengkap" beserta `keterangan_kelengkapan`,
 * `null` → tanpa tanda.
 *
 * @var CodeIgniter\View\View                                   $this
 * @var array<string, list<array<string, mixed>>>               $kelompok judul kelompok => butir (lihat `BerandaPengaturanController::daftarHalaman()`)
 */

// Ikon dari `daftarHalaman()`; tulis juga di sini supaya masuk sprite.
// ikon-sprite: calendar-days door-open users trophy landmark app-window clock download phone circle-help images table award monitor user-cog
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?>Pengaturan<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Pengaturan<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-6 p-4 sm:p-6">
    <p class="text-gray-700">
        Pilih bagian yang ingin diisi atau diubah. Bagian pertama wajib lengkap sebelum pendaftaran bisa dibuka.
    </p>

    <?php foreach ($kelompok as $judulKelompok => $daftarButir) : ?>
        <?php $idKelompok = 'kelompok-' . url_title($judulKelompok, '-', true); ?>
        <section aria-labelledby="<?= esc($idKelompok, 'attr') ?>">
            <h2 id="<?= esc($idKelompok, 'attr') ?>" class="judul-kelompok-pengaturan mb-3 text-xs font-bold uppercase tracking-wide text-gray-600"><?= esc($judulKelompok) ?></h2>
            <ul class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
                <?php foreach ($daftarButir as $butir) : ?>
                    <li class="min-w-0">
                        <a class="kartu-pengaturan flex h-full items-start gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-lembut transition-colors hover:border-primary-300 hover:bg-primary-50 focus:outline-none focus:ring-4 focus:ring-primary-100" href="<?= site_url($butir['alamat']) ?>" data-kunci="<?= esc($butir['kunci'], 'attr') ?>">
                            <span class="kartu-pengaturan-ikon inline-flex size-11 shrink-0 items-center justify-center rounded-xl bg-primary-100 text-primary-700">
                                <?= ikon($butir['ikon'], 'size-6') ?>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="kartu-pengaturan-judul block text-base font-bold text-gray-900"><?= esc($butir['label']) ?></span>
                                <span class="kartu-pengaturan-keterangan mt-1 block text-sm text-gray-600"><?= esc($butir['keterangan']) ?></span>
                                <?php if ($butir['lengkap'] === true) : ?>
                                    <span class="tanda-kelengkapan mt-2 inline-flex items-center gap-1 rounded-md bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800" data-lengkap="1">
                                        <?= ikon('circle-check', 'size-3.5') ?> Lengkap
                                    </span>
                                <?php elseif ($butir['lengkap'] === false) : ?>
                                    <span class="tanda-kelengkapan mt-2 inline-flex items-center gap-1 rounded-md bg-yellow-100 px-2.5 py-0.5 text-xs font-semibold text-yellow-800" data-lengkap="0">
                                        <?= ikon('circle-alert', 'size-3.5') ?> Belum lengkap
                                    </span>
                                    <?php if (($butir['keterangan_kelengkapan'] ?? '') !== '') : ?>
                                        <span class="keterangan-kelengkapan mt-1 block text-sm text-gray-700"><?= esc($butir['keterangan_kelengkapan']) ?></span>
                                    <?php endif ?>
                                <?php endif ?>
                            </span>
                            <?= ikon('chevron-right', 'kartu-pengaturan-panah size-5 shrink-0 self-center text-gray-400') ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endforeach; ?>
</div>
<?= $this->endSection() ?>
