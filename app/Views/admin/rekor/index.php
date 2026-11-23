<?php
/**
 * Rekor kejuaraan di panel admin (`GET /admin/rekor`, F11-06, K172 butir 3),
 * tata letak `admin_tw`: sakelar halaman Rekor publik, lalu tabel rekor per
 * kelompok umur. Rekor yang berlaku ditandai; rekor lama tetap tampil
 * sebagai riwayat. Rekor isian admin bisa diubah dan dihapus; rekor dari
 * hasil berubah lewat hasilnya.
 *
 * @var CodeIgniter\View\View                                                     $this
 * @var list<array{kelompok_umur: string, rekor: list<array<string, mixed>>}>    $kelompok `Rekor::daftar()`
 * @var bool                                                                      $tampil   sakelar `Rekor.tampil`
 */

use App\Libraries\Hasil\Rekor;

$alamat = static fn (string $jalur = ''): string => site_url('admin/rekor' . ($jalur !== '' ? '/' . $jalur : ''));
$jumlah = array_sum(array_map(static fn (array $k): int => count($k['rekor']), $kelompok));
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?>Rekor kejuaraan<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-4 p-4 sm:px-6 sm:py-4">
    <div class="flex items-start gap-3 rounded-xl border border-primary-200 bg-primary-100 p-4 text-sm text-primary-900" role="note">
        <?= ikon('info', 'mt-0.5 shrink-0') ?>
        <div>
            Rekor dicatat mulai Seri 2. Saat hasil Final diterbitkan, prestasi yang lebih baik dari rekor menjadi rekor baru dan ditandai <?= Rekor::TANDA ?> di hasil dan videotron.
            Rekor dicatat per nomor, spesifikasi alat, dan cara pencatatan waktu: waktu tangan dan elektronik tidak saling memecahkan. NM, DNS, DNF, dan DQ tidak membuat rekor.
            Bila terbitan ditarik, rekor lama berlaku lagi. Anda juga bisa mengisi rekor dengan tangan.
        </div>
    </div>

    <section id="sakelar-rekor" class="rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-5" aria-labelledby="judul-sakelar-rekor">
        <h2 id="judul-sakelar-rekor" class="text-lg font-bold text-gray-900">Halaman Rekor</h2>
        <p class="mt-1 text-sm text-gray-600">
            Bila dimatikan, alamat /rekor tidak bisa dibuka. Tanda <?= Rekor::TANDA ?> di hasil tetap tampil.
            <?php if ($tampil) : ?>
                Lihat di <a href="<?= site_url('rekor') ?>" target="_blank" rel="noopener noreferrer" class="font-semibold text-primary-700 underline">halaman Rekor</a>.
            <?php endif ?>
        </p>
        <form action="<?= $alamat('tampil') ?>" method="post" class="mt-3 flex flex-wrap items-center gap-3">
            <?= csrf_field() ?>
            <?= komponen('sakelar', ['label' => 'Tampilkan halaman Rekor', 'nama' => 'tampil_rekor', 'centang' => $tampil]) ?>
            <?= komponen('tombol', ['label' => 'Simpan sakelar Rekor', 'tipe' => 'submit', 'varian' => 'kedua', 'ikon' => 'check']) ?>
        </form>
    </section>

    <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-5" id="daftar-rekor" aria-labelledby="judul-daftar-rekor">
        <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
            <h2 id="judul-daftar-rekor" class="flex flex-wrap items-center gap-2 text-lg font-bold text-gray-900">
                <?= ikon('trophy', 'text-primary-700') ?>
                Rekor
                <?= komponen('lencana', ['label' => (string) $jumlah, 'warna' => 'abu']) ?>
            </h2>
            <?= komponen('tombol', ['label' => 'Tambah rekor', 'href' => $alamat('tambah'), 'ikon' => 'plus']) ?>
        </div>

        <?php if ($kelompok === []) : ?>
            <p class="rounded-xl border border-gray-200 px-4 py-3 text-gray-600" data-rekor-kosong>Belum ada rekor. Rekor tercatat sendiri saat hasil Final diterbitkan, atau tekan <strong class="font-semibold text-gray-900">Tambah rekor</strong> untuk mengisinya.</p>
        <?php endif ?>

        <div class="space-y-6">
            <?php foreach ($kelompok as $k) : ?>
                <div>
                    <h3 class="mb-2 font-bold text-gray-900"><?= esc($k['kelompok_umur']) ?></h3>
                    <div class="relative overflow-x-auto rounded-xl border border-gray-200">
                        <table class="w-full text-left text-sm text-gray-700">
                            <caption class="sr-only">Rekor <?= esc($k['kelompok_umur']) ?></caption>
                            <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-700">
                                <tr>
                                    <th scope="col" class="px-3 py-3">Nomor</th>
                                    <th scope="col" class="px-3 py-3">Prestasi</th>
                                    <th scope="col" class="px-3 py-3">Atlet dan sekolah</th>
                                    <th scope="col" class="px-3 py-3">Seri dan tanggal</th>
                                    <th scope="col" class="px-3 py-3"><span class="sr-only">Tindakan</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($k['rekor'] as $r) : ?>
                                    <tr id="rekor-<?= $r['id'] ?>" data-rekor="<?= $r['id'] ?>"<?= $r['berlaku'] ? ' data-berlaku' : '' ?> class="border-t border-gray-100<?= $r['berlaku'] ? '' : ' text-gray-500' ?>">
                                        <td class="min-w-40 px-3 py-3 align-top">
                                            <span class="font-semibold <?= $r['berlaku'] ? 'text-gray-900' : '' ?>"><?= esc($r['nomor']) ?></span>
                                            <?php if ($r['berat_alat'] !== '' || $r['cara'] !== '') : ?>
                                                <span class="block text-gray-500"><?= esc(implode(' · ', array_filter([$r['berat_alat'], $r['cara']]))) ?></span>
                                            <?php endif ?>
                                            <?php if ($r['nomor_lomba_id'] === null) : ?>
                                                <span class="block text-xs text-gray-500">Nomor sudah dihapus</span>
                                            <?php endif ?>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-3 align-top">
                                            <span class="font-semibold <?= $r['berlaku'] ? 'text-gray-900' : '' ?>"><?= esc($r['teks']) ?></span>
                                            <span class="mt-1 flex flex-wrap gap-1">
                                                <?= $r['berlaku'] ? komponen('lencana', ['label' => 'Berlaku', 'warna' => 'hijau']) : komponen('lencana', ['label' => 'Rekor lama', 'warna' => 'abu']) ?>
                                                <?= $r['otomatis'] ? komponen('lencana', ['label' => 'Dari hasil', 'warna' => 'biru']) : komponen('lencana', ['label' => 'Diisi admin', 'warna' => 'oranye']) ?>
                                            </span>
                                        </td>
                                        <td class="min-w-48 px-3 py-3 align-top">
                                            <span class="font-semibold <?= $r['berlaku'] ? 'text-gray-900' : '' ?>"><?= esc($r['atlet']) ?></span>
                                            <span class="block"><?= esc($r['sekolah']) ?></span>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-3 align-top">
                                            <?= esc($r['seri']) ?>
                                            <?php if ($r['tanggal'] !== null) : ?><span class="block text-gray-500"><?= esc(tanggal_id((string) $r['tanggal'])) ?></span><?php endif ?>
                                        </td>
                                        <td class="px-3 py-3 align-top">
                                            <div class="flex flex-wrap justify-end gap-2">
                                                <?php if ($r['otomatis']) : ?>
                                                    <?= komponen('tombol', ['label' => 'Lihat hasil', 'href' => site_url('admin/hasil/nomor/' . (int) $r['nomor_lomba_id']), 'ikon' => 'trophy', 'varian' => 'kedua', 'ukuran' => 'kecil', 'kelas' => 'min-h-11 whitespace-nowrap', 'atribut' => ['aria-label' => 'Lihat hasil ' . $r['nomor'] . ' ' . $k['kelompok_umur']]]) ?>
                                                <?php else : ?>
                                                    <?= komponen('tombol', ['label' => 'Ubah', 'href' => $alamat($r['id'] . '/ubah'), 'ikon' => 'pencil', 'varian' => 'kedua', 'ukuran' => 'kecil', 'kelas' => 'min-h-11 whitespace-nowrap', 'atribut' => ['aria-label' => 'Ubah rekor ' . $r['nomor'] . ' ' . $k['kelompok_umur']]]) ?>
                                                    <form action="<?= $alamat($r['id'] . '/hapus') ?>" method="post"
                                                        data-konfirmasi="<?= esc('Rekor ' . $r['nomor'] . ' ' . $k['kelompok_umur'] . ' (' . $r['teks'] . ', ' . $r['atlet'] . ') akan dihapus. Tindakan ini tidak bisa dibatalkan.', 'attr') ?>"
                                                        data-konfirmasi-judul="Hapus rekor?"
                                                        data-konfirmasi-tombol="Hapus rekor">
                                                        <?= csrf_field() ?>
                                                        <?= komponen('tombol', ['label' => 'Hapus', 'ikon' => 'trash-2', 'tipe' => 'submit', 'varian' => 'kedua', 'ukuran' => 'kecil', 'kelas' => 'min-h-11 whitespace-nowrap text-red-700 hover:text-red-800', 'atribut' => ['aria-label' => 'Hapus rekor ' . $r['nomor'] . ' ' . $k['kelompok_umur']]]) ?>
                                                    </form>
                                                <?php endif ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
    </section>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<?= $this->endSection() ?>
