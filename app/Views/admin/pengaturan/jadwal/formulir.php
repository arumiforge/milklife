<?php
/**
 * Formulir tambah dan ubah jadwal (`GET …/jadwal/tambah`, `GET …/jadwal/<id>/ubah`).
 * Ditampilkan ulang dengan isian yang diketik bila ada yang salah.
 *
 * @var CodeIgniter\View\View       $this
 * @var array<string, mixed>|null   $jadwal jadwal yang diubah, `null` untuk jadwal baru
 * @var array<string, string>       $isian  judul, keterangan, waktu (`Y-m-d\TH:i`), tempat, tautan, urutan, tampil ('1'/'0')
 * @var array<string, string>       $galat  pesan per kotak
 */

use App\Models\JadwalModel;

$baru   = $jadwal === null;
$tujuan = $baru ? site_url('admin/pengaturan/jadwal') : site_url('admin/pengaturan/jadwal/' . (int) $jadwal['id']);

// Atribut bersama tiap kotak: tanda salah dan uraian (bantuan dan pesan galat).
$salah  = static fn (string $kotak): string => isset($galat[$kotak]) ? ' is-invalid' : '';
$uraian = static function (string $kotak, bool $adaBantuan = true) use ($galat): string {
    $id = array_filter([$adaBantuan ? $kotak . '-bantuan' : '', isset($galat[$kotak]) ? $kotak . '-galat' : '']);

    return $id === [] ? '' : ' aria-describedby="' . implode(' ', $id) . '"';
};
$pesan = static fn (string $kotak): string => isset($galat[$kotak])
    ? '<div id="' . $kotak . '-galat" class="invalid-feedback">' . esc($galat[$kotak]) . '</div>'
    : '';
?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?><?= $baru ? 'Tambah Jadwal' : 'Ubah Jadwal' ?> – Admin MilkLife<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Jadwal<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid px-3 px-lg-4">
    <?= $this->include('admin/pengaturan/_navigasi') ?>

    <div class="row">
        <div class="col-12 col-xl-10 col-xxl-8">
            <section class="card" aria-labelledby="judul-formulir-jadwal">
                <div class="card-body p-3 p-lg-4">
                    <h2 id="judul-formulir-jadwal" class="h5 mb-1"><?= $baru ? 'Tambah jadwal' : 'Ubah jadwal' ?></h2>
                    <p class="text-body-secondary small mb-4">
                        Hanya judul yang wajib diisi. Isi tempat untuk kegiatan tatap muka, tautan untuk kegiatan daring.
                    </p>

                    <?= view('admin/_galat_isian', ['galat' => $galat], ['saveData' => false]) ?>

                    <form action="<?= $tujuan ?>" method="post" novalidate>
                        <?= csrf_field() ?>

                        <div class="row g-3">
                            <div class="col-12">
                                <label for="judul" class="form-label">Judul</label>
                                <input type="text" class="form-control<?= $salah('judul') ?>" id="judul" name="judul"
                                    value="<?= esc($isian['judul'], 'attr') ?>" maxlength="<?= JadwalModel::PANJANG['judul'] ?>" required<?= $uraian('judul') ?>>
                                <div id="judul-bantuan" class="form-text">Contoh: Technical Meeting, Sosialisasi Daring.</div>
                                <?= $pesan('judul') ?>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="waktu" class="form-label">Waktu <span class="text-body-secondary fw-normal">(boleh kosong)</span></label>
                                <input type="datetime-local" class="form-control<?= $salah('waktu') ?>" id="waktu" name="waktu"
                                    value="<?= esc($isian['waktu'], 'attr') ?>"<?= $uraian('waktu') ?>>
                                <div id="waktu-bantuan" class="form-text">Tanggal dan jam dalam WIB. Kosongkan bila belum pasti.</div>
                                <?= $pesan('waktu') ?>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="tempat" class="form-label">Tempat <span class="text-body-secondary fw-normal">(boleh kosong)</span></label>
                                <input type="text" class="form-control<?= $salah('tempat') ?>" id="tempat" name="tempat"
                                    value="<?= esc($isian['tempat'], 'attr') ?>" maxlength="<?= JadwalModel::PANJANG['tempat'] ?>"<?= $uraian('tempat') ?>>
                                <div id="tempat-bantuan" class="form-text">Untuk kegiatan tatap muka, misalnya Supersoccer Arena, Kudus.</div>
                                <?= $pesan('tempat') ?>
                            </div>

                            <div class="col-12">
                                <label for="tautan" class="form-label">Tautan <span class="text-body-secondary fw-normal">(boleh kosong)</span></label>
                                <input type="url" inputmode="url" class="form-control<?= $salah('tautan') ?>" id="tautan" name="tautan"
                                    value="<?= esc($isian['tautan'], 'attr') ?>" maxlength="<?= JadwalModel::PANJANG['tautan'] ?>"
                                    placeholder="https://" autocapitalize="off" spellcheck="false"<?= $uraian('tautan') ?>>
                                <div id="tautan-bantuan" class="form-text">Untuk kegiatan daring, misalnya alamat Zoom, Google Meet, atau siaran YouTube.</div>
                                <?= $pesan('tautan') ?>
                            </div>

                            <div class="col-12">
                                <label for="keterangan" class="form-label">Keterangan <span class="text-body-secondary fw-normal">(boleh kosong)</span></label>
                                <textarea class="form-control<?= $salah('keterangan') ?>" id="keterangan" name="keterangan" rows="3"
                                    maxlength="<?= JadwalModel::PANJANG['keterangan'] ?>"<?= $uraian('keterangan') ?>><?= esc($isian['keterangan']) ?></textarea>
                                <div id="keterangan-bantuan" class="form-text">Siapa yang perlu hadir atau apa yang perlu dibawa. Paling banyak <?= JadwalModel::PANJANG['keterangan'] ?> huruf.</div>
                                <?= $pesan('keterangan') ?>
                            </div>

                            <div class="col-12 col-sm-6 col-md-4">
                                <label for="urutan" class="form-label">Urutan</label>
                                <input type="number" class="form-control<?= $salah('urutan') ?>" id="urutan" name="urutan"
                                    value="<?= esc($isian['urutan'], 'attr') ?>" min="0" max="<?= JadwalModel::URUTAN_MAKSIMAL ?>" step="1" inputmode="numeric"<?= $uraian('urutan') ?>>
                                <div id="urutan-bantuan" class="form-text">Angka kecil tampil lebih dulu.</div>
                                <?= $pesan('urutan') ?>
                            </div>

                            <div class="col-12 col-sm-6 col-md-8 d-flex align-items-sm-center">
                                <div class="form-check form-switch mt-sm-3">
                                    <input class="form-check-input" type="checkbox" role="switch" id="tampil" name="tampil" value="1"
                                        <?= $isian['tampil'] === '1' ? 'checked' : '' ?> aria-describedby="tampil-bantuan">
                                    <label class="form-check-label" for="tampil">Tampilkan di halaman depan</label>
                                    <div id="tampil-bantuan" class="form-text mt-0">Matikan untuk menyembunyikan tanpa menghapus.</div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg" aria-hidden="true"></i> <?= $baru ? 'Simpan jadwal' : 'Simpan perubahan' ?>
                            </button>
                            <a class="btn btn-outline-secondary" href="<?= site_url('admin/pengaturan/jadwal') ?>">Batal</a>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
