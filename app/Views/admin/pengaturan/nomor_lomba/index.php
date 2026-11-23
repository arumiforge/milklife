<?php
/**
 * Daftar nomor lomba, dikelompokkan per kelompok umur, dengan saringan
 * kelompok umur dan "belum lengkap saja", tata letak Tailwind `admin_tw`.
 * Data dari `NomorLombaController::index()`.
 *
 * Di ponsel, kolom jenis, kategori, tim, dan biaya diringkas di bawah nama
 * nomor supaya tabel tidak perlu digulir mendatar; di layar ≥ md tampil
 * sebagai kolom sendiri.
 *
 * @var CodeIgniter\View\View                                        $this
 * @var array<int, array{id: int, nama: string, aktif: bool}>        $kelompokUmur
 * @var array<int, array{id: int, nama: string, aktif: bool, nomor: list<array<string, mixed>>}> $kelompok
 * @var int                                                          $jumlahSemua
 * @var array<int, int>                                              $jumlahPerKu
 * @var int                                                          $jumlahBelum
 * @var int                                                          $jumlahTampil
 * @var int                                                          $saringKu
 * @var bool                                                         $saringBelumLengkap
 */

use App\Models\NomorLombaModel;

$alamat = 'admin/pengaturan/nomor-lomba';

/**
 * Ringkasan tim satu nomor beregu, misalnya "4 inti, paling banyak 6 · paling sedikit 4 putra dan 4 putri".
 *
 * @param array<string, mixed> $nomor
 */
$ringkasTim = static function (array $nomor): string {
    if ($nomor['jenis'] !== 'beregu') {
        return '';
    }

    $bagian = [];

    if ($nomor['jumlah_inti'] !== null) {
        $bagian[] = $nomor['jumlah_inti'] . ' inti' . ($nomor['batas_anggota'] !== null ? ', paling banyak ' . $nomor['batas_anggota'] : '')
            . ', ' . (($nomor['regu_maksimal'] ?? null) !== null ? 'paling banyak ' . $nomor['regu_maksimal'] . ' regu per sekolah' : 'regu tidak dibatasi');
    }

    if ($nomor['kategori'] === 'mix' && $nomor['jumlah_putra'] !== null && $nomor['jumlah_putri'] !== null) {
        $bagian[] = 'minimal ' . $nomor['jumlah_putra'] . ' putra dan ' . $nomor['jumlah_putri'] . ' putri';
    }

    return implode(' · ', $bagian);
};
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('tabel') ?>ya<?= $this->endSection() ?>

