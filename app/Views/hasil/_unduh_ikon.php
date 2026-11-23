<?php
/**
 * Tombol unduh hasil publik berupa ikon saja (K175 butir 7): ikon PDF atau
 * lembar kerja. Nama aksesibel lengkap lewat `aria-label`; teks pendek
 * ("Unduh PDF") muncul sebagai gelembung saat kursor di atasnya, saat
 * difokus papan ketik, atau saat ditekan di ponsel. Target sentuh 44 piksel.
 *
 * Sementara di tampilan hasil publik; disatukan dengan komponen `tombol_unduh`
 * panel admin (F11-10, K174 butir 8) saat keduanya digabung.
 *
 *     view('hasil/_unduh_ikon', ['jenis' => 'pdf', 'href' => site_url('hasil/3/pdf'), 'label' => 'Unduh PDF hasil Lari 60 m putra KU 10'], ['saveData' => false]);
 *
 * @var string                     $jenis   pdf | excel
 * @var string                     $href
 * @var string|null                $label   nama aksesibel lengkap; bawaan "Unduh PDF" / "Unduh Excel"
 * @var array<string, scalar|null> $atribut atribut tambahan (`data-*`)
 */
// Lewat `view()`, jadi pemanggil selalu mengisi `label` dan `atribut` supaya tidak terisi data halaman lain.

$excel   = ($jenis ?? 'pdf') === 'excel';
$pendek  = $excel ? 'Unduh Excel' : 'Unduh PDF';
$label   = ($label ?? '') !== '' ? $label : $pendek;
$atribut = ($atribut ?? []) + ['data-unduh-ikon' => $excel ? 'excel' : 'pdf'];
?>
<a href="<?= esc($href, 'attr') ?>" class="group relative inline-flex size-11 shrink-0 items-center justify-center rounded-lg border border-gray-300 bg-white transition-colors hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-100 active:bg-gray-100 <?= $excel ? 'text-green-700' : 'text-red-700' ?>" aria-label="<?= esc($label, 'attr') ?>"<?= atribut_html($atribut) ?>>
    <?= ikon($excel ? 'file-spreadsheet' : 'file-text', 'size-5') ?>
    <span class="pointer-events-none absolute left-1/2 top-full z-20 mt-1.5 hidden -translate-x-1/2 whitespace-nowrap rounded-md bg-gray-900 px-2 py-1 text-xs font-semibold text-white shadow group-hover:block group-focus-visible:block group-active:block" aria-hidden="true"><?= esc($pendek) ?></span>
</a>
