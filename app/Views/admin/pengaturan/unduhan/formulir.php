<?php
/**
 * Formulir tambah (`$baris` null) dan ubah berkas unduhan (F2-07).
 * Data dari `UnduhanController::tampilkanFormulir()`.
 *
 * @var CodeIgniter\View\View      $this
 * @var array<string, mixed>|null  $baris      baris tersimpan + `info`, atau null saat menambah
 * @var array<string, mixed>       $isian      judul, keterangan, urutan (teks), tampil (bool)
 * @var array<string, string>      $galat      nama kotak => pesan
 * @var bool                       $pilihUlang berkas yang dipilih sah, tetapi belum disimpan karena isian lain salah
 */

use App\Libraries\Unggahan;
use App\Models\UnduhanModel;

$menambah  = $baris === null;
$judulForm = $menambah ? 'Tambah Unduhan' : 'Ubah Unduhan';
$aksi      = $menambah ? site_url('admin/pengaturan/unduhan') : site_url('admin/pengaturan/unduhan/' . (int) $baris['id']);

$jenisBerkas = array_map('strtoupper', array_keys(Unggahan::JENIS_BERKAS));
$akhirJenis  = array_pop($jenisBerkas);
$teksJenis   = implode(', ', $jenisBerkas) . ', atau ' . $akhirJenis;
$terimaJenis = '.pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx';

