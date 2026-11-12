<?php
/**
 * Formulir tambah dan ubah kontak (`GET …/kontak/tambah?jenis=…`,
 * `GET …/kontak/<id>/ubah`). Label dan bantuan kotak mengikuti jenisnya.
 * Ditampilkan ulang dengan isian yang diketik bila ada yang salah.
 *
 * @var CodeIgniter\View\View        $this
 * @var string                       $jenis       `narahubung` atau `media_sosial`
 * @var string                       $jenisAlamat `narahubung` atau `media-sosial` (kotak tersembunyi `jenis`)
 * @var array<string, string>        $teks        teks per jenis (`KontakController::TEKS`)
 * @var array<string, mixed>|null    $kontak      kontak yang diubah, `null` untuk kontak baru
 * @var array<string, string>        $isian       label, nilai, urutan
 * @var array<string, string>        $galat       pesan per kotak
 */

use App\Models\TautanModel;

$baru       = $kontak === null;
$narahubung = $jenis === TautanModel::NARAHUBUNG;
$tujuan     = $baru ? site_url('admin/pengaturan/kontak') : site_url('admin/pengaturan/kontak/' . (int) $kontak['id']);
$judul      = ($baru ? 'Tambah ' : 'Ubah ') . $teks['nama'];

$salah  = static fn (string $kotak): string => isset($galat[$kotak]) ? ' is-invalid' : '';
$uraian = static fn (string $kotak): string => ' aria-describedby="' . $kotak . '-bantuan' . (isset($galat[$kotak]) ? ' ' . $kotak . '-galat' : '') . '"';
$pesan  = static fn (string $kotak): string => isset($galat[$kotak])
    ? '<div id="' . $kotak . '-galat" class="invalid-feedback">' . esc($galat[$kotak]) . '</div>'
    : '';
?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?><?= esc(ucfirst($judul)) ?><?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Kontak<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid px-3 px-lg-4">
    <?= $this->include('admin/pengaturan/_navigasi') ?>

    <div class="row">
        <div class="col-12 col-lg-9 col-xl-7 col-xxl-6">
            <section class="card" aria-labelledby="judul-formulir-kontak">
                <div class="card-body p-3 p-lg-4">
                    <h2 id="judul-formulir-kontak" class="h5 mb-1">
                        <i class="bi bi-<?= $narahubung ? 'person-lines-fill' : 'share' ?> text-primary" aria-hidden="true"></i>
                        <?= esc(ucfirst($judul)) ?>
                    </h2>
                    <p class="text-body-secondary small mb-4">
                        <?= $narahubung
                            ? 'Sekolah bisa langsung mengirim WA ke nomor ini dari halaman depan.'
                            : 'Alamat ini dibuka saat sekolah menekan nama media sosial di halaman depan.' ?>
                    </p>

                    <?= view('admin/_galat_isian', ['galat' => $galat], ['saveData' => false]) ?>

                    <form action="<?= $tujuan ?>" method="post" novalidate>
                        <?= csrf_field() ?>
                        <?php if ($baru) : ?>
                            <input type="hidden" name="jenis" value="<?= esc($jenisAlamat, 'attr') ?>">
                        <?php endif ?>

                        <div class="mb-3">
                            <label for="label" class="form-label"><?= esc($teks['label']) ?></label>
                            <input type="text" class="form-control<?= $salah('label') ?>" id="label" name="label"
                                value="<?= esc($isian['label'], 'attr') ?>" maxlength="<?= TautanModel::PANJANG['label'] ?>" required<?= $uraian('label') ?>>
                            <div id="label-bantuan" class="form-text"><?= esc($teks['label_bantuan']) ?></div>
                            <?= $pesan('label') ?>
                        </div>

                        <div class="mb-3">
                            <label for="nilai" class="form-label"><?= esc($teks['nilai']) ?></label>
                            <?php if ($narahubung) : ?>
                                <input type="tel" inputmode="tel" autocomplete="off" class="form-control<?= $salah('nilai') ?>" id="nilai" name="nilai"
                                    value="<?= esc($isian['nilai'], 'attr') ?>" maxlength="30" required<?= $uraian('nilai') ?>>
                            <?php else : ?>
                                <input type="url" inputmode="url" class="form-control<?= $salah('nilai') ?>" id="nilai" name="nilai"
                                    value="<?= esc($isian['nilai'], 'attr') ?>" maxlength="<?= TautanModel::PANJANG['nilai'] ?>"
                                    placeholder="https://" autocapitalize="off" spellcheck="false" required<?= $uraian('nilai') ?>>
                            <?php endif ?>
                            <div id="nilai-bantuan" class="form-text"><?= esc($teks['nilai_bantuan']) ?></div>
                            <?= $pesan('nilai') ?>
                        </div>

                        <div class="mb-3 col-sm-6 col-md-5 px-0">
                            <label for="urutan" class="form-label">Urutan</label>
                            <input type="number" class="form-control<?= $salah('urutan') ?>" id="urutan" name="urutan"
                                value="<?= esc($isian['urutan'], 'attr') ?>" min="0" max="<?= TautanModel::URUTAN_MAKSIMAL ?>" step="1" inputmode="numeric"<?= $uraian('urutan') ?>>
                            <div id="urutan-bantuan" class="form-text">Angka kecil tampil lebih dulu.</div>
                            <?= $pesan('urutan') ?>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg" aria-hidden="true"></i> <?= $baru ? 'Simpan ' . esc($teks['nama']) : 'Simpan perubahan' ?>
                            </button>
                            <a class="btn btn-outline-secondary" href="<?= site_url('admin/pengaturan/kontak') ?>">Batal</a>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
