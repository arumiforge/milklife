<?php
/**
 * Isi kolom Keadaan di tabel Atlet ditolak (K116, K117), dirender
 * `AtletDitolakController::data()` per baris dan dikirim sebagai HTML
 * (bentuk `html` di `tabel-admin.js`). Ditulis di view supaya kelasnya ikut
 * dibangun ke `tw.css` (F9-15). Penanda uji: `data-keadaan-ulang="belum|sudah"`.
 *
 * @var string|null $ulangId     ID pendaftaran baru bila atlet sudah didaftarkan ulang
 * @var string|null $ulangAlamat alamat rincian pendaftaran baru
 */

$sudah = ($ulangId ?? null) !== null;
?>
<?php if (! $sudah) : ?>
<span class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-semibold whitespace-nowrap text-yellow-800" data-keadaan-ulang="belum">Belum didaftarkan ulang</span>
<?php else : ?>
<span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold whitespace-nowrap text-green-800" data-keadaan-ulang="sudah">Sudah didaftarkan ulang</span><div class="mt-1 text-xs whitespace-nowrap"><a class="font-semibold text-primary-700 hover:underline" href="<?= esc((string) $ulangAlamat, 'attr') ?>"><?= esc((string) $ulangId) ?></a></div>
<?php endif ?>
