<?php
/**
 * Langkah 3: Atlet (rancangan 6.4–6.6, 7). Formulir atlet dan daftar
 * sementara diisi `pendaftaran.js`.
 *
 * @var array<string, mixed> $wizard
 */

$maksimalIndividu = (int) $wizard['aturan']['nomor_individu_maksimal'];
?>
<section class="langkah" id="langkah-3" data-langkah="3" aria-labelledby="judul-langkah-3" hidden>
    <h2 id="judul-langkah-3" tabindex="-1">3. Atlet</h2>
    <p class="langkah-keterangan">
        Kelompok umur ditentukan dari tahun lahir. Setiap atlet ikut minimal satu nomor, dan paling banyak
        <?= $maksimalIndividu ?> nomor individu.
    </p>

    <div class="ringkasan-galat alert alert-danger" role="alert" hidden></div>

    <form class="kartu-isian" id="formulir-atlet" novalidate>
        <h3 class="h6" id="formulir-atlet-judul">Tambah atlet</h3>
        <div class="row g-3">
            <div class="col-12 col-md-6">
                <label class="form-label" for="atlet-nama">Nama lengkap</label>
                <input type="text" class="form-control huruf-besar" id="atlet-nama" maxlength="100" autocomplete="off">
                <div class="invalid-feedback"></div>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label" for="atlet-nisn">NISN</label>
                <input type="text" class="form-control" id="atlet-nisn" inputmode="numeric" maxlength="10" autocomplete="off" placeholder="10 angka">
                <div class="invalid-feedback"></div>
            </div>
            <div class="col-12 col-md-6">
                <label class="form-label" for="atlet-tanggal-lahir">Tanggal lahir</label>
                <input type="date" class="form-control" id="atlet-tanggal-lahir">
                <div class="invalid-feedback"></div>
            </div>
            <div class="col-12 col-md-6">
                <fieldset id="atlet-jenis-kelamin">
                    <legend class="form-label">Jenis kelamin</legend>
                    <div class="pilihan-tombol">
                        <input type="radio" class="btn-check" name="atlet-jk" id="atlet-jk-L" value="L">
                        <label class="btn btn-outline-primary" for="atlet-jk-L">Laki-laki</label>
                        <input type="radio" class="btn-check" name="atlet-jk" id="atlet-jk-P" value="P">
                        <label class="btn btn-outline-primary" for="atlet-jk-P">Perempuan</label>
                    </div>
                    <div class="invalid-feedback"></div>
                </fieldset>
            </div>
        </div>

        <div id="atlet-ku" class="info-ku" aria-live="polite" hidden></div>

        <div id="atlet-nomor" hidden>
            <fieldset class="mt-3" id="atlet-nomor-individu">
                <legend class="form-label">Nomor individu <span class="text-body-secondary fw-normal">(paling banyak <?= $maksimalIndividu ?>)</span></legend>
                <div class="daftar-pilihan-nomor"></div>
            </fieldset>
            <fieldset class="mt-3" id="atlet-nomor-beregu">
                <legend class="form-label">Nomor beregu</legend>
                <div class="daftar-pilihan-nomor"></div>
            </fieldset>
            <div class="invalid-feedback d-block" id="atlet-nomor-galat"></div>
        </div>

        <div class="formulir-atlet-tombol">
            <button type="submit" class="btn btn-primary" id="atlet-simpan"><i class="bi bi-plus-lg" aria-hidden="true"></i> <span>Tambahkan ke daftar</span></button>
            <button type="button" class="btn btn-outline-secondary" id="atlet-batal" hidden>Batal ubah</button>
        </div>
    </form>

    <div class="daftar-sementara" aria-live="polite">
        <h3 class="h5">Daftar sementara <span class="badge text-bg-secondary" id="jumlah-atlet">0 atlet</span></h3>
        <p class="text-body-secondary" id="daftar-kosong">Belum ada atlet. Isi formulir di atas lalu tekan "Tambahkan ke daftar".</p>

        <div id="bagian-individu" hidden>
            <h4 class="h6 daftar-judul"><i class="bi bi-person" aria-hidden="true"></i> Individu</h4>
            <ul class="daftar-atlet" id="daftar-individu"></ul>
        </div>

        <div id="bagian-beregu" hidden>
            <h4 class="h6 daftar-judul"><i class="bi bi-people" aria-hidden="true"></i> Tim beregu</h4>
            <div id="daftar-beregu"></div>
        </div>
    </div>

    <div class="langkah-tombol">
        <button type="button" class="btn btn-outline-secondary" data-kembali><i class="bi bi-arrow-left" aria-hidden="true"></i> Kembali</button>
        <button type="button" class="btn btn-primary btn-lg" data-lanjut>Lanjut <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
    </div>
</section>
