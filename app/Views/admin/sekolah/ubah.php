<?php
/**
 * Formulir ubah sekolah (K97): `GET admin/sekolah/<id>/ubah`. Ditampilkan
 * ulang dengan isian yang diketik bila ada yang salah. NPSN hanya
 * ditampilkan; tidak bisa diubah. Di samping formulir tampil sekolah lain
 * yang namanya sama, supaya admin tahu pembeda apa yang perlu ditambahkan.
 *
 * @var CodeIgniter\View\View       $this
 * @var array<string, mixed>        $sekolah           data tersimpan
 * @var array<string, string>       $isian             nilai kotak formulir
 * @var array<string, string>       $galat             pesan per kotak
 * @var list<string>                $kabupaten
 * @var array<string, string>       $jenjang           kode data sekolah => label
 * @var list<array<string, mixed>>  $namaSama          sekolah lain bernama sama (paling banyak 20)
 * @var int                         $jumlahPendaftaran pendaftaran dengan NPSN ini
 */

use App\Models\SekolahModel;

$salah = static fn (string $kotak): string => isset($galat[$kotak]) ? ' is-invalid' : '';
$pesan = static fn (string $kotak): string => isset($galat[$kotak])
    ? '<div id="' . $kotak . '-galat" class="invalid-feedback">' . esc($galat[$kotak]) . '</div>'
    : '';
$uraian = static fn (string $kotak, bool $bantuan = false): string => ($bantuan || isset($galat[$kotak]))
    ? ' aria-describedby="' . trim(($bantuan ? $kotak . '-bantuan ' : '') . (isset($galat[$kotak]) ? $kotak . '-galat' : '')) . '"'
    : '';

