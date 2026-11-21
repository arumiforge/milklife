<?php
/**
 * Formulir tambah dan ubah album Galeri (`GET …/galeri/tambah`,
 * `GET …/galeri/<id>/ubah`, F9-06). Ditampilkan ulang dengan isian yang
 * diketik bila ada yang salah.
 *
 * @var CodeIgniter\View\View      $this
 * @var array<string, mixed>|null  $album      yang diubah, `null` untuk yang baru
 * @var array<string, string>      $isian      judul, keterangan, tautan, urutan, aktif
 * @var array<string, string>      $galat      pesan per kotak
 * @var bool                       $pilihUlang sampul yang dipilih sah, tetapi belum disimpan karena isian lain salah
 * @var App\Libraries\Unggahan     $unggahan
 */

use App\Libraries\Unggahan;
use App\Models\GaleriModel;

$baru   = $album === null;
$tujuan = $baru ? site_url('admin/pengaturan/galeri') : site_url('admin/pengaturan/galeri/' . (int) $album['id']);
$judul  = $baru ? 'Tambah album' : 'Ubah album';
$sampul = ! $baru && is_string($album['sampul']) && $unggahan->ada($album['sampul']) ? $album['sampul'] : null;

$salah  = static fn (string $kotak): string => isset($galat[$kotak]) ? ' is-invalid' : '';
$uraian = static fn (string $kotak): string => ' aria-describedby="' . $kotak . '-bantuan' . (isset($galat[$kotak]) ? ' ' . $kotak . '-galat' : '') . '"';
$pesan  = static fn (string $kotak): string => isset($galat[$kotak])
    ? '<div id="' . $kotak . '-galat" class="invalid-feedback">' . esc($galat[$kotak]) . '</div>'
    : '';
