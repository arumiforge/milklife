<?php
/**
 * Formulir pengaturan pembayaran (`PembayaranController`).
 *
 * @var CodeIgniter\View\View  $this
 * @var array<string, string>  $isian                isian formulir (tersimpan atau yang baru diketik)
 * @var array<string, string>  $galat                nama kotak => pesan
 * @var string                 $logo                 jalur logo bank tersimpan yang berkasnya ada, atau ''
 * @var bool                   $logoPerluDipilihLagi logo baru sah tetapi belum tersimpan karena isian lain salah
 * @var array<string, string>  $penanda              penanda templat => keterangan
 * @var array<string, string>  $dataContoh           penanda => isi karangan untuk contoh
 * @var string                 $contohPesan          hasil templat dengan data karangan
 * @var string                 $templatAwal          templat pesan bawaan
 * @var string                 $nomorWaTersimpan     nomor WA tersimpan (bentuk baku) untuk tautan uji coba
 */

use App\Libraries\Unggahan;

$kotakTeks = static function (string $nama, string $label, string $bantuan, array $isian, array $galat, array $atribut = []): string {
    $salah   = isset($galat[$nama]);
    $jelaskan = trim(($bantuan !== '' ? $nama . '-bantuan ' : '') . ($salah ? $nama . '-galat' : ''));
    $tambahan = '';
    foreach ($atribut as $kunci => $nilai) {
        $tambahan .= ' ' . $kunci . '="' . esc($nilai, 'attr') . '"';
    }

    return '<div class="mb-3">'
        . '<label for="' . $nama . '" class="form-label">' . esc($label) . '</label>'
        . '<input type="text" class="form-control' . ($salah ? ' is-invalid' : '') . '" id="' . $nama . '" name="' . $nama . '"'
        . ' value="' . esc($isian[$nama] ?? '', 'attr') . '"' . ($jelaskan !== '' ? ' aria-describedby="' . $jelaskan . '"' : '') . $tambahan . '>'
        . ($salah ? '<div id="' . $nama . '-galat" class="invalid-feedback">' . esc($galat[$nama]) . '</div>' : '')
        . ($bantuan !== '' ? '<div id="' . $nama . '-bantuan" class="form-text">' . esc($bantuan) . '</div>' : '')
        . '</div>';
};
?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?>Pembayaran<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Pembayaran<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid px-3 px-lg-4">
    <?= $this->include('admin/pengaturan/_navigasi') ?>

    <p class="text-body-secondary mb-4">
        Rekening ini tampil di langkah terakhir pendaftaran. Official sekolah mentransfer ke rekening ini,
        lalu menekan tombol WA untuk mengirim pesan konfirmasi ke nomor di bawah.
    </p>

    <?= view('admin/_galat_isian', ['galat' => $galat], ['saveData' => false]) ?>

    <?php if ($logoPerluDipilihLagi) : ?>
        <div class="alert alert-warning" role="status">
            <i class="bi bi-info-circle-fill" aria-hidden="true"></i>
            Logo bank yang tadi dipilih belum tersimpan karena ada isian yang perlu diperbaiki. Pilih logonya lagi sebelum menyimpan.
        </div>
    <?php endif ?>

    <form action="<?= site_url('admin/pengaturan/pembayaran') ?>" method="post" enctype="multipart/form-data" novalidate>
        <?= csrf_field() ?>

        <div class="row g-4">
            <div class="col-12 col-xl-6">
                <section class="card h-100" aria-labelledby="judul-rekening">
                    <div class="card-body p-3 p-md-4">
                        <h2 id="judul-rekening" class="h5 mb-3">Rekening tujuan transfer</h2>

                        <?= $kotakTeks('bank', 'Nama bank', 'Contoh: BRI, BCA, atau Bank Jateng.', $isian, $galat, ['maxlength' => '50', 'autocomplete' => 'off']) ?>
                        <?= $kotakTeks('nomor_rekening', 'Nomor rekening', 'Boleh diketik dengan spasi atau tanda hubung; yang disimpan hanya angkanya.', $isian, $galat, ['inputmode' => 'numeric', 'autocomplete' => 'off']) ?>
                        <?= $kotakTeks('pemilik_rekening', 'Nama pemilik rekening', 'Tulis persis seperti di buku tabungan, supaya official yakin rekeningnya benar.', $isian, $galat, ['maxlength' => '100', 'autocomplete' => 'off']) ?>

                        <div class="mb-0">
                            <span class="form-label d-block" id="logo_bank-label">Logo bank</span>
                            <div class="mb-2" style="max-width: 16rem;" data-pratinjau="logo_bank">
                                <?php if ($logo !== '') : ?>
                                    <div class="ratio ratio-16x9 border rounded bg-white">
                                        <img src="<?= esc(base_url($logo), 'attr') ?>" alt="Logo bank saat ini" class="p-2" style="object-fit: contain;">
                                    </div>
                                <?php else : ?>
                                    <?= view('tata_letak/_tanpa_gambar', ['rasio' => '16x9', 'label' => 'Logo bank'], ['saveData' => false]) ?>
                                <?php endif ?>
                            </div>
                            <label for="logo_bank" class="form-label small mb-1"><?= $logo === '' ? 'Unggah logo' : 'Ganti logo' ?></label>
                            <input type="file" class="form-control<?= isset($galat['logo_bank']) ? ' is-invalid' : '' ?>" id="logo_bank" name="logo_bank"
                                accept="image/jpeg,image/png,image/webp" data-ukuran-maksimal="<?= Unggahan::BATAS_GAMBAR ?>"
                                aria-describedby="logo_bank-bantuan<?= isset($galat['logo_bank']) ? ' logo_bank-galat' : '' ?>">
                            <?php if (isset($galat['logo_bank'])) : ?>
                                <div id="logo_bank-galat" class="invalid-feedback"><?= esc($galat['logo_bank']) ?></div>
                            <?php endif ?>
                            <div id="logo_bank-bantuan" class="form-text">
                                JPG, PNG, atau WEBP, paling besar <?= Unggahan::ukuranTeks(Unggahan::BATAS_GAMBAR) ?>. Boleh dikosongkan.
                            </div>
                            <?php if ($logo !== '') : ?>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" value="1" id="hapus_logo_bank" name="hapus_logo_bank">
                                    <label class="form-check-label" for="hapus_logo_bank">Hapus logo ini</label>
                                </div>
                            <?php endif ?>
                        </div>
                    </div>
                </section>
            </div>

            <div class="col-12 col-xl-6">
                <section class="card h-100" aria-labelledby="judul-konfirmasi">
                    <div class="card-body p-3 p-md-4">
                        <h2 id="judul-konfirmasi" class="h5 mb-3">Konfirmasi lewat WA</h2>

                        <?= $kotakTeks('nomor_wa', 'Nomor WA pemilik rekening', 'Nomor yang menerima pesan konfirmasi dan bukti transfer, misalnya 0812-3456-7890.', $isian, $galat, ['inputmode' => 'tel', 'autocomplete' => 'off']) ?>

                        <div class="mb-3">
                            <div class="d-flex flex-wrap align-items-end justify-content-between gap-2 mb-2">
                                <label for="templat_pesan" class="form-label mb-0">Isi pesan konfirmasi</label>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="pakai-pesan-awal" data-pesan-awal="<?= esc($templatAwal, 'attr') ?>">
                                    <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Pakai pesan awal
                                </button>
                            </div>
                            <textarea class="form-control<?= isset($galat['templat_pesan']) ? ' is-invalid' : '' ?>" id="templat_pesan" name="templat_pesan" rows="10"
                                maxlength="2000" aria-describedby="templat_pesan-bantuan<?= isset($galat['templat_pesan']) ? ' templat_pesan-galat' : '' ?>"><?= esc($isian['templat_pesan']) ?></textarea>
                            <?php if (isset($galat['templat_pesan'])) : ?>
                                <div id="templat_pesan-galat" class="invalid-feedback"><?= esc($galat['templat_pesan']) ?></div>
                            <?php endif ?>
                            <div id="templat_pesan-bantuan" class="form-text">
                                Penanda di bawah diganti dengan data pendaftaran saat official menekan tombol WA. Tekan penanda untuk menambahkannya ke pesan.
                            </div>
                        </div>

                        <h3 class="h6 mb-2">Penanda yang bisa dipakai</h3>
                        <ul class="list-unstyled d-flex flex-wrap gap-2 mb-4" id="daftar-penanda">
                            <?php foreach ($penanda as $kode => $keterangan) : ?>
                                <li>
                                    <button type="button" class="btn btn-sm btn-light border text-start" data-penanda="<?= esc($kode, 'attr') ?>"
                                        title="Tambahkan <?= esc($kode, 'attr') ?> ke pesan">
                                        <code><?= esc($kode) ?></code>
                                        <span class="d-block small text-body-secondary"><?= esc($keterangan) ?></span>
                                    </button>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <h3 class="h6 mb-1">Contoh hasil pesan</h3>
                        <p class="small text-body-secondary mb-2">Dengan data karangan, supaya terlihat apa yang diterima pemilik rekening.</p>
                        <div class="border rounded bg-body-tertiary p-3 small" id="contoh-pesan" style="white-space: pre-wrap; overflow-wrap: anywhere;"
                            data-contoh="<?= esc(json_encode($dataContoh, JSON_UNESCAPED_UNICODE), 'attr') ?>" aria-live="polite"><?= esc($contohPesan) ?></div>

                        <?php if ($nomorWaTersimpan !== '' && $galat === []) : ?>
                            <p class="small mt-2 mb-0">
                                <a href="https://wa.me/<?= esc($nomorWaTersimpan, 'attr') ?>?text=<?= rawurlencode($contohPesan) ?>" target="_blank" rel="noopener">
                                    <i class="bi bi-whatsapp" aria-hidden="true"></i> Coba buka contoh pesan di WA
                                </a>
                                <span class="text-body-secondary">(memakai nomor dan pesan yang sudah tersimpan)</span>
                            </p>
                        <?php endif ?>
                    </div>
                </section>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-4 mb-2">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg" aria-hidden="true"></i> Simpan pengaturan pembayaran
            </button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<script>
