<?php
/**
 * Keterangan nomor yang tidak dilombakan atau peringatan batas peserta
 * (F11-03) di halaman Hasil dan Seri admin. Tidak menampilkan apa pun bila
 * nomor dilombakan dan pesertanya cukup.
 *
 * @var array<string, mixed> $nomor      `HasilLomba::nomor()`
 * @var string|null          $peringatan `StatusNomor::peringatan()`
 */
?>
<?php if (! ($nomor['dilombakan'] ?? true)) : ?>
    <div data-status-nomor="tidak-dilombakan">
        <?= komponen('peringatan', [
            'jenis' => 'peringatan',
            'judul' => 'Nomor ini tidak dilombakan',
            'isi'   => esc($nomor['keterangan']) . ' Nomor ini tidak tampil di jadwal, daftar seri, klasemen, dan videotron. Ubah statusnya di <a class="font-semibold underline" href="' . site_url('admin/pengaturan/nomor-lomba/' . (int) $nomor['id'] . '/ubah') . '">Pengaturan > Nomor lomba</a>.',
        ]) ?>
    </div>
<?php elseif (($peringatan ?? null) !== null) : ?>
    <div data-status-nomor="peserta-kurang">
        <?= komponen('peringatan', ['jenis' => 'peringatan', 'judul' => 'Peserta kurang dari batas', 'isi' => esc($peringatan)]) ?>
    </div>
<?php endif ?>