?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?><?= esc($judul) ?><?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Galeri<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid px-3 px-lg-4">
    <?= $this->include('admin/pengaturan/_navigasi') ?>

    <section class="card maks-w-48r" aria-labelledby="judul-formulir-album">
        <div class="card-body p-3 p-lg-4">
            <h2 id="judul-formulir-album" class="h5 mb-1">
                <i class="bi bi-images text-primary" aria-hidden="true"></i> <?= esc($judul) ?>
            </h2>
            <p class="text-body-secondary small mb-4">Album tampil di halaman Galeri sebagai kartu bersampul. Pengunjung yang menekannya dibawa ke tautan album di tab baru.</p>

            <?= view('admin/_galat_isian', ['galat' => $galat], ['saveData' => false]) ?>

            <form action="<?= $tujuan ?>" method="post" enctype="multipart/form-data" novalidate data-formulir-album>
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label for="judul" class="form-label">Judul album</label>
                    <input type="text" class="form-control<?= $salah('judul') ?>" id="judul" name="judul"
                        value="<?= esc($isian['judul'], 'attr') ?>" maxlength="<?= GaleriModel::PANJANG['judul'] ?>" required<?= $uraian('judul') ?>>
                    <div id="judul-bantuan" class="form-text">Nama singkat, misalnya "Seri 1 – 2025" atau "Hari kedua".</div>
                    <?= $pesan('judul') ?>
                </div>

                <div class="mb-3">
                    <label for="keterangan" class="form-label">Keterangan <span class="text-body-secondary fw-normal">(boleh kosong)</span></label>
                    <input type="text" class="form-control<?= $salah('keterangan') ?>" id="keterangan" name="keterangan"
                        value="<?= esc($isian['keterangan'], 'attr') ?>" maxlength="<?= GaleriModel::PANJANG['keterangan'] ?>"<?= $uraian('keterangan') ?>>
                    <div id="keterangan-bantuan" class="form-text">Satu kalimat di bawah judul kartu.</div>
                    <?= $pesan('keterangan') ?>
                </div>

                <div class="mb-3">
                    <label for="tautan" class="form-label">Tautan album <span class="text-body-secondary fw-normal">(boleh diisi nanti)</span></label>
                    <input type="url" class="form-control<?= $salah('tautan') ?>" id="tautan" name="tautan" inputmode="url"
                        value="<?= esc($isian['tautan'], 'attr') ?>" maxlength="<?= GaleriModel::PANJANG['tautan'] ?>" placeholder="https://drive.google.com/…"<?= $uraian('tautan') ?>>
                    <div id="tautan-bantuan" class="form-text">
                        Buka album di Instagram atau Google Drive, lalu salin alamat lengkapnya dari kotak alamat peramban (diawali https://).
                        Untuk Google Drive, pastikan foldernya dibagikan ke "Siapa saja yang memiliki link".
                        Selama tautan kosong, album tidak tampil untuk umum.
                    </div>
                    <?= $pesan('tautan') ?>
                </div>

                <div class="mb-3">
                    <?php if ($sampul !== null) : ?>
                        <p class="form-label mb-2">Sampul sekarang</p>
                        <img src="<?= base_url($sampul) ?>" alt="" width="240" height="160" class="rounded border object-fit-cover mb-2 d-block">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="hapus_sampul" name="hapus_sampul" value="1">
                            <label class="form-check-label" for="hapus_sampul">Hapus sampul (kartu memakai latar warna)</label>
                        </div>
                    <?php endif ?>
                    <label for="sampul" class="form-label">
                        <?= $sampul === null ? 'Sampul' : 'Ganti sampul' ?> <span class="text-body-secondary fw-normal">(<?= $sampul === null ? 'boleh kosong' : 'kosongkan bila tidak diganti' ?>)</span>
                    </label>
                    <input type="file" class="form-control<?= $salah('sampul') ?>" id="sampul" name="sampul" accept=".jpg,.jpeg,.png,.webp"
                        data-ukuran-maksimal="<?= Unggahan::BATAS_GAMBAR ?>"<?= $uraian('sampul') ?>>
                    <div id="sampul-bantuan" class="form-text">
                        Foto JPG, PNG, atau WEBP, paling besar <?= esc(Unggahan::ukuranTeks(Unggahan::BATAS_GAMBAR)) ?>. Foto mendatar paling bagus.
                        Foto dikecilkan otomatis.<?php if ($sampul !== null) : ?> Sampul lama dihapus setelah sampul baru tersimpan.<?php endif ?>
                    </div>
                    <?= $pesan('sampul') ?>
                    <?php if ($pilihUlang) : ?>
                        <div class="alert alert-warning small py-2 mt-2 mb-0 pilih-ulang" role="status">
                            <i class="bi bi-info-circle-fill" aria-hidden="true"></i>
                            Sampul yang tadi dipilih belum tersimpan karena ada isian lain yang perlu diperbaiki. Pilih sampulnya lagi sebelum menyimpan.
                        </div>
                    <?php endif ?>
                </div>

                <div class="row g-3 align-items-end">
                    <div class="col-sm-5">
                        <label for="urutan" class="form-label">Urutan</label>
                        <input type="number" class="form-control<?= $salah('urutan') ?>" id="urutan" name="urutan"
                            value="<?= esc($isian['urutan'], 'attr') ?>" min="0" max="<?= GaleriModel::URUTAN_MAKSIMAL ?>" step="1" inputmode="numeric"<?= $uraian('urutan') ?>>
                        <div id="urutan-bantuan" class="form-text">Angka kecil tampil lebih dulu. Kosongkan untuk paling akhir.</div>
                        <?= $pesan('urutan') ?>
                    </div>
                    <div class="col-sm-7">
                        <div class="form-check form-switch mb-sm-4">
                            <input class="form-check-input" type="checkbox" role="switch" id="aktif" name="aktif" value="1"<?= $isian['aktif'] === '1' ? ' checked' : '' ?>>
                            <label class="form-check-label" for="aktif">Tampilkan di halaman Galeri</label>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg" aria-hidden="true"></i> <?= $baru ? 'Simpan album' : 'Simpan perubahan' ?>
                    </button>
                    <a class="btn btn-outline-secondary" href="<?= site_url('admin/pengaturan/galeri') ?>">Batal</a>
                </div>
            </form>
        </div>
    </section>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<?= $this->endSection() ?>
