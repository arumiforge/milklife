<?php
/**
 * Seri dan lintasan satu nomor di panel admin (F9-08), tata letak Tailwind
 * `admin_tw`: aturan seri nomor itu, unduhan daftar seri, dan bagan alur
 * babak. Data dari `Admin\SeriController::nomor()`.
 *
 * @var CodeIgniter\View\View      $this
 * @var array<string, mixed>       $nomor  `HasilLomba::nomor()`
 * @var array<string, mixed>       $aturan `SeriLintasan::aturanNomor()`
 * @var list<array<string, mixed>> $babak  `SeriLintasan::daftarBabak()`
 * @var list<array<string, mixed>> $bagan  `SeriLintasan::bagan()` (admin)
 * @var array<string, string>      $galat  kotak => pesan
 * @var array<string, mixed>       $isian  isian yang dikembalikan saat ada galat
 */

use App\Libraries\Hasil\PembagiSeri;

$lintasan = $aturan['lintasan'];
$nilai    = static fn (string $kotak, string $asal): string => is_string($isian[$kotak] ?? null) ? $isian[$kotak] : $asal;
$adaBagi  = in_array(true, array_map(static fn (array $b): bool => $b['dibagi'] > 0, $babak), true);
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?>Seri <?= esc($nomor['nama']) ?> <?= esc($nomor['kelompok_umur']) ?><?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-6 p-4 sm:p-6">
    <a href="<?= site_url('admin/seri') ?>" class="inline-flex min-h-11 items-center gap-1 text-sm font-semibold text-primary-700 hover:underline"><?= ikon('arrow-left', 'size-4') ?>Semua nomor</a>

    <section id="aturan-seri" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-lembut sm:p-6" aria-labelledby="judul-aturan-seri">
        <h2 id="judul-aturan-seri" class="text-lg font-bold text-gray-900">Aturan seri nomor ini</h2>
        <p class="mt-1 text-sm text-gray-600">
            <?php if ($lintasan) : ?>
                Nomor lari: peserta dibagi ke seri dengan satu lintasan per orang. Urutan lintasan unggulan dipakai di babak lanjutan: kelompok dipisah titik koma dan diundi di dalam kelompok.
                Bawaan World Athletics untuk 8 lintasan: <strong><?= PembagiSeri::URUTAN_WA ?></strong> (empat terbaik diundi di lintasan 3–6, dua berikutnya 7–8, dua terakhir 1–2).
            <?php else : ?>
                Nomor lompat, lempar, dan poin tanpa lintasan: peserta mendapat urutan tampil yang diacak. Isi peserta per kelompok bila ingin dibagi ke beberapa kelompok; kosong atau 0 = semua satu kelompok.
            <?php endif ?>
            Aturan berlaku untuk pembagian berikutnya.
        </p>
        <form action="<?= site_url('admin/seri/nomor/' . $nomor['id']) ?>" method="post" class="mt-4 grid gap-4 sm:grid-cols-2 lg:max-w-3xl">
            <?= csrf_field() ?>
            <?= komponen('isian', [
                'label'   => $lintasan ? 'Lintasan per seri' : 'Peserta per kelompok',
                'nama'    => 'tempat_per_seri',
                'tipe'    => 'number',
                'nilai'   => $nilai('tempat_per_seri', $aturan['tempat_isian']),
                'galat'   => $galat['tempat_per_seri'] ?? null,
                'bantuan' => $lintasan ? 'Kosong = 8 lintasan.' : 'Kosong atau 0 = satu kelompok.',
                'atribut' => ['min' => $lintasan ? 1 : 0, 'max' => $lintasan ? PembagiSeri::LINTASAN_MAKSIMAL : PembagiSeri::KELOMPOK_MAKSIMAL, 'inputmode' => 'numeric', 'placeholder' => $lintasan ? '8' : 'Satu kelompok'],
            ]) ?>
            <?php if ($lintasan) : ?>
                <?= komponen('isian', [
                    'label'   => 'Urutan lintasan unggulan',
                    'nama'    => 'urutan_lintasan',
                    'nilai'   => $nilai('urutan_lintasan', $aturan['urutan_isian']),
                    'galat'   => $galat['urutan_lintasan'] ?? null,
                    'bantuan' => 'Kosong = ' . PembagiSeri::urutanBawaan($aturan['tempat']) . '. Contoh urutan tetap tanpa undian: 4;5;3;6;2;7;1;8.',
                    'atribut' => ['placeholder' => PembagiSeri::urutanBawaan($aturan['tempat']), 'autocomplete' => 'off', 'maxlength' => 60],
                ]) ?>
            <?php endif ?>
            <div class="sm:col-span-2">
                <?= komponen('tombol', ['label' => 'Simpan aturan seri', 'tipe' => 'submit', 'ikon' => 'save']) ?>
            </div>
        </form>
    </section>

    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-lembut sm:p-6" aria-labelledby="judul-unduh-nomor">
        <h2 id="judul-unduh-nomor" class="text-lg font-bold text-gray-900">Unduh daftar seri</h2>
        <?php if ($adaBagi) : ?>
            <p class="mt-1 text-sm text-gray-600">Semua babak nomor ini yang sudah dibagi. Babak yang belum dikunci ditandai di berkasnya.</p>
            <div class="mt-3 flex flex-wrap gap-2">
                <?= komponen('tombol', ['label' => 'Unduh PDF', 'href' => site_url('admin/seri/nomor/' . $nomor['id'] . '/pdf'), 'varian' => 'kedua', 'ikon' => 'download']) ?>
                <?= komponen('tombol', ['label' => 'Unduh Excel', 'href' => site_url('admin/seri/nomor/' . $nomor['id'] . '/excel'), 'varian' => 'kedua', 'ikon' => 'sheet']) ?>
            </div>
        <?php else : ?>
            <p class="mt-1 text-sm text-gray-600">Belum ada babak yang dibagi. Buka babak di bawah, lalu tekan Bagi otomatis.</p>
        <?php endif ?>
    </section>

    <section aria-labelledby="judul-alur-babak">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 id="judul-alur-babak" class="text-lg font-bold text-gray-900">Alur babak</h2>
            <?= komponen('tombol', ['label' => 'Atur babak di Hasil lomba', 'href' => site_url('admin/hasil/nomor/' . $nomor['id']), 'varian' => 'hantu', 'ikon' => 'trophy']) ?>
        </div>
        <?php if ($bagan === []) : ?>
            <div class="mt-3"><?= komponen('kosong', ['gambar' => ['gambar/admin/kosong-admin.webp', 'gambar/keadaan/kosong-data.webp'], 'ikon' => 'list-ordered', 'judul' => 'Belum ada babak', 'teks' => 'Tambahkan babak (misalnya Penyisihan, lalu Final berisi "Kosong") di Hasil lomba. Final terisi sendiri dari yang lolos setelah hasil Penyisihan lengkap.']) ?></div>
        <?php else : ?>
            <p class="mt-1 text-sm text-gray-600">Tekan nama babak untuk membagi, menggeser, atau mengunci daftar serinya.</p>
            <div class="mt-3">
                <?= view('seri/_bagan', ['bagan' => $bagan, 'jenisHasil' => (string) $nomor['jenis_hasil'], 'publik' => false, 'aktif' => null, 'lintasan' => $lintasan], ['saveData' => false]) ?>
            </div>
        <?php endif ?>
    </section>
</div>
<?= $this->endSection() ?>
