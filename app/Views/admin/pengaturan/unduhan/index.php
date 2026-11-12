<?php
/**
 * Daftar berkas unduhan (F2-07): judul, keterangan, jenis dan ukuran berkas
 * beserta tautan untuk membukanya, tampil atau tidak, urutan, dan tombol
 * ubah/hapus. Data dari `UnduhanController::index()`.
 *
 * @var CodeIgniter\View\View       $this
 * @var list<array<string, mixed>>  $daftar baris `unduhan` + `info` (lihat `UnduhanController::infoBerkas()`)
 */
?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?>Unduhan<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Unduhan<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid px-3 px-lg-4">
    <?= $this->include('admin/pengaturan/_navigasi') ?>

    <?php if ($daftar === []) : ?>
        <section class="card kartu-kosong" aria-labelledby="judul-kosong">
            <div class="card-body text-center px-3 py-5">
                <span class="ikon-bulat mb-3"><i class="bi bi-download" aria-hidden="true"></i></span>
                <h2 id="judul-kosong" class="h4">Belum ada berkas unduhan</h2>
                <p class="text-body-secondary mx-auto mb-4">
                    Unggah flyer, juknis, atau berkas lain yang perlu diunduh sekolah.
                    Berkas yang ditandai tampil akan muncul di halaman depan.
                </p>
                <a class="btn btn-primary" href="<?= site_url('admin/pengaturan/unduhan/tambah') ?>">
                    <i class="bi bi-plus-lg" aria-hidden="true"></i> Tambah berkas
                </a>
            </div>
        </section>
    <?php else : ?>
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <p class="text-body-secondary mb-0">
                Berkas yang ditandai tampil muncul di halaman depan, urut dari angka urutan terkecil.
            </p>
            <a class="btn btn-primary" href="<?= site_url('admin/pengaturan/unduhan/tambah') ?>">
                <i class="bi bi-plus-lg" aria-hidden="true"></i> Tambah berkas
            </a>
        </div>

        <div class="card">
            <div class="tabel-gulir position-relative">
                <table class="table align-middle mb-0">
                    <caption class="visually-hidden">Daftar berkas unduhan</caption>
                    <thead>
                        <tr>
                            <th scope="col" class="text-center">Urutan</th>
                            <th scope="col">Judul</th>
                            <th scope="col">Berkas</th>
                            <th scope="col">Tampil</th>
                            <th scope="col" class="text-end">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($daftar as $baris) : ?>
                            <?php
                            $info       = $baris['info'];
                            $keterangan = (string) ($baris['keterangan'] ?? '');
                            ?>
                            <tr data-id="<?= (int) $baris['id'] ?>">
                                <td class="text-center"><?= (int) $baris['urutan'] ?></td>
                                <td style="min-width: 12rem">
                                    <span class="fw-semibold"><?= esc($baris['judul']) ?></span>
                                    <?php if ($keterangan !== '') : ?>
                                        <span class="d-block small text-body-secondary"><?= esc(mb_strimwidth($keterangan, 0, 120, '…')) ?></span>
                                    <?php endif ?>
                                </td>
                                <td class="text-nowrap">
                                    <?php if ($info['ada']) : ?>
                                        <span class="badge text-bg-light border jenis-berkas"><?= esc($info['jenis']) ?></span>
                                        <span class="small text-body-secondary ms-1 ukuran-berkas"><?= esc($info['ukuran_teks']) ?></span>
                                        <a class="d-block small mt-1" href="<?= esc($info['alamat'], 'attr') ?>" target="_blank" rel="noopener">
                                            <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Buka berkas<span class="visually-hidden"> <?= esc($baris['judul']) ?> (di tab baru)</span>
                                        </a>
                                    <?php else : ?>
                                        <span class="text-danger small berkas-hilang">
                                            <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
                                            Berkas tidak ditemukan. Unggah lagi lewat Ubah.
                                        </span>
                                    <?php endif ?>
                                </td>
                                <td>
                                    <?php if ((int) $baris['tampil'] === 1) : ?>
                                        <span class="badge rounded-pill text-bg-success">Tampil</span>
                                    <?php else : ?>
                                        <span class="badge rounded-pill text-bg-secondary">Disembunyikan</span>
                                    <?php endif ?>
                                </td>
                                <td class="text-end text-nowrap">
                                    <a class="btn btn-sm btn-outline-primary" href="<?= site_url('admin/pengaturan/unduhan/' . (int) $baris['id'] . '/ubah') ?>">
                                        <i class="bi bi-pencil" aria-hidden="true"></i> Ubah<span class="visually-hidden"> <?= esc($baris['judul']) ?></span>
                                    </a>
                                    <form class="d-inline" action="<?= site_url('admin/pengaturan/unduhan/' . (int) $baris['id'] . '/hapus') ?>" method="post"
                                        data-konfirmasi="<?= esc('Berkas "' . $baris['judul'] . '" akan dihapus dari server dan tidak bisa dikembalikan.', 'attr') ?>"
                                        data-konfirmasi-judul="Hapus berkas?"
                                        data-konfirmasi-tombol="Hapus berkas">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash" aria-hidden="true"></i> Hapus<span class="visually-hidden"> <?= esc($baris['judul']) ?></span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<?= $this->endSection() ?>
