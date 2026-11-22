<?php
/**
 * Formulir tambah dan ubah tanya-jawab Bantuan (`GET …/bantuan/tambah?kategori=…`,
 * `GET …/bantuan/<id>/ubah`, F9-04), tata letak `admin_tw`. Ditampilkan ulang
 * dengan isian yang diketik bila ada yang salah, beserta pratinjau jawaban.
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

$kelasIsian = static fn (string $kotak): string => 'block w-full rounded-lg border p-2.5 text-base focus:ring-4 sm:text-sm '
    . (isset($galat[$kotak])
        ? 'border-red-500 bg-red-50 text-red-900 focus:border-red-500 focus:ring-red-100'
        : 'border-gray-300 bg-white text-gray-900 focus:border-primary-600 focus:ring-primary-100');
$uraian = static fn (string $kotak): string => ' aria-describedby="' . $kotak . '-bantuan' . (isset($galat[$kotak]) ? ' ' . $kotak . '-galat' : '') . '"'
    . (isset($galat[$kotak]) ? ' aria-invalid="true"' : '');
$pesan  = static fn (string $kotak): string => isset($galat[$kotak])
    ? '<p id="' . $kotak . '-galat" class="mt-1.5 text-sm font-medium text-red-700">' . esc($galat[$kotak]) . '</p>'
    : '';
$label   = 'mb-1.5 block text-sm font-semibold text-gray-900';
$bantuan = 'mt-1.5 text-sm text-gray-600';
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?><?= esc($judul) ?><?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Bantuan<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-4 p-4 sm:px-6 sm:py-4">
    <?= $this->include('admin/pengaturan/_navigasi') ?>

    <div class="grid gap-4 xl:grid-cols-12">
        <section class="min-w-0 rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-5 xl:col-span-7" aria-labelledby="judul-formulir-faq">
            <h2 id="judul-formulir-faq" class="flex items-center gap-2 text-lg font-bold text-gray-900">
                <?= ikon('circle-help', 'text-primary-700') ?> <?= esc($judul) ?>
            </h2>
            <p class="mt-1 text-sm text-gray-600">Tampil di halaman Bantuan dan di tombol bantuan formulir pendaftaran.</p>

            <div class="mt-4">
                <?= view('admin/_galat_isian_tw', ['galat' => $galat], ['saveData' => false]) ?>
            </div>

            <form action="<?= $tujuan ?>" method="post" novalidate class="mt-4 space-y-5">
                <?= csrf_field() ?>

                <div>
                    <label for="kategori" class="<?= $label ?>">Kategori</label>
                    <select class="<?= $kelasIsian('kategori') ?> min-h-11" id="kategori" name="kategori" required<?= $uraian('kategori') ?>>
                        <option value=""<?= $isian['kategori'] === '' ? ' selected' : '' ?>>Pilih kategori</option>
                        <?php foreach (FaqModel::KATEGORI as $kunci => $kategori) : ?>
                            <option value="<?= esc($kunci, 'attr') ?>"<?= $isian['kategori'] === $kunci ? ' selected' : '' ?>><?= esc($kategori['label']) ?></option>
                        <?php endforeach ?>
                    </select>
                    <p id="kategori-bantuan" class="<?= $bantuan ?>">Kelompok pertanyaan di halaman Bantuan.</p>
                    <?= $pesan('kategori') ?>
                </div>

                <div>
                    <label for="pertanyaan" class="<?= $label ?>">Pertanyaan</label>
                    <input type="text" class="<?= $kelasIsian('pertanyaan') ?>" id="pertanyaan" name="pertanyaan"
                        value="<?= esc($isian['pertanyaan'], 'attr') ?>" maxlength="<?= FaqModel::PANJANG['pertanyaan'] ?>" required<?= $uraian('pertanyaan') ?>>
                    <p id="pertanyaan-bantuan" class="<?= $bantuan ?>">Tulis seperti yang biasa ditanyakan sekolah, misalnya "Foto ofisial dari HP boleh?".</p>
                    <?= $pesan('pertanyaan') ?>
                </div>

                <div>
                    <label for="jawaban" class="<?= $label ?>">Jawaban</label>
                    <textarea class="<?= $kelasIsian('jawaban') ?> font-mono" id="jawaban" name="jawaban" rows="10"
                        maxlength="<?= FaqModel::PANJANG['jawaban'] ?>" required<?= $uraian('jawaban') ?>><?= esc($isian['jawaban']) ?></textarea>
                    <p id="jawaban-bantuan" class="<?= $bantuan ?>">
                        Baris kosong memisahkan paragraf. <code class="rounded bg-gray-100 px-1 font-mono text-gray-900">**tebal**</code> untuk menebalkan nama tombol,
                        baris diawali <code class="rounded bg-gray-100 px-1 font-mono text-gray-900">1.</code> atau <code class="rounded bg-gray-100 px-1 font-mono text-gray-900">-</code> untuk daftar, dan
                        <code class="rounded bg-gray-100 px-1 font-mono break-all text-gray-900">[Cek Pendaftaran](/cek-pendaftaran)</code> untuk tautan.
                    </p>
                    <?= $pesan('jawaban') ?>
                    <?php if ($tidakDikenal !== []) : ?>
                        <div class="mt-2 rounded-xl border border-yellow-200 bg-yellow-50 p-3 text-sm text-yellow-900" role="status">
                            Isian otomatis ini tidak dikenal dan akan tampil apa adanya:
                            <?= esc(implode(', ', array_map(static fn (string $n): string => '{' . $n . '}', $tidakDikenal))) ?>.
                            Periksa ejaannya dengan daftar di samping.
                        </div>
                    <?php endif ?>
                </div>

                <div class="grid items-end gap-4 sm:grid-cols-12">
                    <div class="sm:col-span-5">
                        <label for="urutan" class="<?= $label ?>">Urutan</label>
                        <input type="number" class="<?= $kelasIsian('urutan') ?>" id="urutan" name="urutan"
                            value="<?= esc($isian['urutan'], 'attr') ?>" min="0" max="<?= FaqModel::URUTAN_MAKSIMAL ?>" step="1" inputmode="numeric"<?= $uraian('urutan') ?>>
                        <p id="urutan-bantuan" class="<?= $bantuan ?>">Angka kecil tampil lebih dulu. Kosongkan untuk paling akhir.</p>
                        <?= $pesan('urutan') ?>
                    </div>
                    <div class="sm:col-span-7 sm:pb-7">
                        <?= komponen('sakelar', ['label' => 'Tampilkan di halaman Bantuan', 'nama' => 'aktif', 'centang' => $isian['aktif'] === '1']) ?>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2 pt-2">
                    <?= komponen('tombol', ['label' => $baru ? 'Simpan tanya-jawab' : 'Simpan perubahan', 'tipe' => 'submit', 'ikon' => 'check']) ?>
                    <?= komponen('tombol', ['label' => 'Batal', 'href' => site_url('admin/pengaturan/bantuan'), 'varian' => 'kedua', 'ikon' => 'x']) ?>
                </div>
            </form>
        </section>

        <div class="min-w-0 space-y-4 xl:col-span-5">
            <?php if ($pratinjau !== '') : ?>
                <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-5" aria-labelledby="judul-pratinjau">
                    <h2 id="judul-pratinjau" class="mb-3 text-sm font-bold uppercase tracking-wide text-gray-500">Pratinjau jawaban</h2>
                    <div class="isi-jawaban-faq format max-w-none break-words text-gray-700 format-primary"><?= $pratinjau ?></div>
                </section>
            <?php endif ?>

            <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-5" aria-labelledby="judul-isian-otomatis">
                <h2 id="judul-isian-otomatis" class="text-lg font-bold text-gray-900">Isian otomatis</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Tulis nama di bawah, lengkap dengan kurung kurawal, di dalam jawaban. Saat tampil, isian itu diganti
                    nilai dari Pengaturan yang berlaku, jadi jawaban ikut berubah bila tanggal, rekening, atau kelompok umur diubah.
                </p>
                <dl class="mt-3 grid gap-x-4 gap-y-1 text-sm sm:grid-cols-2">
                    <?php foreach ($isianOtomatis as $nama => $keterangan) : ?>
                        <dt class="font-mono break-all text-gray-900 sm:pt-2">{<?= esc($nama) ?>}</dt>
                        <dd class="mb-2 text-gray-600 sm:mb-0 sm:pt-2"><?= esc($keterangan) ?></dd>
                    <?php endforeach ?>
                </dl>
            </section>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
