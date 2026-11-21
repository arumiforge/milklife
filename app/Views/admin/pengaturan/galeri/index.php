<?php
/**
 * Daftar album Galeri (`GET /admin/pengaturan/galeri`, F9-06): sakelar
 * halaman Galeri publik, lalu daftar album dengan sampul, tautan, dan
 * tombol Naik, Turun, Ubah, Sembunyikan/Tampilkan, dan Hapus (dengan konfirmasi).
 *
 * @var CodeIgniter\View\View       $this
 * @var list<array<string, mixed>>  $album    semua album menurut urutan
 * @var bool                        $tampil   sakelar `Galeri.tampil`
 * @var App\Libraries\Unggahan      $unggahan
 */

use App\Models\GaleriModel;

$alamat   = static fn (string $jalur = ''): string => site_url('admin/pengaturan/galeri' . ($jalur !== '' ? '/' . $jalur : ''));
$terakhir = count($album) - 1;
$penuh    = count($album) >= GaleriModel::BATAS;
?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?>Galeri<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Galeri<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid px-3 px-lg-4">
    <?= $this->include('admin/pengaturan/_navigasi') ?>

    <div class="alert alert-info d-flex gap-2 align-items-start" role="note">
        <i class="bi bi-info-circle" aria-hidden="true"></i>
        <div>
            Foto acara tidak diunggah ke web ini. Setiap album berisi sampul dan tautan ke album Instagram atau Google Drive panitia;
            pengunjung yang menekan album dibawa ke sana di tab baru. Album tanpa tautan atau yang disembunyikan tidak tampil untuk umum.
        </div>
    </div>

    <section id="sakelar-galeri" class="card mb-4" aria-labelledby="judul-sakelar-galeri">
        <div class="card-body p-3 p-lg-4">
            <h2 id="judul-sakelar-galeri" class="h5 mb-1">Halaman Galeri</h2>
            <p class="small text-body-secondary mb-3">
                Bila dimatikan, alamat /galeri tidak bisa dibuka dan menu Galeri tidak tampil di halaman depan.
                <?php if ($tampil) : ?>
                    Lihat di <a href="<?= site_url('galeri') ?>" target="_blank" rel="noopener noreferrer">halaman Galeri</a>.
                <?php endif ?>
            </p>
            <form action="<?= $alamat('tampil') ?>" method="post">
                <?= csrf_field() ?>
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" role="switch" id="tampil_galeri" name="tampil_galeri" value="1"<?= $tampil ? ' checked' : '' ?>>
                    <label class="form-check-label fw-semibold" for="tampil_galeri">Tampilkan halaman Galeri</label>
                </div>
                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-check-lg" aria-hidden="true"></i> Simpan sakelar Galeri
                </button>
            </form>
        </div>
    </section>

    <section class="card" id="daftar-album" aria-labelledby="judul-daftar-album">
        <div class="card-body p-3 p-lg-4">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
                <div>
                    <h2 id="judul-daftar-album" class="h5 mb-1">
                        <i class="bi bi-images text-primary" aria-hidden="true"></i>
                        Album
                        <span class="badge text-bg-light border ms-1"><?= count($album) ?></span>
                    </h2>
                    <p class="text-body-secondary small mb-0">Urutan di sini sama dengan urutan di halaman Galeri.</p>
                </div>
                <?php if (! $penuh) : ?>
                    <a class="btn btn-primary" href="<?= $alamat('tambah') ?>">
                        <i class="bi bi-plus-lg" aria-hidden="true"></i> Tambah album
                    </a>
                <?php endif ?>
            </div>

            <?php if ($album === []) : ?>
                <p class="text-body-secondary border rounded-3 px-3 py-3 mb-0">Belum ada album. Tekan <strong>Tambah album</strong> untuk menambahkan album pertama.</p>
            <?php else : ?>
                <div class="tabel-gulir position-relative">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col" class="text-end">No</th>
                                <th scope="col">Sampul</th>
                                <th scope="col">Album</th>
                                <th scope="col"><span class="visually-hidden">Tindakan</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($album as $i => $baris) : ?>
                                <?php
                                $id     = (int) $baris['id'];
                                $aktif  = (int) $baris['aktif'] === 1;
                                $judul  = (string) $baris['judul'];
                                $tautan = (string) $baris['tautan'];
                                $sampul = is_string($baris['sampul']) && $unggahan->ada($baris['sampul']) ? $baris['sampul'] : null;
                                ?>
                                <tr id="album-<?= $id ?>" data-album="<?= $id ?>"<?= $aktif ? '' : ' class="text-body-secondary"' ?>>
                                    <td class="text-end text-body-secondary"><?= $i + 1 ?></td>
                                    <td>
                                        <?php if ($sampul !== null) : ?>
                                            <img src="<?= base_url($sampul) ?>" alt="" width="96" height="64" class="rounded object-fit-cover border" loading="lazy">
                                        <?php else : ?>
                                            <span class="ikon-bulat"><i class="bi bi-image" aria-hidden="true"></i><span class="visually-hidden">Tanpa sampul</span></span>
                                        <?php endif ?>
                                    </td>
                                    <td class="min-w-12r">
                                        <span class="fw-semibold"><?= esc($judul) ?></span>
                                        <?php if (! $aktif) : ?>
                                            <span class="badge text-bg-secondary ms-1">Disembunyikan</span>
                                        <?php endif ?>
                                        <?php if ($tautan === '') : ?>
                                            <span class="badge text-bg-warning ms-1" data-tanpa-tautan>Belum ada tautan</span>
                                        <?php else : ?>
                                            <a class="d-block small text-break" href="<?= esc($tautan, 'attr') ?>" target="_blank" rel="noopener noreferrer"><?= esc($tautan) ?></a>
                                        <?php endif ?>
                                        <?php if ((string) $baris['keterangan'] !== '') : ?>
                                            <span class="d-block small text-body-secondary"><?= esc((string) $baris['keterangan']) ?></span>
                                        <?php endif ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex flex-wrap justify-content-end gap-2">
                                            <?php foreach (['naik' => ['arrow-up', 'Naikkan', $i === 0], 'turun' => ['arrow-down', 'Turunkan', $i === $terakhir]] as $arah => [$ikon, $label, $mati]) : ?>
                                                <form action="<?= $alamat($id . '/' . $arah) ?>" method="post">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary" title="<?= $label ?>"<?= $mati ? ' disabled' : '' ?>>
                                                        <i class="bi bi-<?= $ikon ?>" aria-hidden="true"></i><span class="visually-hidden"> <?= $label ?> <?= esc($judul) ?></span>
                                                    </button>
                                                </form>
                                            <?php endforeach ?>
                                            <a class="btn btn-sm btn-outline-primary text-nowrap" href="<?= $alamat($id . '/ubah') ?>">
                                                <i class="bi bi-pencil" aria-hidden="true"></i> Ubah<span class="visually-hidden"> <?= esc($judul) ?></span>
                                            </a>
                                            <form action="<?= $alamat($id . '/aktif') ?>" method="post">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-secondary text-nowrap">
                                                    <i class="bi bi-<?= $aktif ? 'eye-slash' : 'eye' ?>" aria-hidden="true"></i>
                                                    <?= $aktif ? 'Sembunyikan' : 'Tampilkan' ?><span class="visually-hidden"> <?= esc($judul) ?></span>
                                                </button>
                                            </form>
                                            <form action="<?= $alamat($id . '/hapus') ?>" method="post"
                                                data-konfirmasi="<?= esc('Album "' . $judul . '" dan sampulnya akan dihapus. Foto di Instagram atau Google Drive tidak ikut terhapus. Tindakan ini tidak bisa dibatalkan. Bila hanya ingin menyembunyikannya, tekan Sembunyikan.', 'attr') ?>"
                                                data-konfirmasi-judul="Hapus album?"
                                                data-konfirmasi-tombol="Hapus album">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-danger text-nowrap">
                                                    <i class="bi bi-trash" aria-hidden="true"></i> Hapus<span class="visually-hidden"> <?= esc($judul) ?></span>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            <?php endif ?>
        </div>
    </section>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<?= $this->endSection() ?>
