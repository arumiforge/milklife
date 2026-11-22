<?php
/**
 * Bukti bayar satu pendaftaran (K34; F6-03), potongan untuk rincian
 * pendaftaran (`admin/pendaftaran/rincian.php`, F6-02).
 *
 * Isinya: gambar kecil setiap bukti bayar (klik untuk melihat gambar besar),
 * tombol unduh dan hapus (dengan jendela konfirmasi) per gambar, dan
 * formulir unggah selama bukti belum `BuktiBayarModel::batas()` gambar.
 * Bukti baru dari ofisial yang belum diperiksa diberi tanda "Baru" dan
 * tombol "Sudah diperiksa" (F8-02).
 * Di layar lebar galeri di kiri dan panel unggah di kanan (K76); di ponsel
 * bertumpuk.
 * Pengendalinya `App\Controllers\Admin\BuktiBayarController`; setelah unggah
 * atau hapus, admin kembali ke rincian dengan pesan kilat.
 *
 * Pemakaian di rincian pendaftaran, di dalam bagian `isi` (`$p` baris
 * pendaftaran dari `DataPanelPendaftaran::rincian()`):
 *
 *     <?= view('admin/pendaftaran/_bukti_bayar', ['pendaftaran' => $p], ['saveData' => false]) ?>
 *
 * Data yang dibaca:
 * - `$pendaftaran` (array) dengan kunci `id` (`pendaftaran.id`) dan, bila
 *   ada, `id_pendaftaran` (untuk teks tombol bagi pembaca layar); atau
 *   `$pendaftaranId` (int) bila halaman tidak punya `$pendaftaran`.
 *   `$pendaftaranId` didahulukan.
 * Daftar bukti bayar dibaca sendiri dari `BuktiBayarModel`, jadi pengendali
 * rincian tidak perlu menyiapkannya.
 *
 * Halaman yang memuat potongan ini wajib memuat `aset/js/konfirmasi.js` di
 * bagian `skrip` (jendela konfirmasi hapus dan pemeriksaan ukuran gambar).
 * Potongan ini tidak memuatnya sendiri supaya skrip itu tidak dimuat dua
 * kali bersama tombol Tandai Lunas dan Hapus di rincian. Gambar besar
 * memakai modal Flowbite (`komponen('modal')`, `data-modal-toggle`), tanpa
 * skrip lain. Tampilan Tailwind (F9-15); penanda uji `data-bukti-kosong` dan
 * `data-bukti-baru-tanda`.
 *
 * @var CodeIgniter\View\View     $this
 * @var array<string, mixed>|null $pendaftaran
 * @var int|null                  $pendaftaranId
 */

use App\Libraries\Panel\BerkasBuktiBayar;
use App\Libraries\Unggahan;
use App\Models\BuktiBayarModel;

$idBukti = (int) ($pendaftaranId ?? (isset($pendaftaran) && is_array($pendaftaran) ? ($pendaftaran['id'] ?? 0) : 0));
$kodeBukti = isset($pendaftaran) && is_array($pendaftaran) ? trim((string) ($pendaftaran['id_pendaftaran'] ?? '')) : '';

if ($idBukti < 1) {
    return;
}

