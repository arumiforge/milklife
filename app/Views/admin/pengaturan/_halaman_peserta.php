<?php
/**
 * Sakelar halaman Peserta publik (F9-12) di Pengaturan > Halaman Depan.
 *
 * Formulir sendiri (bukan bagian formulir isi halaman depan), langsung
 * tersimpan ke `HalamanDepan.tampil_peserta` lewat
 * `Admin\Pengaturan\HalamanPesertaController`. Bawaan mati: `/peserta` 404 dan
 * butir menu Peserta tidak tampil sampai admin menyalakannya.
 */

use App\Controllers\PesertaController;

$tampilPeserta = PesertaController::tampil();
?>
<section id="halaman-peserta" class="card mt-4 mb-2" aria-labelledby="judul-halaman-peserta">
    <div class="card-body p-3 p-md-4">
        <h2 id="judul-halaman-peserta" class="h5 mb-1">Halaman Peserta</h2>
        <p class="small text-body-secondary mb-3">
            Daftar atlet dari pendaftaran Lunas yang bisa dicari siapa saja: nama, sekolah, nomor atlet, dan nomor lomba.
            NISN, tanggal lahir, foto, dan data official tidak pernah tampil.
            <?php if ($tampilPeserta) : ?>
                Lihat di <a href="<?= site_url('peserta') ?>" target="_blank" rel="noopener">halaman Peserta</a>.
            <?php endif ?>
        </p>
        <form action="<?= site_url('admin/pengaturan/halaman-depan/peserta') ?>" method="post">
            <?= csrf_field() ?>
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" role="switch" id="tampil_peserta" name="tampil_peserta" value="1"
                    aria-describedby="tampil_peserta-bantuan"<?= $tampilPeserta ? ' checked' : '' ?>>
                <label class="form-check-label fw-semibold" for="tampil_peserta">Tampilkan halaman Peserta</label>
            </div>
            <div id="tampil_peserta-bantuan" class="form-text mb-3">
                Bila dimatikan, alamat /peserta tidak bisa dibuka dan menu Peserta tidak tampil.
            </div>
            <button type="submit" class="btn btn-outline-primary">
                <i class="bi bi-check-lg" aria-hidden="true"></i> Simpan sakelar Peserta
            </button>
        </form>
    </div>
</section>
