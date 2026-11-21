<?php
/**
 * Daftar kontak (`GET /admin/pengaturan/kontak`), tata letak `admin_tw`: dua
 * bagian, narahubung dan media sosial, masing-masing dengan tombol tambah
 * sendiri. Ikon media sosial dari `TautanModel::ikonMediaSosial()` bernama
 * Bootstrap Icons (juga dipakai halaman depan); di sini dipetakan ke Lucide
 * seperti `beranda/_kontak.php`, dan nama aslinya ditulis di `data-ikon-media`.
 *
 * @var CodeIgniter\View\View        $this
 * @var list<array<string, mixed>>   $narahubung  menurut urutan tampil
 * @var list<array<string, mixed>>   $mediaSosial menurut urutan tampil
 */

use App\Libraries\NomorWa;
use App\Models\TautanModel;

$bagian = [
    [
        'id'         => 'narahubung',
        'judul'      => 'Narahubung',
        'nama'       => 'narahubung',
        'ikon'       => 'contact',
        'pengantar'  => 'Orang yang bisa dihubungi sekolah lewat WA bila ada pertanyaan.',
        'tombol'     => 'Tambah narahubung',
        'kosong'     => 'Belum ada narahubung. Tambahkan nama dan nomor WA panitia supaya sekolah tahu siapa yang bisa ditanya.',
        'daftar'     => $narahubung,
        'alamat'     => 'narahubung',
    ],
    [
        'id'         => 'media-sosial',
        'judul'      => 'Media sosial',
        'nama'       => 'media sosial',
        'ikon'       => 'share-2',
        'pengantar'  => 'Akun media sosial acara yang tampil di halaman depan.',
        'tombol'     => 'Tambah media sosial',
        'kosong'     => 'Belum ada media sosial. Tambahkan alamat Instagram, YouTube, atau akun lain supaya sekolah bisa mengikuti kabar acara.',
        'daftar'     => $mediaSosial,
        'alamat'     => 'media-sosial',
    ],
];