$daftarBukti  = model(BuktiBayarModel::class)->milikPendaftaran($idBukti);
$jumlahBukti  = count($daftarBukti);
$batasBukti   = BuktiBayarModel::batas();
$bolehTambah  = $jumlahBukti < $batasBukti;
$dasarBukti   = 'admin/pendaftaran/' . $idBukti . '/bukti-bayar';
$untukPembaca = $kodeBukti === '' ? '' : ' pendaftaran ' . $kodeBukti;
// F8-02: bukti dari ofisial yang belum diperiksa admin.
$jumlahBaru   = count(array_filter($daftarBukti, static fn (array $b): bool => ($b['diperiksa_pada'] ?? null) === null));
?>
<section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-5" id="bukti-bayar" aria-labelledby="judul-bukti-bayar" data-jumlah-bukti="<?= $jumlahBukti ?>">
    <div class="mb-3 flex flex-wrap items-baseline justify-between gap-2">
        <h2 id="judul-bukti-bayar" class="flex items-center gap-2 text-lg font-bold text-gray-900">
            <?= ikon('receipt', 'size-5 text-gray-500') ?> Bukti bayar
        </h2>
        <span class="inline-flex items-center rounded-md px-2.5 py-0.5 text-xs font-semibold <?= $bolehTambah ? 'border border-gray-200 bg-gray-50 text-gray-800' : 'bg-gray-700 text-white' ?>" id="jumlah-bukti-bayar">
            <?= $jumlahBukti ?> dari <?= $batasBukti ?> gambar
        </span>
    </div>

    <?php if ($jumlahBaru > 0) : ?>
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-primary-200 bg-primary-50 px-4 py-3 text-sm text-primary-900" id="bukti-baru" role="status">
            <p class="flex min-w-0 flex-1 basis-64 items-start gap-2">
                <?= ikon('bell', 'mt-0.5 size-4 shrink-0') ?>
                <span><?= $jumlahBaru ?> bukti bayar baru dari ofisial belum diperiksa. Bukti ikut ditandai sudah diperiksa saat pendaftaran ditandai Lunas.</span>
            </p>
            <form action="<?= site_url($dasarBukti . '/diperiksa') ?>" method="post" id="form-bukti-diperiksa">
                <?= csrf_field() ?>
                <?= komponen('tombol', ['label' => 'Sudah diperiksa', 'tipe' => 'submit', 'ukuran' => 'kecil', 'ikon' => 'check', 'kelas' => 'min-h-11 whitespace-nowrap']) ?>
            </form>
        </div>
    <?php endif ?>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <?php if ($daftarBukti === []) : ?>
                <div class="flex flex-col items-center rounded-xl border border-dashed border-gray-300 bg-gray-50 px-4 py-6 text-center" data-bukti-kosong>
                    <span class="mb-2 inline-flex size-12 items-center justify-center rounded-full bg-white text-gray-500 shadow-lembut"><?= ikon('receipt-text', 'size-6') ?></span>
                    <p class="font-semibold text-gray-900">Belum ada bukti bayar</p>
                    <p class="mt-1 max-w-md text-sm text-gray-600">
                        Ofisial bisa mengunggah bukti transfer sendiri saat mendaftar atau lewat Cek Pendaftaran.
                        Bila bukti dikirim lewat WA, simpan di sini, paling banyak <?= $batasBukti ?> gambar.
                    </p>
                </div>
            <?php else : ?>
                <ul class="grid gap-4 sm:grid-cols-2 2xl:grid-cols-3" aria-label="Daftar bukti bayar">
                    <?php foreach ($daftarBukti as $urutan => $bukti) : ?>
                        <?php
                        $nomorBukti  = $urutan + 1;
                        $alamatBukti = site_url($dasarBukti . '/' . (int) $bukti['id']);
                        $alamatUnduh = site_url($dasarBukti . '/' . (int) $bukti['id'] . '/unduh');
                        $idJendela   = 'bukti-bayar-besar-' . (int) $bukti['id'];
                        $keterangan  = 'Bukti bayar ke-' . $nomorBukti . $untukPembaca;
                        $waktu       = tanggal_id((string) ($bukti['waktu_unggah'] ?? ''), true);
                        ?>
                        <li data-bukti-id="<?= (int) $bukti['id'] ?>">
                            <figure class="flex h-full flex-col overflow-hidden rounded-xl border border-gray-200 bg-white">
                                <button type="button" class="block w-full focus:outline-none focus:ring-4 focus:ring-primary-200" data-modal-target="<?= $idJendela ?>" data-modal-toggle="<?= $idJendela ?>"
                                    title="Perbesar gambar">
                                    <span class="block aspect-[4/3] bg-gray-100">
                                        <img src="<?= esc($alamatBukti, 'attr') ?>" alt="<?= esc($keterangan, 'attr') ?>" class="size-full object-cover" loading="lazy">
                                    </span>
                                    <span class="sr-only">Perbesar <?= esc(lcfirst($keterangan)) ?></span>
                                </button>
                                <figcaption class="flex-1 space-y-1 px-3 py-2">
                                    <span class="flex flex-wrap items-center gap-2 font-semibold text-gray-900">
                                        Bukti ke-<?= $nomorBukti ?>
                                        <?php if (($bukti['diperiksa_pada'] ?? null) === null) : ?>
                                            <span class="inline-flex items-center rounded-md bg-primary-100 px-2 py-0.5 text-xs font-semibold text-primary-800" data-bukti-baru-tanda>Baru</span>
                                        <?php endif ?>
                                    </span>
                                    <span class="block text-sm text-gray-600">
                                        <?= esc($waktu === '' ? 'Waktu unggah tidak tercatat' : 'Diunggah ' . $waktu) ?>
                                        · <?= esc(Unggahan::ukuranTeks((int) $bukti['ukuran'])) ?>
                                    </span>
                                    <div class="flex flex-wrap gap-2 pt-1">
                                        <a class="inline-flex min-h-11 items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 text-sm font-semibold text-gray-900 hover:bg-gray-50 hover:text-primary-700" href="<?= $alamatUnduh ?>">
                                            <?= ikon('download', 'size-4') ?> Unduh<span class="sr-only"> <?= esc(lcfirst($keterangan)) ?></span>
                                        </a>
                                        <form action="<?= site_url($dasarBukti . '/' . (int) $bukti['id'] . '/hapus') ?>" method="post"
                                            data-konfirmasi="<?= esc('Bukti bayar ke-' . $nomorBukti . ' akan dihapus dari server dan tidak bisa dikembalikan. Status pembayaran tidak berubah.', 'attr') ?>"
                                            data-konfirmasi-judul="Hapus bukti bayar?"
                                            data-konfirmasi-tombol="Hapus gambar">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="inline-flex min-h-11 items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 text-sm font-semibold text-red-700 hover:bg-red-50">
                                                <?= ikon('trash-2', 'size-4') ?> Hapus<span class="sr-only"> <?= esc(lcfirst($keterangan)) ?></span>
                                            </button>
                                        </form>
                                    </div>
                                </figcaption>
                            </figure>

                            <?= komponen('modal', [
                                'id'    => $idJendela,
                                'judul' => $keterangan,
                                'lebar' => 'lebar',
                                'isi'   => '<div class="rounded-lg bg-gray-50 p-2 text-center"><img src="' . esc($alamatBukti, 'attr') . '" alt="' . esc($keterangan, 'attr') . '" class="mx-auto h-auto max-h-[75vh] max-w-full" loading="lazy"></div>',
                                'kaki'  => komponen('tombol', ['label' => 'Unduh gambar', 'href' => $alamatUnduh, 'varian' => 'kedua', 'ikon' => 'download'])
                                    . komponen('tombol', ['label' => 'Tutup', 'atribut' => ['data-modal-hide' => $idJendela]]),
                            ]) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <div>
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                <?php if ($bolehTambah) : ?>
                    <form action="<?= site_url($dasarBukti) ?>" method="post" enctype="multipart/form-data" id="unggah-bukti-bayar" class="space-y-2">
                        <?= csrf_field() ?>
                        <label class="flex items-center gap-2 text-sm font-semibold text-gray-900" for="bukti-bayar-berkas">
                            <?= ikon('cloud-upload', 'size-4') ?> Unggah bukti bayar
                        </label>
                        <input type="file" class="block w-full cursor-pointer rounded-lg border border-gray-300 bg-white text-sm text-gray-900 file:me-3 file:min-h-11 file:cursor-pointer file:border-0 file:bg-gray-800 file:px-4 file:text-sm file:font-semibold file:text-white hover:file:bg-gray-700 focus:outline-none focus:ring-4 focus:ring-primary-100" id="bukti-bayar-berkas" name="<?= App\Controllers\Admin\BuktiBayarController::KOTAK ?>" required
                            accept="image/jpeg,image/png,image/webp" aria-describedby="bukti-bayar-bantuan"
                            data-ukuran-maksimal="<?= BerkasBuktiBayar::BATAS_BITA ?>">
                        <?= komponen('tombol', ['label' => 'Unggah bukti bayar', 'tipe' => 'submit', 'ikon' => 'upload', 'blok' => true]) ?>
                        <p id="bukti-bayar-bantuan" class="text-sm text-gray-600">
                            Foto atau tangkapan layar JPG, PNG, atau WEBP, paling besar 10 MB. Gambar dikecilkan otomatis.
                            Masih bisa <?= $batasBukti - $jumlahBukti ?> gambar lagi.
                        </p>
                    </form>
                <?php else : ?>
                    <p class="flex items-start gap-2 text-sm text-gray-600" id="bukti-bayar-penuh">
                        <?= ikon('info', 'mt-0.5 size-4 shrink-0') ?>
                        <span>Sudah <?= $jumlahBukti ?> gambar, batas paling banyak <?= $batasBukti ?> gambar. Hapus gambar yang tidak dipakai bila ingin mengunggah yang lain.</span>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
