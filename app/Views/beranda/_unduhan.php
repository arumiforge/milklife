<?php
/**
 * Bagian unduhan halaman depan (`#unduhan`): daftar berkas (potongan
 * bersama `beranda/_daftar_unduhan`, juga dipakai halaman `/unduhan`) dan
 * tautan ke halaman semua unduhan.
 *
 * Data yang tersedia (dari `IsiHalamanDepan::unduhan()`):
 *
 * @var list<array{id: int, judul: string, keterangan: string, alamat: string, jenis: string, ukuran: string}> $unduhan
 *      Berkas yang ditandai tampil dan ada di disk, berurutan; [] bila belum ada.
 *      `alamat` sudah `base_url()`, `jenis` contoh 'PDF', `ukuran` contoh '1,5 MB'.
 *
 * Kesepakatan (rencana Tahap 3, "Halaman depan" dan "Halaman unduhan"):
 * - Bagian tidak ditampilkan bila `$unduhan === []`.
 * - `<section id="unduhan" aria-labelledby="judul-unduhan">` dengan `<h2 id="judul-unduhan">`.
 * - Tautan ke halaman semua unduhan: `site_url('unduhan')`.
 * - Tata letak baru Tailwind (F9-03); penanda uji berupa atribut `data-*`.
 */

if ($unduhan === []) {
    return;
}
?>
<section id="unduhan" class="scroll-mt-20" aria-labelledby="judul-unduhan">
    <div class="mx-auto max-w-screen-xl px-4 py-12 sm:py-16">
        <div class="flex flex-col items-center gap-4 text-center">
            <div>
                <p class="judul-tampilan" lang="en" aria-hidden="true" data-judul-inggris>Downloads</p>
                <h2 id="judul-unduhan" class="judul-keterangan mt-2">Unduhan</h2>
                <p class="mt-2 text-lg text-gray-600">Flyer, petunjuk teknis, dan berkas lain dari panitia.</p>
            </div>
            <?= komponen('tombol', ['label' => 'Lihat semua unduhan', 'href' => site_url('unduhan'), 'varian' => 'kedua', 'ikon' => 'arrow-right', 'ikonKanan' => true, 'atribut' => ['data-unduhan-semua' => true]]) ?>
        </div>

        <div class="mt-8">
            <?= view('beranda/_daftar_unduhan', ['unduhan' => $unduhan, 'tingkatJudul' => 'h3'], ['saveData' => false]) ?>
        </div>
    </div>
</section>
