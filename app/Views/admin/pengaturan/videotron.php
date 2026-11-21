<?php
/**
 * Pengaturan > Videotron (F9-13, `Admin\Pengaturan\VideotronController`):
 * sakelar papan `/layar`, isi yang diputar, dan lama tiap tampilan.
 *
 * @var CodeIgniter\View\View                               $this
 * @var array{tampil: bool, lama: string, isi: list<string>} $isian
 * @var array<string, string>                               $galat
 * @var array<string, string>                               $pilihanIsi kode => label (`PapanVideotron::ISI`)
 * @var bool                                                $adaJadwal  kolom jadwal babak (F9-07) sudah ada
 */
?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?>Pengaturan Videotron<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Videotron<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid px-3 px-lg-4">
    <?= $this->include('admin/pengaturan/_navigasi') ?>

    <p class="teks-info mb-4">
        Papan hasil untuk videotron di arena: hasil terbaru, juara umum, dan jadwal berganti sendiri, tanpa operator.
        Hanya hasil yang sudah diterbitkan yang tampil.
    </p>

    <div class="row">
        <div class="col-12 col-xl-9 col-xxl-7">
            <?= view('admin/_galat_isian', ['galat' => $galat], ['saveData' => false]) ?>

            <form action="<?= site_url('admin/pengaturan/videotron') ?>" method="post" novalidate>
                <?= csrf_field() ?>

                <div class="card mb-4">
                    <div class="card-body p-3 p-md-4">
                        <h2 class="h5 mb-1">Tampilkan papan</h2>
                        <div class="form-check form-switch my-3">
                            <input class="form-check-input" type="checkbox" role="switch" id="tampil" name="tampil" value="1"
                                aria-describedby="tampil-keterangan"<?= $isian['tampil'] ? ' checked' : '' ?>>
                            <label class="form-check-label fw-semibold" for="tampil">Tampilkan papan hasil di /layar</label>
                        </div>
                        <div id="tampil-keterangan" class="form-text">
                            Bila dimatikan, alamat /layar tidak bisa dibuka.
                            <?php if ($isian['tampil']) : ?>
                                Buka papan: <a href="<?= site_url('layar') ?>" target="_blank" rel="noopener"><?= esc(site_url('layar')) ?></a>, lalu tekan tombol layar penuh di pojok kanan atas (atau tombol F).
                            <?php endif ?>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body p-3 p-md-4">
                        <fieldset aria-describedby="isi-keterangan<?= isset($galat['isi']) ? ' isi-galat' : '' ?>">
                            <legend class="h5 mb-1">Isi yang tampil</legend>
                            <p id="isi-keterangan" class="text-body-secondary small mb-3">
                                Berganti sendiri dengan urutan ini. Isi yang belum punya data dilewati.
                            </p>
                            <?php foreach ($pilihanIsi as $kode => $label) : ?>
                                <div class="form-check mb-2">
                                    <input class="form-check-input<?= isset($galat['isi']) ? ' is-invalid' : '' ?>" type="checkbox" id="isi-<?= esc($kode, 'attr') ?>" name="isi[]" value="<?= esc($kode, 'attr') ?>"<?= in_array($kode, $isian['isi'], true) ? ' checked' : '' ?>>
                                    <label class="form-check-label" for="isi-<?= esc($kode, 'attr') ?>"><?= esc($label) ?></label>
                                    <?php if (! $adaJadwal && in_array($kode, ['jadwal', 'berlangsung'], true)) : ?>
                                        <span class="d-block small text-body-secondary">Tampil setelah jam mulai babak bisa diisi (fitur Jadwal pertandingan).</span>
                                    <?php endif ?>
                                </div>
                            <?php endforeach; ?>
                            <?php if (isset($galat['isi'])) : ?>
                                <div id="isi-galat" class="invalid-feedback d-block"><?= esc($galat['isi']) ?></div>
                            <?php endif ?>
                        </fieldset>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body p-3 p-md-4">
                        <h2 class="h5 mb-3">Lama tiap tampilan</h2>
                        <label for="lama" class="form-label fw-semibold">Lama (detik)</label>
                        <input type="number" class="form-control maks-w-10r<?= isset($galat['lama']) ? ' is-invalid' : '' ?>" id="lama" name="lama"
                            value="<?= esc($isian['lama'], 'attr') ?>" min="5" max="120" step="1" inputmode="numeric"
                            aria-describedby="lama-keterangan<?= isset($galat['lama']) ? ' lama-galat' : '' ?>">
                        <?php if (isset($galat['lama'])) : ?>
                            <div id="lama-galat" class="invalid-feedback"><?= esc($galat['lama']) ?></div>
                        <?php endif ?>
                        <div id="lama-keterangan" class="form-text">5 sampai 120 detik. Tabel yang panjang dibagi beberapa halaman; tiap halaman tampil selama ini.</div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg" aria-hidden="true"></i> Simpan pengaturan videotron
                </button>
            </form>

            <details class="card mt-4 mb-4">
                <summary class="card-body p-3 p-md-4 fw-semibold">Bila tulisan di videotron terlalu besar atau terlalu kecil</summary>
                <div class="card-body pt-0 px-3 px-md-4 small">
                    <p>Papan menyesuaikan sendiri dengan ukuran layar. Bila perlu, tambahkan pengaturan di belakang alamat:</p>
                    <ul class="mb-0">
                        <li><code>/layar?skala=1.3</code>: huruf 30% lebih besar (0.5 sampai 3).</li>
                        <li><code>/layar?baris=10</code>: 10 baris tabel per halaman.</li>
                        <li><code>/layar?susunan=lebar</code>: paksa susunan <code>lebar</code> (pita LED), <code>datar</code>, <code>tegak</code>, atau <code>kecil</code>.</li>
                        <li>Bisa digabung: <code>/layar?skala=1.2&amp;baris=8</code>.</li>
                    </ul>
                </div>
            </details>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
