<?php
/**
 * Dasbor admin: keadaan pendaftaran, daftar centang kelengkapan pengaturan
 * (F2-09), keadaan kosong pendaftaran, dan gambar kepala halaman depan.
 * Data dari `DasborController::index()`. Angka ringkasan menyusul di Tahap 6.
 *
 * @var CodeIgniter\View\View                                                                             $this
 * @var list<array{kunci: string, label: string, lengkap: bool, keterangan: string, alamat: string}>     $butir
 * @var int                                                                                               $jumlahLengkap
 * @var bool                                                                                              $semuaLengkap
 * @var string                                                                                            $keadaan       salah satu konstanta BukaTutupPendaftaran
 * @var string                                                                                            $labelKeadaan
 * @var string                                                                                            $kalimat
 * @var bool                                                                                              $lewatManual
 * @var string                                                                                            $jadwal        kosong bila belum diisi
 * @var string                                                                                            $bukaManual
 * @var string                                                                                            $gambarKepala  jalur unggahan, boleh kosong
 */

use App\Libraries\BukaTutupPendaftaran;

$warnaKeadaan = [
    BukaTutupPendaftaran::DISIAPKAN    => ['text-bg-secondary', 'hourglass-split'],
    BukaTutupPendaftaran::BELUM_DIBUKA => ['text-bg-info', 'calendar-event'],
    BukaTutupPendaftaran::TERBUKA      => ['text-bg-success', 'door-open-fill'],
    BukaTutupPendaftaran::DITUTUP      => ['text-bg-dark', 'door-closed-fill'],
][$keadaan];
$jumlahButir = count($butir);
$persen      = $jumlahButir > 0 ? (int) round($jumlahLengkap / $jumlahButir * 100) : 0;
?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?>Dasbor<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Dasbor<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid px-3 px-lg-4">
    <div class="row g-4 mb-4">
        <section class="col-12 col-xl-5" aria-labelledby="judul-keadaan" id="keadaan-pendaftaran" data-keadaan="<?= esc($keadaan, 'attr') ?>">
            <div class="card h-100">
                <div class="card-body p-4">
                    <h2 id="judul-keadaan" class="h5 mb-3">Keadaan pendaftaran</h2>
                    <p class="mb-2">
                        <span class="badge rounded-pill <?= $warnaKeadaan[0] ?> fs-6 lencana-keadaan">
                            <i class="bi bi-<?= $warnaKeadaan[1] ?>" aria-hidden="true"></i> <?= esc($labelKeadaan) ?>
                        </span>
                    </p>
                    <p class="fs-5 mb-3 kalimat-keadaan"><?= esc($kalimat) ?></p>

                    <?php if ($keadaan === BukaTutupPendaftaran::DISIAPKAN) : ?>
                        <p class="text-body-secondary">
                            Pendaftaran baru bisa dibuka setelah semua butir kelengkapan pengaturan bercentang.
                            Sampai saat itu, sekolah melihat keterangan "Pendaftaran sedang disiapkan".
                        </p>
                    <?php elseif ($lewatManual) : ?>
                        <p class="text-body-secondary">
                            Pendaftaran terbuka karena dibuka manual oleh admin, di luar jadwal.
                        </p>
                    <?php endif ?>

                    <dl class="row small mb-3">
                        <dt class="col-sm-4 fw-semibold">Jadwal</dt>
                        <dd class="col-sm-8"><?= $jadwal !== '' ? esc($jadwal) : '<span class="text-body-secondary">Belum diisi</span>' ?></dd>
                        <dt class="col-sm-4 fw-semibold">Buka manual</dt>
                        <dd class="col-sm-8 mb-0"><?= esc($bukaManual) ?></dd>
                    </dl>

                    <a class="btn btn-outline-primary" href="<?= site_url('admin/pengaturan/pendaftaran') ?>">
                        <i class="bi bi-calendar-week" aria-hidden="true"></i> Atur jadwal pendaftaran
                    </a>
                </div>
            </div>
        </section>

        <section class="col-12 col-xl-7" aria-labelledby="judul-kelengkapan" id="kelengkapan-pengaturan">
            <div class="card h-100">
                <div class="card-body p-4">
                    <h2 id="judul-kelengkapan" class="h5 mb-1">Kelengkapan pengaturan</h2>
                    <p class="text-body-secondary mb-2 ringkasan-kelengkapan">
                        <?php if ($semuaLengkap) : ?>
                            Semua pengaturan wajib sudah lengkap.
                        <?php else : ?>
                            <?= $jumlahLengkap ?> dari <?= $jumlahButir ?> sudah lengkap.
                        <?php endif ?>
                    </p>
                    <?php if (! $semuaLengkap) : ?>
                        <p class="small mb-2">
                            <strong>Langkah berikutnya:</strong> buka butir yang belum bercentang dan lengkapi isinya supaya pendaftaran bisa dibuka.
                        </p>
                    <?php endif ?>
                    <div class="progress mb-3" role="progressbar" aria-label="Kelengkapan pengaturan" aria-valuenow="<?= $persen ?>" aria-valuemin="0" aria-valuemax="100" style="height: .5rem">
                        <div class="progress-bar <?= $semuaLengkap ? 'bg-success' : '' ?>" style="width: <?= $persen ?>%"></div>
                    </div>

                    <ul class="list-unstyled mb-0 daftar-kelengkapan">
                        <?php foreach ($butir as $satu) : ?>
                            <li class="d-flex gap-3 py-2 border-top" data-kunci="<?= esc($satu['kunci'], 'attr') ?>" data-lengkap="<?= $satu['lengkap'] ? 'ya' : 'tidak' ?>">
                                <?php if ($satu['lengkap']) : ?>
                                    <i class="bi bi-check-circle-fill text-success fs-5 flex-shrink-0" aria-hidden="true"></i>
                                <?php else : ?>
                                    <i class="bi bi-exclamation-circle-fill text-warning fs-5 flex-shrink-0" aria-hidden="true"></i>
                                <?php endif ?>
                                <div class="flex-grow-1 min-w-0">
                                    <a class="fw-semibold" href="<?= site_url($satu['alamat']) ?>"><?= esc($satu['label']) ?></a>
                                    <span class="visually-hidden">: <?= $satu['lengkap'] ? 'lengkap' : 'belum lengkap' ?>.</span>
                                    <div class="small <?= $satu['lengkap'] ? 'text-body-secondary' : '' ?> keterangan-kelengkapan"><?= esc($satu['keterangan']) ?></div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </section>
    </div>

    <div class="row g-4">
        <section class="col-12 col-xl-7" aria-labelledby="judul-kosong">
            <div class="card kartu-kosong h-100">
                <div class="card-body text-center px-3 py-5">
                    <span class="ikon-bulat mb-3"><i class="bi bi-inbox" aria-hidden="true"></i></span>
                    <h2 id="judul-kosong" class="h4">Belum ada pendaftaran</h2>
                    <p class="text-body-secondary mb-0 mx-auto">
                        Pendaftaran dari sekolah akan muncul di sini setelah formulir pendaftaran dibuka.
                        Untuk sekarang belum ada yang perlu diperiksa.
                    </p>
                </div>
            </div>
        </section>

        <section class="col-12 col-xl-5" aria-labelledby="judul-gambar">
            <div class="card h-100">
                <div class="card-body p-4">
                    <h2 id="judul-gambar" class="h5 mb-3">Gambar kepala halaman depan</h2>
                    <?php if ($gambarKepala !== '') : ?>
                        <img class="img-fluid rounded w-100" src="<?= esc(base_url($gambarKepala), 'attr') ?>" alt="Gambar kepala halaman depan">
                    <?php else : ?>
                        <?= view('tata_letak/_tanpa_gambar', ['rasio' => '21x9', 'label' => 'Gambar kepala halaman depan'], ['saveData' => false]) ?>
                    <?php endif ?>
                    <p class="text-body-secondary small mt-3 mb-0">
                        Gambar kepala dan logo diatur di
                        <a href="<?= site_url('admin/pengaturan/halaman-depan') ?>">Pengaturan Halaman Depan</a>.
                    </p>
                </div>
            </div>
        </section>
    </div>
</div>
<?= $this->endSection() ?>
