<?php
/**
 * Langkah 1: Data Sekolah (rancangan 6.2, 7). Diisi `aset/js/pendaftaran/sekolah.js`.
 *
 * @var array<string, mixed> $wizard
 */

use App\Libraries\Pendaftaran\KelasTampilan as K;
?>
<section class="langkah" id="langkah-1" data-langkah="1" aria-labelledby="judul-langkah-1" hidden>
    <?= view('pendaftaran/_kepala_langkah', ['langkah' => 1, 'judul' => 'Data sekolah'], ['saveData' => false]) ?>
    <p class="langkah-keterangan mb-5 text-gray-600">Jenjang sekolah menentukan kelompok umur yang boleh diikuti atlet.</p>

    <div class="<?= K::RINGKASAN ?>" role="alert" hidden></div>

    <div class="<?= K::KARTU ?> space-y-5">
        <fieldset data-jalur="sekolah.jenjang">
            <legend class="<?= K::LABEL ?> !text-base">Jenjang sekolah</legend>
            <?php // Ponsel: daftar pilihan (K160); layar sm ke atas: tombol pilihan. Keduanya disamakan `pendaftaran/sekolah.js`. ?>
            <select class="<?= K::ISIAN ?> sm:hidden [fieldset.is-invalid_&]:border-red-500 [fieldset.is-invalid_&]:bg-red-50" id="sekolah-jenjang-pilih" aria-label="Jenjang sekolah" data-jenjang-pilih>
                <option value="">Pilih jenjang sekolah</option>
                <?php foreach ($wizard['jenjang'] as $kode => $label) : ?>
                    <option value="<?= esc($kode, 'attr') ?>"><?= esc($label) ?></option>
                <?php endforeach ?>
            </select>
            <div class="pilihan-tombol hidden flex-wrap gap-2 sm:flex">
                <?php foreach ($wizard['jenjang'] as $kode => $label) : ?>
                    <label class="<?= K::PILIHAN ?>" for="sekolah-jenjang-<?= esc($kode, 'attr') ?>">
                        <input type="radio" class="sr-only" name="sekolah-jenjang" id="sekolah-jenjang-<?= esc($kode, 'attr') ?>" value="<?= esc($kode, 'attr') ?>"><?= esc($label) ?>
                    </label>
                <?php endforeach ?>
            </div>
            <div class="<?= K::UMPAN ?>"></div>
        </fieldset>

        <div>
            <label class="<?= K::LABEL ?>" for="sekolah-kabupaten">Kabupaten</label>
            <select class="<?= K::ISIAN ?>" id="sekolah-kabupaten" data-jalur="sekolah.kabupaten">
                <option value="">Pilih kabupaten</option>
                <?php foreach ($wizard['kabupaten'] as $kabupaten) : ?>
                    <option value="<?= esc($kabupaten, 'attr') ?>"><?= esc($kabupaten) ?></option>
                <?php endforeach ?>
            </select>
            <div class="<?= K::UMPAN ?>"></div>
        </div>

        <div>
            <label class="<?= K::LABEL ?>" for="sekolah-npsn">NPSN</label>
            <div class="input-group flex flex-wrap gap-2">
                <input type="text" class="<?= K::ISIAN ?> !w-auto min-w-0 flex-1" id="sekolah-npsn" inputmode="numeric" autocomplete="off" maxlength="8" placeholder="8 angka" data-jalur="sekolah.npsn" aria-describedby="sekolah-npsn-keterangan">
                <button type="button" class="<?= K::GARIS ?> min-h-12" id="sekolah-cari">
                    <?= ikon('search', 'size-5') ?> Cari
                </button>
                <div class="<?= K::UMPAN ?> basis-full !mt-0"></div>
            </div>
            <div id="sekolah-npsn-keterangan" class="<?= K::BANTU ?>">Nama sekolah muncul otomatis setelah Anda mencari NPSN.</div>
        </div>

        <div class="flex items-center gap-3">
            <input class="<?= K::CENTANG ?>" type="checkbox" id="sekolah-tanpa-npsn">
            <label class="text-gray-900" for="sekolah-tanpa-npsn">Sekolah belum punya NPSN</label>
        </div>

        <div id="sekolah-hasil" class="empty:hidden" aria-live="polite"></div>

        <?php // Usulan nama sekolah yang lebih lengkap (K99); ditampilkan `pendaftaran/sekolah.js`. ?>
        <div id="sekolah-usulan-kotak" hidden>
            <label class="<?= K::LABEL ?>" for="sekolah-nama-usulan">Nama sekolah lengkap yang diusulkan</label>
            <input type="text" class="<?= K::ISIAN ?>" id="sekolah-nama-usulan" maxlength="150" autocomplete="off" data-jalur="sekolah.nama_usulan"
                placeholder="Contoh: MIS MUHAMMADIYAH JATI" aria-describedby="sekolah-nama-usulan-keterangan">
            <div class="<?= K::UMPAN ?>"></div>
            <div id="sekolah-nama-usulan-keterangan" class="<?= K::BANTU ?>">
                Nama ini langsung dipakai di pendaftaran Anda, lalu panitia memeriksanya.
                Bila panitia tidak menyetujuinya, pendaftaran memakai nama dari data sekolah. Kosongkan bila nama di atas sudah benar.
            </div>
        </div>

        <div id="sekolah-nama-kotak" hidden>
            <label class="<?= K::LABEL ?>" for="sekolah-nama">Nama sekolah</label>
            <input type="text" class="<?= K::ISIAN ?>" id="sekolah-nama" maxlength="150" autocomplete="organization" data-jalur="sekolah.nama" placeholder="Contoh: SD Negeri 1 Kudus">
            <div class="<?= K::UMPAN ?>"></div>
        </div>
    </div>

    <div class="langkah-tombol mt-6 flex flex-wrap items-center justify-between gap-3">
        <span></span>
        <button type="button" class="<?= K::UTAMA_BESAR ?>" data-lanjut>Lanjut <?= ikon('arrow-right', 'size-5') ?></button>
    </div>
</section>
