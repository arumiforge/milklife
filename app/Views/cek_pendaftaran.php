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
 * @var string|null              $info  keterangan ID lama yang sudah digabung (K109)
 * @var string|null              $waTanya alamat WA tanya admin, untuk atlet yang ditolak (K116)
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
                <?php if (($info ?? null) !== null) : ?>
                    <div class="alert alert-info mt-3 mb-0" id="kode-info" role="status"><?= esc($info) ?></div>
                <?php endif ?>
            </form>

            <?php if ($sekolah !== null) : ?>
                <section class="cek-hasil" aria-labelledby="cek-sekolah">
                    <div class="cek-sekolah">
                        <span class="cek-sekolah-ikon" aria-hidden="true"><i class="bi bi-building"></i></span>
                        <div class="cek-sekolah-isi">
                        <p class="cek-sekolah-label">Sekolah</p>
                        <h2 class="h3" id="cek-sekolah"><?= esc($sekolah['nama_sekolah']) ?></h2>
                        <dl class="cek-sekolah-data">
                            <div><dt>NPSN</dt><dd><?= esc($sekolah['npsn'] ?? 'Tanpa NPSN') ?></dd></div>
                            <div><dt>Jenjang</dt><dd><?= esc($sekolah['jenjang_label']) ?></dd></div>
                            <div><dt>Kabupaten / Kota</dt><dd><?= esc($sekolah['kabupaten']) ?></dd></div>
                            <div><dt>Jumlah pendaftaran</dt><dd><?= count($hasil) ?></dd></div>
                        </dl>
                        </div>
                    </div>

                    <?php $jumlahDitolak = array_sum(array_map(static fn (array $h): int => count($h['atlet_ditolak'] ?? []), $hasil)); ?>
                    <?php if ($jumlahDitolak > 0) : ?>
                        <div class="alert alert-danger d-flex gap-2" role="alert" id="ringkasan-atlet-ditolak">
                            <i class="bi bi-exclamation-octagon flex-shrink-0" aria-hidden="true"></i>
                            <div>
                                <strong><?= $jumlahDitolak ?> atlet ditolak panitia.</strong>
                                Alasan, kelompok umur yang sesuai, dan perubahan biayanya ada di bagian "Atlet ditolak panitia" pada pendaftarannya di bawah.
                                Daftarkan ulang atletnya lewat menu Pendaftaran.
                            </div>
                        </div>
                    <?php endif ?>

                    <?php foreach ($hasil as $nomor => $pendaftaran) : ?>
                        <?= view('cek_pendaftaran/_kartu', ['p' => $pendaftaran, 'urutan' => $nomor + 1, 'banyak' => count($hasil) > 1, 'kode' => $kode, 'waTanya' => $waTanya ?? null], ['saveData' => false]) ?>
                    <?php endforeach ?>

                    <?php if (count($hasil) > 1) : ?>
                        <div class="cek-total-semua">
                            <span><i class="bi bi-wallet2" aria-hidden="true"></i> Total semua pendaftaran</span>
                            <strong><?= rupiah(array_sum(array_column($hasil, 'total'))) ?></strong>
                        </div>
                    <?php endif ?>
                </section>
            <?php endif ?>
        <?php endif ?>
    </div>
</div>
<?= $this->endSection() ?>