$teks = static function (string $kotak, string $label, int $panjang, string $bantuan = '') use ($isian, $salah, $pesan, $uraian): string {
    return '<div class="mb-3">'
        . '<label for="' . $kotak . '" class="form-label">' . esc($label) . '</label>'
        . '<input type="text" class="form-control' . $salah($kotak) . '" id="' . $kotak . '" name="' . $kotak . '"'
        . ' value="' . esc($isian[$kotak], 'attr') . '" maxlength="' . $panjang . '"' . $uraian($kotak, $bantuan !== '') . '>'
        . ($bantuan !== '' ? '<div id="' . $kotak . '-bantuan" class="form-text">' . esc($bantuan) . '</div>' : '')
        . $pesan($kotak)
        . '</div>';
};
?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?>Ubah sekolah<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Sekolah<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid px-3 px-lg-4">
    <a class="tautan-kembali mb-3" href="<?= site_url('admin/sekolah') ?>">
        <i class="bi bi-arrow-left" aria-hidden="true"></i> Kembali ke daftar sekolah
    </a>

    <div class="row g-4">
        <div class="col-12 col-lg-8 col-xl-7">
            <section class="card" aria-labelledby="judul-formulir-sekolah">
                <div class="card-body p-3 p-lg-4">
                    <h2 id="judul-formulir-sekolah" class="h5 mb-1">
                        <i class="bi bi-building text-primary" aria-hidden="true"></i>
                        Ubah sekolah
                    </h2>
                    <p class="text-body-secondary small mb-4">
                        NPSN <strong><?= esc($sekolah['npsn']) ?></strong> tidak bisa diubah.
                        <?php if ($sekolah['disunting_pada'] !== null) : ?>
                            Terakhir disunting <?= esc(tanggal_id((string) $sekolah['disunting_pada'], true)) ?>.
                        <?php endif ?>
                        Setelah disimpan, sekolah ini tidak ditimpa saat data sekolah diimpor ulang.
                    </p>

                    <?= view('admin/_galat_isian', ['galat' => $galat], ['saveData' => false]) ?>

                    <form action="<?= site_url('admin/sekolah/' . (int) $sekolah['id']) ?>" method="post" novalidate>
                        <?= csrf_field() ?>

                        <?= $teks('nama', 'Nama sekolah', SekolahModel::PANJANG['nama'], 'Bila namanya sama dengan sekolah lain, tambahkan pembeda seperti nama desa, misalnya MIS MUHAMMADIYAH JATI.') ?>

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="terapkan" name="terapkan" value="1"<?= $isian['terapkan'] === '1' ? ' checked' : '' ?> aria-describedby="terapkan-bantuan">
                            <label class="form-check-label" for="terapkan">Ganti juga nama sekolah di pendaftaran yang sudah masuk</label>
                            <div id="terapkan-bantuan" class="form-text">
                                <?= $jumlahPendaftaran > 0
                                    ? 'Ada ' . $jumlahPendaftaran . ' pendaftaran dengan NPSN ini. Nama di rekap dan bukti pendaftarannya ikut berganti bila nama sekolah diubah.'
                                    : 'Belum ada pendaftaran dengan NPSN ini.' ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-6 mb-3">
                                <label for="jenjang" class="form-label">Jenjang</label>
                                <select class="form-select<?= $salah('jenjang') ?>" id="jenjang" name="jenjang"<?= $uraian('jenjang', true) ?>>
                                    <?php foreach ($jenjang as $kode => $label) : ?>
                                        <option value="<?= esc($kode, 'attr') ?>"<?= strtoupper($isian['jenjang']) === $kode ? ' selected' : '' ?>><?= esc($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div id="jenjang-bantuan" class="form-text">Menentukan kelompok umur yang boleh diikuti atlet.</div>
                                <?= $pesan('jenjang') ?>
                            </div>
                            <div class="col-sm-6">
                                <?= $teks('bentuk', 'Bentuk', SekolahModel::PANJANG['bentuk'], 'Contoh: SD, MI, SMP, MTs, SMA, SMK, MA.') ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-6 mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select<?= $salah('status') ?>" id="status" name="status"<?= $uraian('status') ?>>
                                    <option value="">Tidak diketahui</option>
                                    <?php foreach (SekolahModel::STATUS as $kode => $label) : ?>
                                        <option value="<?= esc($kode, 'attr') ?>"<?= strtoupper($isian['status']) === $kode ? ' selected' : '' ?>><?= esc($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?= $pesan('status') ?>
                            </div>
                            <div class="col-sm-6 mb-3">
                                <label for="kabupaten" class="form-label">Kabupaten</label>
                                <select class="form-select<?= $salah('kabupaten') ?>" id="kabupaten" name="kabupaten"<?= $uraian('kabupaten') ?>>
                                    <?php foreach ($kabupaten as $nama) : ?>
                                        <option value="<?= esc($nama, 'attr') ?>"<?= strcasecmp($isian['kabupaten'], $nama) === 0 ? ' selected' : '' ?>><?= esc($nama) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?= $pesan('kabupaten') ?>
                            </div>
                        </div>

                        <?= $teks('alamat', 'Alamat', SekolahModel::PANJANG['alamat']) ?>

                        <div class="row">
                            <div class="col-sm-6">
                                <?= $teks('desa_kelurahan', 'Desa/kelurahan', SekolahModel::PANJANG['desa_kelurahan']) ?>
                            </div>
                            <div class="col-sm-6">
                                <?= $teks('kecamatan', 'Kecamatan', SekolahModel::PANJANG['kecamatan']) ?>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg" aria-hidden="true"></i> Simpan perubahan
                            </button>
                            <a class="btn btn-outline-secondary" href="<?= site_url('admin/sekolah') ?>">Batal</a>
                        </div>
                    </form>
                </div>
            </section>
        </div>

        <div class="col-12 col-lg-4 col-xl-5">
            <section class="card" aria-labelledby="judul-nama-sama">
                <div class="card-body p-3 p-lg-4">
                    <h2 id="judul-nama-sama" class="h6 mb-2">Sekolah lain bernama <?= esc($sekolah['nama']) ?></h2>
                    <?php if ($namaSama === []) : ?>
                        <p class="text-body-secondary small mb-0">Tidak ada. Nama sekolah ini sudah berbeda dari sekolah lain.</p>
                    <?php else : ?>
                        <ul class="list-unstyled small mb-0" id="daftar-nama-sama">
                            <?php foreach ($namaSama as $lain) : ?>
                                <li class="mb-2">
                                    <a href="<?= site_url('admin/sekolah/' . (int) $lain['id'] . '/ubah') ?>">NPSN <?= esc($lain['npsn']) ?></a>
                                    <span class="d-block text-body-secondary"><?= esc(SekolahModel::lokasi($lain)) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif ?>
                </div>
            </section>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
