<?php
/**
 * Tombol unduh ikon saja (F11-10, K174 butir 8, juga K175 butir 7): ikon
 * lembar kerja untuk Excel, ikon PDF untuk PDF. Komponen umum, bisa dipakai
 * panel admin dan halaman publik. Teksnya muncul sebagai label kecil saat
 * kursor di atas tombol, saat tombol difokus papan ketik, atau selama
 * ditekan (`:active`, cukup CSS). Label yang tetap tampil sebentar setelah
 * diketuk di layar sentuh memakai atribut `data-tampil-label`, yang di panel
 * admin dipasang `aset/js/panel-ringkas.js`; halaman lain boleh memasangnya
 * dengan skrip sendiri. Nama aksesibel tetap lengkap lewat `aria-label`;
 * label kecil `aria-hidden`.
 *
 *     komponen('tombol_unduh', ['jenis' => 'excel', 'href' => site_url('admin/atlet/excel'), 'atribut' => ['id' => 'excel-atlet']]);
 *     komponen('tombol_unduh', ['jenis' => 'pdf', 'href' => …, 'label' => 'Unduh PDF daftar seri Sabtu']);
 *
 * Label kecil ditambatkan ke tepi kanan tombol dan baru ikut tata letak saat
 * tampil, jadi tidak membuat halaman bergulir mendatar.
 *
 * @var string                     $jenis   excel | pdf | zip
 * @var string                     $href
 * @var string|null                $label   nama aksesibel lengkap; bawaan "Unduh Excel"/"Unduh PDF"/"Unduh ZIP"
 * @var string|null                $teks    teks label kecil; bawaan sama dengan `label` bawaan jenisnya
 * @var string|null                $kelas   kelas tambahan
 * @var array<string, scalar|null> $atribut atribut tambahan (`id`, `data-*`, …)
 */
$jenis   = in_array($jenis ?? '', ['excel', 'pdf', 'zip'], true) ? $jenis : 'excel';
$bawaan  = ['excel' => 'Unduh Excel', 'pdf' => 'Unduh PDF', 'zip' => 'Unduh ZIP'][$jenis];
$label ??= $bawaan;
$teks  ??= $bawaan;
$warna   = ['excel' => 'text-green-700', 'pdf' => 'text-red-700', 'zip' => 'text-primary-700'][$jenis];
$atribut = ($atribut ?? []) + ['aria-label' => $label, 'data-tombol-unduh' => $jenis];

$kelasAkhir = trim('group/unduh relative inline-flex size-11 shrink-0 items-center justify-center rounded-lg border border-gray-300 bg-white hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-100 ' . $warna . ' ' . ($kelas ?? ''));
// ikon-sprite: file-spreadsheet file-archive
?>
<a href="<?= esc($href ?? '#') ?>" class="<?= esc($kelasAkhir) ?>"<?= atribut_html($atribut) ?>>
    <?php if ($jenis === 'pdf') : ?>
        <svg class="ikon size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" data-ikon-pdf><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><text x="12" y="17.5" text-anchor="middle" font-size="6.5" font-weight="800" font-family="inherit" fill="currentColor" stroke="none">PDF</text></svg>
    <?php else : ?>
        <?= ikon($jenis === 'zip' ? 'file-archive' : 'file-spreadsheet', 'size-6') ?>
    <?php endif ?>
    <span class="pointer-events-none absolute end-0 bottom-full z-30 mb-1.5 hidden whitespace-nowrap rounded-md bg-gray-900 px-2 py-1 text-xs font-semibold text-white shadow-lg group-hover/unduh:block group-focus-visible/unduh:block group-active/unduh:block group-data-tampil-label/unduh:block" aria-hidden="true"><?= esc($teks) ?></span>
</a>
