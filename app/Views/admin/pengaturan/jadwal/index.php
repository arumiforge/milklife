<?php
/**
 * Daftar jadwal (`GET /admin/pengaturan/jadwal`).
 *
 * @var CodeIgniter\View\View            $this
 * @var list<array<string, mixed>>       $daftar jadwal menurut urutan tampil (`JadwalModel::daftar()`)
 */

use App\Models\TautanModel;

?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?>Jadwal<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Jadwal<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid px-3 px-lg-4">
    <?= $this->include('admin/pengaturan/_navigasi') ?>

    <?php if ($daftar === []) : ?>
        <section class="card kartu-kosong" aria-labelledby="judul-jadwal-kosong">
            <div class="card-body text-center px-3 py-5">
                <span class="ikon-bulat mb-3"><i class="bi bi-clock" aria-hidden="true"></i></span>
                <h2 id="judul-jadwal-kosong" class="h4">Belum ada jadwal</h2>
                <p class="text-body-secondary mx-auto mb-4">
                    Tambahkan sosialisasi daring, technical meeting, atau kegiatan lain supaya sekolah tahu
                    kapan dan di mana harus hadir. Jadwal tampil di halaman depan.
                </p>
                <a class="btn btn-primary" href="<?= site_url('admin/pengaturan/jadwal/tambah') ?>">
                    <i class="bi bi-plus-lg" aria-hidden="true"></i> Tambah jadwal
                </a>
            </div>
        </section>
    <?php else : ?>
        <section class="card" aria-labelledby="judul-daftar-jadwal">
            <div class="card-body p-3 p-lg-4">
                <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
                    <div>
                        <h2 id="judul-daftar-jadwal" class="h5 mb-1">Daftar jadwal</h2>
                        <p class="text-body-secondary small mb-0">
                            Tampil di halaman depan menurut urutan; angka kecil lebih dulu.
                        </p>
                    </div>
                    <a class="btn btn-primary" href="<?= site_url('admin/pengaturan/jadwal/tambah') ?>">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i> Tambah jadwal
                    </a>
                </div>

                <div class="tabel-gulir position-relative">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col" class="text-center">Urutan</th>
                                <th scope="col">Jadwal</th>
                                <th scope="col">Tampil</th>
                                <th scope="col"><span class="visually-hidden">Tindakan</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($daftar as $jadwal) : ?>
                                <tr data-id="<?= (int) $jadwal['id'] ?>">
                                    <td class="text-center"><?= (int) $jadwal['urutan'] ?></td>
                                    <td style="min-width: 13rem">
                                        <span class="fw-semibold d-block"><?= esc($jadwal['judul']) ?></span>
                                        <ul class="list-unstyled small text-body-secondary mb-0 mt-1">
                                            <li>
                                                <i class="bi bi-calendar-event" aria-hidden="true"></i>
                                                <?php if ($jadwal['waktu'] !== null) : ?>
                                                    <?= esc(tanggal_id($jadwal['waktu'], true)) ?> WIB
                                                <?php else : ?>
                                                    Waktu belum pasti
                                                <?php endif ?>
                                            </li>
                                            <?php if (($jadwal['tempat'] ?? '') !== '') : ?>
                                                <li><i class="bi bi-geo-alt" aria-hidden="true"></i> <?= esc($jadwal['tempat']) ?></li>
                                            <?php endif ?>
                                            <?php if (($jadwal['tautan'] ?? '') !== '') : ?>
                                                <li class="text-break">
                                                    <i class="bi bi-link-45deg" aria-hidden="true"></i>
                                                    <a href="<?= esc($jadwal['tautan'], 'attr') ?>" target="_blank" rel="noopener noreferrer"><?= esc(TautanModel::alamatRingkas($jadwal['tautan'])) ?></a>
                                                </li>
                                            <?php endif ?>
                                            <?php if (($jadwal['keterangan'] ?? '') !== '') : ?>
                                                <li class="mt-1"><?= esc(mb_strimwidth($jadwal['keterangan'], 0, 140, '…')) ?></li>
                                            <?php endif ?>
                                        </ul>
                                    </td>
                                    <td>
                                        <?php if ((int) $jadwal['tampil'] === 1) : ?>
                                            <span class="badge rounded-pill text-bg-success">Tampil</span>
                                        <?php else : ?>
                                            <span class="badge rounded-pill text-bg-secondary">Disembunyikan</span>
                                        <?php endif ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-2">
                                            <a class="btn btn-sm btn-outline-primary text-nowrap" href="<?= site_url('admin/pengaturan/jadwal/' . (int) $jadwal['id'] . '/ubah') ?>">
                                                <i class="bi bi-pencil" aria-hidden="true"></i> Ubah<span class="visually-hidden"> <?= esc($jadwal['judul']) ?></span>
                                            </a>
                                            <form action="<?= site_url('admin/pengaturan/jadwal/' . (int) $jadwal['id'] . '/hapus') ?>" method="post"
                                                data-konfirmasi="<?= esc('Jadwal "' . $jadwal['judul'] . '" akan dihapus dari daftar dan dari halaman depan. Tindakan ini tidak bisa dibatalkan.', 'attr') ?>"
                                                data-konfirmasi-judul="Hapus jadwal?"
                                                data-konfirmasi-tombol="Hapus jadwal">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-danger text-nowrap">
                                                    <i class="bi bi-trash" aria-hidden="true"></i> Hapus<span class="visually-hidden"> <?= esc($jadwal['judul']) ?></span>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    <?php endif ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<?= $this->endSection() ?>
