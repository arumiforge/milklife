<?php
/**
 * Usulan nama sekolah dari pendaftar (K99): `admin/sekolah/usulan`.
 * Data dari `SekolahController::usulan()` (`UsulanNamaSekolahModel::daftarLengkap()`).
 *
 * Usulan yang menunggu punya formulir Setujui (nama boleh dirapikan dulu)
 * dan Tolak (alasan pilihan), keduanya memakai jendela konfirmasi
 * `aset/js/konfirmasi.js`. Usulan yang sudah diputuskan tampil sebagai riwayat.
 *
 * @var CodeIgniter\View\View      $this
 * @var list<array<string, mixed>> $usulan
 */

use App\Models\SekolahModel;
use App\Models\UsulanNamaSekolahModel;

$menunggu   = array_values(array_filter($usulan, static fn (array $u): bool => $u['status'] === UsulanNamaSekolahModel::MENUNGGU));
$diputuskan = array_values(array_filter($usulan, static fn (array $u): bool => $u['status'] !== UsulanNamaSekolahModel::MENUNGGU));
?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?>Usulan nama sekolah<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Sekolah<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid px-3 px-lg-4">
    <a class="tautan-kembali mb-3" href="<?= site_url('admin/sekolah') ?>">
        <i class="bi bi-arrow-left" aria-hidden="true"></i> Kembali ke daftar sekolah
    </a>

    <p class="teks-info mb-4">
        Pendaftar bisa mengusulkan nama sekolah yang lebih lengkap saat mendaftar. Pendaftarannya langsung memakai nama usulan.
        Bila disetujui, nama di data sekolah dan semua pendaftaran dari sekolah itu ikut berganti. Bila ditolak, pendaftaran pengusul
        kembali memakai nama dari data sekolah.
    </p>

    <section class="mb-4" aria-labelledby="judul-usulan-menunggu">
        <h2 id="judul-usulan-menunggu" class="h5 mb-3">Menunggu keputusan (<?= count($menunggu) ?>)</h2>

        <?php if ($menunggu === []) : ?>
            <p class="text-body-secondary" id="usulan-kosong">Belum ada usulan yang menunggu.</p>
        <?php endif ?>

        <div class="row g-3">
            <?php foreach ($menunggu as $u) : ?>
                <?php $id = (int) $u['id']; ?>
                <div class="col-12 col-xl-6">
                    <article class="card h-100 kartu-usulan" data-usulan="<?= $id ?>">
                        <div class="card-body">
                            <p class="small text-body-secondary mb-1">
                                NPSN <?= esc($u['npsn']) ?>
                                <?php if ($u['sekolah_id'] !== null) : ?>
                                    · <?= esc(SekolahModel::lokasi($u)) ?>
                                <?php endif ?>
                                · diusulkan <?= esc(tanggal_id((string) $u['dibuat_pada'], true)) ?>
                                <?php if ($u['id_pendaftaran'] !== null) : ?>
                                    · pendaftaran <a href="<?= site_url('admin/pendaftaran/' . (int) $u['pendaftaran_id']) ?>"><?= esc($u['id_pendaftaran']) ?></a>
                                <?php endif ?>
                            </p>
                            <p class="mb-1">Nama di data sekolah: <strong><?= esc($u['nama_sekarang'] ?? $u['nama_lama']) ?></strong></p>
                            <p class="mb-3">Usulan pendaftar: <strong><?= esc($u['nama_usulan']) ?></strong></p>

                            <form action="<?= site_url('admin/sekolah/usulan/' . $id . '/setujui') ?>" method="post" class="mb-3"
                                data-konfirmasi="Nama sekolah NPSN <?= esc($u['npsn'], 'attr') ?> akan diganti di data sekolah dan di semua pendaftaran dari sekolah itu."
                                data-konfirmasi-judul="Setujui usulan nama?" data-konfirmasi-tombol="Setujui" data-konfirmasi-jenis="utama">
                                <?= csrf_field() ?>
                                <label class="form-label small" for="usulan-<?= $id ?>-nama">Nama yang dipakai (boleh dirapikan)</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="usulan-<?= $id ?>-nama" name="nama" maxlength="<?= SekolahModel::PANJANG['nama'] ?>"
                                        value="<?= esc($u['nama_usulan'], 'attr') ?>" required>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-lg" aria-hidden="true"></i> Setujui
                                    </button>
                                </div>
                            </form>

                            <form action="<?= site_url('admin/sekolah/usulan/' . $id . '/tolak') ?>" method="post"
                                data-konfirmasi="Usulan ditolak. Pendaftaran pengusul kembali memakai nama dari data sekolah."
                                data-konfirmasi-judul="Tolak usulan nama?" data-konfirmasi-tombol="Tolak">
                                <?= csrf_field() ?>
                                <label class="form-label small" for="usulan-<?= $id ?>-alasan">Alasan menolak (pilihan)</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="usulan-<?= $id ?>-alasan" name="alasan" maxlength="<?= UsulanNamaSekolahModel::ALASAN_MAKSIMAL ?>">
                                    <button type="submit" class="btn btn-outline-danger">
                                        <i class="bi bi-x-lg" aria-hidden="true"></i> Tolak
                                    </button>
                                </div>
                            </form>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <?php if ($diputuskan !== []) : ?>
        <section aria-labelledby="judul-usulan-riwayat">
            <h2 id="judul-usulan-riwayat" class="h5 mb-3">Sudah diputuskan</h2>
            <div class="card">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <caption class="visually-hidden">Riwayat usulan nama sekolah</caption>
                        <thead>
                            <tr>
                                <th scope="col">NPSN</th>
                                <th scope="col">Nama lama</th>
                                <th scope="col">Usulan</th>
                                <th scope="col">Keputusan</th>
                                <th scope="col">Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($diputuskan as $u) : ?>
                                <tr>
                                    <td><?= esc($u['npsn']) ?></td>
                                    <td><?= esc($u['nama_lama']) ?></td>
                                    <td><?= esc($u['nama_usulan']) ?></td>
                                    <td>
                                        <?php if ($u['status'] === UsulanNamaSekolahModel::DISETUJUI) : ?>
                                            <span class="badge text-bg-success">Disetujui</span> <?= esc((string) $u['nama_disetujui']) ?>
                                        <?php else : ?>
                                            <span class="badge text-bg-secondary">Ditolak</span> <?= esc((string) ($u['alasan'] ?? '')) ?>
                                        <?php endif ?>
                                    </td>
                                    <td><?= esc(tanggal_id((string) $u['diputuskan_pada'])) ?></td>
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
