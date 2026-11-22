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
            <label class="<?= K::LABEL ?>" for="sekolah-kabupaten" id="sekolah-kabupaten-label">Kabupaten</label>
            <?php
            // Ponsel: pemilih dengan kotak cari (K161), diisi dan disamakan `pendaftaran/sekolah.js`
            // dengan `<select>` di bawahnya, yang tetap menjadi sumber nilai. Tanpa JS, `<select>` yang tampil.
            ?>
            <div class="relative hidden" data-kabupaten-cari>
                <button type="button" class="<?= K::ISIAN ?> flex items-center justify-between gap-2 text-start disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-600" id="sekolah-kabupaten-tombol" aria-expanded="false" aria-controls="sekolah-kabupaten-panel" aria-labelledby="sekolah-kabupaten-label sekolah-kabupaten-tombol">
                    <span class="truncate" data-kabupaten-teks>Pilih kabupaten</span><?= ikon('chevron-down', 'size-5 shrink-0 text-gray-500') ?>
                </button>
                <div class="mt-2 rounded-xl border border-gray-200 bg-white p-2 shadow-angkat" id="sekolah-kabupaten-panel" hidden>
                    <div class="relative">
                        <?= ikon('search', 'pointer-events-none absolute start-3 top-1/2 size-5 -translate-y-1/2 text-gray-500') ?>
                        <input type="search" class="<?= K::ISIAN ?> ps-10" id="sekolah-kabupaten-saring" autocomplete="off" placeholder="Contoh: Kudus" aria-label="Cari kabupaten" aria-controls="sekolah-kabupaten-daftar">
                    </div>
                    <ul class="mt-2 max-h-64 overflow-y-auto overscroll-contain" id="sekolah-kabupaten-daftar" aria-label="Daftar kabupaten"></ul>
                    <p class="px-3 py-2 text-sm text-gray-600" data-kabupaten-kosong hidden>Kabupaten tidak ditemukan. Coba ketik nama lain.</p>
                </div>
            </div>
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
        <div id="sekolah-usulan-kotak" class="rounded-xl border border-gray-200 bg-gray-50 p-4" hidden>
            <div class="mb-2 flex items-center justify-between gap-3">
                <label class="<?= K::LABEL ?> !mb-0 flex items-center gap-2" for="sekolah-nama-usulan"><?= ikon('pencil', 'size-4 text-primary-700') ?>Usulan nama lengkap sekolah</label>
                <button type="button" class="<?= K::TAUTAN ?> !min-h-9 text-sm" id="sekolah-usulan-batal" hidden><?= ikon('x', 'size-4') ?>Batal</button>
            </div>
            <input type="text" class="<?= K::ISIAN ?>" id="sekolah-nama-usulan" maxlength="150" autocomplete="off" data-jalur="sekolah.nama_usulan"
                placeholder="Contoh: MIS MUHAMMADIYAH JATI" aria-describedby="sekolah-nama-usulan-keterangan">
            <div class="<?= K::UMPAN ?>"></div>
            <ul id="sekolah-nama-usulan-keterangan" class="mt-2 space-y-1 text-sm text-gray-600">
                <li class="flex gap-2"><?= ikon('check', 'mt-0.5 size-4 shrink-0 text-green-700') ?>Nama ini langsung dipakai di pendaftaran Anda.</li>
                <li class="flex gap-2"><?= ikon('info', 'mt-0.5 size-4 shrink-0 text-primary-700') ?>Panitia memeriksanya. Bila tidak disetujui, pendaftaran memakai nama dari data sekolah.</li>
            </ul>
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
