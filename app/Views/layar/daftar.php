<?php
/**
 * Daftar tampilan videotron (`/layar`, F11-09, K173), tata letak `publik_tw`.
 *
 * Untuk operator videotron vendor: memilih satu tampilan sesuai keadaan di
 * arena, lalu membuka alamatnya di peramban videotron. Setiap tautan membuka
 * satu tampilan tetap layar penuh (`layar/<jenis>/…`) yang tidak berganti
 * sendiri. Alamat lengkap tertulis dan bisa disalin (`aset/js/layar-daftar.js`;
 * tanpa JavaScript alamat tetap bisa dipilih dan disalin manual).
 *
 * @var CodeIgniter\View\View $this
 * @var string                $namaAcara
 * @var array<string, mixed>  $daftar    `TampilanVideotron::daftar()`
 * @var string                $sebutan   sebutan pembagian babak, "Heat" atau "Seri" (`PapanVideotron::sebutanSeri()`, K192)
 */

// ikon-sprite: monitor copy check calendar-clock trophy users list-ordered repeat
$kartu = 'rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-5';

/**
 * Satu tautan tampilan: label, alamat lengkap, tombol buka dan salin.
 */
$tautan = static function (string $label, string $alamat, string $keterangan = '', bool $utama = false): string {
    $url = site_url($alamat);

    return '<div class="flex flex-wrap items-center gap-x-3 gap-y-1 py-2" data-tautan-layar="' . esc($alamat, 'attr') . '">'
        . '<a href="' . esc($url, 'attr') . '" target="_blank" rel="noopener" class="inline-flex min-h-11 items-center gap-2 rounded-lg ' . ($utama ? 'bg-primary-700 px-4 font-bold text-white hover:bg-primary-800' : 'px-2 font-semibold text-primary-700 hover:bg-primary-50 hover:underline') . ' focus:outline-none focus:ring-4 focus:ring-primary-300">'
        . ikon('monitor', 'size-4 shrink-0') . esc($label) . '<span class="sr-only"> (membuka tab baru)</span></a>'
        . ($keterangan === '' ? '' : '<span class="text-sm text-gray-600">' . esc($keterangan) . '</span>')
        . '<span class="flex min-w-0 basis-full items-center gap-1 sm:basis-auto">'
        . '<code class="min-w-0 break-all rounded bg-gray-100 px-2 py-1 text-xs text-gray-800 select-all">' . esc($url) . '</code>'
        . '<button type="button" class="hidden min-h-11 shrink-0 items-center gap-1 rounded-lg px-2 text-sm font-semibold text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-primary-300" data-salin-alamat="' . esc($url, 'attr') . '">'
        . ikon('copy', 'size-4') . '<span>Salin</span><span class="sr-only"> alamat ' . esc($label) . '</span></button>'
        . '</span></div>';
};
?>
<?= $this->extend('tata_letak/publik_tw') ?>

<?= $this->section('judul') ?>Tampilan videotron – <?= esc($namaAcara) ?><?= $this->endSection() ?>

<?= $this->section('keterangan') ?>Daftar tampilan videotron per <?= esc(mb_strtolower($sebutan)) ?>, sesi, klasemen, kelompok umur, dan nomor lomba.<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<section class="latar-merek border-b border-gray-200/70" aria-labelledby="judul-tampilan-videotron">
    <div class="mx-auto max-w-screen-xl px-4 py-8 sm:py-12">
        <?= view('tata_letak/_judul_editorial', ['inggris' => 'Big Screen', 'judul' => 'Tampilan videotron', 'ikon' => 'monitor', 'id' => 'judul-tampilan-videotron'], ['saveData' => false]) ?>
        <p class="mt-3 max-w-3xl text-lg text-gray-700">Pilih tampilan yang dibutuhkan, lalu buka alamatnya di peramban videotron. Tampilan tidak berganti sendiri. Hasil baru masuk sendiri tanpa perlu memuat ulang.</p>
    </div>
</section>

