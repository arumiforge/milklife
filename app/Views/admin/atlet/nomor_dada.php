<?php
/**
 * Urutkan ulang nomor dada per sekolah (K111). Data dari
 * `NomorDadaController::index()` (`UrutkanNomorDada::ringkasan()`).
 *
 * @var CodeIgniter\View\View $this
 * @var array{atlet: int, sekolah: int, menunggu_bernomor: int, per_kelompok_umur: list<array{nama: string, atlet: int, awalan: string, cukup: bool}>, diurutkan_pada: string|null, diurutkan_oleh: string|null} $ringkasan
 */

use App\Controllers\Admin\NomorDadaController;

$kurang = array_filter($ringkasan['per_kelompok_umur'], static fn (array $ku): bool => ! $ku['cukup']);
?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?>Urutkan Nomor Atlet (BIB)<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Urutkan Nomor Atlet (BIB)<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid px-3 px-lg-4">
    <p class="teks-info mb-4">
        Nomor atlet (BIB) dibagikan saat pertemuan teknik (technical meeting). Sebelum itu, nomor bisa dibuat ulang supaya nomor atlet satu sekolah berurutan,
        walau pendaftarannya masuk tidak berurutan. Sekolah yang mendaftar lebih dulu mendapat nomor lebih kecil, dan di dalam satu sekolah
        atlet diurutkan menurut urutan mendaftar. Nomor dimulai lagi dari 001 di awalan setiap kelompok umur.
    </p>

    <div class="row g-4">
        <div class="col-lg-7">
            <section class="card h-100" aria-labelledby="judul-ringkasan">
                <div class="card-body">
                    <h2 class="h5" id="judul-ringkasan">Yang akan diberi nomor</h2>

                    <?php if ($ringkasan['atlet'] === 0) : ?>
                        <p class="text-body-secondary mb-0" id="ringkasan-kosong">Belum ada atlet dari pendaftaran Lunas. Nomor atlet (BIB) hanya dibuat untuk pendaftaran yang sudah Lunas.</p>
                    <?php else : ?>
                        <p id="ringkasan-jumlah"><strong><?= $ringkasan['atlet'] ?></strong> atlet dari <strong><?= $ringkasan['sekolah'] ?></strong> sekolah dengan pendaftaran Lunas.</p>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead><tr><th scope="col">Kelompok umur</th><th scope="col" class="text-end">Atlet</th><th scope="col">Awalan</th><th scope="col">Keadaan</th></tr></thead>
                                <tbody>
                                    <?php foreach ($ringkasan['per_kelompok_umur'] as $ku) : ?>
                                        <tr>
                                            <td><?= esc($ku['nama']) ?></td>
                                            <td class="text-end"><?= $ku['atlet'] ?></td>
                                            <td><?= $ku['awalan'] === '' ? '<span class="text-danger">Belum ada</span>' : esc($ku['awalan']) ?></td>
                                            <td><?= $ku['cukup'] ? '<span class="text-success">Cukup</span>' : '<span class="text-danger">Awalan kurang</span>' ?></td>
                                        </tr>
                                    <?php endforeach ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif ?>

                    <?php if ($ringkasan['menunggu_bernomor'] > 0) : ?>
                        <p class="small text-body-secondary mt-3 mb-0">
                            <?= $ringkasan['menunggu_bernomor'] ?> atlet dari pendaftaran yang kembali ke Menunggu masih memegang nomor lama.
                            Nomornya akan dikosongkan, lalu dibuat lagi saat pendaftarannya ditandai Lunas.
                        </p>
                    <?php endif ?>
                </div>
            </section>
        </div>

        <div class="col-lg-5">
            <section class="card h-100" aria-labelledby="judul-urutkan">
                <div class="card-body">
                    <h2 class="h5" id="judul-urutkan">Urutkan ulang</h2>

                    <p class="small mb-3" id="terakhir-diurutkan">
                        <?php if ($ringkasan['diurutkan_pada'] === null) : ?>
                            Nomor atlet (BIB) belum pernah diurutkan ulang.
                        <?php else : ?>
                            Terakhir diurutkan ulang <?= esc(tanggal_id($ringkasan['diurutkan_pada'])) ?><?= $ringkasan['diurutkan_oleh'] !== null ? ' oleh ' . esc($ringkasan['diurutkan_oleh']) : '' ?>.
                            Halaman Cek Pendaftaran, bukti PDF, dan rincian pendaftaran menuliskan keterangan ini.
                        <?php endif ?>
                    </p>

                    <div class="alert alert-warning small" role="note">
                        Semua nomor atlet (BIB) lama diganti. Jangan urutkan ulang setelah nomor atlet dibagikan ke peserta.
                        Pendaftaran yang ditandai Lunas sesudahnya mendapat nomor lanjutan, jadi urutkan lagi sebelum pertemuan teknik (technical meeting) bila perlu.
                    </div>

                    <?php if ($kurang !== []) : ?>
                        <p class="small text-danger" id="awalan-kurang">
                            Tambahkan awalan untuk <?= esc(implode(', ', array_column($kurang, 'nama'))) ?> di
                            <a href="<?= site_url('admin/pengaturan/kelompok-umur') ?>">Pengaturan, Kelompok Umur</a> sebelum mengurutkan.
                        </p>
                    <?php endif ?>

                    <form action="<?= site_url('admin/atlet/nomor-dada') ?>" method="post" id="formulir-urutkan"
                        data-konfirmasi="<?= esc('Nomor atlet (BIB) ' . $ringkasan['atlet'] . ' atlet akan dibuat ulang, berurutan per sekolah. Nomor lama tidak berlaku lagi.', 'attr') ?>"
                        data-konfirmasi-judul="Urutkan ulang nomor atlet (BIB)?"
                        data-konfirmasi-tombol="Urutkan ulang">
                        <?= csrf_field() ?>
                        <label class="form-label" for="konfirmasi">Ketik <strong><?= NomorDadaController::KATA_KONFIRMASI ?></strong> untuk melanjutkan</label>
                        <input class="form-control mb-3" type="text" id="konfirmasi" name="konfirmasi" autocomplete="off" required
                            pattern="[Uu][Rr][Uu][Tt][Kk][Aa][Nn]" title="Ketik <?= NomorDadaController::KATA_KONFIRMASI ?>"<?= $ringkasan['atlet'] === 0 ? ' disabled' : '' ?>>
                        <button type="submit" class="btn btn-danger"<?= $ringkasan['atlet'] === 0 ? ' disabled' : '' ?>>
                            <i class="bi bi-sort-numeric-down" aria-hidden="true"></i>
                            Urutkan ulang nomor atlet (BIB)
                        </button>
                    </form>
                </div>
            </section>
        </div>
    </div>

    <p class="mt-4 mb-0"><a href="<?= site_url('admin/atlet') ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> Kembali ke daftar atlet</a></p>
</div>
<?= $this->endSection() ?>
