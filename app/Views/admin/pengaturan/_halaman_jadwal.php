<?php
/**
 * Sakelar halaman Jadwal pertandingan publik (F9-07) di Pengaturan > Halaman Depan.
 *
 * Formulir sendiri, langsung tersimpan ke `HalamanDepan.tampil_jadwal` lewat
 * `Admin\Pengaturan\HalamanJadwalController`. Bawaan mati: `/jadwal` 404 dan
 * butir menu Jadwal tidak tampil sampai admin menyalakannya. Agenda di halaman
 * depan tidak terpengaruh. Tata letak `admin_tw`.
 */

use App\Controllers\JadwalController;

$tampilJadwal = JadwalController::tampil();
?>
<section id="halaman-jadwal" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-lembut sm:p-6" aria-labelledby="judul-halaman-jadwal">
    <h2 id="judul-halaman-jadwal" class="text-lg font-bold text-gray-900">Halaman Jadwal pertandingan</h2>
    <p class="mb-3 mt-1 text-sm text-gray-600">
        Jam mulai setiap babak per hari, dengan status Belum mulai, Berlangsung, atau Selesai dan tautan ke hasil.
        Isi jadwalnya di <a href="<?= site_url('admin/jadwal-pertandingan') ?>" class="font-semibold text-primary-700 underline">Jadwal pertandingan</a>.
        Agenda di halaman depan tetap tampil seperti biasa.
        <?php if ($tampilJadwal) : ?>
            Lihat di <a href="<?= site_url('jadwal') ?>" target="_blank" rel="noopener" class="font-semibold text-primary-700 underline">halaman Jadwal</a>.
        <?php endif ?>
    </p>
    <form action="<?= site_url('admin/pengaturan/halaman-depan/jadwal') ?>" method="post" class="space-y-3">
        <?= csrf_field() ?>
        <div>
            <label for="tampil_jadwal" class="inline-flex min-h-11 cursor-pointer items-center gap-3">
                <input type="checkbox" role="switch" id="tampil_jadwal" name="tampil_jadwal" value="1" class="peer sr-only"
                    aria-describedby="tampil_jadwal-bantuan"<?= $tampilJadwal ? ' checked' : '' ?>>
                <span class="relative h-6 w-11 shrink-0 rounded-full bg-gray-300 transition-colors after:absolute after:start-[2px] after:top-[2px] after:size-5 after:rounded-full after:bg-white after:shadow after:transition-transform peer-checked:bg-primary-700 peer-checked:after:translate-x-full peer-focus-visible:ring-4 peer-focus-visible:ring-primary-300 rtl:peer-checked:after:-translate-x-full" aria-hidden="true"></span>
                <span class="text-sm font-semibold text-gray-900">Tampilkan halaman Jadwal pertandingan</span>
            </label>
            <p id="tampil_jadwal-bantuan" class="text-sm text-gray-600">
                Bila dimatikan, alamat /jadwal tidak bisa dibuka dan menu Jadwal tidak tampil.
            </p>
        </div>
        <?= komponen('tombol', ['label' => 'Simpan sakelar Jadwal', 'tipe' => 'submit', 'varian' => 'kedua', 'ikon' => 'check']) ?>
    </form>
</section>
