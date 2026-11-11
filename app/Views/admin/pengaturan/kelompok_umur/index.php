<?php
/**
 * Daftar kelompok umur: nama, batas tahun lahir, jenjang yang boleh ikut,
 * biaya individu, awalan nomor dada, dan status aktif. Data dari
 * `KelompokUmurController::index()` (`KelompokUmurModel::daftarLengkap()`).
 *
 * Tombol hapus memakai jendela konfirmasi bila kelompok umur boleh dihapus.
 * Bila tidak boleh, tombol langsung mengirim dan server menjelaskan sebabnya
 * lewat pesan kilat, supaya admin tidak diminta mengonfirmasi hal yang pasti
 * ditolak.
 *
 * @var CodeIgniter\View\View             $this
 * @var list<array<string, mixed>>        $daftar
 */

use App\Libraries\Jenjang;
use App\Models\KelompokUmurModel;

$belumDiisi = static fn (string $teks = 'Belum diisi'): string => '<span class="text-warning-emphasis text-nowrap"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> ' . esc($teks) . '</span>';
?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?>Kelompok Umur – Admin MilkLife<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Kelompok Umur<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid px-3 px-lg-4">
    <?= $this->include('admin/pengaturan/_navigasi') ?>

    <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between gap-3 mb-4">
        <p class="text-body-secondary mb-0 pengaturan-pengantar">
            Kelompok umur atlet ditentukan dari tahun lahirnya. Tiap kelompok umur aktif perlu batas tahun lahir,
            jenjang sekolah yang boleh ikut, biaya per nomor individu, dan awalan nomor dada.
        </p>
        <a class="btn btn-primary flex-shrink-0" href="<?= site_url('admin/pengaturan/kelompok-umur/tambah') ?>">
            <i class="bi bi-plus-lg" aria-hidden="true"></i>
            Tambah kelompok umur
        </a>
    </div>

    <?php if ($daftar === []) : ?>
        <section class="card kartu-kosong" aria-labelledby="judul-kosong">
            <div class="card-body text-center px-3 py-5">
                <span class="ikon-bulat mb-3"><i class="bi bi-people" aria-hidden="true"></i></span>
                <h2 id="judul-kosong" class="h4">Belum ada kelompok umur</h2>
                <p class="text-body-secondary mx-auto">
                    Pendaftaran belum bisa dibuka sebelum ada kelompok umur. Tambahkan kelompok umur pertama,
                    misalnya KU 10 untuk atlet yang lahir 2016 sampai 2018.
                </p>
                <a class="btn btn-primary" href="<?= site_url('admin/pengaturan/kelompok-umur/tambah') ?>">
                    <i class="bi bi-plus-lg" aria-hidden="true"></i>
                    Tambah kelompok umur
                </a>
            </div>
        </section>
    <?php else : ?>
        <section class="card" aria-labelledby="judul-daftar">
            <div class="card-body p-0">
                <h2 id="judul-daftar" class="visually-hidden">Daftar kelompok umur</h2>
                <div class="tabel-gulir position-relative">
                    <table class="table align-middle mb-0" id="tabel-kelompok-umur">
                        <thead>
                            <tr>
                                <th scope="col" class="ps-3 ps-lg-4">Kelompok umur</th>
                                <th scope="col">Tahun lahir</th>
                                <th scope="col">Jenjang yang boleh ikut</th>
                                <th scope="col" class="text-end">Biaya per nomor</th>
                                <th scope="col">Awalan nomor dada</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="pe-3 pe-lg-4 text-end"><span class="visually-hidden">Tindakan</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($daftar as $ku) : ?>
                                <?php
                                $tahunLahir = KelompokUmurModel::teksTahunLahir($ku['tahun_lahir_awal'], $ku['tahun_lahir_akhir']);
                                $bisaHapus  = $ku['alasan_tidak_bisa_dihapus'] === null;
                                ?>
                                <tr data-id="<?= (int) $ku['id'] ?>"<?= (int) $ku['aktif'] === 1 ? '' : ' class="text-body-secondary"' ?>>
                                    <th scope="row" class="ps-3 ps-lg-4 text-nowrap"><?= esc($ku['nama']) ?></th>
                                    <td class="text-nowrap"><?= $tahunLahir === '' ? $belumDiisi() : esc($tahunLahir) ?></td>
                                    <td>
                                        <?php if ($ku['jenjang'] === []) : ?>
                                            <?= $belumDiisi('Belum dipilih') ?>
                                        <?php else : ?>
                                            <span class="d-flex flex-wrap gap-1">
                                                <?php foreach ($ku['jenjang'] as $kode) : ?>
                                                    <span class="badge rounded-pill text-bg-light border"><?= esc(Jenjang::label($kode)) ?></span>
                                                <?php endforeach; ?>
                                            </span>
                                        <?php endif ?>
                                    </td>
                                    <td class="text-end text-nowrap"><?= $ku['biaya_individu'] === null ? $belumDiisi() : esc(rupiah($ku['biaya_individu'])) ?></td>
                                    <td class="text-nowrap">
                                        <?= $ku['awalan'] === [] ? $belumDiisi() : esc(implode(', ', array_column($ku['awalan'], 'awalan'))) ?>
                                    </td>
                                    <td>
                                        <?php if ((int) $ku['aktif'] === 1) : ?>
                                            <span class="badge rounded-pill text-bg-success">Aktif</span>
                                        <?php else : ?>
                                            <span class="badge rounded-pill text-bg-secondary">Mati</span>
                                        <?php endif ?>
                                    </td>
                                    <td class="pe-3 pe-lg-4">
                                        <div class="d-flex justify-content-end gap-2">
                                            <a class="btn btn-sm btn-outline-primary text-nowrap" href="<?= site_url('admin/pengaturan/kelompok-umur/' . (int) $ku['id'] . '/ubah') ?>">
                                                <i class="bi bi-pencil" aria-hidden="true"></i>
                                                Ubah<span class="visually-hidden"> <?= esc($ku['nama']) ?></span>
                                            </a>
                                            <form action="<?= site_url('admin/pengaturan/kelompok-umur/' . (int) $ku['id'] . '/hapus') ?>" method="post" class="m-0"
                                                <?php if ($bisaHapus) : ?>
                                                    data-konfirmasi="<?= esc('Kelompok umur ' . $ku['nama'] . ' akan dihapus beserta jenjang dan awalan nomor dadanya. Tindakan ini tidak bisa dibatalkan.', 'attr') ?>"
                                                    data-konfirmasi-judul="Hapus kelompok umur?"
                                                    data-konfirmasi-tombol="Hapus kelompok umur"
                                                <?php endif ?>>
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-danger text-nowrap">
                                                    <i class="bi bi-trash" aria-hidden="true"></i>
                                                    Hapus<span class="visually-hidden"> <?= esc($ku['nama']) ?></span>
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
        <p class="text-body-secondary small mt-3 mb-0">
            Kelompok umur yang sudah punya nomor lomba, atlet, atau nomor dada tidak bisa dihapus.
            Ubah statusnya menjadi mati supaya tidak muncul di pendaftaran.
        </p>
    <?php endif ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<?= $this->endSection() ?>
