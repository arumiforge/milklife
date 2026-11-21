<?php
/**
 * Sakelar halaman Jadwal pertandingan publik (F9-07) di Pengaturan > Halaman Depan.
 *
 * Formulir sendiri, langsung tersimpan ke `HalamanDepan.tampil_jadwal` lewat
 * `Admin\Pengaturan\HalamanJadwalController`. Bawaan mati: `/jadwal` 404 dan
 * butir menu Jadwal tidak tampil sampai admin menyalakannya. Agenda di halaman
 * depan tidak terpengaruh.
 */

use App\Controllers\JadwalController;

$tampilJadwal = JadwalController::tampil();
?>
<section id="halaman-jadwal" class="card mt-4 mb-2" aria-labelledby="judul-halaman-jadwal">
    <div class="card-body p-3 p-md-4">
        <h2 id="judul-halaman-jadwal" class="h5 mb-1">Halaman Jadwal pertandingan</h2>
        <p class="small text-body-secondary mb-3">
            Jam mulai setiap babak per hari, dengan status Belum mulai, Berlangsung, atau Selesai dan tautan ke hasil.
            Isi jadwalnya di <a href="<?= site_url('admin/jadwal-pertandingan') ?>">Jadwal pertandingan</a>.
            Agenda di halaman depan tetap tampil seperti biasa.
            <?php if ($tampilJadwal) : ?>
                Lihat di <a href="<?= site_url('jadwal') ?>" target="_blank" rel="noopener">halaman Jadwal</a>.
            <?php endif ?>
        </p>
        <form action="<?= site_url('admin/pengaturan/halaman-depan/jadwal') ?>" method="post">
            <?= csrf_field() ?>
            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" role="switch" id="tampil_jadwal" name="tampil_jadwal" value="1"
                    aria-describedby="tampil_jadwal-bantuan"<?= $tampilJadwal ? ' checked' : '' ?>>
                <label class="form-check-label fw-semibold" for="tampil_jadwal">Tampilkan halaman Jadwal pertandingan</label>
            </div>
            <div id="tampil_jadwal-bantuan" class="form-text mb-3">
                Bila dimatikan, alamat /jadwal tidak bisa dibuka dan menu Jadwal tidak tampil.
            </div>
            <button type="submit" class="btn btn-outline-primary">
                <i class="bi bi-check-lg" aria-hidden="true"></i> Simpan sakelar Jadwal
            </button>
        </form>
    </div>
</section>
