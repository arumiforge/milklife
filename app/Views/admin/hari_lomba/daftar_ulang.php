<?php
/**
 * Daftar ulang hari lomba (F8-03). Data dari `DaftarUlangController::index()`.
 *
 * Pemindai kode QR (`aset/js/daftar-ulang.js`) mengisi kotak `id` dengan isi
 * kode QR lalu mengirim formulir pencarian; peramban tanpa `BarcodeDetector`
 * hanya menampilkan isian ID.
 *
 * @var CodeIgniter\View\View $this
 * @var string                $masukan isian sebelumnya bila tidak ditemukan
 * @var string|null           $galat
 * @var array{pendaftaran: array<string, mixed>, id: int, atlet: list<array<string, mixed>>, digabung_dari: string|null}|null $hasil
 */

use App\Libraries\DataPendaftaran;

$p          = $hasil['pendaftaran'] ?? null;
$atlet      = $hasil['atlet'] ?? [];
$lunas      = $p !== null && $p['status'] === 'lunas';
$jumlahHadir = count(array_filter($atlet, static fn (array $a): bool => $a['hadir_pada'] !== null));
?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?>Daftar Ulang<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid px-3 px-lg-4">
    <?= view('admin/hari_lomba/_tab', ['aktif' => 'admin/daftar-ulang'], ['saveData' => false]) ?>

    <section class="card mb-4" aria-labelledby="judul-cari" id="kotak-pindai">
        <div class="card-body">
            <h2 class="h5" id="judul-cari">Pindai kode QR bukti pendaftaran</h2>

            <div id="pemindai" class="mb-3" hidden>
                <button type="button" class="btn btn-primary btn-lg w-100 mb-2" id="tombol-pindai">
                    <i class="bi bi-camera" aria-hidden="true"></i> Pindai dengan kamera
                </button>
                <div id="bingkai-kamera" class="ratio ratio-4x3 bg-dark rounded overflow-hidden mb-2" hidden>
                    <video id="video-pindai" muted playsinline class="object-fit-cover"></video>
                </div>
                <button type="button" class="btn btn-outline-secondary w-100" id="tombol-berhenti" hidden>Berhenti memindai</button>
                <p class="small text-body-secondary mt-2 mb-0" id="status-pindai" role="status" aria-live="polite"></p>
            </div>

            <p class="small text-body-secondary" id="tanpa-pemindai">
                Peramban ini tidak bisa memindai kode QR di halaman ini (paling mudah memakai Chrome di Android).
                Ketik ID pendaftaran di bawah, atau pindai kode QR dengan aplikasi kamera ponsel: halaman bukti akan menampilkan tombol "Buka daftar ulang".
            </p>

            <form action="<?= site_url('admin/daftar-ulang') ?>" method="get" id="formulir-cari" class="d-flex gap-2">
                <label class="visually-hidden" for="isian-id">ID pendaftaran</label>
                <input class="form-control" type="text" id="isian-id" name="id" value="<?= esc($masukan, 'attr') ?>" placeholder="Contoh: MAC2A0000126"
                    autocomplete="off" autocapitalize="characters" spellcheck="false" required>
                <button type="submit" class="btn btn-outline-primary text-nowrap"><i class="bi bi-search" aria-hidden="true"></i> Cari</button>
            </form>
        </div>
    </section>

    <?php if ($galat !== null) : ?>
        <div class="alert alert-danger" role="alert" id="galat-cari"><?= esc($galat) ?></div>
    <?php endif ?>

    <?php if ($p !== null) : ?>
        <section class="card" aria-labelledby="judul-sekolah" id="hasil-daftar-ulang">
            <div class="card-body">
                <?php if ($hasil['digabung_dari'] !== null) : ?>
                    <div class="alert alert-info small" id="keterangan-gabung">
                        ID <?= esc($hasil['digabung_dari']) ?> sudah digabung ke <?= esc($p['id_pendaftaran']) ?> (pendaftaran sekolah yang sama). Yang tampil di bawah ini pendaftaran gabungannya.
                    </div>
                <?php endif ?>

                <h2 class="h4 mb-1" id="judul-sekolah"><?= esc($p['nama_sekolah']) ?></h2>
                <p class="text-body-secondary mb-2">
                    <a href="<?= site_url('admin/pendaftaran/' . $hasil['id']) ?>"><?= esc($p['id_pendaftaran']) ?></a>
                    <?php if ($p['kabupaten'] !== null && $p['kabupaten'] !== '') : ?> · <?= esc($p['kabupaten']) ?><?php endif ?>
                    <?php if ($p['npsn'] !== null) : ?> · NPSN <?= esc($p['npsn']) ?><?php endif ?>
                </p>

                <p class="mb-3" id="status-pendaftaran">
                    <?php if ($p['ditolak_semua']) : ?>
                        <span class="badge rounded-pill text-bg-secondary fs-6"><i class="bi bi-person-x" aria-hidden="true"></i> <?= esc(DataPendaftaran::LABEL_DITOLAK_SEMUA) ?></span>
                    <?php elseif ($lunas) : ?>
                        <span class="badge rounded-pill text-bg-success fs-6"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Lunas</span>
                    <?php else : ?>
                        <span class="badge rounded-pill text-bg-warning fs-6"><i class="bi bi-hourglass-split" aria-hidden="true"></i> Menunggu</span>
                    <?php endif ?>
                    <?php if ($atlet !== []) : ?>
                        <span class="ms-2" id="jumlah-hadir"><strong><?= $jumlahHadir ?></strong> dari <?= count($atlet) ?> atlet hadir</span>
                    <?php endif ?>
                </p>

                <?php if (! $lunas && ! $p['ditolak_semua']) : ?>
                    <div class="alert alert-warning" id="peringatan-menunggu">
                        Pendaftaran ini belum Lunas, jadi atletnya belum punya nomor atlet (BIB) dan belum bisa ditandai hadir.
                        Periksa pembayarannya di <a href="<?= site_url('admin/pendaftaran/' . $hasil['id']) ?>">rincian pendaftaran</a>.
                    </div>
                <?php elseif ($p['ditolak_semua']) : ?>
                    <div class="alert alert-secondary" id="peringatan-ditolak">Semua atlet pendaftaran ini ditolak panitia, jadi tidak ada atlet yang ikut lomba.</div>
                <?php endif ?>

                <?php if ($lunas && $atlet !== [] && $jumlahHadir < count($atlet)) : ?>
                    <form action="<?= site_url('admin/daftar-ulang/' . $hasil['id'] . '/hadir') ?>" method="post" class="mb-3" id="formulir-hadir-semua">
                        <?= csrf_field() ?>
                        <input type="hidden" name="semua" value="1">
                        <button type="submit" class="btn btn-success btn-lg w-100">
                            <i class="bi bi-check2-all" aria-hidden="true"></i> Tandai semua atlet hadir (<?= count($atlet) - $jumlahHadir ?>)
                        </button>
                    </form>
                <?php endif ?>

                <ul class="list-group" id="daftar-atlet">
                    <?php foreach ($atlet as $a) : ?>
                        <?php $hadir = $a['hadir_pada'] !== null; ?>
                        <li class="list-group-item d-flex flex-wrap gap-2 align-items-center<?= $hadir ? ' list-group-item-success' : '' ?>" data-atlet="<?= $a['id'] ?>">
                            <div class="fs-3 fw-bold font-monospace me-1 min-w-bib"><?= $a['nomor_dada'] !== null ? esc($a['nomor_dada']) : '<span class="text-body-secondary fs-6 fw-normal">Belum ada</span>' ?></div>
                            <div class="flex-grow-1 min-w-12r">
                                <div class="fw-semibold"><?= esc($a['nama']) ?></div>
                                <div class="small text-body-secondary"><?= esc($a['kelompok_umur']) ?> · <?= esc(implode(', ', $a['nomor_lomba'])) ?></div>
                                <?php if ($hadir) : ?>
                                    <div class="small text-success-emphasis"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Hadir <?= esc(tanggal_id($a['hadir_pada'], true)) ?><?= $a['hadir_oleh'] !== null ? ' oleh ' . esc($a['hadir_oleh']) : '' ?></div>
                                <?php endif ?>
                            </div>
                            <?php if ($lunas && ! $hadir) : ?>
                                <form action="<?= site_url('admin/daftar-ulang/' . $hasil['id'] . '/hadir') ?>" method="post" class="ms-auto">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="atlet[]" value="<?= $a['id'] ?>">
                                    <button type="submit" class="btn btn-success"><i class="bi bi-check2" aria-hidden="true"></i> Tandai hadir</button>
                                </form>
                            <?php elseif ($hadir) : ?>
                                <form action="<?= site_url('admin/daftar-ulang/' . $hasil['id'] . '/batal-hadir') ?>" method="post" class="ms-auto"
                                    data-konfirmasi="<?= esc('Tanda hadir ' . $a['nama'] . ' dibatalkan.', 'attr') ?>" data-konfirmasi-judul="Batal hadir?" data-konfirmasi-tombol="Batal hadir">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="atlet" value="<?= $a['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Batal hadir</button>
                                </form>
                            <?php endif ?>
                        </li>
                    <?php endforeach ?>
                    <?php if ($atlet === []) : ?>
                        <li class="list-group-item text-body-secondary">Pendaftaran ini tidak punya atlet.</li>
                    <?php endif ?>
                </ul>
            </div>
        </section>
    <?php endif ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<script src="<?= aset('js/daftar-ulang.js') ?>"></script>
<?= $this->endSection() ?>
