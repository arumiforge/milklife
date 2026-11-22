<?php
/**
 * Judul editorial halaman publik (F10-09, K153): judul Inggris
 * besar bergaya tampilan, lalu judul Indonesia sebagai `<h1>`/`<h2>`.
 *
 * Judul Inggris hanya hiasan editorial (`aria-hidden`, `lang="en"`); judul
 * yang dibaca pembaca layar, judul tab, dan uji tetap judul Indonesia.
 * Tanpa `inggris`, judul Indonesia sendiri yang tampil besar (gaya F10-05).
 *
 *     view('tata_letak/_judul_editorial', ['inggris' => 'Race Day', 'judul' => 'Jadwal pertandingan', 'ikon' => 'calendar-clock'], ['saveData' => false]);
 *
 * @var string      $judul   judul Indonesia (di-escape)
 * @var string|null $inggris judul editorial Inggris (di-escape)
 * @var string|null $ikon    nama ikon Lucide, polos tanpa latar
 * @var string|null $id      atribut `id` judul Indonesia
 * @var string|null $tingkat 'h1' (bawaan) atau 'h2'
 */

$tingkat = ($tingkat ?? 'h1') === 'h2' ? 'h2' : 'h1';
$atribut = empty($id) ? '' : ' id="' . esc($id, 'attr') . '"';
?>
<?php if (! empty($inggris)) : ?>
    <p class="judul-tampilan ornamen-judul min-w-0 break-words" lang="en" aria-hidden="true" data-judul-inggris><?= esc($inggris) ?></p>
    <<?= $tingkat . $atribut ?> class="judul-keterangan mt-2 flex min-w-0 items-center gap-2 break-words">
        <?php if (! empty($ikon)) : ?><?= ikon($ikon, 'size-6 text-oranye-600 sm:size-7') ?><?php endif ?><?= esc($judul) ?>
    </<?= $tingkat ?>>
<?php else : ?>
    <div class="flex flex-wrap items-center gap-3">
        <?php if (! empty($ikon)) : ?><?= ikon($ikon, 'size-9 text-oranye-600 sm:size-11') ?><?php endif ?>
        <<?= $tingkat . $atribut ?> class="judul-tampilan min-w-0 break-words"><?= esc($judul) ?></<?= $tingkat ?>>
    </div>
<?php endif ?>
