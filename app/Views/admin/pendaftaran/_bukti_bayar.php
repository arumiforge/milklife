<?php
/**
 * Bukti bayar satu pendaftaran (K34; F6-03), potongan untuk rincian
 * pendaftaran (`admin/pendaftaran/rincian.php`, F6-02).
 *
 * Isinya: gambar kecil setiap bukti bayar (klik untuk melihat gambar besar),
 * tombol unduh dan hapus (dengan jendela konfirmasi) per gambar, dan
 * formulir unggah selama bukti belum `BuktiBayarModel::BATAS` gambar.
 * Di layar lebar galeri di kiri dan panel unggah di kanan (K76); di ponsel
 * bertumpuk.
 * Pengendalinya `App\Controllers\Admin\BuktiBayarController`; setelah unggah
 * atau hapus, admin kembali ke rincian dengan pesan kilat.
 *
 * Pemakaian di rincian pendaftaran, di dalam bagian `isi` (`$p` baris
 * pendaftaran dari `DataPanelPendaftaran::rincian()`):
 *
 *     <?= view('admin/pendaftaran/_bukti_bayar', ['pendaftaran' => $p], ['saveData' => false]) ?>
 *
 * Data yang dibaca:
 * - `$pendaftaran` (array) dengan kunci `id` (`pendaftaran.id`) dan, bila
 *   ada, `id_pendaftaran` (untuk teks tombol bagi pembaca layar); atau
 *   `$pendaftaranId` (int) bila halaman tidak punya `$pendaftaran`.
 *   `$pendaftaranId` didahulukan.
 * Daftar bukti bayar dibaca sendiri dari `BuktiBayarModel`, jadi pengendali
 * rincian tidak perlu menyiapkannya.
 *
 * Halaman yang memuat potongan ini wajib memuat `aset/js/konfirmasi.js` di
 * bagian `skrip` (jendela konfirmasi hapus dan pemeriksaan ukuran gambar).
 * Potongan ini tidak memuatnya sendiri supaya skrip itu tidak dimuat dua
 * kali bersama tombol Tandai Lunas dan Hapus di rincian. Gambar besar
 * memakai jendela Bootstrap (`data-bs-toggle="modal"`), tanpa skrip lain.
 *
 * @var CodeIgniter\View\View     $this
 * @var array<string, mixed>|null $pendaftaran
 * @var int|null                  $pendaftaranId
 */

use App\Libraries\Panel\BerkasBuktiBayar;
use App\Libraries\Unggahan;
use App\Models\BuktiBayarModel;

$idBukti = (int) ($pendaftaranId ?? (isset($pendaftaran) && is_array($pendaftaran) ? ($pendaftaran['id'] ?? 0) : 0));
$kodeBukti = isset($pendaftaran) && is_array($pendaftaran) ? trim((string) ($pendaftaran['id_pendaftaran'] ?? '')) : '';

if ($idBukti < 1) {
    return;
}

