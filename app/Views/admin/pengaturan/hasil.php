<?php
/**
 * Pengaturan > Hasil (F9-11): metode klasemen juara umum dan poin per
 * peringkat. Data dari `MetodeKlasemenController`.
 *
 * @var CodeIgniter\View\View $this
 * @var string                $metode `Klasemen::MEDALI` atau `Klasemen::POIN` (tersimpan atau yang baru dipilih)
 * @var list<string>          $poin   poin peringkat 1–8 (tersimpan atau yang baru diketik)
 * @var array<string, string> $galat  `metode`, `poin`, atau `poin_<n>` => pesan
 */

use App\Libraries\Hasil\Klasemen;
use App\Libraries\Hasil\MetodeKlasemen;

$keteranganMetode = [
    Klasemen::MEDALI => 'Sesuai petunjuk teknis bagian 11: sekolah dengan juara I terbanyak menjadi juara umum; bila sama, dibandingkan juara II, lalu juara III.',
    Klasemen::POIN   => 'Setiap atlet atau regu di peringkat 1–' . Klasemen::PERINGKAT_POIN . ' babak Final menyumbang poin untuk sekolahnya. Sekolah dengan poin terbanyak menjadi juara umum; bila sama, dibandingkan juara I, lalu II, lalu III.',
];
$poinGalat = array_filter($galat, static fn (string $kunci): bool => str_starts_with($kunci, 'poin'), ARRAY_FILTER_USE_KEY);
?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?>Hasil<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Hasil<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid px-3 px-lg-4">
    <?= $this->include('admin/pengaturan/_navigasi') ?>

    <p class="teks-info mb-4">
        Pilih cara menentukan juara umum di klasemen sekolah. Klasemen tetap dihitung terpisah per jenjang sekolah,
        dan kolom poin tampil di klasemen apa pun metodenya. Perubahan langsung berlaku di klasemen panel, halaman Hasil, PDF, Excel, dan videotron.
    </p>

    <?= view('admin/_galat_isian', ['galat' => $galat], ['saveData' => false]) ?>

    <form action="<?= site_url('admin/pengaturan/hasil') ?>" method="post" novalidate>
        <?= csrf_field() ?>

        <div class="row g-4">
            <div class="col-12 col-xl-6">
                <section class="card h-100" aria-labelledby="judul-metode">
                    <div class="card-body p-3 p-md-4">
                        <h2 id="judul-metode" class="h5 mb-3">Metode klasemen juara umum</h2>
                        <fieldset<?= isset($galat['metode']) ? ' aria-describedby="metode-galat"' : '' ?>>
                            <legend class="visually-hidden">Metode klasemen juara umum</legend>
                            <?php foreach (Klasemen::METODE as $kode => $label) : ?>
                                <div class="form-check mb-3">
                                    <input class="form-check-input<?= isset($galat['metode']) ? ' is-invalid' : '' ?>" type="radio" name="metode" id="metode-<?= $kode ?>" value="<?= $kode ?>"
                                        aria-describedby="metode-<?= $kode ?>-bantuan"<?= $metode === $kode ? ' checked' : '' ?>>
                                    <label class="form-check-label fw-semibold" for="metode-<?= $kode ?>">
                                        <?= esc($label) ?><?= $kode === Klasemen::MEDALI ? ' (bawaan)' : '' ?>
                                    </label>
                                    <div id="metode-<?= $kode ?>-bantuan" class="form-text mt-0"><?= esc($keteranganMetode[$kode]) ?></div>
                                </div>
                            <?php endforeach ?>
                            <?php if (isset($galat['metode'])) : ?>
                                <div id="metode-galat" class="invalid-feedback d-block"><?= esc($galat['metode']) ?></div>
                            <?php endif ?>
                        </fieldset>
                        <p class="small text-body-secondary mb-0">
                            Bila masih sama, sekolah-sekolah itu berbagi peringkat yang sama. Peringkat 1–3 di setiap jenjang adalah juara umum I–III.
                        </p>
                    </div>
                </section>
            </div>

            <div class="col-12 col-xl-6">
                <section class="card h-100" aria-labelledby="judul-poin">
                    <div class="card-body p-3 p-md-4">
                        <h2 id="judul-poin" class="h5 mb-1">Poin per peringkat</h2>
                        <p class="small text-body-secondary mb-3" id="poin-bantuan">
                            Poin yang didapat sekolah untuk setiap atlet atau regu di peringkat babak Final. Bawaan: <?= esc(implode('-', array_slice(Klasemen::POIN_BAWAAN, 0, 6))) ?>, peringkat 7 dan 8 tanpa poin.
                            Angka bulat 0–<?= MetodeKlasemen::POIN_MAKSIMAL ?>, tidak boleh naik untuk peringkat berikutnya. Peringkat sama di Final mendapat poin yang sama.
                        </p>
                        <?php if (isset($galat['poin'])) : ?>
                            <div class="alert alert-danger py-2" id="poin-galat"><?= esc($galat['poin']) ?></div>
                        <?php endif ?>
                        <div class="row g-2 row-cols-2 row-cols-sm-4">
                            <?php foreach ($poin as $i => $nilai) : ?>
                                <?php $id = 'poin_' . ($i + 1); ?>
                                <div class="col">
                                    <label for="<?= $id ?>" class="form-label small mb-1">Peringkat <?= $i + 1 ?></label>
                                    <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="3" autocomplete="off"
                                        class="form-control<?= isset($galat[$id]) ? ' is-invalid' : '' ?>" id="<?= $id ?>" name="poin[]" value="<?= esc($nilai, 'attr') ?>"
                                        aria-describedby="poin-bantuan<?= isset($galat[$id]) ? ' ' . $id . '-galat' : '' ?>">
                                    <?php if (isset($galat[$id])) : ?>
                                        <div id="<?= $id ?>-galat" class="invalid-feedback"><?= esc($galat[$id]) ?></div>
                                    <?php endif ?>
                                </div>
                            <?php endforeach ?>
                        </div>
                        <?php if ($poinGalat === [] && $metode === Klasemen::POIN) : ?>
                            <p class="small mt-3 mb-0" data-ringkasan-poin>Berlaku sekarang: <?= esc(Klasemen::teksPoin(array_map('intval', $poin))) ?>.</p>
                        <?php endif ?>
                    </div>
                </section>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-4 mb-4">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg" aria-hidden="true"></i> Simpan metode klasemen</button>
            <a class="btn btn-outline-secondary" href="<?= site_url('admin/klasemen') ?>"><i class="bi bi-trophy" aria-hidden="true"></i> Lihat klasemen</a>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
