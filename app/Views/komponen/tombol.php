<?php
/**
 * Tombol atau tautan bergaya tombol (Flowbite Pro), target sentuh paling kecil 44 piksel.
 *
 *     komponen('tombol', ['label' => 'Daftarkan sekolah', 'href' => site_url('pendaftaran'), 'ikon' => 'arrow-right', 'ikonKanan' => true]);
 *     komponen('tombol', ['label' => 'Simpan', 'tipe' => 'submit']);
 *     komponen('tombol', ['label' => 'Hapus', 'varian' => 'bahaya', 'atribut' => ['data-konfirmasi' => 'Hapus album ini?']]);
 *
 * @var string                     $label
 * @var string|null                $varian    utama (bawaan) | oranye | kedua | bahaya | hantu
 * @var string|null                $ukuran    sedang (bawaan) | kecil | besar
 * @var string|null                $ikon      nama ikon Lucide
 * @var bool|null                  $ikonKanan ikon di kanan label
 * @var string|null                $href      bila diisi, menjadi `<a>`
 * @var string|null                $tipe      button (bawaan) | submit | reset
 * @var bool|null                  $blok      selebar wadah
 * @var bool|null                  $nonaktif
 * @var string|null                $kelas     kelas tambahan
 * @var array<string, scalar|null> $atribut   atribut tambahan (`data-*`, `aria-*`, `id`, …)
 */

$varian = [
    // Warna dan latar sapuan kuas G10-11 (bila berkasnya ada) dari kelas `tombol-utama-berart` (F10-05).
    'utama'  => 'tombol-utama-berart focus:ring-primary-300',
    'oranye' => 'tombol-utama-berart tombol-berart-oranye focus:ring-oranye-300',
    'kedua'  => 'border border-gray-300 bg-white text-gray-900 hover:bg-gray-50 hover:text-primary-700 focus:ring-gray-100',
    'bahaya' => 'bg-red-700 text-white hover:bg-red-800 focus:ring-red-300',
    'hantu'  => 'text-primary-700 hover:bg-primary-100 focus:ring-primary-100',
][$varian ?? 'utama'] ?? '';

$ukuran = [
    'kecil'  => 'min-h-9 px-3 py-1.5 text-sm',
    'sedang' => 'min-h-11 px-5 py-2.5 text-sm',
    'besar'  => 'min-h-12 px-6 py-3 text-base',
][$ukuran ?? 'sedang'] ?? '';

$kelasAkhir = trim('inline-flex items-center justify-center gap-2 rounded-lg text-center font-semibold transition-colors focus:outline-none focus:ring-4 '
    . $varian . ' ' . $ukuran . (($blok ?? false) ? ' w-full' : '')
    . (($nonaktif ?? false) ? ' pointer-events-none cursor-not-allowed opacity-60' : '') . ' ' . ($kelas ?? ''));

$isi = esc($label);

if (! empty($ikon)) {
    $isi = ($ikonKanan ?? false) ? $isi . ikon($ikon) : ikon($ikon) . $isi;
}

$atribut ??= [];
?>
<?php if (! empty($href)) : ?>
<a href="<?= esc($href) ?>" class="<?= esc($kelasAkhir) ?>"<?= atribut_html($atribut + (($nonaktif ?? false) ? ['aria-disabled' => 'true', 'tabindex' => '-1'] : [])) ?>><?= $isi ?></a>
<?php else : ?>
<button type="<?= esc($tipe ?? 'button') ?>" class="<?= esc($kelasAkhir) ?>"<?= atribut_html($atribut + ['disabled' => $nonaktif ?? false]) ?>><?= $isi ?></button>
<?php endif ?>