$daftarBukti  = model(BuktiBayarModel::class)->milikPendaftaran($idBukti);
$jumlahBukti  = count($daftarBukti);
$batasBukti   = BuktiBayarModel::BATAS;
$bolehTambah  = $jumlahBukti < $batasBukti;
$dasarBukti   = 'admin/pendaftaran/' . $idBukti . '/bukti-bayar';
$untukPembaca = $kodeBukti === '' ? '' : ' pendaftaran ' . $kodeBukti;
?>
<section class="card mb-4" id="bukti-bayar" aria-labelledby="judul-bukti-bayar" data-jumlah-bukti="<?= $jumlahBukti ?>">
    <div class="card-body">
        <div class="d-flex flex-wrap align-items-baseline justify-content-between gap-2 mb-3">
            <h2 id="judul-bukti-bayar" class="h5 mb-0">
                <i class="bi bi-receipt" aria-hidden="true"></i> Bukti bayar
            </h2>
            <span class="badge rounded-pill <?= $bolehTambah ? 'text-bg-light border' : 'text-bg-secondary' ?>" id="jumlah-bukti-bayar">
                <?= $jumlahBukti ?> dari <?= $batasBukti ?> gambar
            </span>
        </div>

        <div class="row g-4">
            <div class="col-12 col-lg-8">
                <?php if ($daftarBukti === []) : ?>
                    <div class="bukti-kosong">
                        <span class="bukti-kosong-ikon"><i class="bi bi-receipt-cutoff" aria-hidden="true"></i></span>
                        <p class="bukti-kosong-judul">Belum ada bukti bayar</p>
                        <p class="bukti-kosong-teks">
                            Official bisa mengunggah bukti transfer sendiri saat mendaftar atau lewat Cek Pendaftaran.
                            Bila bukti dikirim lewat WA, simpan di sini, paling banyak <?= $batasBukti ?> gambar.
                        </p>
                    </div>
                <?php else : ?>
                    <ul class="row g-3 list-unstyled mb-0" aria-label="Daftar bukti bayar">
                        <?php foreach ($daftarBukti as $urutan => $bukti) : ?>
                            <?php
                            $nomorBukti = $urutan + 1;
                            $alamatBukti = site_url($dasarBukti . '/' . (int) $bukti['id']);
                            $idJendela   = 'bukti-bayar-besar-' . (int) $bukti['id'];
                            $keterangan  = 'Bukti bayar ke-' . $nomorBukti . $untukPembaca;
                            $waktu       = tanggal_id((string) ($bukti['waktu_unggah'] ?? ''), true);
                            ?>
                            <li class="col-12 col-sm-6 col-xxl-4" data-bukti-id="<?= (int) $bukti['id'] ?>">
                                <figure class="card h-100 mb-0">
                                    <button type="button" class="btn p-0 border-0 rounded-top overflow-hidden" data-bs-toggle="modal" data-bs-target="#<?= $idJendela ?>"
                                        title="Perbesar gambar">
                                        <span class="ratio ratio-4x3 d-block bg-body-tertiary">
                                            <img src="<?= esc($alamatBukti, 'attr') ?>" alt="<?= esc($keterangan, 'attr') ?>" class="object-fit-cover" loading="lazy">
                                        </span>
                                        <span class="visually-hidden">Perbesar <?= esc(lcfirst($keterangan)) ?></span>
                                    </button>
                                    <figcaption class="card-body py-2 px-3">
                                        <span class="fw-semibold d-block">Bukti ke-<?= $nomorBukti ?></span>
                                        <span class="small text-body-secondary d-block">
                                            <?= esc($waktu === '' ? 'Waktu unggah tidak tercatat' : 'Diunggah ' . $waktu) ?>
                                            · <?= esc(Unggahan::ukuranTeks((int) $bukti['ukuran'])) ?>
                                        </span>
                                        <div class="d-flex flex-wrap gap-2 mt-2">
                                            <a class="btn btn-sm btn-outline-primary" href="<?= site_url($dasarBukti . '/' . (int) $bukti['id'] . '/unduh') ?>">
                                                <i class="bi bi-download" aria-hidden="true"></i> Unduh<span class="visually-hidden"> <?= esc(lcfirst($keterangan)) ?></span>
                                            </a>
                                            <form class="d-inline" action="<?= site_url($dasarBukti . '/' . (int) $bukti['id'] . '/hapus') ?>" method="post"
                                                data-konfirmasi="<?= esc('Bukti bayar ke-' . $nomorBukti . ' akan dihapus dari server dan tidak bisa dikembalikan. Status pembayaran tidak berubah.', 'attr') ?>"
                                                data-konfirmasi-judul="Hapus bukti bayar?"
                                                data-konfirmasi-tombol="Hapus gambar">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-trash" aria-hidden="true"></i> Hapus<span class="visually-hidden"> <?= esc(lcfirst($keterangan)) ?></span>
                                                </button>
                                            </form>
                                        </div>
                                    </figcaption>
                                </figure>

                                <div class="modal fade" id="<?= $idJendela ?>" tabindex="-1" aria-labelledby="<?= $idJendela ?>-judul" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered modal-xl">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h3 class="modal-title h5" id="<?= $idJendela ?>-judul"><?= esc($keterangan) ?></h3>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                            </div>
                                            <div class="modal-body text-center bg-body-tertiary">
                                                <img src="<?= esc($alamatBukti, 'attr') ?>" alt="<?= esc($keterangan, 'attr') ?>" class="img-fluid" loading="lazy">
                                            </div>
                                            <div class="modal-footer">
                                                <a class="btn btn-outline-primary" href="<?= site_url($dasarBukti . '/' . (int) $bukti['id'] . '/unduh') ?>">
                                                    <i class="bi bi-download" aria-hidden="true"></i> Unduh gambar
                                                </a>
                                                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Tutup</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
            <div class="col-12 col-lg-4">
                <div class="bukti-unggah">
                    <?php if ($bolehTambah) : ?>
                        <form action="<?= site_url($dasarBukti) ?>" method="post" enctype="multipart/form-data" id="unggah-bukti-bayar">
                            <?= csrf_field() ?>
                            <label class="form-label fw-semibold d-flex align-items-center gap-2" for="bukti-bayar-berkas">
                                <i class="bi bi-cloud-arrow-up" aria-hidden="true"></i> Unggah bukti bayar
                            </label>
                            <div class="d-grid gap-2">
                                <input type="file" class="form-control" id="bukti-bayar-berkas" name="<?= App\Controllers\Admin\BuktiBayarController::KOTAK ?>" required
                                    accept="image/jpeg,image/png,image/webp" aria-describedby="bukti-bayar-bantuan"
                                    data-ukuran-maksimal="<?= BerkasBuktiBayar::BATAS_BITA ?>">
                                <button type="submit" class="btn btn-primary text-nowrap">
                                    <i class="bi bi-upload" aria-hidden="true"></i> Unggah
                                </button>
                            </div>
                            <p id="bukti-bayar-bantuan" class="form-text mb-0">
                                Foto atau tangkapan layar JPG, PNG, atau WEBP, paling besar 10 MB. Gambar dikecilkan otomatis.
                                Masih bisa <?= $batasBukti - $jumlahBukti ?> gambar lagi.
                            </p>
                        </form>
                    <?php else : ?>
                        <p class="text-body-secondary mb-0" id="bukti-bayar-penuh">
                            <i class="bi bi-info-circle" aria-hidden="true"></i>
                            Sudah <?= $batasBukti ?> gambar, batas paling banyak. Hapus gambar yang tidak dipakai bila ingin mengunggah yang lain.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
