<?php
/**
 * Baris satu komponen dan kategori tabel skor (F9-10): ubah, tambah, hapus
 * baris; ganti semua baris dari Excel; unduh Excel. Data dari
 * `TabelSkorController::baris()`.
 *
 * @var CodeIgniter\View\View                          $this
 * @var array<string, mixed>                           $tabel      `DataTabelSkor::tabel()`
 * @var array{kode: string, nama: string, jenis: string} $komponen
 * @var string                                         $kategori   putra atau putri
 * @var list<array{0: int, 1: int}>                    $baris      urut dari prestasi terbaik
 * @var array<string, string>                          $galat      galat simpan baris
 * @var list<string>                                   $galatImpor galat impor Excel per baris
 * @var array<string, mixed>                           $isian      isian simpan baris yang dikembalikan
 */

use App\Libraries\Hasil\JenisHasil;
use App\Libraries\Hasil\Prestasi;

$alamat  = 'admin/pengaturan/tabel-skor/' . $tabel['id'] . '/' . $komponen['kode'] . '/' . $kategori;
$waktu   = $komponen['jenis'] === JenisHasil::WAKTU;
$contoh  = $waktu ? '7,00' : '4,25';
$judul   = $komponen['nama'] . ' ' . $kategori;
?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?><?= esc($judul) ?> – <?= esc($tabel['nama']) ?><?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Tabel Skor<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid px-3 px-lg-4">
    <?= $this->include('admin/pengaturan/_navigasi') ?>

    <h2 class="h4 mb-1"><?= esc($judul) ?></h2>
    <p class="text-body-secondary mb-4"><?= esc($tabel['nama']) ?> · <?= count($baris) ?> baris · prestasi <?= $waktu ? 'waktu dalam detik (makin kecil makin baik)' : 'jarak dalam meter (makin jauh makin baik)' ?></p>

    <div class="row g-4">
        <div class="col-12 col-lg-5 order-lg-2">
            <section class="card mb-4" id="ubah-baris" aria-labelledby="judul-ubah-baris">
                <div class="card-body">
                    <h3 class="h5" id="judul-ubah-baris">Ubah atau tambah baris</h3>
                    <p class="small text-body-secondary">Tulis prestasi dan poinnya. Bila prestasi itu sudah ada, poinnya diganti.</p>
                    <form action="<?= site_url($alamat) ?>" method="post" class="row g-2 align-items-end" novalidate>
                        <?= csrf_field() ?>
                        <div class="col-6">
                            <label for="prestasi" class="form-label">Prestasi</label>
                            <input type="text" inputmode="decimal" class="form-control<?= isset($galat['prestasi']) ? ' is-invalid' : '' ?>" id="prestasi" name="prestasi" placeholder="<?= $contoh ?>" value="<?= esc((string) ($isian['prestasi'] ?? ''), 'attr') ?>">
                        </div>
                        <div class="col-6">
                            <label for="poin" class="form-label">Poin</label>
                            <input type="text" inputmode="numeric" class="form-control<?= isset($galat['poin']) ? ' is-invalid' : '' ?>" id="poin" name="poin" placeholder="882" value="<?= esc((string) ($isian['poin'] ?? ''), 'attr') ?>">
                        </div>
                        <?php foreach ($galat as $pesan) : ?><div class="col-12 invalid-feedback d-block"><?= esc($pesan) ?></div><?php endforeach ?>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-save" aria-hidden="true"></i> Simpan baris</button>
                        </div>
                    </form>
                </div>
            </section>

            <section class="card mb-4" id="impor" aria-labelledby="judul-impor">
                <div class="card-body">
                    <h3 class="h5" id="judul-impor">Ganti dari Excel</h3>
                    <p class="small text-body-secondary">Kolom A prestasi (misalnya <?= $contoh ?><?= $waktu ? ' atau 2:05,10' : '' ?>), kolom B poin. Baris judul boleh ada. Semua baris <?= esc($judul) ?> diganti isi berkas; bila ada baris yang salah, tidak ada yang diganti.</p>
                    <?php if ($galatImpor !== []) : ?>
                        <ul class="small text-danger" id="galat-impor">
                            <?php foreach ($galatImpor as $pesan) : ?><li><?= esc($pesan) ?></li><?php endforeach ?>
                        </ul>
                    <?php endif ?>
                    <form action="<?= site_url($alamat . '/impor') ?>" method="post" enctype="multipart/form-data"
                        data-konfirmasi="<?= esc('Semua baris ' . $judul . ' akan diganti isi berkas Excel, lalu poin hasil lomba dihitung ulang.', 'attr') ?>"
                        data-konfirmasi-judul="Ganti baris tabel?" data-konfirmasi-tombol="Ganti dari Excel">
                        <?= csrf_field() ?>
                        <label for="berkas" class="form-label">Berkas Excel</label>
                        <input type="file" class="form-control mb-2" id="berkas" name="berkas" accept=".xlsx,.xls,.csv">
                        <button type="submit" class="btn btn-outline-primary"><i class="bi bi-upload" aria-hidden="true"></i> Ganti dari Excel</button>
                    </form>
                    <a class="btn btn-link px-0 mt-2" href="<?= site_url($alamat . '/excel') ?>"><i class="bi bi-file-earmark-excel" aria-hidden="true"></i> Unduh baris ini sebagai Excel</a>
                </div>
            </section>
            <a class="btn btn-outline-secondary mb-4" href="<?= site_url('admin/pengaturan/tabel-skor') . '#tabel-' . $tabel['id'] ?>">Kembali ke daftar tabel</a>
        </div>

        <div class="col-12 col-lg-7 order-lg-1">
            <section class="card" aria-labelledby="judul-baris">
                <div class="card-body">
                    <h3 class="h5" id="judul-baris">Baris tabel</h3>
                    <?php if ($baris === []) : ?>
                        <p class="text-body-secondary mb-0" id="baris-kosong">Belum ada baris. Isi lewat Excel atau satu per satu.</p>
                    <?php else : ?>
                        <form action="<?= site_url($alamat . '/hapus') ?>" method="post" id="hapus-baris"
                            data-konfirmasi="<?= esc('Baris yang dipilih akan dihapus dari tabel ' . $judul . ', lalu poin hasil lomba dihitung ulang.', 'attr') ?>"
                            data-konfirmasi-judul="Hapus baris?" data-konfirmasi-tombol="Hapus baris">
                            <?= csrf_field() ?>
                            <div class="tabel-gulir">
                                <table class="table table-sm align-middle mb-0" id="tabel-baris">
                                    <caption class="visually-hidden">Baris <?= esc($judul) ?></caption>
                                    <thead>
                                        <tr><th scope="col">Prestasi</th><th scope="col" class="text-end">Poin</th><th scope="col" class="text-end">Hapus</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($baris as [$prestasi, $poin]) : ?>
                                            <?php $teks = esc(Prestasi::tulis($prestasi, $komponen['jenis'])); ?>
                                            <tr>
                                                <td><?= $teks ?></td>
                                                <td class="text-end"><?= $poin ?></td>
                                                <td class="text-end"><button type="submit" name="prestasi" value="<?= $prestasi ?>" class="btn btn-link btn-sm text-danger p-0"><i class="bi bi-trash" aria-hidden="true"></i><span class="visually-hidden"> Hapus baris <?= $teks ?></span></button></td>
                                            </tr>
                                        <?php endforeach ?>
                                    </tbody>
                                </table>
                            </div>
                        </form>
                    <?php endif ?>
                </div>
            </section>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<?= $this->endSection() ?>