<?= $this->section('judul') ?>Nomor lomba<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Nomor lomba<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-4 p-4 sm:px-6 sm:py-4">
    <?= $this->include('admin/pengaturan/_navigasi') ?>

    <?php if ($kelompokUmur === []) : ?>
        <?= komponen('kosong', ['padat' => true,
            'gambar' => ['gambar/admin/kosong-admin.webp', 'gambar/keadaan/kosong-data.webp'],
            'ikon'   => 'users',
            'judul'  => 'Belum ada kelompok umur',
            'teks'   => 'Setiap nomor lomba milik satu kelompok umur. Buat kelompok umur dulu, lalu kembali ke sini untuk menambah nomor lombanya.',
            'aksi'   => komponen('tombol', ['label' => 'Buka kelompok umur', 'href' => site_url('admin/pengaturan/kelompok-umur'), 'ikon' => 'users']),
        ]) ?>
    <?php elseif ($jumlahSemua === 0) : ?>
        <?= komponen('kosong', ['padat' => true,
            'gambar' => ['gambar/admin/kosong-admin.webp', 'gambar/keadaan/kosong-data.webp'],
            'ikon'   => 'trophy',
            'judul'  => 'Belum ada nomor lomba',
            'teks'   => 'Tambahkan nomor individu dan beregu untuk tiap kelompok umur. Sekolah hanya bisa memilih nomor yang ada di sini.',
            'aksi'   => komponen('tombol', ['label' => 'Tambah nomor lomba', 'href' => site_url($alamat . '/tambah'), 'ikon' => 'plus']),
        ]) ?>
    <?php else : ?>
        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
            <p class="text-gray-700">
                <?= $jumlahSemua ?> nomor lomba di <?= count($kelompokUmur) ?> kelompok umur.
                Nomor yang tidak aktif tidak muncul di pendaftaran.
            </p>
            <?= komponen('tombol', ['label' => 'Tambah nomor lomba', 'href' => site_url($alamat . '/tambah' . ($saringKu !== 0 ? '?kelompok-umur=' . $saringKu : '')), 'ikon' => 'plus', 'kelas' => 'shrink-0 self-start']) ?>
        </div>

        <?php if ($jumlahBelum > 0) : ?>
            <div class="flex items-start gap-3 rounded-xl border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-900" role="status" id="ringkasan-belum-lengkap">
                <?= ikon('circle-alert', 'mt-0.5 shrink-0') ?>
                <div class="min-w-0">
                    <strong><?= $jumlahBelum ?> nomor beregu belum lengkap.</strong>
                    Isi jumlah inti, batas anggota, dan biaya beregu (untuk nomor mixed juga jumlah putra dan putri minimal) supaya pendaftaran bisa dibuka.
                    <?php if (! $saringBelumLengkap) : ?>
                        <a class="font-semibold underline hover:no-underline" href="<?= site_url($alamat) ?>?belum-lengkap=1">Tampilkan yang belum lengkap saja</a>
                    <?php endif ?>
                </div>
            </div>
        <?php endif ?>

        <form class="flex flex-col gap-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-5 md:flex-row md:flex-wrap md:items-end" method="get" action="<?= site_url($alamat) ?>" id="saringan" role="search" aria-label="Saring nomor lomba">
            <div class="w-full md:w-80">
                <label for="saring-kelompok-umur" class="mb-1.5 block text-sm font-semibold text-gray-900">Kelompok umur</label>
                <select class="block min-h-11 w-full rounded-lg border border-gray-300 bg-white p-2.5 text-base text-gray-900 focus:border-primary-600 focus:ring-4 focus:ring-primary-100 sm:text-sm" id="saring-kelompok-umur" name="kelompok-umur">
                    <option value="">Semua kelompok umur (<?= $jumlahSemua ?> nomor)</option>
                    <?php foreach ($kelompokUmur as $id => $ku) : ?>
                        <option value="<?= $id ?>"<?= $saringKu === $id ? ' selected' : '' ?>>
                            <?= esc($ku['nama']) ?> (<?= $jumlahPerKu[$id] ?? 0 ?> nomor)<?= $ku['aktif'] ? '' : ' – tidak aktif' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <label class="inline-flex min-h-11 items-center gap-2 text-sm text-gray-900" for="saring-belum-lengkap">
                <input class="size-4 rounded border-gray-300 text-primary-700 focus:ring-primary-300" type="checkbox" id="saring-belum-lengkap" name="belum-lengkap" value="1"<?= $saringBelumLengkap ? ' checked' : '' ?>>
                Belum lengkap saja (<?= $jumlahBelum ?>)
            </label>
            <div class="flex flex-wrap gap-2" data-tombol-aksi>
                <?= komponen('tombol', ['label' => 'Tampilkan', 'tipe' => 'submit', 'varian' => 'kedua', 'ikon' => 'filter']) ?>
                <?php if ($saringKu !== 0 || $saringBelumLengkap) : ?>
                    <?= komponen('tombol', ['label' => 'Hapus saringan', 'href' => site_url($alamat), 'varian' => 'hantu']) ?>
                <?php endif ?>
            </div>
        </form>

        <?php if ($jumlahTampil === 0 && $saringBelumLengkap) : ?>
            <?= komponen('kosong', ['padat' => true,
                'gambar' => 'gambar/keadaan/berhasil.webp',
                'ikon'   => 'circle-check',
                'judul'  => 'Semua nomor sudah lengkap',
                'teks'   => 'Tidak ada nomor beregu yang belum lengkap' . ($saringKu !== 0 ? ' di ' . $kelompokUmur[$saringKu]['nama'] : '') . '.',
                'aksi'   => komponen('tombol', ['label' => 'Tampilkan semua nomor', 'href' => site_url($alamat), 'varian' => 'kedua']),
            ]) ?>
        <?php endif ?>

        <?php foreach ($kelompok as $kuId => $ku) : ?>
            <section class="kelompok-nomor-lomba rounded-2xl border border-gray-200 bg-white shadow-lembut" id="ku-<?= $kuId ?>" aria-labelledby="judul-ku-<?= $kuId ?>">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 px-4 py-3 sm:px-5">
                    <h2 id="judul-ku-<?= $kuId ?>" class="flex flex-wrap items-center gap-2 text-lg font-bold text-gray-900">
                        <?= esc($ku['nama']) ?>
                        <?= komponen('lencana', ['label' => ($jumlahPerKu[$kuId] ?? 0) . ' nomor', 'warna' => 'abu']) ?>
                        <?php if (! $ku['aktif']) : ?>
                            <?= komponen('lencana', ['label' => 'Kelompok umur tidak aktif', 'warna' => 'kuning']) ?>
                        <?php endif ?>
                    </h2>
                    <a class="inline-flex min-h-11 items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 text-sm font-semibold text-gray-900 hover:bg-gray-50 hover:text-primary-700 focus:outline-none focus:ring-4 focus:ring-gray-100" href="<?= site_url($alamat . '/tambah?kelompok-umur=' . $kuId) ?>">
                        <?= ikon('plus', 'size-4') ?> Tambah nomor<span class="sr-only"> di <?= esc($ku['nama']) ?></span>
                    </a>
                </div>

                <?php if ($ku['nomor'] === []) : ?>
                    <p class="px-4 py-3 text-gray-600 sm:px-5">
                        Belum ada nomor lomba di kelompok umur ini.
                    </p>
                <?php else : ?>
                    <div class="relative overflow-x-auto rounded-b-2xl">
                        <table class="w-full text-left text-sm text-gray-700" data-tabel-klien>
                            <caption class="sr-only">Nomor lomba <?= esc($ku['nama']) ?></caption>
                            <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-700">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-end">No</th>
                                    <th scope="col" class="hidden px-4 py-3 text-end md:table-cell">Urutan</th>
                                    <th scope="col" class="px-4 py-3">Nomor</th>
                                    <th scope="col" class="hidden px-4 py-3 md:table-cell">Jenis</th>
                                    <th scope="col" class="hidden px-4 py-3 md:table-cell">Kategori</th>
                                    <th scope="col" class="hidden px-4 py-3 md:table-cell">Tim</th>
                                    <th scope="col" class="hidden px-4 py-3 text-end md:table-cell">Biaya beregu</th>
                                    <th scope="col" class="px-4 py-3">Aktif</th>
                                    <th scope="col" class="px-4 py-3 text-end"><span class="sr-only">Tindakan</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 0; ?>
                                <?php foreach ($ku['nomor'] as $nomor) : ?>
                                    <?php
                                    $id          = (int) $nomor['id'];
                                    $namaLengkap = NomorLombaModel::namaLengkap($nomor);
                                    $tim         = $ringkasTim($nomor);
                                    $biaya       = $nomor['jenis'] === 'beregu' ? rupiah($nomor['biaya_beregu']) : '';
                                    $belum       = $nomor['kekurangan'] !== [];
                                    ?>
                                    <tr id="nomor-<?= $id ?>" data-id="<?= $id ?>" class="border-t border-gray-100 hover:bg-gray-50"<?= $belum ? ' data-belum-lengkap="1"' : '' ?>>
                                        <td class="px-4 py-3 text-end text-gray-500"><?= ++$no ?></td>
                                        <td class="hidden px-4 py-3 text-end text-gray-500 md:table-cell"><?= (int) $nomor['urutan'] ?></td>
                                        <td class="min-w-48 px-4 py-3">
                                            <span class="font-semibold text-gray-900"><?= esc($nomor['nama']) ?></span>
                                            <span class="text-gray-600 md:hidden"><?= esc($nomor['kategori']) ?></span>
                                            <span class="block text-sm text-gray-600 md:hidden">
                                                <?= esc(NomorLombaModel::JENIS[$nomor['jenis']] ?? $nomor['jenis']) ?><?= $tim !== '' ? ' · ' . esc($tim) : '' ?><?= $biaya !== '' ? ' · ' . $biaya . ' per regu' : '' ?>
                                            </span>
                                            <?php if ($belum) : ?>
                                                <span class="mt-1 flex items-start gap-1 text-sm font-medium text-yellow-800">
                                                    <?= ikon('circle-alert', 'mt-0.5 size-4 shrink-0') ?>
                                                    <span>Belum lengkap: <?= esc(implode(', ', $nomor['kekurangan'])) ?> belum diisi.</span>
                                                </span>
                                            <?php endif ?>
                                        </td>
                                        <td class="hidden px-4 py-3 md:table-cell"><?= esc(NomorLombaModel::JENIS[$nomor['jenis']] ?? $nomor['jenis']) ?></td>
                                        <td class="hidden px-4 py-3 md:table-cell"><?= esc(NomorLombaModel::KATEGORI[$nomor['kategori']] ?? $nomor['kategori']) ?></td>
                                        <td class="hidden px-4 py-3 md:table-cell"><?= $tim !== '' ? esc($tim) : '<span class="text-gray-500">–</span>' ?></td>
                                        <td class="hidden whitespace-nowrap px-4 py-3 text-end md:table-cell"><?= $biaya !== '' ? $biaya : '<span class="text-gray-500">–</span>' ?></td>
                                        <td class="px-4 py-3">
                                            <?php if ((int) $nomor['aktif'] === 1) : ?>
                                                <?= komponen('lencana', ['label' => 'Aktif', 'warna' => 'hijau']) ?>
                                            <?php else : ?>
                                                <?= komponen('lencana', ['label' => 'Tidak aktif', 'warna' => 'abu']) ?>
                                            <?php endif ?>
                                            <?php if ((int) ($nomor['tidak_dilombakan'] ?? 0) === 1) : ?>
                                                <?= komponen('lencana', ['label' => 'Tidak dilombakan', 'warna' => 'merah']) ?>
                                            <?php endif ?>
                                        </td>
                                        <td class="px-4 py-3 text-end">
                                            <div class="inline-flex gap-1">
                                                <a class="inline-flex min-h-11 min-w-11 items-center justify-center gap-1.5 whitespace-nowrap rounded-lg px-3 text-sm font-semibold focus:outline-none focus:ring-4 <?= $belum ? 'bg-primary-700 text-white hover:bg-primary-800 focus:ring-primary-300' : 'border border-gray-300 bg-white text-gray-900 hover:bg-gray-50 hover:text-primary-700 focus:ring-gray-100' ?>" href="<?= site_url($alamat . '/' . $id . '/ubah') ?>"
                                                    aria-label="<?= esc(($belum ? 'Lengkapi ' : 'Ubah ') . $namaLengkap, 'attr') ?>">
                                                    <?= ikon('pencil', 'size-4') ?>
                                                    <span class="hidden lg:inline"><?= $belum ? 'Lengkapi' : 'Ubah' ?></span>
                                                </a>
                                                <?php if (! $nomor['dipakai']) : ?>
                                                    <form method="post" action="<?= site_url($alamat . '/' . $id . '/hapus') ?>"
                                                        data-konfirmasi="Nomor <?= esc($namaLengkap . ' di ' . $ku['nama'], 'attr') ?> akan dihapus dan tidak bisa dikembalikan."
                                                        data-konfirmasi-tombol="Hapus nomor">
                                                        <?= csrf_field() ?>
                                                        <button type="submit" class="inline-flex size-11 items-center justify-center rounded-lg border border-red-300 bg-white text-red-700 hover:bg-red-50 focus:outline-none focus:ring-4 focus:ring-red-100" aria-label="<?= esc('Hapus ' . $namaLengkap, 'attr') ?>">
                                                            <?= ikon('trash-2', 'size-4') ?>
                                                        </button>
                                                    </form>
                                                <?php endif ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif ?>
            </section>
        <?php endforeach; ?>
    <?php endif ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<script src="<?= aset('js/admin-nomor-lomba-saringan.js') ?>"></script>
<?= $this->endSection() ?>
