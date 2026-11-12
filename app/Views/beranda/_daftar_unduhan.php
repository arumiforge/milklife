<?php
/**
 * Daftar berkas unduhan, dipakai bersama oleh bagian unduhan halaman depan
 * (`beranda/_unduhan.php`) dan halaman `/unduhan` (`unduhan.php`).
 *
 * Tiap berkas menjadi satu kartu: ikon jenis, judul, keterangan (baris baru
 * menjadi `<br>`), jenis dan ukuran, serta tombol unduh. Teks admin di-escape.
 * Tombol memakai atribut `download` dengan nama berkas dari judulnya
 * (alamat berkas sendiri berupa nama acak).
 *
 * @var list<array{id: int, judul: string, keterangan: string, alamat: string, jenis: string, ukuran: string}> $unduhan
 *      `IsiHalamanDepan::unduhan()`; tidak kosong (pemanggil yang memeriksa).
 * @var string $tingkatJudul Tag judul tiap berkas: 'h3' di halaman depan (di bawah h2), 'h2' di `/unduhan`.
 */

$tingkatJudul = ($tingkatJudul ?? 'h3') === 'h2' ? 'h2' : 'h3';

// Ikon Bootstrap Icons per jenis berkas yang boleh diunggah (Unggahan, F2-07).
$ikonJenis = static fn (string $jenis): string => match ($jenis) {
    'PDF'                => 'file-earmark-pdf',
    'JPG', 'JPEG', 'PNG' => 'file-earmark-image',
    'DOC', 'DOCX'        => 'file-earmark-word',
    'XLS', 'XLSX'        => 'file-earmark-excel',
    default              => 'file-earmark',
};

// Nama berkas saat disimpan: judul tanpa tanda yang dilarang sistem berkas, ditambah ekstensinya.
$namaSimpan = static function (string $judul, string $jenis): string {
    $nama = trim((string) preg_replace('/[\\\\\/:*?"<>|\x00-\x1F]+/u', '-', $judul), " .-");

    return ($nama === '' ? 'unduhan' : $nama) . ($jenis === '' ? '' : '.' . strtolower($jenis));
};
?>
<ul class="daftar-unduhan list-unstyled mb-0">
    <?php foreach ($unduhan as $berkas) : ?>
        <?php $rincian = trim($berkas['jenis'] . ($berkas['jenis'] !== '' && $berkas['ukuran'] !== '' ? ' · ' : '') . $berkas['ukuran']); ?>
        <li class="unduhan-butir" data-id="<?= (int) $berkas['id'] ?>">
            <span class="unduhan-ikon"><i class="bi bi-<?= $ikonJenis($berkas['jenis']) ?>" aria-hidden="true"></i></span>
            <div class="unduhan-isi">
                <<?= $tingkatJudul ?> class="unduhan-judul"><?= esc($berkas['judul']) ?></<?= $tingkatJudul ?>>
                <?php if ($berkas['keterangan'] !== '') : ?>
                    <p class="unduhan-keterangan"><?= nl2br(esc($berkas['keterangan']), false) ?></p>
                <?php endif ?>
                <?php if ($rincian !== '') : ?>
                    <p class="unduhan-rincian"><?= esc($rincian) ?></p>
                <?php endif ?>
            </div>
            <a class="btn btn-primary unduhan-tombol" href="<?= esc($berkas['alamat'], 'attr') ?>" download="<?= esc($namaSimpan($berkas['judul'], $berkas['jenis']), 'attr') ?>">
                <i class="bi bi-download" aria-hidden="true"></i> Unduh<span class="visually-hidden"> <?= esc($berkas['judul']) ?><?= $rincian !== '' ? ' (' . esc($rincian) . ')' : '' ?></span>
            </a>
        </li>
    <?php endforeach ?>
</ul>
