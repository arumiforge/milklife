<?php
/**
 * Formulir Data Acara (`AcaraController`). Nama, seri, tahun, tempat, dan
 * tanggal acara yang disimpan di sini langsung dipakai tata letak dan halaman
 * depan.
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

    return '<label for="' . $nama . '" class="form-label fw-semibold">' . esc($label) . '</label>'
        . '<input type="' . $jenis . '" class="form-control' . ($salah ? ' is-invalid' : '') . '"'
        . ' id="' . $nama . '" name="' . $nama . '" value="' . esc($isian[$nama] ?? '', 'attr') . '"'
        . ($penjelas !== '' ? ' aria-describedby="' . $penjelas . '"' : '')
        . $tambahan . '>'
        . ($salah ? '<div id="' . $nama . '-galat" class="invalid-feedback">' . esc($galat[$nama]) . '</div>' : '')
        . ($keterangan !== '' ? '<div id="' . $nama . '-keterangan" class="form-text">' . esc($keterangan) . '</div>' : '');
};
?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?>Data Acara<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Data Acara<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid px-3 px-lg-4">
    <?= $this->include('admin/pengaturan/_navigasi') ?>

    <div class="row">
        <div class="col-12 col-xl-9 col-xxl-7">
            <p class="text-body-secondary mb-4">
                Nama, seri, tahun, tempat, dan tanggal ini tampil di halaman depan, di bagian atas setiap halaman,
                dan di bukti pendaftaran. Semua kotak wajib diisi.
            </p>

            <?= view('admin/_galat_isian', ['galat' => $galat], ['saveData' => false]) ?>

            <form action="<?= site_url('admin/pengaturan/acara') ?>" method="post" novalidate>
                <?= csrf_field() ?>

                <div class="card mb-4">
                    <div class="card-body p-3 p-md-4">
                        <h2 class="h5 mb-3">Nama acara</h2>
                        <div class="row g-3">
                            <div class="col-12">
                                <?= $kotak('nama', 'Nama acara', 'text', 'Kata pertama tampil sebagai merek, sisanya di sebelahnya. Contoh: MilkLife Athletics Challenge.', ['maxlength' => '100', 'autocomplete' => 'off']) ?>
                            </div>
                            <div class="col-6 col-md-4">
                                <?= $kotak('seri', 'Seri', 'text', 'Angkanya saja, misalnya 2.', ['inputmode' => 'numeric', 'maxlength' => '2', 'autocomplete' => 'off']) ?>
                            </div>
                            <div class="col-6 col-md-4">
                                <?= $kotak('tahun', 'Tahun', 'text', 'Empat angka, misalnya 2026.', ['inputmode' => 'numeric', 'maxlength' => '4', 'autocomplete' => 'off']) ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body p-3 p-md-4">
                        <h2 class="h5 mb-3">Tempat dan tanggal</h2>
                        <div class="row g-3">
                            <div class="col-12">
                                <?= $kotak('tempat', 'Tempat', 'text', 'Nama tempat dan kotanya, misalnya Supersoccer Arena, Kudus.', ['maxlength' => '150']) ?>
                            </div>
                            <div class="col-12 col-sm-6">
                                <?= $kotak('tanggal_mulai', 'Tanggal mulai', 'date', 'Hari pertama acara.') ?>
                            </div>
                            <div class="col-12 col-sm-6">
                                <?= $kotak('tanggal_selesai', 'Tanggal selesai', 'date', 'Hari terakhir acara. Untuk acara sehari, pilih tanggal yang sama.') ?>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg" aria-hidden="true"></i>
                    Simpan data acara
                </button>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
