<?php
/**
 * Sakelar halaman Peserta publik (F9-12) di Pengaturan > Halaman depan.
 *
 * Formulir sendiri (bukan bagian formulir isi halaman depan), langsung
 * tersimpan ke `HalamanDepan.tampil_peserta` lewat
 * `Admin\Pengaturan\HalamanPesertaController`. Bawaan mati: `/peserta` 404 dan
 * butir menu Peserta tidak tampil sampai admin menyalakannya. Tata letak
 * `admin_tw`.
 */

use App\Controllers\PesertaController;

$tampilPeserta = PesertaController::tampil();
?>
<section id="halaman-peserta" class="rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-5" aria-labelledby="judul-halaman-peserta">
    <h2 id="judul-halaman-peserta" class="text-lg font-bold text-gray-900">Halaman Peserta</h2>
    <p class="mb-3 mt-1 text-sm text-gray-600">
        Daftar atlet dari pendaftaran Lunas yang bisa dicari siapa saja: nama, sekolah, nomor atlet, dan nomor lomba.
        NISN, tanggal lahir, foto, dan data ofisial tidak pernah tampil.
        <?php if ($tampilPeserta) : ?>
            Lihat di <a href="<?= site_url('peserta') ?>" target="_blank" rel="noopener" class="font-semibold text-primary-700 underline">halaman Peserta</a>.
        <?php endif ?>
    </p>
    <form action="<?= site_url('admin/pengaturan/halaman-depan/peserta') ?>" method="post" class="space-y-3">
        <?= csrf_field() ?>
        <div>
            <label for="tampil_peserta" class="inline-flex min-h-11 cursor-pointer items-center gap-3">
                <input type="checkbox" role="switch" id="tampil_peserta" name="tampil_peserta" value="1" class="peer sr-only"
                    aria-describedby="tampil_peserta-bantuan"<?= $tampilPeserta ? ' checked' : '' ?>>
                <span class="relative h-6 w-11 shrink-0 rounded-full bg-gray-300 transition-colors after:absolute after:start-[2px] after:top-[2px] after:size-5 after:rounded-full after:bg-white after:shadow after:transition-transform peer-checked:bg-primary-700 peer-checked:after:translate-x-full peer-focus-visible:ring-4 peer-focus-visible:ring-primary-300 rtl:peer-checked:after:-translate-x-full" aria-hidden="true"></span>
                <span class="text-sm font-semibold text-gray-900">Tampilkan halaman Peserta</span>
            </label>
            <p id="tampil_peserta-bantuan" class="text-sm text-gray-600">
                Bila dimatikan, alamat /peserta tidak bisa dibuka dan menu Peserta tidak tampil.
            </p>
        </div>
        <?= komponen('tombol', ['label' => 'Simpan sakelar Peserta', 'tipe' => 'submit', 'varian' => 'kedua', 'ikon' => 'check']) ?>
    </form>
</section>