<div class="mx-auto max-w-screen-xl space-y-6 px-4 py-6 sm:py-8">
    <details class="group <?= $kartu ?>" data-petunjuk-layar>
        <summary class="flex min-h-11 cursor-pointer list-none items-center justify-between gap-3 font-semibold text-gray-900 [&::-webkit-details-marker]:hidden">
            Cara memakai di videotron
            <?= ikon('chevron-down', 'size-5 shrink-0 text-gray-500 transition-transform group-open:rotate-180') ?>
        </summary>
        <ol class="mt-3 list-decimal space-y-1.5 ps-5 text-gray-700">
            <li>Buka alamat tampilan di peramban komputer videotron.</li>
            <li>Tekan tombol <kbd class="rounded border border-gray-300 bg-gray-50 px-1.5 text-sm">F</kbd> atau tombol layar penuh di pojok kanan atas.</li>
            <li>Bila tabel tidak muat satu layar, tabel dibagi beberapa halaman. Tekan panah kanan atau kiri untuk berpindah halaman, atau tambahkan <code class="rounded bg-gray-100 px-1">?halaman=2</code> di belakang alamat.</li>
            <li>Huruf terlalu kecil atau terlalu besar? Tambahkan <code class="rounded bg-gray-100 px-1">?skala=1.3</code> (lebih besar) atau <code class="rounded bg-gray-100 px-1">?skala=0.8</code> (lebih kecil). Bisa digabung: <code class="rounded bg-gray-100 px-1">?skala=1.3&amp;halaman=2</code>.</li>
            <li>Tampilan yang belum punya data menulis kapan isinya muncul. Biarkan terbuka; isinya masuk sendiri setelah panitia menerbitkan hasil.</li>
        </ol>
    </details>

    <nav aria-label="Jenis tampilan" class="flex flex-wrap gap-2">
        <?php foreach (['bagian-sesi' => 'Per hari dan sesi', 'bagian-klasemen' => 'Klasemen', 'bagian-kelompok-umur' => 'Kelompok umur dan nomor lomba', 'bagian-putar' => 'Papan putar'] as $id => $label) : ?>
            <a href="#<?= $id ?>" class="inline-flex min-h-11 items-center rounded-full border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-800 hover:bg-gray-50"><?= esc($label) ?></a>
        <?php endforeach ?>
    </nav>

    <section id="bagian-sesi" class="scroll-mt-24 space-y-4" aria-labelledby="judul-bagian-sesi" data-bagian-layar="sesi">
        <h2 id="judul-bagian-sesi" class="flex items-center gap-2 text-xl font-bold text-primary-900 sm:text-2xl"><?= ikon('calendar-clock', 'size-6 text-oranye-600') ?>Per hari dan sesi</h2>
        <p class="text-gray-700">Sesi dibagi menurut jam mulai babak: Pagi sebelum 11.00, Siang 11.00 sampai sebelum 15.00, Sore mulai 15.00. Tiap <?= esc(mb_strtolower($sebutan)) ?> menampilkan daftar start setelah panitia mengunci pembagiannya, lalu hasil <?= esc(mb_strtolower($sebutan)) ?> itu setelah terbit.</p>

        <?php if ($daftar['hari'] === []) : ?>
            <p class="<?= $kartu ?> text-gray-700">Jadwal pertandingan belum diisi panitia. Tampilan per sesi dan per <?= esc(mb_strtolower($sebutan)) ?> muncul di sini setelah babak diberi tanggal.</p>
        <?php endif ?>

        <?php foreach ($daftar['hari'] as $hari) : ?>
            <div class="space-y-3" data-hari-layar="<?= esc($hari['tanggal'], 'attr') ?>">
                <h3 class="text-lg font-bold text-gray-900"><?= esc($hari['label']) ?></h3>
                <?php foreach ($hari['sesi'] as $sesi) : ?>
                    <div class="<?= $kartu ?>" data-sesi-layar="<?= esc($sesi['bagian'], 'attr') ?>">
                        <h4 class="text-base font-bold text-oranye-700">Sesi <?= esc(mb_strtolower($sesi['label'])) ?></h4>
                        <?= $tautan('Semua hasil sesi ' . mb_strtolower($sesi['label']), $sesi['alamat'], $sesi['terbit'] . ' dari ' . count($sesi['babak']) . ' babak sudah terbit', true) ?>
                        <ul class="mt-2 divide-y divide-gray-100 border-t border-gray-100">
                            <?php foreach ($sesi['babak'] as $b) : ?>
                                <li class="py-2" data-babak-layar="<?= $b['id'] ?>">
                                    <p class="font-semibold text-gray-900">
                                        <?php if ($b['jam'] !== '') : ?><span class="me-1 text-oranye-700 tabular-nums"><?= esc($b['jam']) ?></span><?php endif ?>
                                        <?= esc($b['nomor']) ?> · <?= esc($b['kelompok_umur']) ?> · <?= esc($b['babak']) ?>
                                        <?= $b['terbit'] ? komponen('lencana', ['label' => 'Hasil terbit', 'warna' => 'hijau']) : '' ?>
                                    </p>
                                    <?php if ($b['seri'] !== []) : ?>
                                        <?php foreach ($b['seri'] as $s) : ?>
                                            <?= $tautan($sebutan . ' ' . $s, 'layar/seri/' . $b['id'] . '/' . $s) ?>
                                        <?php endforeach ?>
                                    <?php else : ?>
                                        <p class="text-sm text-gray-600"><?= esc($b['dikunci'] ? 'Babak ini tidak dibagi ke ' . mb_strtolower($sebutan) . '.' : 'Pembagian ' . mb_strtolower($sebutan) . ' belum dikunci panitia.') ?></p>
                                    <?php endif ?>
                                </li>
                            <?php endforeach ?>
                        </ul>
                    </div>
                <?php endforeach ?>
            </div>
        <?php endforeach ?>
    </section>

    <section id="bagian-klasemen" class="scroll-mt-24 space-y-3" aria-labelledby="judul-bagian-klasemen" data-bagian-layar="klasemen">
        <h2 id="judul-bagian-klasemen" class="flex items-center gap-2 text-xl font-bold text-primary-900 sm:text-2xl"><?= ikon('trophy', 'size-6 text-oranye-600') ?>Klasemen juara umum</h2>
        <div class="<?= $kartu ?>">
            <?php if ($daftar['klasemen'] === []) : ?>
                <p class="text-gray-700">Klasemen muncul di sini setelah ada hasil Final yang terbit.</p>
            <?php endif ?>
            <?php foreach ($daftar['klasemen'] as $k) : ?>
                <?= $tautan('Klasemen ' . $k['label'], $k['alamat']) ?>
            <?php endforeach ?>
        </div>
    </section>

    <section id="bagian-kelompok-umur" class="scroll-mt-24 space-y-3" aria-labelledby="judul-bagian-kelompok-umur" data-bagian-layar="kelompok-umur">
        <h2 id="judul-bagian-kelompok-umur" class="flex items-center gap-2 text-xl font-bold text-primary-900 sm:text-2xl"><?= ikon('users', 'size-6 text-oranye-600') ?>Kelompok umur dan nomor lomba</h2>
        <p class="text-gray-700">Hasil per nomor memuat semua babak yang sudah terbit, Final paling atas.</p>
        <?php if ($daftar['kelompok_umur'] === []) : ?>
            <p class="<?= $kartu ?> text-gray-700">Nomor lomba belum ada.</p>
        <?php endif ?>
        <div class="grid gap-4 lg:grid-cols-2">
            <?php foreach ($daftar['kelompok_umur'] as $ku) : ?>
                <div class="<?= $kartu ?>" data-kelompok-umur-layar="<?= $ku['id'] ?>">
                    <h3 class="text-lg font-bold text-gray-900"><?= esc($ku['nama']) ?></h3>
                    <?= $tautan('Semua hasil ' . $ku['nama'], $ku['alamat'], $ku['terbit'] . ' dari ' . count($ku['nomor']) . ' nomor sudah terbit', true) ?>
                    <ul class="mt-2 divide-y divide-gray-100 border-t border-gray-100">
                        <?php foreach ($ku['nomor'] as $n) : ?>
                            <li><?= $tautan($n['nama'], $n['alamat'], $n['terbit'] ? 'Hasil terbit' : 'Belum terbit') ?></li>
                        <?php endforeach ?>
                    </ul>
                </div>
            <?php endforeach ?>
        </div>
    </section>

    <section id="bagian-putar" class="scroll-mt-24 space-y-3" aria-labelledby="judul-bagian-putar" data-bagian-layar="putar">
        <h2 id="judul-bagian-putar" class="flex items-center gap-2 text-xl font-bold text-primary-900 sm:text-2xl"><?= ikon('repeat', 'size-6 text-oranye-600') ?>Papan putar</h2>
        <div class="<?= $kartu ?>">
            <p class="text-gray-700">Berganti sendiri antara hasil terbaru, juara umum, babak yang sedang berlangsung, dan jadwal berikutnya. Isi dan lamanya diatur admin.</p>
            <?= $tautan('Papan putar', 'layar/putar') ?>
        </div>
    </section>

    <p class="sr-only" role="status" aria-live="polite" data-salin-status></p>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/layar-daftar.js') ?>" defer></script>
<?= $this->endSection() ?>
