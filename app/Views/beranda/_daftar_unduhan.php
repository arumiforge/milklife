<?php
/**
 * Daftar berkas unduhan, dipakai bersama oleh bagian unduhan halaman depan
 * (`beranda/_unduhan.php`) dan halaman `/unduhan` (`unduhan.php`).
 *
 * Tiap berkas menjadi satu kartu (tata letak baru Tailwind, F9-03): ikon
 * jenis, judul, keterangan (baris baru menjadi `<br>`), jenis dan ukuran,
 * serta tombol unduh. Penanda uji berupa atribut `data-*`. Teks admin di-escape.
 * Tombol memakai atribut `download` dengan nama berkas dari judulnya
 * (alamat berkas sendiri berupa nama acak).
 *
 * @var list<array{id: int, judul: string, keterangan: string, alamat: string, jenis: string, ukuran: string}> $unduhan
 *      `IsiHalamanDepan::unduhan()`; tidak kosong (pemanggil yang memeriksa).
 * @var string $tingkatJudul Tag judul tiap berkas: 'h3' di halaman depan (di bawah h2), 'h2' di `/unduhan`.
 */

$tingkatJudul = ($tingkatJudul ?? 'h3') === 'h2' ? 'h2' : 'h3';

// Ikon Lucide per jenis berkas yang boleh diunggah (Unggahan, F2-07).
$ikonJenis = static fn (string $jenis): string => match ($jenis) {
    'PDF'                => 'file-text',
    'JPG', 'JPEG', 'PNG' => 'file-image',
    'DOC', 'DOCX'        => 'file-type',
    'XLS', 'XLSX'        => 'file-spreadsheet',
    default              => 'file',
};
// ikon-sprite: file-text file-image file-type file-spreadsheet file download

// Nama berkas saat disimpan: judul tanpa tanda yang dilarang sistem berkas, ditambah ekstensinya.
$namaSimpan = static function (string $judul, string $jenis): string {
    $nama = trim((string) preg_replace('/[\\\\\/:*?"<>|\x00-\x1F]+/u', '-', $judul), " .-");

    return ($nama === '' ? 'unduhan' : $nama) . ($jenis === '' ? '' : '.' . strtolower($jenis));
};
?>
<ul class="grid gap-4 md:grid-cols-2" data-daftar-unduhan>
    <?php foreach ($unduhan as $berkas) : ?>
        <?php $rincian = trim($berkas['jenis'] . ($berkas['jenis'] !== '' && $berkas['ukuran'] !== '' ? ' · ' : '') . $berkas['ukuran']); ?>
        <li class="flex flex-col gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-lembut sm:flex-row sm:items-center" data-unduhan-butir data-id="<?= (int) $berkas['id'] ?>">
            <span class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-primary-100 text-primary-700" data-ikon-jenis="<?= esc($ikonJenis($berkas['jenis']), 'attr') ?>"><?= ikon($ikonJenis($berkas['jenis']), 'size-6') ?></span>
            <div class="min-w-0 flex-1">
                <<?= $tingkatJudul ?> class="text-lg font-bold text-gray-900 break-words"><?= esc($berkas['judul']) ?></<?= $tingkatJudul ?>>
                <?php if ($berkas['keterangan'] !== '') : ?>
                    <p class="mt-1 text-gray-600 break-words" data-unduhan-keterangan><?= nl2br(esc($berkas['keterangan']), false) ?></p>
                <?php endif ?>
                <?php if ($rincian !== '') : ?>
                    <p class="mt-1 text-sm text-gray-500" data-unduhan-rincian><?= esc($rincian) ?></p>
                <?php endif ?>
            </div>
            <a class="inline-flex min-h-11 shrink-0 items-center justify-center gap-2 rounded-lg bg-primary-700 px-5 text-sm font-semibold text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300" href="<?= esc($berkas['alamat'], 'attr') ?>" download="<?= esc($namaSimpan($berkas['judul'], $berkas['jenis']), 'attr') ?>" data-unduhan-tombol>
                <?= ikon('download', 'size-4') ?> Unduh<span class="sr-only"> <?= esc($berkas['judul']) ?><?= $rincian !== '' ? ' (' . esc($rincian) . ')' : '' ?></span>
            </a>
        </li>
    <?php endforeach ?>
</ul>
