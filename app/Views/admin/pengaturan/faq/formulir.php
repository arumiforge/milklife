<?php
/**
 * Formulir tambah dan ubah tanya-jawab Bantuan (`GET …/bantuan/tambah?kategori=…`,
 * `GET …/bantuan/<id>/ubah`, F9-04). Ditampilkan ulang dengan isian yang
 * diketik bila ada yang salah, beserta pratinjau jawaban.
 *
 * @var CodeIgniter\View\View        $this
 * @var array<string, mixed>|null    $faq           yang diubah, `null` untuk yang baru
 * @var array<string, string>        $isian         kategori, pertanyaan, jawaban, urutan, aktif
 * @var array<string, string>        $galat         pesan per kotak
 * @var array<string, string>        $isianOtomatis nama isian => keterangan (`JawabanFaq::ISIAN`)
 * @var list<string>                 $tidakDikenal  isian di jawaban yang tidak dikenal
 * @var string                       $pratinjau     HTML jawaban (sudah di-escape), kosong bila jawaban kosong
 */

use App\Models\FaqModel;

$baru   = $faq === null;
$tujuan = $baru ? site_url('admin/pengaturan/bantuan') : site_url('admin/pengaturan/bantuan/' . (int) $faq['id']);
$judul  = $baru ? 'Tambah tanya-jawab' : 'Ubah tanya-jawab';

$salah  = static fn (string $kotak): string => isset($galat[$kotak]) ? ' is-invalid' : '';
$uraian = static fn (string $kotak): string => ' aria-describedby="' . $kotak . '-bantuan' . (isset($galat[$kotak]) ? ' ' . $kotak . '-galat' : '') . '"';
$pesan  = static fn (string $kotak): string => isset($galat[$kotak])
    ? '<div id="' . $kotak . '-galat" class="invalid-feedback">' . esc($galat[$kotak]) . '</div>'
    : '';