$kelasKotak = static fn (string $kotak): string => isset($galat[$kotak]) ? ' is-invalid' : '';
$uraian     = static function (string $kotak, string $bantuan = '') use ($galat): string {
    $id = [];
    if ($bantuan !== '') {
        $id[] = $bantuan;
    }
    if (isset($galat[$kotak])) {
        $id[] = $kotak . '-galat';
    }

    return $id === [] ? '' : ' aria-describedby="' . implode(' ', $id) . '"';
};
?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?><?= esc($judulForm) ?><?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?><?= esc($judulForm) ?><?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid px-3 px-lg-4">
    <?= $this->include('admin/pengaturan/_navigasi') ?>

    <div class="card" style="max-width: 48rem">
        <div class="card-body p-3 p-md-4">
            <?= view('admin/_galat_isian', ['galat' => $galat], ['saveData' => false]) ?>

            <form action="<?= $aksi ?>" method="post" enctype="multipart/form-data" novalidate>
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label for="judul" class="form-label">Judul</label>
                    <input type="text" class="form-control<?= $kelasKotak('judul') ?>" id="judul" name="judul"
                        value="<?= esc($isian['judul'], 'attr') ?>" maxlength="<?= UnduhanModel::PANJANG_JUDUL ?>" required
                        <?= $uraian('judul', 'judul-bantuan') ?>>
                    <div id="judul-bantuan" class="form-text">Nama berkas yang dilihat sekolah, misalnya Juknis Seri 2 atau Flyer Pendaftaran.</div>
                    <?php if (isset($galat['judul'])) : ?>
                        <div id="judul-galat" class="invalid-feedback"><?= esc($galat['judul']) ?></div>
                    <?php endif ?>
                </div>

                <div class="mb-3">
                    <label for="keterangan" class="form-label">Keterangan <span class="text-body-secondary fw-normal">(boleh dikosongkan)</span></label>
                    <textarea class="form-control<?= $kelasKotak('keterangan') ?>" id="keterangan" name="keterangan" rows="3"
                        maxlength="<?= UnduhanModel::PANJANG_KETERANGAN ?>"<?= $uraian('keterangan', 'keterangan-bantuan') ?>><?= esc($isian['keterangan']) ?></textarea>
                    <div id="keterangan-bantuan" class="form-text">Kalimat pendek tentang isi berkas, paling banyak <?= UnduhanModel::PANJANG_KETERANGAN ?> huruf.</div>
                    <?php if (isset($galat['keterangan'])) : ?>
                        <div id="keterangan-galat" class="invalid-feedback"><?= esc($galat['keterangan']) ?></div>
                    <?php endif ?>
                </div>

                <div class="mb-3">
                    <?php if (! $menambah) : ?>
                        <?php $info = $baris['info']; ?>
                        <p class="form-label mb-1">Berkas sekarang</p>
                        <div class="border rounded p-2 mb-3 berkas-sekarang">
                            <?php if ($info['ada']) : ?>
                                <span class="badge text-bg-light border"><?= esc($info['jenis']) ?></span>
                                <span class="small text-body-secondary ms-1"><?= esc($info['ukuran_teks']) ?></span>
                                <a class="ms-2 small" href="<?= esc($info['alamat'], 'attr') ?>" target="_blank" rel="noopener">
                                    <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Buka berkas<span class="visually-hidden"> (di tab baru)</span>
                                </a>
                            <?php else : ?>
                                <span class="text-danger small">
                                    <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
                                    Berkas tidak ditemukan di server. Pilih berkas pengganti di bawah.
                                </span>
                            <?php endif ?>
                        </div>
                    <?php endif ?>

                    <label for="berkas" class="form-label">
                        <?= $menambah ? 'Berkas' : 'Ganti berkas <span class="text-body-secondary fw-normal">(kosongkan bila tidak diganti)</span>' ?>
                    </label>
                    <input type="file" class="form-control<?= $kelasKotak('berkas') ?>" id="berkas" name="berkas"
                        accept="<?= $terimaJenis ?>" data-ukuran-maksimal="<?= Unggahan::BATAS_BERKAS ?>"
                        <?= $menambah ? 'required' : '' ?><?= $uraian('berkas', 'berkas-bantuan') ?>>
                    <div id="berkas-bantuan" class="form-text">
                        <?= esc($teksJenis) ?>, paling besar <?= esc(Unggahan::ukuranTeks(Unggahan::BATAS_BERKAS)) ?>.
                        <?php if (! $menambah) : ?>Berkas lama dihapus setelah berkas baru tersimpan.<?php endif ?>
                    </div>
                    <?php if (isset($galat['berkas'])) : ?>
                        <div id="berkas-galat" class="invalid-feedback"><?= esc($galat['berkas']) ?></div>
                    <?php elseif ($pilihUlang) : ?>
                        <div class="alert alert-warning small py-2 mt-2 mb-0 pilih-ulang" role="status">
                            <i class="bi bi-info-circle-fill" aria-hidden="true"></i>
                            Berkas yang tadi dipilih belum tersimpan karena ada isian lain yang perlu diperbaiki. Pilih berkasnya lagi sebelum menyimpan.
                        </div>
                    <?php endif ?>
                </div>

                <div class="mb-3">
                    <label for="urutan" class="form-label">Urutan</label>
                    <input type="number" class="form-control<?= $kelasKotak('urutan') ?>" id="urutan" name="urutan" style="max-width: 10rem"
                        value="<?= esc($isian['urutan'], 'attr') ?>" min="0" max="<?= UnduhanModel::URUTAN_TERBESAR ?>" step="1" inputmode="numeric" required
                        <?= $uraian('urutan', 'urutan-bantuan') ?>>
                    <div id="urutan-bantuan" class="form-text">Angka kecil tampil lebih dulu.</div>
                    <?php if (isset($galat['urutan'])) : ?>
                        <div id="urutan-galat" class="invalid-feedback"><?= esc($galat['urutan']) ?></div>
                    <?php endif ?>
                </div>

                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" id="tampil" name="tampil" value="1"<?= $isian['tampil'] ? ' checked' : '' ?>
                        aria-describedby="tampil-bantuan">
                    <label class="form-check-label" for="tampil">Tampilkan di halaman depan</label>
                    <div id="tampil-bantuan" class="form-text">Hilangkan centang untuk menyembunyikan berkas tanpa menghapusnya.</div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg" aria-hidden="true"></i> <?= $menambah ? 'Simpan berkas' : 'Simpan perubahan' ?>
                    </button>
                    <a class="btn btn-outline-primary" href="<?= site_url('admin/pengaturan/unduhan') ?>">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<?= $this->endSection() ?>
