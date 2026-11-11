<?php
/**
 * Formulir tambah dan ubah kelompok umur. Data dari `KelompokUmurController`.
 *
 * Kotak: nama, tahun lahir paling awal dan paling akhir (K36), centang
 * jenjang (K24), biaya individu, awalan nomor dada (urut sesuai ketikan),
 * urutan tampil, dan aktif.
 *
 * @var CodeIgniter\View\View       $this
 * @var array<string, mixed>|null   $ku         kelompok umur tersimpan (ubah) atau null (tambah)
 * @var array<string, mixed>        $isian      nilai kotak (teks; `jenjang` larik kode)
 * @var array<string, string>       $galat      nama kotak => pesan
 * @var array<string, string>       $awalanLain awalan => nama kelompok umur lain pemiliknya
 */

use App\Libraries\Jenjang;
use App\Models\KelompokUmurModel;

$baru   = $ku === null;
$aksi   = $baru ? site_url('admin/pengaturan/kelompok-umur') : site_url('admin/pengaturan/kelompok-umur/' . (int) $ku['id']);
$judul  = $baru ? 'Tambah Kelompok Umur' : 'Ubah ' . $ku['nama'];

// Kelas dan atribut untuk kotak yang salah.
$salah     = static fn (string $kotak): string => isset($galat[$kotak]) ? ' is-invalid' : '';
$jelaskan  = static function (string $kotak, string $bantuan = '') use ($galat): string {
    $id = trim($bantuan . (isset($galat[$kotak]) ? ' ' . $kotak . '-galat' : ''));

    return $id === '' ? '' : ' aria-describedby="' . $id . '"';
};
$pesan     = static fn (string $kotak): string => isset($galat[$kotak]) ? '<div id="' . $kotak . '-galat" class="invalid-feedback">' . esc($galat[$kotak]) . '</div>' : '';

// Tabrakan tahun lahir disebut di kotak pertama; kotak kedua ikut ditandai.
$tahunSalah = isset($galat['tahun_lahir_awal']) || isset($galat['tahun_lahir_akhir']);

