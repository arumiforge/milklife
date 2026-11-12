<?php
/**
 * Halaman cek pendaftaran (`/cek-pendaftaran`, rancangan 8). Pengendali:
 * `App\Controllers\CekPendaftaranController`.
 *
 * Satu kartu per pendaftaran (`cek_pendaftaran/_kartu`), berurutan (K49).
 * Data sekolah tampil sekali di atas; total keseluruhan di bawah bila lebih
 * dari satu pendaftaran. Foto penanggung jawab tidak pernah ditampilkan.
 *
 * @var CodeIgniter\View\View    $this
 * @var string                   $namaAcara
 * @var bool                     $siap  pengaturan lengkap
 * @var string                   $kode  isian yang sudah dirapikan
 * @var string|null              $galat pesan untuk official
 * @var list<array<string, mixed>> $hasil lihat App\Libraries\DataPendaftaran
 */
$sekolah = $hasil[0] ?? null;
?>
<?= $this->extend('tata_letak/publik') ?>

<?= $this->section('judul') ?>Cek Pendaftaran – <?= esc($namaAcara) ?><?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="halaman-cek">
    <div class="halaman-cek-kepala">
        <div class="container">
            <h1><i class="bi bi-search" aria-hidden="true"></i> Cek Pendaftaran</h1>
            <p class="mb-0">Lihat data pendaftaran, biaya, dan status pembayaran sekolah Anda.</p>
        </div>
    </div>

    <div class="container halaman-cek-isi">
        <?php if (! $siap) : ?>
            <div class="cek-belum-siap" role="status">
                <span class="ikon-bulat mb-3"><i class="bi bi-hourglass-split" aria-hidden="true"></i></span>
                <h2 class="h4">Cek pendaftaran belum bisa dipakai</h2>
                <p>Panitia masih menyiapkan pengaturan pendaftaran. Silakan kembali lagi nanti.</p>
                <a class="btn btn-outline-primary" href="<?= site_url('/') ?>">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i> Kembali ke beranda
                </a>
            </div>
        <?php else : ?>
            <form class="cek-formulir" method="get" action="<?= site_url('cek-pendaftaran') ?>" role="search">
                <label class="form-label fw-semibold" for="kode">NPSN atau ID pendaftaran</label>
                <div class="input-group">
                    <input class="form-control form-control-lg<?= $galat !== null ? ' is-invalid' : '' ?>" type="text" id="kode" name="kode" value="<?= esc($kode) ?>" maxlength="30" autocomplete="off" required aria-describedby="kode-keterangan<?= $galat !== null ? ' kode-galat' : '' ?>">
                    <button class="btn btn-primary btn-lg" type="submit"><i class="bi bi-search" aria-hidden="true"></i> Cari</button>
                </div>
                <div class="form-text" id="kode-keterangan">NPSN menampilkan semua pendaftaran sekolah itu. ID pendaftaran (contoh MAC2A0000126) menampilkan satu pendaftaran.</div>
                <?php if ($galat !== null) : ?>
                    <div class="alert alert-warning mt-3 mb-0" id="kode-galat" role="alert"><?= esc($galat) ?></div>
                <?php endif ?>
            </form>

            <?php if ($sekolah !== null) : ?>
                <section class="cek-hasil" aria-labelledby="cek-sekolah">
                    <div class="cek-sekolah">
                        <h2 class="h3" id="cek-sekolah"><?= esc($sekolah['nama_sekolah']) ?></h2>
                        <dl class="cek-sekolah-data">
                            <div><dt>NPSN</dt><dd><?= esc($sekolah['npsn'] ?? 'Tanpa NPSN') ?></dd></div>
                            <div><dt>Jenjang</dt><dd><?= esc($sekolah['jenjang_label']) ?></dd></div>
                            <div><dt>Kabupaten / Kota</dt><dd><?= esc($sekolah['kabupaten']) ?></dd></div>
                            <div><dt>Jumlah pendaftaran</dt><dd><?= count($hasil) ?></dd></div>
                        </dl>
                    </div>

                    <?php foreach ($hasil as $nomor => $pendaftaran) : ?>
                        <?= view('cek_pendaftaran/_kartu', ['p' => $pendaftaran, 'urutan' => $nomor + 1, 'banyak' => count($hasil) > 1], ['saveData' => false]) ?>
                    <?php endforeach ?>

                    <?php if (count($hasil) > 1) : ?>
                        <div class="cek-total-semua">
                            <span>Total semua pendaftaran</span>
                            <strong><?= rupiah(array_sum(array_column($hasil, 'total'))) ?></strong>
                        </div>
                    <?php endif ?>
                </section>
            <?php endif ?>
        <?php endif ?>
    </div>
</div>
<?= $this->endSection() ?>
