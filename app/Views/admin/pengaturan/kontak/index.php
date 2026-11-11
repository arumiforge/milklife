<?php
/**
 * Daftar kontak (`GET /admin/pengaturan/kontak`): dua bagian, narahubung dan
 * media sosial, masing-masing dengan tombol tambah sendiri.
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
        'ikon'       => 'person-lines-fill',
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
        'ikon'       => 'share',
        'pengantar'  => 'Akun media sosial acara yang tampil di halaman depan.',
        'tombol'     => 'Tambah media sosial',
        'kosong'     => 'Belum ada media sosial. Tambahkan alamat Instagram, YouTube, atau akun lain supaya sekolah bisa mengikuti kabar acara.',
        'daftar'     => $mediaSosial,
        'alamat'     => 'media-sosial',
    ],
];
?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?>Kontak – Admin MilkLife<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Kontak<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid px-3 px-lg-4">
    <?= $this->include('admin/pengaturan/_navigasi') ?>

    <div class="row g-4">
        <?php foreach ($bagian as $b) : ?>
            <div class="col-12">
                <section class="card" id="<?= $b['id'] ?>" aria-labelledby="judul-<?= $b['id'] ?>">
                    <div class="card-body p-3 p-lg-4">
                        <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
                            <div>
                                <h2 id="judul-<?= $b['id'] ?>" class="h5 mb-1">
                                    <i class="bi bi-<?= $b['ikon'] ?> text-primary" aria-hidden="true"></i> <?= esc($b['judul']) ?>
                                </h2>
                                <p class="text-body-secondary small mb-0"><?= esc($b['pengantar']) ?></p>
                            </div>
                            <a class="btn btn-primary" href="<?= site_url('admin/pengaturan/kontak/tambah') ?>?jenis=<?= $b['alamat'] ?>">
                                <i class="bi bi-plus-lg" aria-hidden="true"></i> <?= esc($b['tombol']) ?>
                            </a>
                        </div>

                        <?php if ($b['daftar'] === []) : ?>
                            <div class="kontak-kosong text-center text-body-secondary border rounded-3 px-3 py-4">
                                <span class="ikon-bulat mb-2"><i class="bi bi-<?= $b['ikon'] ?>" aria-hidden="true"></i></span>
                                <p class="mb-0 mx-auto"><?= esc($b['kosong']) ?></p>
                            </div>
                        <?php else : ?>
                            <div class="tabel-gulir position-relative">
                                <table class="table align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th scope="col" class="text-center">Urutan</th>
                                            <th scope="col"><?= esc($b['judul']) ?></th>
                                            <th scope="col"><span class="visually-hidden">Tindakan</span></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($b['daftar'] as $kontak) : ?>
                                            <tr data-id="<?= (int) $kontak['id'] ?>">
                                                <td class="text-center"><?= (int) $kontak['urutan'] ?></td>
                                                <td class="w-100">
                                                    <?php if ($kontak['jenis'] === TautanModel::NARAHUBUNG) : ?>
                                                        <span class="fw-semibold d-block"><?= esc($kontak['label']) ?></span>
                                                        <a class="small text-nowrap" href="https://wa.me/<?= esc($kontak['nilai'], 'url') ?>" target="_blank" rel="noopener noreferrer">
                                                            <i class="bi bi-whatsapp" aria-hidden="true"></i> <?= esc(NomorWa::tampilan($kontak['nilai'])) ?>
                                                        </a>
                                                    <?php else : ?>
                                                        <span class="fw-semibold d-block">
                                                            <i class="bi bi-<?= TautanModel::ikonMediaSosial($kontak['nilai']) ?> text-primary" aria-hidden="true"></i>
                                                            <?= esc($kontak['label']) ?>
                                                        </span>
                                                        <a class="small text-break" href="<?= esc($kontak['nilai'], 'attr') ?>" target="_blank" rel="noopener noreferrer"><?= esc(TautanModel::alamatRingkas($kontak['nilai'])) ?></a>
                                                    <?php endif ?>
                                                </td>
                                                <td class="text-end">
                                                    <div class="d-flex justify-content-end gap-2">
                                                        <a class="btn btn-sm btn-outline-primary text-nowrap" href="<?= site_url('admin/pengaturan/kontak/' . (int) $kontak['id'] . '/ubah') ?>">
                                                            <i class="bi bi-pencil" aria-hidden="true"></i> Ubah<span class="visually-hidden"> <?= esc($kontak['label']) ?></span>
                                                        </a>
                                                        <form action="<?= site_url('admin/pengaturan/kontak/' . (int) $kontak['id'] . '/hapus') ?>" method="post"
                                                            data-konfirmasi="<?= esc($b['judul'] . ' "' . $kontak['label'] . '" akan dihapus dari daftar dan dari halaman depan. Tindakan ini tidak bisa dibatalkan.', 'attr') ?>"
                                                            data-konfirmasi-judul="Hapus <?= $b['nama'] ?>?"
                                                            data-konfirmasi-tombol="Hapus <?= $b['nama'] ?>">
                                                            <?= csrf_field() ?>
                                                            <button type="submit" class="btn btn-sm btn-outline-danger text-nowrap">
                                                                <i class="bi bi-trash" aria-hidden="true"></i> Hapus<span class="visually-hidden"> <?= esc($kontak['label']) ?></span>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif ?>
                    </div>
                </section>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<?= $this->endSection() ?>