?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?><?= esc($judul) ?><?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Bantuan<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid px-3 px-lg-4">
    <?= $this->include('admin/pengaturan/_navigasi') ?>

    <div class="row g-4">
        <div class="col-12 col-xl-7">
            <section class="card" aria-labelledby="judul-formulir-faq">
                <div class="card-body p-3 p-lg-4">
                    <h2 id="judul-formulir-faq" class="h5 mb-1">
                        <i class="bi bi-question-circle text-primary" aria-hidden="true"></i> <?= esc($judul) ?>
                    </h2>
                    <p class="text-body-secondary small mb-4">Tampil di halaman Bantuan dan di tombol bantuan formulir pendaftaran.</p>

                    <?= view('admin/_galat_isian', ['galat' => $galat], ['saveData' => false]) ?>

                    <form action="<?= $tujuan ?>" method="post" novalidate>
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label for="kategori" class="form-label">Kategori</label>
                            <select class="form-select<?= $salah('kategori') ?>" id="kategori" name="kategori" required<?= $uraian('kategori') ?>>
                                <option value=""<?= $isian['kategori'] === '' ? ' selected' : '' ?>>Pilih kategori</option>
                                <?php foreach (FaqModel::KATEGORI as $kunci => $kategori) : ?>
                                    <option value="<?= esc($kunci, 'attr') ?>"<?= $isian['kategori'] === $kunci ? ' selected' : '' ?>><?= esc($kategori['label']) ?></option>
                                <?php endforeach ?>
                            </select>
                            <div id="kategori-bantuan" class="form-text">Kelompok pertanyaan di halaman Bantuan.</div>
                            <?= $pesan('kategori') ?>
                        </div>

                        <div class="mb-3">
                            <label for="pertanyaan" class="form-label">Pertanyaan</label>
                            <input type="text" class="form-control<?= $salah('pertanyaan') ?>" id="pertanyaan" name="pertanyaan"
                                value="<?= esc($isian['pertanyaan'], 'attr') ?>" maxlength="<?= FaqModel::PANJANG['pertanyaan'] ?>" required<?= $uraian('pertanyaan') ?>>
                            <div id="pertanyaan-bantuan" class="form-text">Tulis seperti yang biasa ditanyakan sekolah, misalnya "Foto ofisial dari HP boleh?".</div>
                            <?= $pesan('pertanyaan') ?>
                        </div>

                        <div class="mb-3">
                            <label for="jawaban" class="form-label">Jawaban</label>
                            <textarea class="form-control font-monospace<?= $salah('jawaban') ?>" id="jawaban" name="jawaban" rows="10"
                                maxlength="<?= FaqModel::PANJANG['jawaban'] ?>" required<?= $uraian('jawaban') ?>><?= esc($isian['jawaban']) ?></textarea>
                            <div id="jawaban-bantuan" class="form-text">
                                Baris kosong memisahkan paragraf. <code>**tebal**</code> untuk menebalkan nama tombol,
                                baris diawali <code>1.</code> atau <code>-</code> untuk daftar, dan
                                <code>[Cek Pendaftaran](/cek-pendaftaran)</code> untuk tautan.
                            </div>
                            <?= $pesan('jawaban') ?>
                            <?php if ($tidakDikenal !== []) : ?>
                                <div class="alert alert-warning small mt-2 mb-0" role="status">
                                    Isian otomatis ini tidak dikenal dan akan tampil apa adanya:
                                    <?= esc(implode(', ', array_map(static fn (string $n): string => '{' . $n . '}', $tidakDikenal))) ?>.
                                    Periksa ejaannya dengan daftar di samping.
                                </div>
                            <?php endif ?>
                        </div>

                        <div class="row g-3 align-items-end">
                            <div class="col-sm-5">
                                <label for="urutan" class="form-label">Urutan</label>
                                <input type="number" class="form-control<?= $salah('urutan') ?>" id="urutan" name="urutan"
                                    value="<?= esc($isian['urutan'], 'attr') ?>" min="0" max="<?= FaqModel::URUTAN_MAKSIMAL ?>" step="1" inputmode="numeric"<?= $uraian('urutan') ?>>
                                <div id="urutan-bantuan" class="form-text">Angka kecil tampil lebih dulu. Kosongkan untuk paling akhir.</div>
                                <?= $pesan('urutan') ?>
                            </div>
                            <div class="col-sm-7">
                                <div class="form-check form-switch mb-sm-4">
                                    <input class="form-check-input" type="checkbox" role="switch" id="aktif" name="aktif" value="1"<?= $isian['aktif'] === '1' ? ' checked' : '' ?>>
                                    <label class="form-check-label" for="aktif">Tampilkan di halaman Bantuan</label>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg" aria-hidden="true"></i> <?= $baru ? 'Simpan tanya-jawab' : 'Simpan perubahan' ?>
                            </button>
                            <a class="btn btn-outline-secondary" href="<?= site_url('admin/pengaturan/bantuan') ?>">Batal</a>
                        </div>
                    </form>
                </div>
            </section>
        </div>

        <div class="col-12 col-xl-5">
            <?php if ($pratinjau !== '') : ?>
                <section class="card mb-4" aria-labelledby="judul-pratinjau">
                    <div class="card-body p-3 p-lg-4">
                        <h2 id="judul-pratinjau" class="h6 text-body-secondary mb-3">Pratinjau jawaban</h2>
                        <div class="isi-jawaban-faq"><?= $pratinjau ?></div>
                    </div>
                </section>
            <?php endif ?>

            <section class="card" aria-labelledby="judul-isian-otomatis">
                <div class="card-body p-3 p-lg-4">
                    <h2 id="judul-isian-otomatis" class="h6 mb-1">Isian otomatis</h2>
                    <p class="text-body-secondary small">
                        Tulis nama di bawah, lengkap dengan kurung kurawal, di dalam jawaban. Saat tampil, isian itu diganti
                        nilai dari Pengaturan yang berlaku, jadi jawaban ikut berubah bila tanggal, rekening, atau kelompok umur diubah.
                    </p>
                    <dl class="row small mb-0">
                        <?php foreach ($isianOtomatis as $nama => $keterangan) : ?>
                            <dt class="col-sm-6 font-monospace text-break">{<?= esc($nama) ?>}</dt>
                            <dd class="col-sm-6"><?= esc($keterangan) ?></dd>
                        <?php endforeach ?>
                    </dl>
                </div>
            </section>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
