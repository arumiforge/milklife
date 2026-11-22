<?php
/**
 * Formulir pengaturan Pendaftaran (`PendaftaranController`): jadwal buka dan
 * tutup, buka manual, tampilan usia, dan batas-batas pendaftaran. Waktu
 * dalam WIB. Tata letak `admin_tw`; isian waktu memakai `datetime-local`
 * (nilai `Y-m-d\TH:i`) yang ditampilkan `pilih-tanggal.js` sebagai pilihan
 * Tanggal, Bulan, Tahun, Jam, dan Menit (K89).
 *
 * @var CodeIgniter\View\View  $this
 * @var array<string, string>  $isian             nilai kotak (tersimpan atau yang tadi diketik); waktu dalam bentuk `Y-m-d\TH:i`
 * @var array<string, string>  $galat             kotak => pesan; kosong bila tidak ada isian salah
 * @var array<string, string>  $pilihanUsia       nilai => label pilihan tampilan usia
 * @var array<string, string>  $pilihanRegu       mode nama regu (`Regu::MODE`)
 * @var string                 $tanggalMulaiAcara tanggal mulai acara dalam bahasa Indonesia, `''` bila belum diisi
 */

/**
 * Satu kotak isian lengkap: label, kotak, keterangan, dan pesan salahnya.
 *
 * @param array<string, string> $atribut atribut tambahan kotak
 */
