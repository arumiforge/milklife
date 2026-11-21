<?php
/**
 * Daftar tanya-jawab Bantuan (`GET /admin/pengaturan/bantuan`, F9-04), tata
 * letak `admin_tw`: satu bagian per kategori, masing-masing dengan tombol
 * tambah sendiri. Tiap baris punya tombol Naik, Turun, Ubah,
 * Sembunyikan/Tampilkan, dan Hapus. Bila daftar masih kosong, ada tombol
 * untuk memasang tanya-jawab bawaan.
 *
 * @var CodeIgniter\View\View                          $this
 * @var array<string, list<array<string, mixed>>>      $perKategori kunci kategori => baris menurut urutan
 * @var int                                            $jumlah      semua tanya-jawab
 */

use App\Models\FaqModel;

$alamat = static fn (string $jalur = ''): string => site_url('admin/pengaturan/bantuan' . ($jalur !== '' ? '/' . $jalur : ''));

// Ikon kategori di `FaqModel::KATEGORI` bernama Bootstrap Icons; padanan Lucide
// sama dengan halaman Bantuan umum.
$ikonKategori = [
    'sebelum'    => 'clipboard-check',
    'mengisi'    => 'smartphone',
    'pembayaran' => 'landmark',
    'cek'        => 'file-search',
    'hari_lomba' => 'flag',
    'hasil'      => 'trophy',
];
// ikon-sprite: clipboard-check smartphone landmark file-search flag trophy circle-help arrow-up arrow-down eye eye-off
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?>Bantuan<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Bantuan<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-6 p-4 sm:p-6">
    <?= $this->include('admin/pengaturan/_navigasi') ?>

    <div class="flex max-w-3xl items-start gap-3 rounded-xl border border-primary-200 bg-primary-100 p-4 text-sm text-primary-900" role="note">
        <?= ikon('info', 'mt-0.5 shrink-0') ?>
        <div>
            Tanya-jawab ini tampil di halaman Bantuan dan di tombol bantuan formulir pendaftaran.
            Tulis jawaban pendek dengan bahasa sehari-hari dan sebut tombol yang perlu ditekan.
            Tanya-jawab yang disembunyikan tetap tersimpan di sini.
        </div>
    </div>

    <?php if ($jumlah === 0) : ?>
        <section class="flex flex-col items-center rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-10 text-center" aria-labelledby="judul-bawaan">
            <span class="mb-4 inline-flex size-14 items-center justify-center rounded-2xl bg-primary-100 text-primary-700"><?= ikon('circle-help', 'size-7') ?></span>
            <h2 id="judul-bawaan" class="text-lg font-bold text-gray-900">Belum ada tanya-jawab</h2>
            <p class="mt-1 max-w-xl text-gray-600">
                Pasang tanya-jawab bawaan yang sudah disusun dari petunjuk teknis, lalu sesuaikan isinya.
                Atau tambahkan sendiri lewat tombol di tiap kategori di bawah.
            </p>
            <form action="<?= $alamat('bawaan') ?>" method="post" class="mt-5">
                <?= csrf_field() ?>
                <?= komponen('tombol', ['label' => 'Pasang tanya-jawab bawaan', 'tipe' => 'submit', 'ikon' => 'wand-sparkles']) ?>
            </form>
        </section>
    <?php endif ?>

    <?php foreach ($perKategori as $kunci => $daftar) : ?>
        <?php
        $kategori = FaqModel::KATEGORI[$kunci] ?? ['label' => $kunci . ' (kategori tidak dikenal)', 'keterangan' => 'Ubah tanya-jawab di bawah dan pilih kategori yang ada.'];
        $idBagian = 'kategori-' . str_replace('_', '-', (string) $kunci);
        $terakhir = count($daftar) - 1;
        ?>
        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-lembut sm:p-6" id="<?= esc($idBagian, 'attr') ?>" aria-labelledby="judul-<?= esc($idBagian, 'attr') ?>">
            <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <h2 id="judul-<?= esc($idBagian, 'attr') ?>" class="flex flex-wrap items-center gap-2 text-lg font-bold text-gray-900">
                        <?= ikon($ikonKategori[$kunci] ?? 'circle-help', 'text-primary-700') ?>
                        <?= esc($kategori['label']) ?>
                        <?= komponen('lencana', ['label' => (string) count($daftar), 'warna' => 'abu']) ?>
                    </h2>
                    <p class="mt-1 text-sm text-gray-600"><?= esc($kategori['keterangan']) ?></p>
                </div>
                <?php if (FaqModel::kategoriSah($kunci)) : ?>
                    <?= komponen('tombol', ['label' => 'Tambah tanya-jawab', 'href' => $alamat('tambah') . '?kategori=' . esc((string) $kunci, 'url'), 'varian' => 'kedua', 'ikon' => 'plus']) ?>
                <?php endif ?>
            </div>

            <?php if ($daftar === []) : ?>
                <p class="rounded-xl border border-gray-200 px-4 py-3 text-gray-600">Belum ada tanya-jawab di kategori ini.</p>
            <?php else : ?>
                <div class="relative overflow-x-auto rounded-xl border border-gray-200">
                    <table class="w-full text-left text-sm text-gray-700">
                        <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-700">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-end">No</th>
                                <th scope="col" class="px-4 py-3">Pertanyaan</th>
                                <th scope="col" class="px-4 py-3"><span class="sr-only">Tindakan</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($daftar as $i => $faq) : ?>
                                <?php
                                $id     = (int) $faq['id'];
                                $aktif  = (int) $faq['aktif'] === 1;
                                $judul  = (string) $faq['pertanyaan'];
                                ?>
                                <tr id="faq-<?= $id ?>" data-id="<?= $id ?>" class="border-t border-gray-100<?= $aktif ? '' : ' text-gray-500' ?>">
                                    <td class="px-4 py-3 text-end align-top text-gray-500"><?= $i + 1 ?></td>
                                    <td class="min-w-48 px-4 py-3 align-top">
                                        <span class="font-semibold break-words <?= $aktif ? 'text-gray-900' : 'text-gray-500' ?>"><?= esc($judul) ?></span>
                                        <?php if (! $aktif) : ?>
                                            <?= komponen('lencana', ['label' => 'Disembunyikan', 'warna' => 'abu', 'ikon' => 'eye-off']) ?>
                                        <?php endif ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap justify-end gap-2">
                                            <?php foreach (['naik' => ['arrow-up', 'Naikkan', $i === 0], 'turun' => ['arrow-down', 'Turunkan', $i === $terakhir]] as $arah => [$ikon, $label, $mati]) : ?>
                                                <form action="<?= $alamat($id . '/' . $arah) ?>" method="post">
                                                    <?= csrf_field() ?>
                                                    <?= komponen('tombol', ['label' => '', 'ikon' => $ikon, 'tipe' => 'submit', 'varian' => 'kedua', 'ukuran' => 'kecil', 'kelas' => 'min-h-11 min-w-11', 'nonaktif' => $mati, 'atribut' => ['title' => $label, 'aria-label' => $label . ' ' . $judul]]) ?>
                                                </form>
                                            <?php endforeach ?>
                                            <?= komponen('tombol', ['label' => 'Ubah', 'href' => $alamat($id . '/ubah'), 'ikon' => 'pencil', 'varian' => 'kedua', 'ukuran' => 'kecil', 'kelas' => 'min-h-11 whitespace-nowrap', 'atribut' => ['aria-label' => 'Ubah ' . $judul]]) ?>
                                            <form action="<?= $alamat($id . '/aktif') ?>" method="post">
                                                <?= csrf_field() ?>
                                                <?= komponen('tombol', ['label' => $aktif ? 'Sembunyikan' : 'Tampilkan', 'ikon' => $aktif ? 'eye-off' : 'eye', 'tipe' => 'submit', 'varian' => 'kedua', 'ukuran' => 'kecil', 'kelas' => 'min-h-11 whitespace-nowrap', 'atribut' => ['aria-label' => ($aktif ? 'Sembunyikan ' : 'Tampilkan ') . $judul]]) ?>
                                            </form>
                                            <form action="<?= $alamat($id . '/hapus') ?>" method="post"
                                                data-konfirmasi="<?= esc('Tanya-jawab "' . $judul . '" akan dihapus. Tindakan ini tidak bisa dibatalkan. Bila hanya ingin menyembunyikannya, tekan Sembunyikan.', 'attr') ?>"
                                                data-konfirmasi-judul="Hapus tanya-jawab?"
                                                data-konfirmasi-tombol="Hapus tanya-jawab">
                                                <?= csrf_field() ?>
                                                <?= komponen('tombol', ['label' => 'Hapus', 'ikon' => 'trash-2', 'tipe' => 'submit', 'varian' => 'kedua', 'ukuran' => 'kecil', 'kelas' => 'min-h-11 whitespace-nowrap text-red-700 hover:text-red-800', 'atribut' => ['aria-label' => 'Hapus ' . $judul]]) ?>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            <?php endif ?>
        </section>
    <?php endforeach ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<?= $this->endSection() ?>
