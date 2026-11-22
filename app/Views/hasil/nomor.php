<?php
/**
 * Hasil satu nomor lomba yang sudah terbit, semua babak (F8-04, K124), tata
 * letak baru `publik_tw` (F9-03). Pengendali: `App\Controllers\HasilController::nomor()`.
 *
 * @var CodeIgniter\View\View      $this
 * @var string                     $namaAcara
 * @var array<string, mixed>       $nomor `HasilLomba::nomor()`
 * @var list<array<string, mixed>> $babak `HasilLomba::semuaBabak()`
 *
 * Nomor yang tidak dilombakan (F11-03) hanya menampilkan keterangannya, tanpa babak dan unduhan.
 */

use App\Libraries\Hasil\JadwalPertandingan;

$dilombakan = $nomor['dilombakan'] ?? true;
?>
<?= $this->extend('tata_letak/publik_tw') ?>

<?= $this->section('judul') ?>Hasil <?= esc($nomor['nama']) ?> <?= esc($nomor['kelompok_umur']) ?> – <?= esc($namaAcara) ?><?= $this->endSection() ?>

<?= $this->section('isi') ?>
<?= view('tata_letak/_judul_halaman_tw', [
    'inggris'      => 'Results',
    'judul'        => trim($nomor['nama'] . ' ' . $nomor['kelompok_umur']),
    'ikon'         => 'trophy',
    'gambar'       => 'judul/hasil.webp',
    'teks'         => $dilombakan ? 'Hasil semua babak. ' . App\Libraries\Hasil\Peringkat::legenda($nomor['jenis_hasil']) . (! empty($nomor['waktu_tangan']) ? ' ' . App\Libraries\Hasil\Prestasi::TANDA_TANGAN : '') : $nomor['keterangan'],
    'kembali'      => site_url('hasil'),
    'labelKembali' => 'Semua hasil',
    'aksi'         => ! $dilombakan ? null : komponen('tombol', ['label' => 'Unduh PDF', 'href' => site_url('hasil/' . $nomor['id'] . '/pdf'), 'varian' => 'kedua', 'ikon' => 'file-text'])
        . komponen('tombol', ['label' => 'Unduh Excel', 'href' => site_url('hasil/' . $nomor['id'] . '/excel'), 'varian' => 'kedua', 'ikon' => 'file-spreadsheet']),
], ['saveData' => false]) ?>

<div class="mx-auto max-w-screen-xl space-y-6 px-4 py-10">
    <?php if (! $dilombakan) : ?>
        <p class="text-gray-700" data-tidak-dilombakan><?= esc($nomor['keterangan']) ?> Nomor ini tidak masuk klasemen sekolah.</p>
    <?php elseif ($babak === []) : ?>
        <p class="text-gray-600">Belum ada babak.</p>
    <?php endif ?>

    <?php foreach ($babak as $b) : ?>
        <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-6" aria-labelledby="babak-<?= $b['id'] ?>">
            <h2 class="flex flex-wrap items-center gap-2 text-xl font-extrabold text-primary-900" id="babak-<?= $b['id'] ?>">
                <?php if (($b['nomor_acara'] ?? null) !== null) : ?><span class="text-base font-bold text-oranye-700" data-nomor-acara><?= esc(JadwalPertandingan::labelAcara($b['nomor_acara'])) ?></span><?php endif ?>
                <?= esc($b['nama']) ?>
                <?php if ($b['final'] && mb_strtolower(trim($b['nama'])) !== 'final') : ?><?= komponen('lencana', ['label' => 'Final', 'warna' => 'oranye']) ?><?php endif ?>
            </h2>
            <div class="mt-4"><?= view('hasil/_tabel_tw', ['nomor' => $nomor, 'babak' => $b], ['saveData' => false]) ?></div>
        </section>
    <?php endforeach ?>
</div>
<?= $this->endSection() ?>
