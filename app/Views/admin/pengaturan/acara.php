<?php
/**
 * Formulir Data Acara (`AcaraController`). Nama, seri, tahun, tempat, dan
 * tanggal acara yang disimpan di sini langsung dipakai tata letak dan halaman
 * depan. Tata letak `admin_tw`.
 *
 * @var CodeIgniter\View\View  $this
 * @var array<string, string>  $isian nilai kotak (tersimpan atau yang tadi diketik)
 * @var array<string, string>  $galat kotak => pesan; kosong bila tidak ada isian salah
 */

/**
 * Satu kotak isian lengkap: label, kotak, keterangan, dan pesan salahnya.
 *
 * @param array<string, string> $atribut atribut tambahan kotak
 */
$kotak = static function (string $nama, string $label, string $jenis, string $keterangan, array $atribut = []) use ($isian, $galat): string {
    $salah     = isset($galat[$nama]);
    $penjelas  = trim(($keterangan !== '' ? $nama . '-keterangan ' : '') . ($salah ? $nama . '-galat' : ''));
    $tambahan  = '';

    foreach ($atribut as $kunci => $nilai) {
        $tambahan .= ' ' . $kunci . '="' . esc($nilai, 'attr') . '"';
    }

    $kelasKotak = $salah
        ? 'border-red-500 bg-red-50 text-red-900 focus:border-red-500 focus:ring-red-100'
        : 'border-gray-300 bg-white text-gray-900 focus:border-primary-600 focus:ring-primary-100';

    return '<label for="' . $nama . '" class="mb-1.5 block text-sm font-semibold text-gray-900">' . esc($label) . '</label>'
        . '<input type="' . $jenis . '" class="block w-full rounded-lg border p-2.5 text-base focus:ring-4 sm:text-sm ' . $kelasKotak . '"'
        . ' id="' . $nama . '" name="' . $nama . '" value="' . esc($isian[$nama] ?? '', 'attr') . '"'
        . ($salah ? ' aria-invalid="true"' : '')
        . ($penjelas !== '' ? ' aria-describedby="' . $penjelas . '"' : '')
        . $tambahan . '>'
        . ($salah ? '<p id="' . $nama . '-galat" class="mt-1.5 text-sm font-medium text-red-700">' . esc($galat[$nama]) . '</p>' : '')
        . ($keterangan !== '' ? '<p id="' . $nama . '-keterangan" class="mt-1.5 text-sm text-gray-600">' . esc($keterangan) . '</p>' : '');
};
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?>Data acara<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Data acara<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-4 p-4 sm:px-6 sm:py-4">
    <?= $this->include('admin/pengaturan/_navigasi') ?>

    <p class="text-gray-700">
        Nama, seri, tahun, tempat, dan tanggal ini tampil di halaman depan, di bagian atas setiap halaman,
        dan di bukti pendaftaran. Semua kotak wajib diisi.
    </p>

    <div class="space-y-4">
        <?= view('admin/_galat_isian_tw', ['galat' => $galat], ['saveData' => false]) ?>

        <form action="<?= site_url('admin/pengaturan/acara') ?>" method="post" novalidate class="space-y-4">
            <?= csrf_field() ?>

            <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-5" aria-labelledby="judul-nama-acara">
                <h2 id="judul-nama-acara" class="mb-4 text-lg font-bold text-gray-900">Nama acara</h2>
                <div class="grid grid-cols-2 gap-4 md:grid-cols-3">
                    <div class="col-span-2 md:col-span-3">
                        <?= $kotak('nama', 'Nama acara', 'text', 'Kata pertama tampil sebagai merek, sisanya di sebelahnya. Contoh: MilkLife Athletics Challenge.', ['maxlength' => '100', 'autocomplete' => 'off']) ?>
                    </div>
                    <div>
                        <?= $kotak('seri', 'Seri', 'text', 'Angkanya saja, misalnya 2.', ['inputmode' => 'numeric', 'maxlength' => '2', 'autocomplete' => 'off']) ?>
                    </div>
                    <div>
                        <?= $kotak('tahun', 'Tahun', 'text', 'Empat angka, misalnya 2026.', ['inputmode' => 'numeric', 'maxlength' => '4', 'autocomplete' => 'off']) ?>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-5" aria-labelledby="judul-tempat-tanggal">
                <h2 id="judul-tempat-tanggal" class="mb-4 text-lg font-bold text-gray-900">Tempat dan tanggal</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <?= $kotak('tempat', 'Tempat', 'text', 'Nama tempat dan kotanya, misalnya Supersoccer Arena, Kudus.', ['maxlength' => '150']) ?>
                    </div>
                    <div>
                        <?= $kotak('tanggal_mulai', 'Tanggal mulai', 'date', 'Hari pertama acara.') ?>
                    </div>
                    <div>
                        <?= $kotak('tanggal_selesai', 'Tanggal selesai', 'date', 'Hari terakhir acara. Untuk acara sehari, pilih tanggal yang sama.') ?>
                    </div>
                </div>
            </section>

            <?= komponen('tombol', ['label' => 'Simpan data acara', 'tipe' => 'submit', 'ikon' => 'check']) ?>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/pilih-tanggal.js') ?>"></script>
<?= $this->endSection() ?>
