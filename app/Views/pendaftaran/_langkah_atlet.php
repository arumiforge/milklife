<?php
/**
 * Langkah 3: Atlet (rancangan 6.4–6.6, 7). Formulir atlet dan daftar
 * sementara diisi `aset/js/pendaftaran/atlet-*.js`.
 *
 * @var array<string, mixed> $wizard
 */

use App\Libraries\Pendaftaran\KelasTampilan as K;

$maksimalIndividu = (int) $wizard['aturan']['nomor_individu_maksimal'];
?>
<section class="langkah" id="langkah-3" data-langkah="3" aria-labelledby="judul-langkah-3" hidden>
    <?= view('pendaftaran/_kepala_langkah', ['langkah' => 3, 'judul' => 'Atlet'], ['saveData' => false]) ?>
    <p class="langkah-keterangan mb-5 text-gray-600">
        Kelompok umur ditentukan dari jenjang sekolah dan tahun lahir. Bila tahun lahir atlet masuk dua kelompok umur,
        Anda memilih salah satunya. Setiap atlet ikut satu kelompok umur, minimal satu nomor, dan paling banyak
        <?= $maksimalIndividu ?> nomor individu.
    </p>

    <div class="<?= K::RINGKASAN ?>" role="alert" hidden></div>

    <form class="<?= K::KARTU ?>" id="formulir-atlet" novalidate>
        <h3 class="mb-4 font-bold text-primary-900" id="formulir-atlet-judul">Tambah atlet</h3>
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="<?= K::LABEL ?>" for="atlet-nama">Nama lengkap</label>
                <input type="text" class="<?= K::ISIAN ?> huruf-besar uppercase" id="atlet-nama" maxlength="100" autocomplete="off">
                <div class="<?= K::UMPAN ?>"></div>
            </div>
            <div>
                <label class="<?= K::LABEL ?>" for="atlet-nisn">NISN</label>
                <input type="text" class="<?= K::ISIAN ?>" id="atlet-nisn" inputmode="numeric" maxlength="10" autocomplete="off" placeholder="10 angka">
                <div class="<?= K::UMPAN ?>"></div>
            </div>
            <div>
                <label class="<?= K::LABEL ?>" for="atlet-tanggal-lahir">Tanggal lahir</label>
                <input type="date" class="<?= K::ISIAN ?>" id="atlet-tanggal-lahir">
                <div class="<?= K::UMPAN ?>"></div>
            </div>
            <div>
                <fieldset id="atlet-jenis-kelamin">
                    <legend class="<?= K::LABEL ?>">Jenis kelamin</legend>
                    <div class="pilihan-tombol flex flex-wrap gap-2">
                        <label class="<?= K::PILIHAN ?>" for="atlet-jk-L"><input type="radio" class="sr-only" name="atlet-jk" id="atlet-jk-L" value="L">Laki-laki</label>
                        <label class="<?= K::PILIHAN ?>" for="atlet-jk-P"><input type="radio" class="sr-only" name="atlet-jk" id="atlet-jk-P" value="P">Perempuan</label>
                    </div>
                    <div class="<?= K::UMPAN ?>"></div>
                </fieldset>
            </div>
        </div>

        <fieldset id="atlet-pilih-ku" class="pilih-ku mt-4 rounded-lg border border-yellow-200 bg-yellow-50 px-3.5 py-3 [&.is-invalid]:border-red-500" hidden>
            <legend class="float-left mb-1 w-full font-bold text-primary-900">Pilih kelompok umur</legend>
            <p class="pilih-ku-keterangan clear-left mb-2 text-sm text-gray-700" id="atlet-pilih-ku-keterangan"></p>
            <div class="pilihan-tombol flex flex-wrap gap-2" id="atlet-pilih-ku-daftar"></div>
            <div class="<?= K::UMPAN ?>"></div>
        </fieldset>

        <div id="atlet-ku" class="info-ku mt-4 flex flex-wrap items-center gap-x-2 gap-y-1 rounded-lg bg-primary-100 px-3 py-2.5 font-semibold text-primary-900 [&.info-ku-galat]:bg-red-50 [&.info-ku-galat]:font-normal [&.info-ku-galat]:text-red-800" aria-live="polite" hidden></div>

        <div id="atlet-nomor" hidden>
            <fieldset class="mt-4" id="atlet-nomor-individu">
                <legend class="<?= K::LABEL ?>">Nomor individu <span class="font-normal text-gray-500">(paling banyak <?= $maksimalIndividu ?>)</span></legend>
                <div class="daftar-pilihan-nomor grid gap-2 sm:grid-cols-2"></div>
            </fieldset>
            <fieldset class="mt-4" id="atlet-nomor-beregu">
                <legend class="<?= K::LABEL ?>">Nomor beregu</legend>
                <div class="daftar-pilihan-nomor grid gap-2 sm:grid-cols-2"></div>
            </fieldset>
            <div class="<?= K::UMPAN ?>" id="atlet-nomor-galat"></div>
        </div>

        <div class="formulir-atlet-tombol mt-5 flex flex-wrap gap-2">
            <button type="submit" class="<?= K::UTAMA ?>" id="atlet-simpan"><?= ikon('plus', 'size-5') ?> <span>Tambahkan ke daftar</span></button>
            <button type="button" class="<?= K::KEDUA ?>" id="atlet-batal" hidden>Batal ubah</button>
        </div>
    </form>

    <div class="daftar-sementara mt-6" aria-live="polite">
        <h3 class="flex flex-wrap items-center gap-2 text-xl font-bold text-primary-900">Daftar sementara <span class="rounded-md bg-gray-200 px-2.5 py-0.5 text-sm font-semibold text-gray-800" id="jumlah-atlet">0 atlet</span></h3>
        <p class="mt-2 text-gray-500" id="daftar-kosong">Belum ada atlet. Isi formulir di atas lalu tekan "Tambahkan ke daftar".</p>

        <div id="bagian-individu" hidden>
            <h4 class="daftar-judul mt-5 mb-2 flex items-center gap-2 font-semibold text-primary-700"><?= ikon('user', 'size-5') ?> Individu</h4>
            <ul class="daftar-atlet space-y-2" id="daftar-individu"></ul>
        </div>

        <div id="bagian-beregu" hidden>
            <h4 class="daftar-judul mt-5 mb-2 flex items-center gap-2 font-semibold text-primary-700"><?= ikon('users', 'size-5') ?> Tim beregu</h4>
            <div id="daftar-beregu"></div>
        </div>
    </div>

    <div class="langkah-tombol mt-6 flex flex-wrap items-center justify-between gap-3">
        <button type="button" class="<?= K::KEDUA ?>" data-kembali><?= ikon('arrow-left', 'size-5') ?> Kembali</button>
        <button type="button" class="<?= K::UTAMA_BESAR ?>" data-lanjut>Lanjut <?= ikon('arrow-right', 'size-5') ?></button>
    </div>
</section>