// Nama ikon media sosial dari `TautanModel` (Bootstrap Icons) ke Lucide, sama dengan `beranda/_kontak.php`.
$ikonMedia = static fn (string $nama): string => match ($nama) {
    'instagram', 'facebook', 'youtube' => $nama,
    'twitter-x'                        => 'twitter',
    'tiktok'                           => 'music',
    'whatsapp'                         => 'message-circle',
    'telegram'                         => 'send',
    default                            => 'globe',
};
// ikon-sprite: contact share-2 instagram facebook youtube twitter music message-circle send globe
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?>Kontak<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Kontak<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-6 p-4 sm:p-6">
    <?= $this->include('admin/pengaturan/_navigasi') ?>

    <?php foreach ($bagian as $b) : ?>
        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-lembut sm:p-6" id="<?= $b['id'] ?>" aria-labelledby="judul-<?= $b['id'] ?>">
            <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <h2 id="judul-<?= $b['id'] ?>" class="flex items-center gap-2 text-lg font-bold text-gray-900">
                        <?= ikon($b['ikon'], 'text-primary-700') ?> <?= esc($b['judul']) ?>
                    </h2>
                    <p class="mt-1 text-sm text-gray-600"><?= esc($b['pengantar']) ?></p>
                </div>
                <?= komponen('tombol', ['label' => $b['tombol'], 'href' => site_url('admin/pengaturan/kontak/tambah') . '?jenis=' . $b['alamat'], 'ikon' => 'plus']) ?>
            </div>

            <?php if ($b['daftar'] === []) : ?>
                <div class="kontak-kosong flex flex-col items-center rounded-xl border border-dashed border-gray-300 px-4 py-6 text-center text-gray-600" data-kontak-kosong>
                    <span class="mb-2 inline-flex size-12 items-center justify-center rounded-2xl bg-primary-100 text-primary-700"><?= ikon($b['ikon'], 'size-6') ?></span>
                    <p class="max-w-xl"><?= esc($b['kosong']) ?></p>
                </div>
            <?php else : ?>
                <div class="overflow-x-auto rounded-xl border border-gray-200">
                    <table class="w-full text-left text-sm text-gray-700">
                        <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-700">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-end">No</th>
                                <th scope="col" class="px-4 py-3 text-center">Urutan</th>
                                <th scope="col" class="px-4 py-3"><?= esc($b['judul']) ?></th>
                                <th scope="col" class="px-4 py-3"><span class="sr-only">Tindakan</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 0; ?>
                            <?php foreach ($b['daftar'] as $kontak) : ?>
                                <tr data-id="<?= (int) $kontak['id'] ?>" class="border-t border-gray-100">
                                    <td class="px-4 py-3 text-end align-top text-gray-500"><?= ++$no ?></td>
                                    <td class="px-4 py-3 text-center align-top"><?= (int) $kontak['urutan'] ?></td>
                                    <td class="min-w-48 px-4 py-3 align-top">
                                        <?php if ($kontak['jenis'] === TautanModel::NARAHUBUNG) : ?>
                                            <span class="block font-semibold break-words text-gray-900"><?= esc($kontak['label']) ?></span>
                                            <a class="mt-0.5 inline-flex items-center gap-1.5 whitespace-nowrap text-sm text-primary-700 hover:underline" href="https://wa.me/<?= esc($kontak['nilai'], 'url') ?>" target="_blank" rel="noopener noreferrer">
                                                <?= ikon('message-circle', 'size-4') ?> <?= esc(NomorWa::tampilan($kontak['nilai'])) ?>
                                            </a>
                                        <?php else : ?>
                                            <?php $namaIkon = TautanModel::ikonMediaSosial($kontak['nilai']); ?>
                                            <span class="flex items-center gap-1.5 font-semibold break-words text-gray-900">
                                                <span class="inline-flex shrink-0 text-primary-700" data-ikon-media="<?= esc($namaIkon, 'attr') ?>"><?= ikon($ikonMedia($namaIkon), 'size-4') ?></span>
                                                <?= esc($kontak['label']) ?>
                                            </span>
                                            <a class="mt-0.5 block text-sm break-all text-primary-700 hover:underline" href="<?= esc($kontak['nilai'], 'attr') ?>" target="_blank" rel="noopener noreferrer"><?= esc(TautanModel::alamatRingkas($kontak['nilai'])) ?></a>
                                        <?php endif ?>
                                    </td>
                                    <td class="px-4 py-3 align-top">
                                        <div class="flex flex-wrap justify-end gap-2">
                                            <?= komponen('tombol', ['label' => 'Ubah', 'href' => site_url('admin/pengaturan/kontak/' . (int) $kontak['id'] . '/ubah'), 'ikon' => 'pencil', 'varian' => 'kedua', 'ukuran' => 'kecil', 'kelas' => 'min-h-11 whitespace-nowrap', 'atribut' => ['aria-label' => 'Ubah ' . $kontak['label']]]) ?>
                                            <form action="<?= site_url('admin/pengaturan/kontak/' . (int) $kontak['id'] . '/hapus') ?>" method="post"
                                                data-konfirmasi="<?= esc($b['judul'] . ' "' . $kontak['label'] . '" akan dihapus dari daftar dan dari halaman depan. Tindakan ini tidak bisa dibatalkan.', 'attr') ?>"
                                                data-konfirmasi-judul="Hapus <?= $b['nama'] ?>?"
                                                data-konfirmasi-tombol="Hapus <?= $b['nama'] ?>">
                                                <?= csrf_field() ?>
                                                <?= komponen('tombol', ['label' => 'Hapus', 'ikon' => 'trash-2', 'tipe' => 'submit', 'varian' => 'kedua', 'ukuran' => 'kecil', 'kelas' => 'min-h-11 whitespace-nowrap text-red-700 hover:text-red-800', 'atribut' => ['aria-label' => 'Hapus ' . $kontak['label']]]) ?>
                                            </form>
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
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<?= $this->endSection() ?>
