<?php
/**
 * Cetak nomor atlet (BIB) massal (F8-03, K137). Data dari `CetakBibController::index()`.
 *
 * @var CodeIgniter\View\View $this
 * @var list<array{id: int, nama: string, atlet: int}> $kelompokUmur
 * @var int $jumlahSemua
 * @var list<array{kunci: string, nama: string, npsn: string, atlet: int}> $sekolahBib
 * @var App\Libraries\HariLomba\TampilanBib $tampilanBib
 * @var bool $kepalaDipilih
 * @var string $warnaDipilih
 */

use App\Libraries\HariLomba\TampilanBib;
use App\Libraries\Unggahan;

?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?>Cetak Nomor Atlet (BIB)<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid px-3 px-lg-4">
    <?= view('admin/hari_lomba/_tab', ['aktif' => 'admin/nomor-atlet/cetak'], ['saveData' => false]) ?>

    <p class="teks-info mb-4">
        Satu nomor atlet (BIB) seukuran setengah A4 mendatar (210 × 148,5 mm). PDF memuat dua BIB per halaman A4; potong di garis putus-putus.
        Gambar JPG berukuran 2480 × 1754 piksel (300 dpi), satu berkas per atlet, untuk dikirim ke percetakan.
        Isinya nomor atlet, nama atlet, sekolah, dan kelompok umur. Hanya atlet dari pendaftaran Lunas.
        Urutkan nomor atlet lebih dulu bila perlu (<a href="<?= site_url('admin/atlet/nomor-dada') ?>">Urutkan nomor atlet</a>).
    </p>

    <section class="card mb-4" aria-labelledby="judul-tampilan">
        <div class="card-body">
            <h2 class="h5" id="judul-tampilan">Tampilan BIB</h2>
            <div class="row g-4">
                <div class="col-12 col-lg-6">
                    <p class="small text-body-secondary mb-2">Contoh hasil cetak<?= $jumlahSemua > 0 ? ' (atlet pertama)' : '' ?>:</p>
                    <img src="<?= site_url('admin/nomor-atlet/cetak/contoh') ?>?v=<?= esc(md5((string) $tampilanBib->latar . (int) $tampilanBib->kepala . $tampilanBib->namaWarna), 'attr') ?>"
                        class="img-fluid border rounded bg-white" id="contoh-bib" alt="Contoh nomor atlet (BIB) dengan tampilan yang berlaku" width="2480" height="1754" loading="lazy">
                </div>
                <div class="col-12 col-lg-6">
                    <form action="<?= site_url('admin/nomor-atlet/cetak/tampilan') ?>" method="post" enctype="multipart/form-data" id="form-tampilan-bib">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label for="latar" class="form-label fw-semibold"><?= $tampilanBib->latar === null ? 'Unggah latar' : 'Ganti latar' ?></label>
                            <input type="file" class="form-control" id="latar" name="latar" accept="image/jpeg,image/png,image/webp"
                                data-ukuran-maksimal="<?= TampilanBib::BATAS_LATAR ?>" aria-describedby="latar-bantuan">
                            <div id="latar-bantuan" class="form-text">
                                Gambar latar penuh satu BIB, dipakai semua BIB. JPG, PNG, atau WEBP, paling besar <?= Unggahan::ukuranTeks(TampilanBib::BATAS_LATAR) ?>.
                                Ukuran terbaik 2480 × 1754 piksel (mendatar); ukuran lain dipotong di tengah supaya pas.
                                Sisakan bagian tengah yang polos untuk nomor dan nama atlet.
                            </div>
                            <?php if ($tampilanBib->latar !== null) : ?>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" value="1" id="hapus_latar" name="hapus_latar">
                                    <label class="form-check-label" for="hapus_latar">Hapus latar (kembali ke BIB putih)</label>
                                </div>
                            <?php endif ?>
                        </div>
                        <div class="form-check form-switch mb-1">
                            <input class="form-check-input" type="checkbox" role="switch" id="tampilkan_kepala" name="tampilkan_kepala" value="1"<?= $kepalaDipilih ? ' checked' : '' ?>>
                            <label class="form-check-label" for="tampilkan_kepala">Cetak kepala di atas latar (logo, nama acara, kelompok umur)</label>
                        </div>
                        <p class="form-text mt-0 mb-3">Matikan bila desain latar sudah memuat logo dan nama acara. Tanpa latar, kepala selalu dicetak.</p>
                        <fieldset class="mb-3">
                            <legend class="form-label fw-semibold fs-6 mb-1">Warna tulisan di atas latar</legend>
                            <?php foreach (TampilanBib::PILIHAN_WARNA as $nilai => $label) : ?>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="warna_teks" id="warna_<?= $nilai ?>" value="<?= $nilai ?>"<?= ($warnaDipilih === $nilai || ($nilai === 'gelap' && ! isset(TampilanBib::PILIHAN_WARNA[$warnaDipilih]))) ? ' checked' : '' ?>>
                                    <label class="form-check-label" for="warna_<?= $nilai ?>"><?= esc($label) ?></label>
                                </div>
                            <?php endforeach ?>
                            <div class="form-text">Pilih Terang untuk latar gelap. Tanpa latar, tulisan selalu gelap.</div>
                        </fieldset>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg" aria-hidden="true"></i> Simpan tampilan</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <section class="card mb-4" aria-labelledby="judul-sekolah-bib">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <h2 class="h5 mb-0" id="judul-sekolah-bib">Per sekolah</h2>
                <?php if ($sekolahBib !== []) : ?>
                    <a class="btn btn-primary" id="unduh-zip-semua" href="<?= site_url('admin/nomor-atlet/cetak/zip') ?>">
                        <i class="bi bi-file-earmark-zip" aria-hidden="true"></i> Unduh semua sekolah (ZIP)
                    </a>
                <?php endif ?>
            </div>
            <?php if ($sekolahBib === []) : ?>
                <p class="text-body-secondary mb-0" id="bib-sekolah-kosong">Belum ada sekolah dengan atlet ber-nomor atlet (BIB).</p>
            <?php else : ?>
                <p class="small text-body-secondary">
                    ZIP sekolah berisi gambar JPG per atlet dan PDF sekolah itu. ZIP semua sekolah berisi satu folder per sekolah
                    (<?= count($sekolahBib) ?> sekolah, <?= $jumlahSemua ?> atlet) dan bisa butuh beberapa menit sampai terunduh.
                </p>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0" id="daftar-bib-sekolah">
                        <thead>
                            <tr><th scope="col">Sekolah</th><th scope="col" class="text-end">Atlet</th><th scope="col" class="text-end">Unduh</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sekolahBib as $s) : ?>
                                <tr>
                                    <td>
                                        <?= esc($s['nama']) ?>
                                        <?php if ($s['npsn'] !== '') : ?><div class="small text-body-secondary">NPSN <?= esc($s['npsn']) ?></div><?php endif ?>
                                    </td>
                                    <td class="text-end"><?= $s['atlet'] ?></td>
                                    <td class="text-end text-nowrap">
                                        <a class="btn btn-sm btn-outline-primary" href="<?= site_url('admin/nomor-atlet/cetak/sekolah/' . $s['kunci'] . '/pdf') ?>" aria-label="<?= esc('PDF BIB ' . $s['nama'], 'attr') ?>"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> PDF</a>
                                        <a class="btn btn-sm btn-outline-primary" href="<?= site_url('admin/nomor-atlet/cetak/sekolah/' . $s['kunci'] . '/zip') ?>" aria-label="<?= esc('ZIP gambar BIB ' . $s['nama'], 'attr') ?>"><i class="bi bi-file-earmark-zip" aria-hidden="true"></i> ZIP</a>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            <?php endif ?>
        </div>
    </section>

    <section class="card" aria-labelledby="judul-bib">
        <div class="card-body">
            <h2 class="h5" id="judul-bib">Per kelompok umur (PDF)</h2>
            <?php if ($jumlahSemua === 0) : ?>
                <p class="text-body-secondary mb-0" id="bib-kosong">Belum ada atlet dengan nomor atlet (BIB). Nomor atlet dibuat saat pendaftaran ditandai Lunas.</p>
            <?php else : ?>
                <div class="list-group" id="daftar-bib">
                    <?php foreach ($kelompokUmur as $ku) : ?>
                        <?php if ($ku['atlet'] > 0) : ?>
                            <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="<?= site_url('admin/nomor-atlet/cetak/pdf') ?>?ku=<?= $ku['id'] ?>">
                                <span><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> <?= esc($ku['nama']) ?></span>
                                <span class="text-body-secondary small"><?= $ku['atlet'] ?> atlet · <?= (int) ceil($ku['atlet'] / 2) ?> halaman</span>
                            </a>
                        <?php else : ?>
                            <div class="list-group-item d-flex justify-content-between text-body-secondary">
                                <span><?= esc($ku['nama']) ?></span><span class="small">Belum ada atlet</span>
                            </div>
                        <?php endif ?>
                    <?php endforeach ?>
                    <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center fw-semibold" href="<?= site_url('admin/nomor-atlet/cetak/pdf') ?>?ku=semua">
                        <span><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> Semua kelompok umur</span>
                        <span class="text-body-secondary small"><?= $jumlahSemua ?> atlet · <?= (int) ceil($jumlahSemua / 2) ?> halaman</span>
                    </a>
                </div>
                <p class="small text-body-secondary mt-3 mb-0">Berkas besar butuh beberapa detik sampai terunduh.</p>
            <?php endif ?>
        </div>
    </section>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<?= $this->endSection() ?>
