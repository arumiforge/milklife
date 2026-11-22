<?php
/**
 * Daftar seri dan lintasan satu babak untuk umum (`/jadwal/<id babak>`,
 * F9-08), tata letak Tailwind `publik_tw`: kartu per seri berisi lintasan
 * (atau urutan tampil), nama, nomor atlet, dan sekolah; di bawahnya bagan
 * alur babak nomor itu. Data dari `SeriController::babak()`.
 *
 * @var CodeIgniter\View\View                  $this
 * @var string                                 $namaAcara
 * @var array<string, mixed>                   $babak       `SeriLintasan::babak()`
 * @var array<int, list<array<string, mixed>>> $perSeri     seri => peserta
 * @var array<string, string>                  $tanda       kunci => Q/q (hanya bila hasil terbit)
 * @var array{q: int, q_kecil: int}            $lolos       aturan lolos babak ini
 * @var list<array<string, mixed>>             $bagan       `SeriLintasan::bagan()` publik
 * @var bool                                   $terbit      hasil nomor sudah terbit
 * @var bool                                   $tampilJadwal halaman Jadwal terbuka
 */

use App\Libraries\Hasil\SeriLintasan;
use App\Libraries\Hasil\UnduhanSeri;

$nomor     = $babak['nomor'];
$lintasan  = $babak['aturan']['lintasan'];
$beregu    = $nomor['jenis'] === 'beregu';
$jadwal    = UnduhanSeri::jadwal($babak);
$teksLolos = SeriLintasan::teksLolos($lolos, (string) $nomor['jenis_hasil']);
?>
<?= $this->extend('tata_letak/publik_tw') ?>

<?= $this->section('judul') ?>Daftar seri <?= esc($nomor['nama'] . ' ' . $nomor['kelompok_umur'] . ' ' . $babak['nama']) ?> – <?= esc($namaAcara) ?><?= $this->endSection() ?>

<?= $this->section('isi') ?>
<section class="latar-merek border-b border-gray-200/70" aria-labelledby="judul-daftar-seri">
    <div class="mx-auto max-w-screen-xl px-4 py-8 sm:py-12 md:flex md:items-center md:gap-8">
        <div class="min-w-0 md:flex-1">
        <?php if ($tampilJadwal) : ?>
            <a href="<?= site_url('jadwal') . ($babak['tanggal'] === null ? '' : '?hari=' . $babak['tanggal']) ?>" class="inline-flex min-h-11 items-center gap-1 text-sm font-semibold text-primary-700 hover:underline"><?= ikon('arrow-left', 'size-4') ?>Jadwal pertandingan</a>
        <?php endif ?>
        <p class="text-sm font-semibold text-oranye-700"><?= esc($nomor['kelompok_umur']) ?> · <?= esc($nomor['nama']) ?></p>
        <div class="mt-1 flex flex-wrap items-center gap-3">
            <?= ikon('list-ordered', 'size-9 text-oranye-600 sm:size-11') ?>
            <h1 id="judul-daftar-seri" class="judul-tampilan min-w-0 break-words"><?= esc($babak['nama']) ?></h1>
        </div>
        <p class="mt-3 max-w-3xl text-lg text-gray-700">
            <?= $jadwal === '' ? 'Jadwal menyusul.' : esc($jadwal) . '.' ?>
            <?php if ($teksLolos !== '') : ?>Lolos ke <?= esc($babak['berikut']['nama'] ?? 'babak berikutnya') ?>: <?= esc($teksLolos) ?>.<?php endif ?>
        </p>
        </div>
        <?php if (ada_gambar('judul/seri.webp')) : ?>
            <div class="hidden shrink-0 md:block" data-judul-ilustrasi><?= gambar_opsional('judul/seri.webp', ['class' => 'h-36 w-auto lg:h-44']) ?></div>
        <?php endif ?>
    </div>
</section>

