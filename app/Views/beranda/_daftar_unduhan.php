<?php
/**
 * Daftar berkas unduhan, dipakai bersama oleh bagian unduhan halaman depan
 * (`beranda/_unduhan.php`) dan halaman `/unduhan` (`unduhan.php`).
 *
 * Tiap berkas menjadi satu kartu (tata letak baru Tailwind, F9-03): ikon
 * jenis, judul, keterangan (baris baru menjadi `<br>`), dan tombol. Jenis dan
 * ukuran berkas tidak ditampilkan (F10-09); pembaca layar tetap mendengarnya
 * di label tombol Unduh. Penanda uji berupa atribut `data-*`. Teks admin di-escape.
 *
 * F10-09: PDF dan gambar punya tombol "Lihat" (rute `unduhan/<id>/lihat`,
 * `inline`). `unduhan-pratinjau.js` (dimuat halaman pemanggil di bagian
 * `skrip` bila `BerkasUnduhan::adaPratinjau()`) membukanya di modal Flowbite
 * di semua ukuran layar (`<img>` untuk gambar, `<iframe>` untuk PDF dari
 * berkas sendiri; di ponsel modal PDF berisi keterangan dengan tombol Buka di
 * tab baru dan Unduh, F10-08); tanpa JavaScript tautannya membuka tab baru. Berkas lain
 * hanya "Unduh". Tombol Unduh tetap ke alamat berkas lama dengan atribut
 * `download` bernama judulnya (`BerkasUnduhan::namaSimpan()`).
 *
 * @var list<array{id: int, judul: string, keterangan: string, alamat: string, jenis: string, ukuran: string}> $unduhan
 *      `IsiHalamanDepan::unduhan()`; tidak kosong (pemanggil yang memeriksa).
 * @var string $tingkatJudul Tag judul tiap berkas: 'h3' di halaman depan (di bawah h2), 'h2' di `/unduhan`.
 */

use App\Libraries\BerkasUnduhan;

$tingkatJudul = ($tingkatJudul ?? 'h3') === 'h2' ? 'h2' : 'h3';

// Ikon Lucide per jenis berkas yang boleh diunggah (Unggahan, F2-07).
$ikonJenis = static fn (string $jenis): string => match ($jenis) {
    'PDF'                => 'file-text',
    'JPG', 'JPEG', 'PNG' => 'file-image',
    'DOC', 'DOCX'        => 'file-type',
    'XLS', 'XLSX'        => 'file-spreadsheet',
    default              => 'file',
};
// ikon-sprite: file-text file-image file-type file-spreadsheet file download eye x external-link

$kelasTombol = 'inline-flex min-h-11 items-center justify-center gap-2 rounded-lg px-4 text-sm font-semibold focus:outline-none focus:ring-4 focus:ring-primary-200';
?>
<ul class="grid gap-4 md:grid-cols-2" data-daftar-unduhan>
    <?php foreach ($unduhan as $berkas) : ?>
        <?php
        $rincian   = trim($berkas['jenis'] . ($berkas['jenis'] !== '' && $berkas['ukuran'] !== '' ? ' · ' : '') . $berkas['ukuran']);
        $pratinjau = BerkasUnduhan::pratinjau($berkas['jenis']);
        $namaSimpan = BerkasUnduhan::namaSimpan($berkas['judul'], $berkas['jenis']);
        ?>
        <li class="flex flex-col gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-lembut sm:flex-row sm:items-center" data-unduhan-butir data-id="<?= (int) $berkas['id'] ?>">
            <span class="flex shrink-0 text-primary-700" data-ikon-jenis="<?= esc($ikonJenis($berkas['jenis']), 'attr') ?>"><?= ikon($ikonJenis($berkas['jenis']), 'size-9') ?></span>
            <div class="min-w-0 flex-1">
                <<?= $tingkatJudul ?> class="text-lg font-bold text-gray-900 break-words"><?= esc($berkas['judul']) ?></<?= $tingkatJudul ?>>
                <?php if ($berkas['keterangan'] !== '') : ?>
                    <p class="mt-1 text-gray-600 break-words" data-unduhan-keterangan><?= nl2br(esc($berkas['keterangan']), false) ?></p>
                <?php endif ?>
            </div>
            <div class="flex shrink-0 flex-wrap gap-2">
                <?php if ($pratinjau !== null) : ?>
                    <a class="<?= $kelasTombol ?> border border-gray-300 bg-white text-primary-700 hover:bg-gray-50" href="<?= esc(BerkasUnduhan::alamatLihat((int) $berkas['id']), 'attr') ?>" target="_blank" rel="noopener" data-unduhan-lihat data-pratinjau="<?= $pratinjau ?>" data-judul="<?= esc($berkas['judul'], 'attr') ?>" data-unduh="<?= esc($berkas['alamat'], 'attr') ?>" data-nama-simpan="<?= esc($namaSimpan, 'attr') ?>">
                        <?= ikon('eye', 'size-4') ?> Lihat<span class="sr-only"> <?= esc($berkas['judul']) ?></span>
                    </a>
                <?php endif ?>
                <a class="<?= $kelasTombol ?> bg-primary-700 text-white hover:bg-primary-800" href="<?= esc($berkas['alamat'], 'attr') ?>" download="<?= esc($namaSimpan, 'attr') ?>" data-unduhan-tombol>
                    <?= ikon('download', 'size-4') ?> Unduh<span class="sr-only"> <?= esc($berkas['judul']) ?><?= $rincian !== '' ? ' (' . esc($rincian) . ')' : '' ?></span>
                </a>
            </div>
        </li>
    <?php endforeach ?>
</ul>

<?php if (BerkasUnduhan::adaPratinjau($unduhan)) : ?>
    <div id="modal-pratinjau-unduhan" tabindex="-1" aria-hidden="true" aria-labelledby="modal-pratinjau-unduhan-judul" role="dialog" aria-modal="true" class="fixed inset-x-0 top-0 z-50 hidden h-full max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden p-4" data-pratinjau-modal>
        <div class="relative max-h-full w-full max-w-5xl">
            <div class="relative rounded-2xl bg-white shadow-angkat">
                <div class="flex items-center justify-between gap-4 border-b border-gray-200 p-4 sm:px-6">
                    <h2 id="modal-pratinjau-unduhan-judul" class="min-w-0 break-words text-lg font-bold text-gray-900" data-pratinjau-judul>Lihat berkas</h2>
                    <button type="button" class="inline-flex size-11 shrink-0 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-900" data-pratinjau-tutup>
                        <?= ikon('x', '', 'Tutup') ?>
                    </button>
                </div>
                <div class="p-2 sm:p-4" data-pratinjau-isi></div>
                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-gray-200 p-4 sm:px-6">
                    <a class="<?= $kelasTombol ?> border border-gray-300 bg-white text-primary-700 hover:bg-gray-50" href="#" target="_blank" rel="noopener" data-pratinjau-tab><?= ikon('external-link', 'size-4') ?> Buka di tab baru</a>
                    <a class="<?= $kelasTombol ?> bg-primary-700 text-white hover:bg-primary-800" href="#" download data-pratinjau-unduh><?= ikon('download', 'size-4') ?> Unduh</a>
                </div>
            </div>
        </div>
    </div>
<?php endif ?>
