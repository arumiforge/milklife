<?php
/**
 * Formulir pengaturan Pendaftaran (`PendaftaranController`): jadwal buka dan
 * tutup, buka manual, tampilan usia, dan batas-batas pendaftaran. Waktu
 * dalam WIB.
 *
 * @var CodeIgniter\View\View  $this
 * @var array<string, string>  $isian             nilai kotak (tersimpan atau yang tadi diketik); waktu dalam bentuk `Y-m-d\TH:i`
 * @var array<string, string>  $galat             kotak => pesan; kosong bila tidak ada isian salah
 * @var array<string, string>  $pilihanUsia       nilai => label pilihan tampilan usia
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

    return '<label for="' . $nama . '" class="form-label fw-semibold">' . esc($label) . '</label>'
        . '<input type="' . $jenis . '" class="form-control' . ($salah ? ' is-invalid' : '') . '"'
        . ' id="' . $nama . '" name="' . $nama . '" value="' . esc($isian[$nama] ?? '', 'attr') . '"'
        . ($penjelas !== '' ? ' aria-describedby="' . $penjelas . '"' : '')
        . $tambahan . '>'
        . ($salah ? '<div id="' . $nama . '-galat" class="invalid-feedback">' . esc($galat[$nama]) . '</div>' : '')
        . ($keterangan !== '' ? '<div id="' . $nama . '-keterangan" class="form-text">' . esc($keterangan) . '</div>' : '');
};

$angka = ['min' => '1', 'step' => '1', 'inputmode' => 'numeric'];
?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?>Pengaturan Pendaftaran – Admin MilkLife<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Pendaftaran<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid px-3 px-lg-4">
    <?= $this->include('admin/pengaturan/_navigasi') ?>

    <div class="row">
        <div class="col-12 col-xl-9 col-xxl-7">
            <p class="text-body-secondary mb-4">
                Atur kapan sekolah bisa mendaftar dan batas-batas isian pendaftaran. Semua waktu dalam WIB.
            </p>

            <?= view('admin/_galat_isian', ['galat' => $galat], ['saveData' => false]) ?>

            <form action="<?= site_url('admin/pengaturan/pendaftaran') ?>" method="post" novalidate>
                <?= csrf_field() ?>

                <div class="card mb-4">
                    <div class="card-body p-3 p-md-4">
                        <h2 class="h5 mb-1">Jadwal pendaftaran</h2>
                        <p class="text-body-secondary small mb-3">
                            Pendaftaran terbuka dengan sendirinya di antara kedua waktu ini, asalkan semua pengaturan wajib sudah lengkap.
                        </p>
                        <div class="row g-3">
                            <div class="col-12 col-sm-6">
                                <?= $kotak('mulai', 'Waktu buka (WIB)', 'datetime-local', 'Contoh: 1 Oktober 2026 pukul 00.00.') ?>
                            </div>
                            <div class="col-12 col-sm-6">
                                <?= $kotak('akhir', 'Waktu tutup (WIB)', 'datetime-local', 'Contoh: 30 November 2026 pukul 23.59.') ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body p-3 p-md-4">
                        <h2 class="h5 mb-1">Buka manual</h2>
                        <p class="text-body-secondary small mb-3">
                            Pakai bila pendaftaran perlu dibuka di luar jadwal, misalnya diperpanjang beberapa hari.
                            Pendaftaran terbuka sampai batas di bawah, lalu tertutup lagi dengan sendirinya.
                        </p>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" role="switch" id="buka_manual" name="buka_manual" value="1"
                                aria-describedby="buka_manual-keterangan"<?= ($isian['buka_manual'] ?? '') === '1' ? ' checked' : '' ?>>
                            <label class="form-check-label fw-semibold" for="buka_manual">Buka pendaftaran sekarang</label>
                            <div id="buka_manual-keterangan" class="form-text">Hilangkan centang untuk kembali mengikuti jadwal di atas.</div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-sm-6">
                                <?= $kotak('buka_manual_sampai', 'Dibuka sampai (WIB)', 'datetime-local', 'Wajib diisi bila buka manual dicentang.') ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body p-3 p-md-4">
                        <h2 class="h5 mb-3">Batas pendaftaran</h2>
                        <div class="row g-3">
                            <div class="col-12 col-sm-6">
                                <?= $kotak('penanggung_jawab_minimal', 'Penanggung jawab paling sedikit', 'number', 'Per pendaftaran. Minimal 1.', $angka + ['max' => '20']) ?>
                            </div>
                            <div class="col-12 col-sm-6">
                                <?= $kotak('penanggung_jawab_maksimal', 'Penanggung jawab paling banyak', 'number', 'Per pendaftaran. Tidak kurang dari jumlah paling sedikit.', $angka + ['max' => '20']) ?>
                            </div>
                            <div class="col-12 col-sm-6">
                                <?= $kotak('nomor_individu_maksimal', 'Nomor individu paling banyak per atlet', 'number', 'Nomor beregu tidak dibatasi.', $angka + ['max' => '10']) ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body p-3 p-md-4">
                        <fieldset aria-describedby="tampilan_usia-keterangan<?= isset($galat['tampilan_usia']) ? ' tampilan_usia-galat' : '' ?>">
                            <legend class="h5 mb-1">Tampilan usia atlet</legend>
                            <p id="tampilan_usia-keterangan" class="text-body-secondary small mb-3">
                                Usia hanya ditampilkan sebagai keterangan dan dihitung pada tanggal mulai acara
                                (<?php if ($tanggalMulaiAcara !== '') : ?><?= esc($tanggalMulaiAcara) ?><?php else : ?>belum diisi di <a href="<?= site_url('admin/pengaturan/acara') ?>">Data Acara</a><?php endif ?>).
                                Kelompok umur atlet ditentukan dari tahun lahirnya, bukan dari usia ini.
                            </p>
                            <?php foreach ($pilihanUsia as $nilai => $label) : ?>
                                <div class="form-check">
                                    <input class="form-check-input<?= isset($galat['tampilan_usia']) ? ' is-invalid' : '' ?>" type="radio"
                                        name="tampilan_usia" id="tampilan_usia<?= $nilai === array_key_first($pilihanUsia) ? '' : '-' . esc($nilai, 'attr') ?>"
                                        value="<?= esc($nilai, 'attr') ?>"<?= ($isian['tampilan_usia'] ?? '') === $nilai ? ' checked' : '' ?>>
                                    <label class="form-check-label" for="tampilan_usia<?= $nilai === array_key_first($pilihanUsia) ? '' : '-' . esc($nilai, 'attr') ?>">
                                        <?= esc($label) ?>
                                        <span class="text-body-secondary">(contoh: <?= $nilai === 'tahun' ? '11 tahun' : '11 tahun 4 bulan' ?>)</span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                            <?php if (isset($galat['tampilan_usia'])) : ?>
                                <div id="tampilan_usia-galat" class="invalid-feedback d-block"><?= esc($galat['tampilan_usia']) ?></div>
                            <?php endif ?>
                        </fieldset>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg" aria-hidden="true"></i>
                    Simpan pengaturan pendaftaran
                </button>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
