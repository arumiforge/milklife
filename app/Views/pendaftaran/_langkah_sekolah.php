<?php
/**
 * Langkah 1: Data Sekolah (rancangan 6.2, 7). Diisi `pendaftaran.js`.
 *
 * @var array<string, mixed> $wizard
 */
?>
<section class="langkah" id="langkah-1" data-langkah="1" aria-labelledby="judul-langkah-1" hidden>
    <h2 id="judul-langkah-1" tabindex="-1">1. Data Sekolah</h2>
    <p class="langkah-keterangan">Jenjang sekolah menentukan kelompok umur yang boleh diikuti atlet.</p>

    <div class="ringkasan-galat alert alert-danger" role="alert" hidden></div>

    <div class="kartu-isian">
        <fieldset class="mb-3" data-jalur="sekolah.jenjang">
            <legend class="form-label">Jenjang sekolah</legend>
            <div class="pilihan-tombol">
                <?php foreach ($wizard['jenjang'] as $kode => $label) : ?>
                    <input type="radio" class="btn-check" name="sekolah-jenjang" id="sekolah-jenjang-<?= esc($kode, 'attr') ?>" value="<?= esc($kode, 'attr') ?>">
                    <label class="btn btn-outline-primary" for="sekolah-jenjang-<?= esc($kode, 'attr') ?>"><?= esc($label) ?></label>
                <?php endforeach ?>
            </div>
            <div class="invalid-feedback"></div>
        </fieldset>

        <div class="mb-3">
            <label class="form-label" for="sekolah-kabupaten">Kabupaten</label>
            <select class="form-select" id="sekolah-kabupaten" data-jalur="sekolah.kabupaten">
                <option value="">Pilih kabupaten</option>
                <?php foreach ($wizard['kabupaten'] as $kabupaten) : ?>
                    <option value="<?= esc($kabupaten, 'attr') ?>"><?= esc($kabupaten) ?></option>
                <?php endforeach ?>
            </select>
            <div class="invalid-feedback"></div>
        </div>

        <div class="mb-2">
            <label class="form-label" for="sekolah-npsn">NPSN</label>
            <div class="input-group has-validation">
                <input type="text" class="form-control" id="sekolah-npsn" inputmode="numeric" autocomplete="off" maxlength="8" placeholder="8 angka" data-jalur="sekolah.npsn" aria-describedby="sekolah-npsn-keterangan">
                <button type="button" class="btn btn-primary" id="sekolah-cari">
                    <i class="bi bi-search" aria-hidden="true"></i> Cari
                </button>
                <div class="invalid-feedback"></div>
            </div>
            <div id="sekolah-npsn-keterangan" class="form-text">Nama sekolah tampil sendiri setelah NPSN dicari.</div>
        </div>

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" id="sekolah-tanpa-npsn">
            <label class="form-check-label" for="sekolah-tanpa-npsn">Sekolah belum punya NPSN</label>
        </div>

        <div id="sekolah-hasil" aria-live="polite"></div>

        <?php // Usulan nama sekolah yang lebih lengkap (K99); ditampilkan pendaftaran.js. ?>
        <div class="mb-3" id="sekolah-usulan-kotak" hidden>
            <label class="form-label" for="sekolah-nama-usulan">Nama sekolah lengkap yang diusulkan</label>
            <input type="text" class="form-control" id="sekolah-nama-usulan" maxlength="150" autocomplete="off" data-jalur="sekolah.nama_usulan"
                placeholder="Contoh: MIS MUHAMMADIYAH JATI" aria-describedby="sekolah-nama-usulan-keterangan">
            <div class="invalid-feedback"></div>
            <div id="sekolah-nama-usulan-keterangan" class="form-text">
                Nama ini langsung dipakai untuk pendaftaran Anda dan dikirim ke panitia untuk diperiksa.
                Bila panitia tidak menyetujuinya, pendaftaran memakai nama dari data sekolah. Kosongkan bila nama di atas sudah benar.
            </div>
        </div>

        <div class="mb-3" id="sekolah-nama-kotak" hidden>
            <label class="form-label" for="sekolah-nama">Nama sekolah</label>
            <input type="text" class="form-control" id="sekolah-nama" maxlength="150" autocomplete="organization" data-jalur="sekolah.nama" placeholder="Contoh: SD Negeri 1 Kudus">
            <div class="invalid-feedback"></div>
        </div>
    </div>

    <div class="langkah-tombol">
        <span></span>
        <button type="button" class="btn btn-primary btn-lg" data-lanjut>Lanjut <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
    </div>
</section>