$terpakai = array_values(array_filter($ku['awalan'] ?? [], static fn (array $a): bool => (int) $a['nomor_terakhir'] > 0));
?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?><?= esc($judul) ?> – Admin MilkLife<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Kelompok Umur<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid px-3 px-lg-4">
    <?= $this->include('admin/pengaturan/_navigasi') ?>

    <div class="row">
        <div class="col-12 col-xl-9 col-xxl-8">
            <h2 class="h4 mb-3"><?= esc($judul) ?></h2>

            <?= view('admin/_galat_isian', ['galat' => $galat], ['saveData' => false]) ?>

            <form action="<?= $aksi ?>" method="post" id="formulir-kelompok-umur" novalidate>
                <?= csrf_field() ?>

                <div class="card mb-3">
                    <div class="card-body p-3 p-md-4">
                        <div class="mb-4">
                            <label for="nama" class="form-label fw-semibold">Nama kelompok umur</label>
                            <input type="text" class="form-control<?= $salah('nama') ?>" id="nama" name="nama"
                                maxlength="<?= KelompokUmurModel::NAMA_MAKSIMAL ?>" value="<?= esc((string) $isian['nama'], 'attr') ?>"
                                placeholder="Contoh: KU 10"<?= $jelaskan('nama') ?>>
                            <?= $pesan('nama') ?>
                        </div>

                        <fieldset class="mb-4">
                            <legend class="form-label fw-semibold fs-6 mb-1">Tahun lahir atlet</legend>
                            <p id="tahun-lahir-bantuan" class="form-text mt-0 mb-2">
                                Atlet masuk kelompok umur ini bila tahun lahirnya ada di antara kedua tahun, termasuk kedua tahun itu.
                                Kosongkan salah satu bila tidak dibatasi, misalnya KU 8 "lahir 2019 ke atas": isi 2019 lalu kosongkan kotak kedua.
                            </p>
                            <div class="row g-2 align-items-start">
                                <div class="col-12 col-sm-5">
                                    <label for="tahun_lahir_awal" class="form-label small mb-1">Paling awal (atlet tertua)</label>
                                    <input type="text" inputmode="numeric" maxlength="4" autocomplete="off"
                                        class="form-control<?= $tahunSalah ? ' is-invalid' : '' ?>" id="tahun_lahir_awal" name="tahun_lahir_awal"
                                        value="<?= esc((string) $isian['tahun_lahir_awal'], 'attr') ?>" placeholder="Contoh: 2016"<?= $jelaskan('tahun_lahir_awal', 'tahun-lahir-bantuan') ?>>
                                    <?= $pesan('tahun_lahir_awal') ?>
                                </div>
                                <div class="col-auto d-none d-sm-flex align-self-center pt-4 text-body-secondary">sampai</div>
                                <div class="col-12 col-sm-5">
                                    <label for="tahun_lahir_akhir" class="form-label small mb-1">Paling akhir (atlet termuda)</label>
                                    <input type="text" inputmode="numeric" maxlength="4" autocomplete="off"
                                        class="form-control<?= $tahunSalah ? ' is-invalid' : '' ?>" id="tahun_lahir_akhir" name="tahun_lahir_akhir"
                                        value="<?= esc((string) $isian['tahun_lahir_akhir'], 'attr') ?>" placeholder="Contoh: 2018"<?= $jelaskan('tahun_lahir_akhir', 'tahun-lahir-bantuan') ?>>
                                    <?= $pesan('tahun_lahir_akhir') ?>
                                </div>
                            </div>
                        </fieldset>

                        <fieldset class="mb-4" id="jenjang" tabindex="-1" aria-describedby="jenjang-bantuan<?= isset($galat['jenjang']) ? ' jenjang-galat' : '' ?>">
                            <legend class="form-label fw-semibold fs-6 mb-1">Jenjang sekolah yang boleh ikut</legend>
                            <p id="jenjang-bantuan" class="form-text mt-0 mb-2">
                                Atlet dari jenjang yang tidak dicentang tidak bisa didaftarkan di kelompok umur ini.
                            </p>
                            <div class="d-flex flex-wrap column-gap-4 row-gap-2">
                                <?php foreach (Jenjang::semua() as $kode => $label) : ?>
                                    <div class="form-check">
                                        <input class="form-check-input<?= $salah('jenjang') ?>" type="checkbox" name="jenjang[]"
                                            id="jenjang-<?= esc($kode, 'attr') ?>" value="<?= esc($kode, 'attr') ?>"
                                            <?= in_array($kode, (array) $isian['jenjang'], true) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="jenjang-<?= esc($kode, 'attr') ?>"><?= esc($label) ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if (isset($galat['jenjang'])) : ?>
                                <div id="jenjang-galat" class="invalid-feedback d-block"><?= esc($galat['jenjang']) ?></div>
                            <?php endif ?>
                        </fieldset>

                        <div class="mb-4">
                            <label for="biaya_individu" class="form-label fw-semibold">Biaya per nomor individu</label>
                            <div class="input-group has-validation">
                                <span class="input-group-text">Rp</span>
                                <input type="text" inputmode="numeric" autocomplete="off"
                                    class="form-control<?= $salah('biaya_individu') ?>" id="biaya_individu" name="biaya_individu"
                                    value="<?= esc((string) $isian['biaya_individu'], 'attr') ?>" placeholder="Contoh: 50000"<?= $jelaskan('biaya_individu', 'biaya-bantuan') ?>>
                                <?= $pesan('biaya_individu') ?>
                            </div>
                            <div id="biaya-bantuan" class="form-text">
                                Dibayar per atlet untuk setiap nomor individu. Tulis angka tanpa titik. Biaya nomor beregu diatur di halaman Nomor Lomba.
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="awalan" class="form-label fw-semibold">Awalan nomor dada</label>
                            <input type="text" inputmode="numeric" autocomplete="off"
                                class="form-control<?= $salah('awalan') ?>" id="awalan" name="awalan"
                                value="<?= esc((string) $isian['awalan'], 'attr') ?>" placeholder="Contoh: 2"<?= $jelaskan('awalan', 'awalan-bantuan') ?>>
                            <?= $pesan('awalan') ?>
                            <div id="awalan-bantuan" class="form-text">
                                Angka pertama nomor dada, satu angka 1 sampai 9. Satu awalan cukup untuk 999 atlet; bila perlu lebih,
                                tulis beberapa awalan dipisah koma, misalnya <strong>2, 6</strong>. Awalan dipakai berurutan seperti ditulis.
                                <?php if ($awalanLain !== []) : ?>
                                    <span class="d-block mt-1">
                                        Sudah dipakai kelompok umur lain:
                                        <?= esc(implode('; ', array_map(static fn (string $kode, string $nama): string => $kode . ' untuk ' . $nama, array_map('strval', array_keys($awalanLain)), $awalanLain))) ?>.
                                    </span>
                                <?php endif ?>
                                <?php foreach ($terpakai as $a) : ?>
                                    <span class="d-block mt-1 text-body">
                                        <i class="bi bi-lock" aria-hidden="true"></i>
                                        Awalan <?= esc((string) $a['awalan']) ?> sudah dipakai sampai nomor dada
                                        <?= esc($a['awalan'] . str_pad((string) $a['nomor_terakhir'], 3, '0', STR_PAD_LEFT)) ?>, jadi tidak bisa dihapus.
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="row g-3 align-items-start">
                            <div class="col-12 col-sm-4 col-md-3">
                                <label for="urutan" class="form-label fw-semibold">Urutan tampil</label>
                                <input type="text" inputmode="numeric" maxlength="3" autocomplete="off"
                                    class="form-control<?= $salah('urutan') ?>" id="urutan" name="urutan"
                                    value="<?= esc((string) $isian['urutan'], 'attr') ?>"<?= $jelaskan('urutan', 'urutan-bantuan') ?>>
                                <?= $pesan('urutan') ?>
                                <div id="urutan-bantuan" class="form-text">Angka kecil tampil lebih dulu.</div>
                            </div>
                            <div class="col-12 col-sm-8 col-md-9 pt-sm-4">
                                <div class="form-check form-switch mt-sm-2">
                                    <input class="form-check-input" type="checkbox" role="switch" id="aktif" name="aktif" value="1"
                                        aria-describedby="aktif-bantuan"<?= (string) $isian['aktif'] === '1' ? ' checked' : '' ?>>
                                    <label class="form-check-label fw-semibold" for="aktif">Aktif</label>
                                </div>
                                <div id="aktif-bantuan" class="form-text">Kelompok umur yang mati tidak muncul di pendaftaran.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg" aria-hidden="true"></i>
                        Simpan
                    </button>
                    <a class="btn btn-outline-secondary" href="<?= site_url('admin/pengaturan/kelompok-umur') ?>">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
