<?php
/**
 * Seri dan lintasan di panel admin (F9-08), tata letak Tailwind `admin_tw`:
 * semua nomor per kelompok umur dengan keadaan seri tiap babak, sakelar
 * tampil untuk umum, dan unduhan daftar seri per hari. Data dari
 * `Admin\SeriController::index()`.
 *
 * @var CodeIgniter\View\View      $this
 * @var list<array<string, mixed>> $kelompokUmur `HasilLomba::daftarNomor()` + `babak_seri` (`SeriLintasan::daftarBabak()`)
 * @var bool                       $tampil       sakelar `HalamanDepan.tampil_seri`
 * @var list<string>               $hari         'Y-m-d' yang punya babak sudah dibagi
 */

use App\Libraries\Hasil\JadwalPertandingan;

// ikon-sprite: lock lock-open list-ordered circle-dashed
$keadaan = static function (array $b): array {
    if ($b['peserta'] === 0) {
        return ['Belum ada peserta', 'abu', 'circle-dashed'];
    }

    if ($b['dikunci']) {
        return ['Dikunci', 'hijau', 'lock'];
    }

    if ($b['dibagi'] === 0) {
        return ['Belum dibagi', 'kuning', 'circle-dashed'];
    }

    return [$b['dibagi'] < $b['peserta'] ? 'Sebagian dibagi' : 'Dibagi, belum dikunci', 'oranye', 'lock-open'];
};
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?>Seri dan lintasan<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-6 p-4 sm:p-6">
    <p class="max-w-3xl text-gray-700">
        Bagi peserta tiap babak ke seri dan lintasan untuk pertemuan teknik dan juri. Babak diatur di
        <a href="<?= site_url('admin/hasil') ?>" class="font-semibold text-primary-700 underline">Hasil lomba</a>.
        Setelah hasil babak yang sudah dibagi lengkap, babak berikutnya terisi sendiri dari yang lolos (Q/q).
    </p>

    <section id="sakelar-seri" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-lembut sm:p-6" aria-labelledby="judul-sakelar-seri">
        <h2 id="judul-sakelar-seri" class="text-lg font-bold text-gray-900">Tampil untuk umum</h2>
        <p class="mt-1 text-sm text-gray-600">
            <?php if ($tampil) : ?>
                Daftar seri yang sudah dikunci dan bagan alur babak tampil untuk umum lewat tautan Daftar seri di halaman Jadwal.
            <?php else : ?>
                Daftar seri dan bagan masih tersembunyi. Bagi, periksa, dan kunci daftarnya dulu, lalu nyalakan sakelar ini.
            <?php endif ?>
            Hanya babak yang dikunci yang tampil.
        </p>
        <form action="<?= site_url('admin/seri/tampil') ?>" method="post" class="mt-3 flex flex-wrap items-center gap-3">
            <?= csrf_field() ?>
            <?= komponen('sakelar', ['label' => 'Tampilkan daftar seri dan bagan', 'nama' => 'tampil_seri', 'centang' => $tampil]) ?>
            <?= komponen('tombol', ['label' => 'Simpan sakelar', 'tipe' => 'submit', 'varian' => 'kedua', 'ikon' => 'check']) ?>
        </form>
    </section>

    <?php if ($hari !== []) : ?>
        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-lembut sm:p-6" aria-labelledby="judul-unduh-hari">
            <h2 id="judul-unduh-hari" class="text-lg font-bold text-gray-900">Unduh daftar seri per hari</h2>
            <p class="mt-1 text-sm text-gray-600">Semua babak terjadwal di hari itu yang sudah dibagi, urut jam. Untuk pertemuan teknik dan juri.</p>
            <ul class="mt-3 space-y-2" data-unduh-hari-seri>
                <?php foreach ($hari as $h) : ?>
                    <li class="flex flex-wrap items-center gap-2">
                        <span class="min-w-0 flex-1 font-semibold text-gray-900"><?= esc(JadwalPertandingan::namaHari($h)) ?></span>
                        <?= komponen('tombol', ['label' => 'PDF', 'href' => site_url('admin/seri/hari/' . $h . '/pdf'), 'varian' => 'kedua', 'ukuran' => 'kecil', 'ikon' => 'download', 'atribut' => ['aria-label' => 'Unduh PDF daftar seri ' . JadwalPertandingan::namaHari($h)]]) ?>
                        <?= komponen('tombol', ['label' => 'Excel', 'href' => site_url('admin/seri/hari/' . $h . '/excel'), 'varian' => 'kedua', 'ukuran' => 'kecil', 'ikon' => 'sheet', 'atribut' => ['aria-label' => 'Unduh Excel daftar seri ' . JadwalPertandingan::namaHari($h)]]) ?>
                    </li>
                <?php endforeach ?>
            </ul>
        </section>
    <?php endif ?>

    <?php if ($kelompokUmur === []) : ?>
        <?= komponen('kosong', ['gambar' => 'gambar/admin/kosong-admin.webp', 'ikon' => 'list-ordered', 'judul' => 'Belum ada nomor lomba', 'teks' => 'Tambahkan nomor lomba di Pengaturan, lalu babaknya di Hasil lomba.']) ?>
    <?php endif ?>

    <?php foreach ($kelompokUmur as $ku) : ?>
        <section class="rounded-2xl border border-gray-200 bg-white shadow-lembut" aria-labelledby="judul-ku-<?= $ku['id'] ?>">
            <h2 id="judul-ku-<?= $ku['id'] ?>" class="border-b border-gray-200 px-5 py-4 text-lg font-bold text-gray-900 sm:px-6"><?= esc($ku['nama']) ?></h2>
            <ul class="divide-y divide-gray-100">
                <?php foreach ($ku['nomor'] as $n) : ?>
                    <li class="grid gap-2 px-5 py-4 sm:px-6 lg:grid-cols-12 lg:items-center" data-nomor-seri="<?= $n['id'] ?>">
                        <div class="min-w-0 lg:col-span-4">
                            <a href="<?= site_url('admin/seri/nomor/' . $n['id']) ?>" class="inline-flex min-h-11 items-center font-bold break-words text-primary-800 underline"><?= esc($n['nama']) ?></a>
                        </div>
                        <div class="flex flex-wrap gap-2 lg:col-span-8">
                            <?php if ($n['babak_seri'] === []) : ?>
                                <span class="text-sm text-gray-600">Belum ada babak. <a href="<?= site_url('admin/hasil/nomor/' . $n['id']) ?>" class="font-semibold text-primary-700 underline">Tambah babak</a></span>
                            <?php endif ?>
                            <?php foreach ($n['babak_seri'] as $b) : ?>
                                <?php [$label, $warna, $ikon] = $keadaan($b); ?>
                                <a href="<?= site_url('admin/seri/babak/' . $b['id']) ?>" class="inline-flex min-h-11 items-center gap-2 rounded-lg border border-gray-200 px-3 text-sm hover:bg-gray-50" data-babak-seri="<?= $b['id'] ?>">
                                    <span class="font-semibold text-gray-900"><?= esc($b['nama']) ?></span>
                                    <?= komponen('lencana', ['label' => $label, 'warna' => $warna, 'ikon' => $ikon]) ?>
                                </a>
                            <?php endforeach ?>
                        </div>
                    </li>
                <?php endforeach ?>
            </ul>
        </section>
    <?php endforeach ?>
</div>
<?= $this->endSection() ?>
