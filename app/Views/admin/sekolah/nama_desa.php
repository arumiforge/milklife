<?php
/**
 * Beri nama desa ke sekolah bernama kembar (K114): `admin/sekolah/nama-desa`.
 * Data dari `SekolahController::namaDesa()` (`NamaDesaSekolah::pratinjau()`).
 *
 * Tabel pertama berisi nama baru yang diusulkan, masing-masing dengan kotak
 * centang (bawaannya tercentang) untuk formulir `#form-nama-desa`. Tabel kedua
 * berisi sekolah yang tidak bisa dibedakan otomatis, dengan tautan Ubah.
 * Jendela konfirmasi dari `aset/js/konfirmasi.js`, centang semua dari
 * `aset/js/centang-semua.js`.
 *
 * @var CodeIgniter\View\View $this
 * @var array{usulan: list<array<string, mixed>>, lewati: list<array<string, mixed>>} $rencana
 * @var array<string, string> $pembeda kolom => label sumber nama pembeda
 */
$usulan = $rencana['usulan'];
$lewati = $rencana['lewati'];
?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?>Tambahkan nama desa<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Sekolah<?= $this->endSection() ?>

<?= $this->section('jejak') ?><?= view('admin/_jejak', ['jejak' => [['label' => 'Sekolah', 'alamat' => 'admin/sekolah'], ['label' => 'Tambahkan nama desa']]], ['saveData' => false]) ?><?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid px-3 px-lg-4">

    <p class="teks-info mb-3">
        Sekolah yang namanya sama dengan sekolah lain diberi nama baru dengan menambahkan nama desanya, misalnya
        MIS MUHAMMADIYAH di desa Jati menjadi MIS MUHAMMADIYAH JATI. Bila desanya sudah tertulis di nama atau sama dengan
        sekolah lain yang bernama sama, nama kecamatan atau kabupaten yang dipakai.
    </p>
    <p class="teks-info mb-4">
        Pendaftaran yang sudah masuk dengan nama lama ikut memakai nama baru. Pendaftaran yang memakai nama usulan pendaftar
        tidak diubah. Setelah diterapkan, nama setiap sekolah masih bisa diubah lagi lewat tombol Ubah di daftar sekolah.
    </p>

    <section class="card mb-4" aria-labelledby="judul-nama-baru">
        <div class="card-body">
            <h2 id="judul-nama-baru" class="h5 mb-3">Nama baru (<?= count($usulan) ?> sekolah)</h2>

            <?php if ($usulan === []) : ?>
                <p class="text-body-secondary mb-0" id="nama-baru-kosong">
                    Tidak ada sekolah yang bisa diberi nama desa. Semua sekolah sudah punya nama yang berbeda dari sekolah lain,
                    atau perlu diubah sendiri (lihat daftar di bawah).
                </p>
            <?php else : ?>
                <form action="<?= site_url('admin/sekolah/nama-desa') ?>" method="post" id="form-nama-desa"
                    data-konfirmasi="Sekolah yang dicentang akan memakai nama baru. Pendaftaran yang memakai nama lama ikut diganti."
                    data-konfirmasi-judul="Terapkan nama baru?"
                    data-konfirmasi-tombol="Terapkan"
                    data-konfirmasi-jenis="utama">
                    <?= csrf_field() ?>

                    <div class="table-responsive position-relative">
                        <table class="table table-sm table-hover align-middle" id="tabel-nama-baru">
                            <caption class="visually-hidden">Nama baru yang akan dipakai</caption>
                            <thead>
                                <tr>
                                    <th scope="col" class="kolom-pilih">
                                        <input class="form-check-input" type="checkbox" id="centang-semua-nama-desa" checked
                                            data-centang-semua="#tabel-nama-baru" aria-label="Centang semua sekolah">
                                    </th>
                                    <th scope="col">NPSN</th>
                                    <th scope="col">Nama sekarang</th>
                                    <th scope="col">Nama baru</th>
                                    <th scope="col">Diambil dari</th>
                                    <th scope="col">Letak</th>
                                    <th scope="col" class="text-end">Pendaftaran</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usulan as $s) : ?>
                                    <tr data-npsn="<?= esc($s['npsn'], 'attr') ?>">
                                        <td>
                                            <input class="form-check-input" type="checkbox" name="id[]" value="<?= (int) $s['id'] ?>" checked
                                                id="pilih-<?= (int) $s['id'] ?>" aria-label="<?= esc('Pakai nama ' . $s['nama_baru'], 'attr') ?>">
                                        </td>
                                        <td class="text-nowrap"><?= esc($s['npsn']) ?></td>
                                        <td><?= esc($s['nama']) ?></td>
                                        <td class="fw-semibold"><?= esc($s['nama_baru']) ?></td>
                                        <td><?= esc($pembeda[$s['pembeda']] ?? $s['pembeda']) ?></td>
                                        <td class="small"><?= esc($s['lokasi']) ?></td>
                                        <td class="text-end"><?= (int) $s['pendaftaran'] ?></td>
                                    </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check2-all" aria-hidden="true"></i> Terapkan nama baru
                    </button>
                </form>
            <?php endif ?>
        </div>
    </section>

    <?php if ($lewati !== []) : ?>
        <section class="card" aria-labelledby="judul-lewati">
            <div class="card-body">
                <h2 id="judul-lewati" class="h5 mb-1">Perlu diubah sendiri (<?= count($lewati) ?> sekolah)</h2>
                <p class="small text-body-secondary mb-3">
                    Letak sekolah ini tidak cukup untuk membedakannya dari sekolah lain bernama sama. Tekan Ubah dan tulis nama pembedanya,
                    misalnya dengan nama dusun atau nomor. Bila semua sekolah lain yang bernama sama sudah diberi nama baru, nama sekolah ini
                    sudah berbeda dan boleh dibiarkan.
                </p>

                <div class="table-responsive position-relative">
                    <table class="table table-sm table-hover align-middle mb-0" id="tabel-lewati">
                        <caption class="visually-hidden">Sekolah yang perlu diubah sendiri</caption>
                        <thead>
                            <tr>
                                <th scope="col">NPSN</th>
                                <th scope="col">Nama</th>
                                <th scope="col">Letak</th>
                                <th scope="col">Sebab</th>
                                <th scope="col"><span class="visually-hidden">Tindakan</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lewati as $s) : ?>
                                <tr data-npsn="<?= esc($s['npsn'], 'attr') ?>">
                                    <td class="text-nowrap"><?= esc($s['npsn']) ?></td>
                                    <td><?= esc($s['nama']) ?></td>
                                    <td class="small"><?= esc($s['lokasi']) ?></td>
                                    <td class="small"><?= esc($s['alasan']) ?></td>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-outline-primary" href="<?= site_url('admin/sekolah/' . (int) $s['id'] . '/ubah') ?>">Ubah</a>
                                    </td>
                                </tr>
                            <?php endforeach ?>
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
<script src="<?= aset('js/centang-semua.js') ?>"></script>
<?= $this->endSection() ?>