<div class="mx-auto max-w-screen-xl px-4 py-6 sm:py-8">
    <h2 class="text-xl font-bold text-primary-900 sm:text-2xl"><?= $lintasan ? 'Seri dan lintasan' : 'Kelompok dan urutan tampil' ?></h2>
    <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3" data-daftar-seri>
        <?php foreach ($perSeri as $seri => $peserta) : ?>
            <section class="rounded-2xl border border-gray-200 bg-white shadow-lembut" aria-labelledby="judul-seri-<?= $seri ?>" data-seri="<?= $seri ?>">
                <h3 id="judul-seri-<?= $seri ?>" class="rounded-t-2xl bg-linear-to-r from-oranye-600 to-magenta-600 px-4 py-2.5 text-base font-bold text-white"><?= $lintasan ? 'Seri ' . $seri : 'Kelompok ' . $seri ?></h3>
                <ol class="divide-y divide-gray-100">
                    <?php foreach ($peserta as $p) : ?>
                        <?php $t = $tanda[$p['kunci']] ?? null; ?>
                        <li class="flex items-center gap-3 px-4 py-2.5" data-peserta="<?= esc($p['kunci'], 'attr') ?>">
                            <span class="flex w-10 shrink-0 flex-col items-center justify-center text-primary-900">
                                <span class="font-tampilan text-3xl leading-none font-extrabold"><?= $p['lintasan'] ?? '–' ?></span>
                                <span class="sr-only"><?= $lintasan ? 'lintasan' : 'urutan' ?></span>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block font-bold break-words text-gray-900"><?= esc($p['nama']) ?></span>
                                <span class="block text-sm break-words text-gray-600"><?= $beregu ? '' : (($p['nomor_dada'] ?? '') === '' ? '' : 'No. ' . esc($p['nomor_dada']) . ' · ') ?><?= esc($p['sekolah']) ?></span>
                                <?php if ($p['anggota'] !== []) : ?><span class="block text-xs break-words text-gray-500"><?= esc(implode(', ', $p['anggota'])) ?></span><?php endif ?>
                            </span>
                            <?php if ($terbit && $p['prestasi_teks'] !== '') : ?><span class="shrink-0 text-sm font-semibold text-gray-800"><?= esc($p['prestasi_teks']) ?></span><?php endif ?>
                            <?php if ($t !== null) : ?><span class="shrink-0 rounded bg-green-700 px-2 py-0.5 text-xs font-bold text-white" data-tanda-lolos="<?= $t ?>"><?= $t ?></span><?php endif ?>
                        </li>
                    <?php endforeach ?>
                </ol>
            </section>
        <?php endforeach ?>
    </div>

    <h2 class="mt-10 text-xl font-bold text-primary-900 sm:text-2xl">Alur babak</h2>
    <p class="mt-1 text-sm text-gray-600">Q = lolos sebagai yang terbaik di serinya, q = lolos dari prestasi terbaik berikutnya. Tanda lolos tampil setelah hasil diumumkan.</p>
    <div class="mt-4">
        <?= view('seri/_bagan', ['bagan' => $bagan, 'jenisHasil' => (string) $nomor['jenis_hasil'], 'publik' => true, 'aktif' => $babak['id'], 'lintasan' => $lintasan], ['saveData' => false]) ?>
    </div>

    <?php if ($terbit) : ?>
        <p class="mt-6"><?= komponen('tombol', ['label' => 'Lihat hasil ' . $nomor['nama'], 'href' => site_url('hasil/' . $nomor['id']), 'varian' => 'kedua', 'ikon' => 'trophy']) ?></p>
    <?php endif ?>

    <p class="mt-8 text-sm text-gray-600">
        Ada pertanyaan tentang seri dan lintasan? Lihat halaman <a href="<?= site_url('bantuan') ?>" class="inline-flex min-h-11 items-center font-semibold text-primary-700 underline">Bantuan</a>.
    </p>
</div>
<?= $this->endSection() ?>