$kotak = static function (string $nama, string $label, string $jenis, string $keterangan, array $atribut = []) use ($isian, $galat): string {
    $salah    = isset($galat[$nama]);
    $penjelas = trim(($keterangan !== '' ? $nama . '-keterangan ' : '') . ($salah ? $nama . '-galat' : ''));
    $tambahan = '';

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

$kartu = 'rounded-2xl border border-gray-200 bg-white p-5 shadow-lembut sm:p-6';
$radio = 'size-4 border-gray-300 text-primary-700 focus:ring-primary-300';
$angka = ['min' => '1', 'step' => '1', 'inputmode' => 'numeric'];
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?>Pengaturan Pendaftaran<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Pendaftaran<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-6 p-4 sm:p-6">
    <?= $this->include('admin/pengaturan/_navigasi') ?>

    <p class="text-gray-700">
        Atur kapan sekolah bisa mendaftar dan batas-batas isian pendaftaran. Semua waktu dalam WIB.
    </p>

    <div class="space-y-6">
        <?= view('admin/_galat_isian_tw', ['galat' => $galat], ['saveData' => false]) ?>

        <form action="<?= site_url('admin/pengaturan/pendaftaran') ?>" method="post" novalidate class="space-y-6">
            <?= csrf_field() ?>

            <section class="<?= $kartu ?>" aria-labelledby="judul-jadwal-pendaftaran">
                <h2 id="judul-jadwal-pendaftaran" class="text-lg font-bold text-gray-900">Jadwal pendaftaran</h2>
                <p class="mb-4 mt-1 text-sm text-gray-600">
                    Pendaftaran terbuka dengan sendirinya di antara kedua waktu ini, asalkan semua pengaturan wajib sudah lengkap.
                </p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <?= $kotak('mulai', 'Waktu buka (WIB)', 'datetime-local', 'Contoh: 1 Oktober 2026 pukul 00.00.') ?>
                    </div>
                    <div>
                        <?= $kotak('akhir', 'Waktu tutup (WIB)', 'datetime-local', 'Contoh: 30 November 2026 pukul 23.59.') ?>
                    </div>
                </div>
            </section>

            <section class="<?= $kartu ?>" aria-labelledby="judul-buka-manual">
                <h2 id="judul-buka-manual" class="text-lg font-bold text-gray-900">Buka manual</h2>
                <p class="mb-4 mt-1 text-sm text-gray-600">
                    Pakai bila pendaftaran perlu dibuka di luar jadwal, misalnya diperpanjang beberapa hari.
                    Pendaftaran terbuka sampai batas di bawah, lalu tertutup lagi dengan sendirinya.
                </p>
                <div class="mb-4">
                    <label for="buka_manual" class="inline-flex min-h-11 cursor-pointer items-center gap-3">
                        <input type="checkbox" role="switch" id="buka_manual" name="buka_manual" value="1" class="peer sr-only"
                            aria-describedby="buka_manual-keterangan"<?= ($isian['buka_manual'] ?? '') === '1' ? ' checked' : '' ?>>
                        <span class="relative h-6 w-11 shrink-0 rounded-full bg-gray-300 transition-colors after:absolute after:start-[2px] after:top-[2px] after:size-5 after:rounded-full after:bg-white after:shadow after:transition-transform peer-checked:bg-primary-700 peer-checked:after:translate-x-full peer-focus-visible:ring-4 peer-focus-visible:ring-primary-300 rtl:peer-checked:after:-translate-x-full" aria-hidden="true"></span>
                        <span class="text-sm font-semibold text-gray-900">Buka pendaftaran sekarang</span>
                    </label>
                    <p id="buka_manual-keterangan" class="text-sm text-gray-600">Hilangkan centang untuk kembali mengikuti jadwal di atas.</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <?= $kotak('buka_manual_sampai', 'Dibuka sampai (WIB)', 'datetime-local', 'Wajib diisi bila buka manual dicentang.') ?>
                    </div>
                </div>
            </section>

            <section class="<?= $kartu ?>" aria-labelledby="judul-batas-pendaftaran">
                <h2 id="judul-batas-pendaftaran" class="mb-4 text-lg font-bold text-gray-900">Batas pendaftaran</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <?= $kotak('penanggung_jawab_minimal', 'Ofisial paling sedikit', 'number', 'Per pendaftaran. Minimal 1.', $angka + ['max' => '20']) ?>
                    </div>
                    <div>
                        <?= $kotak('penanggung_jawab_maksimal', 'Ofisial paling banyak', 'number', 'Per pendaftaran. Tidak kurang dari jumlah paling sedikit.', $angka + ['max' => '20']) ?>
                    </div>
                    <div>
                        <?= $kotak('nomor_individu_maksimal', 'Nomor individu paling banyak per atlet', 'number', 'Nomor beregu tidak dibatasi.', $angka + ['max' => '10']) ?>
                    </div>
                    <div>
                        <?= $kotak('atlet_per_nomor_maksimal', 'Atlet per sekolah pada satu nomor individu, paling banyak', 'number', 'Dihitung bersama pendaftaran sebelumnya dari NPSN yang sama. Kosongkan bila tidak dibatasi.', $angka + ['max' => '100']) ?>
                    </div>
                </div>
            </section>

            <section class="<?= $kartu ?>">
                <fieldset aria-describedby="nama_regu-keterangan<?= isset($galat['nama_regu']) ? ' nama_regu-galat' : '' ?>">
                    <legend class="text-lg font-bold text-gray-900">Nama regu beregu</legend>
                    <p id="nama_regu-keterangan" class="mb-3 mt-1 text-sm text-gray-600">
                        Satu sekolah boleh mendaftarkan beberapa regu di satu nomor beregu. Pilih cara ofisial memberi nama regunya.
                        Aturan inti dan cadangan tetap berlaku, dan batas regu tiap nomor diatur di <a href="<?= site_url('admin/pengaturan/nomor-lomba') ?>" class="font-semibold text-primary-700 underline">Nomor Lomba</a>.
                    </p>
                    <?php $keteranganRegu = [
                        'ketentuan' => 'Ofisial memilih dari daftar nama regu di bawah, atau huruf A, B, C, … bila daftarnya kosong. Banyaknya nama di daftar juga membatasi regu per sekolah.',
                        'bebas'     => 'Ofisial menulis nama regu sendiri, misalnya "Merah" atau "Putra 1".',
                    ] ?>
                    <div class="space-y-1">
                        <?php foreach ($pilihanRegu as $nilai => $label) : ?>
                            <label class="flex min-h-11 items-start gap-3 py-1" for="nama_regu-<?= esc($nilai, 'attr') ?>">
                                <input class="mt-1 shrink-0 <?= $radio ?>" type="radio"
                                    name="nama_regu" id="nama_regu-<?= esc($nilai, 'attr') ?>"
                                    value="<?= esc($nilai, 'attr') ?>"<?= isset($galat['nama_regu']) ? ' aria-invalid="true"' : '' ?><?= ($isian['nama_regu'] ?? '') === $nilai ? ' checked' : '' ?>>
                                <span class="min-w-0">
                                    <span class="block text-sm font-semibold text-gray-900"><?= esc($label) ?></span>
                                    <span class="block text-sm text-gray-600"><?= esc($keteranganRegu[$nilai] ?? '') ?></span>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <?php if (isset($galat['nama_regu'])) : ?>
                        <p id="nama_regu-galat" class="mt-1.5 text-sm font-medium text-red-700"><?= esc($galat['nama_regu']) ?></p>
                    <?php endif ?>
                </fieldset>
                <div class="mt-4 grid gap-4 sm:grid-cols-3">
                    <div>
                        <?= $kotak('awalan_regu', 'Awalan nama regu', 'text', 'Kata di depan nama regu: "Regu" tampil "Regu A", "Tim" tampil "Tim A".', ['maxlength' => '15', 'required' => 'required']) ?>
                    </div>
                    <div class="sm:col-span-2">
                        <?= $kotak('label_regu', 'Daftar nama regu', 'text', 'Dipisah koma, misalnya A, B, C atau Putra 1, Putra 2; awalan tidak perlu ditulis. Kosongkan supaya ofisial memilih huruf A, B, C, … tanpa batas. Dipakai pada pilihan "Ketentuan admin".', ['maxlength' => '400', 'placeholder' => 'Kosong: A, B, C, … tanpa batas']) ?>
                    </div>
                </div>
            </section>

            <section class="<?= $kartu ?>">
                <fieldset aria-describedby="tampilan_usia-keterangan<?= isset($galat['tampilan_usia']) ? ' tampilan_usia-galat' : '' ?>">
                    <legend class="text-lg font-bold text-gray-900">Tampilan usia atlet</legend>
                    <p id="tampilan_usia-keterangan" class="mb-3 mt-1 text-sm text-gray-600">
                        Usia hanya ditampilkan sebagai keterangan dan dihitung pada tanggal mulai acara
                        (<?php if ($tanggalMulaiAcara !== '') : ?><?= esc($tanggalMulaiAcara) ?><?php else : ?>belum diisi di <a href="<?= site_url('admin/pengaturan/acara') ?>" class="font-semibold text-primary-700 underline">Data Acara</a><?php endif ?>).
                        Kelompok umur atlet ditentukan dari tahun lahirnya, bukan dari usia ini.
                    </p>
                    <div class="space-y-1">
                        <?php foreach ($pilihanUsia as $nilai => $label) : ?>
                            <?php $idUsia = 'tampilan_usia' . ($nilai === array_key_first($pilihanUsia) ? '' : '-' . esc($nilai, 'attr')); ?>
                            <label class="flex min-h-11 items-center gap-3" for="<?= $idUsia ?>">
                                <input class="shrink-0 <?= $radio ?>" type="radio"
                                    name="tampilan_usia" id="<?= $idUsia ?>"
                                    value="<?= esc($nilai, 'attr') ?>"<?= isset($galat['tampilan_usia']) ? ' aria-invalid="true"' : '' ?><?= ($isian['tampilan_usia'] ?? '') === $nilai ? ' checked' : '' ?>>
                                <span class="text-sm text-gray-900">
                                    <?= esc($label) ?>
                                    <span class="text-gray-600">(contoh: <?= $nilai === 'tahun' ? '11 tahun' : '11 tahun 4 bulan' ?>)</span>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <?php if (isset($galat['tampilan_usia'])) : ?>
                        <p id="tampilan_usia-galat" class="mt-1.5 text-sm font-medium text-red-700"><?= esc($galat['tampilan_usia']) ?></p>
                    <?php endif ?>
                </fieldset>
            </section>

            <?= komponen('tombol', ['label' => 'Simpan pengaturan pendaftaran', 'tipe' => 'submit', 'ikon' => 'check']) ?>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/pilih-tanggal.js') ?>"></script>
<?= $this->endSection() ?>
