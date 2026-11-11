<?php
/**
 * Daftar semua halaman pengaturan dan halaman Akun, dikelompokkan, dalam
 * kartu berikon dan berketerangan singkat. Data dari
 * `BerandaPengaturanController::index()`.
 *
 * Tanda lengkap/belum (F2-09) dibaca dari `lengkap` tiap butir: `true` →
 * "Lengkap", `false` → "Belum lengkap" beserta `keterangan_kelengkapan`,
 * `null` → tanpa tanda.
 *
 * @var CodeIgniter\View\View                                   $this
 * @var array<string, list<array<string, mixed>>>               $kelompok judul kelompok => butir (lihat `BerandaPengaturanController::daftarHalaman()`)
 */
?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?>Pengaturan – Admin MilkLife<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Pengaturan<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid px-3 px-lg-4">
    <p class="text-body-secondary mb-4 pengaturan-pengantar">
        Pilih bagian yang ingin diisi atau diubah. Bagian pertama wajib lengkap sebelum pendaftaran bisa dibuka.
    </p>

    <?php foreach ($kelompok as $judulKelompok => $daftarButir) : ?>
        <?php $idKelompok = 'kelompok-' . url_title($judulKelompok, '-', true); ?>
        <section class="mb-4" aria-labelledby="<?= esc($idKelompok, 'attr') ?>">
            <h2 id="<?= esc($idKelompok, 'attr') ?>" class="h6 text-uppercase text-body-secondary fw-bold mb-3 judul-kelompok-pengaturan"><?= esc($judulKelompok) ?></h2>
            <ul class="row g-3 list-unstyled mb-0">
                <?php foreach ($daftarButir as $butir) : ?>
                    <li class="col-12 col-md-6 col-xxl-4">
                        <a class="card h-100 kartu-pengaturan" href="<?= site_url($butir['alamat']) ?>" data-kunci="<?= esc($butir['kunci'], 'attr') ?>">
                            <span class="card-body d-flex align-items-start gap-3">
                                <span class="ikon-bulat kartu-pengaturan-ikon flex-shrink-0">
                                    <i class="bi bi-<?= esc($butir['ikon'], 'attr') ?>" aria-hidden="true"></i>
                                </span>
                                <span class="flex-grow-1 min-w-0">
                                    <span class="kartu-pengaturan-judul d-block h5 mb-1"><?= esc($butir['label']) ?></span>
                                    <span class="d-block text-body-secondary small"><?= esc($butir['keterangan']) ?></span>
                                    <?php if ($butir['lengkap'] === true) : ?>
                                        <span class="badge rounded-pill text-bg-success mt-2 tanda-kelengkapan">
                                            <i class="bi bi-check-circle-fill" aria-hidden="true"></i> Lengkap
                                        </span>
                                    <?php elseif ($butir['lengkap'] === false) : ?>
                                        <span class="badge rounded-pill text-bg-warning mt-2 tanda-kelengkapan">
                                            <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i> Belum lengkap
                                        </span>
                                        <?php if (($butir['keterangan_kelengkapan'] ?? '') !== '') : ?>
                                            <span class="d-block small mt-1 keterangan-kelengkapan"><?= esc($butir['keterangan_kelengkapan']) ?></span>
                                        <?php endif ?>
                                    <?php endif ?>
                                </span>
                                <i class="bi bi-chevron-right text-body-secondary align-self-center kartu-pengaturan-panah" aria-hidden="true"></i>
                            </span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endforeach; ?>
</div>
<?= $this->endSection() ?>