(function () {
    'use strict';

    var kotak = document.getElementById('templat_pesan');
    var contoh = document.getElementById('contoh-pesan');
    var data = JSON.parse(contoh.getAttribute('data-contoh') || '{}');

    function perbaruiContoh() {
        var hasil = kotak.value;
        Object.keys(data).forEach(function (penanda) {
            hasil = hasil.split(penanda).join(data[penanda]);
        });
        contoh.textContent = hasil.trim();
    }

    kotak.addEventListener('input', perbaruiContoh);

    document.getElementById('pakai-pesan-awal').addEventListener('click', function () {
        kotak.value = this.getAttribute('data-pesan-awal');
        perbaruiContoh();
        kotak.focus();
    });

    // Penanda disisipkan di posisi kursor terakhir; bila kotak pesan belum
    // pernah disentuh, di akhir pesan.
    var disentuh = false;
    kotak.addEventListener('focus', function () { disentuh = true; });

    document.querySelectorAll('[data-penanda]').forEach(function (tombol) {
        tombol.addEventListener('click', function () {
            var penanda = tombol.getAttribute('data-penanda');
            var awal = disentuh ? kotak.selectionStart : kotak.value.length;
            var akhir = disentuh ? kotak.selectionEnd : kotak.value.length;
            kotak.value = kotak.value.slice(0, awal) + penanda + kotak.value.slice(akhir);
            kotak.focus();
            kotak.selectionStart = kotak.selectionEnd = awal + penanda.length;
            perbaruiContoh();
        });
    });

    // Pratinjau logo yang baru dipilih, sebelum disimpan.
    var berkas = document.getElementById('logo_bank');
    var wadah = document.querySelector('[data-pratinjau="logo_bank"]');
    berkas.addEventListener('change', function () {
        if (!berkas.files || !berkas.files[0] || !/^image\//.test(berkas.files[0].type)) {
            return;
        }
        var bingkai = document.createElement('div');
        bingkai.className = 'ratio ratio-16x9 border rounded bg-white';
        var gambar = document.createElement('img');
        gambar.src = URL.createObjectURL(berkas.files[0]);
        gambar.alt = 'Logo bank yang baru dipilih (belum disimpan)';
        gambar.className = 'p-2';
        gambar.style.objectFit = 'contain';
        bingkai.appendChild(gambar);
        wadah.replaceChildren(bingkai);
    });
}());
</script>
<?= $this->endSection() ?>
