<?php
/**
 * Dasbor admin (K102): kartu ringkasan data dan grafik interaktif
 * (ApexCharts, `aset/js/dasbor.js`), bergaya kartu Flowbite. Keadaan
 * pendaftaran, kelengkapan pengaturan, dan gambar kepala ada di Pengecekan;
 * di sini hanya lencana keadaan dan peringatan bila pengaturan belum lengkap.
 * Data dari `DasborController::index()`.
 *
 * Kartu utama `#angka-pendaftaran [data-angka]` dari `Rekap::ringkasan()`
 * (F6-06), kartu tambahan `#angka-tambahan [data-tambahan]` dari
 * `RingkasanDasbor::angka()`. Data grafik dikirim sebagai JSON di
 * `#data-grafik-dasbor`.
 *
 * @var CodeIgniter\View\View $this
 * @var array{pendaftaran: int, atlet: int, tim: int, sekolah: int, tagihan: int, lunas: int, belum: int, tim_tanpa_pembayar: int} $angka
 * @var array<string, int|float> $tambahan
 * @var array<string, array<string, list<int|string>>> $grafik
 * @var string $keadaan salah satu konstanta BukaTutupPendaftaran
 * @var string $labelKeadaan
 * @var string $kalimat
 * @var bool   $semuaLengkap
 * @var int    $jumlahLengkap
 * @var int    $jumlahButir
 * @var int    $usulanNama usulan nama sekolah yang menunggu keputusan (K99)
 */

use App\Libraries\BukaTutupPendaftaran;

$warnaKeadaan = [
    BukaTutupPendaftaran::DISIAPKAN    => ['text-bg-secondary', 'hourglass-split'],
    BukaTutupPendaftaran::BELUM_DIBUKA => ['text-bg-info', 'calendar-event'],
    BukaTutupPendaftaran::TERBUKA      => ['text-bg-success', 'door-open-fill'],
    BukaTutupPendaftaran::DITUTUP      => ['text-bg-dark', 'door-closed-fill'],
][$keadaan];

$angkaBiasa = static fn (int|float $n): string => number_format($n, is_float($n) && floor($n) !== $n ? 1 : 0, ',', '.');
$persenLunas = $angka['tagihan'] > 0 ? (int) round($angka['lunas'] / $angka['tagihan'] * 100) : 0;
?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?>Dasbor<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Dasbor<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid px-3 px-lg-4 dasbor">
    <section class="kartu-dasbor kartu-sapa mb-4" aria-label="Keadaan pendaftaran">
        <div class="d-flex flex-wrap align-items-center gap-3">
            <span class="badge rounded-pill <?= $warnaKeadaan[0] ?> fs-6 lencana-keadaan" data-keadaan="<?= esc($keadaan, 'attr') ?>">
                <i class="bi bi-<?= $warnaKeadaan[1] ?>" aria-hidden="true"></i> <?= esc($labelKeadaan) ?>
            </span>
            <p class="mb-0 flex-grow-1 kalimat-keadaan"><?= esc($kalimat) ?></p>
            <a class="btn btn-sm btn-outline-primary" href="<?= site_url('admin/pengecekan') ?>">
                <i class="bi bi-clipboard2-pulse" aria-hidden="true"></i> Buka Pengecekan
            </a>
        </div>
        <?php if (! $semuaLengkap) : ?>
            <p class="small mb-0 mt-3 text-warning-emphasis" id="peringatan-kelengkapan">
                <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
                Pengaturan wajib baru <?= $jumlahLengkap ?> dari <?= $jumlahButir ?> yang lengkap.
                <a href="<?= site_url('admin/pengecekan') ?>#kelengkapan-pengaturan">Lihat yang belum lengkap</a>.
            </p>
        <?php endif ?>
    </section>

    <section class="mb-4" aria-labelledby="judul-angka" id="angka-pendaftaran">
        <h2 id="judul-angka" class="judul-bagian-dasbor">Ringkasan pendaftaran</h2>
        <div class="row g-3">
            <?php foreach ([
                'pendaftaran' => ['Pendaftaran', 'journal-text', 'biru', false, $tambahan['pendaftaran_hari_ini'] > 0 ? '+' . $tambahan['pendaftaran_hari_ini'] . ' hari ini' : ''],
                'atlet'       => ['Atlet', 'person-arms-up', 'oranye', false, $tambahan['atlet_hari_ini'] > 0 ? '+' . $tambahan['atlet_hari_ini'] . ' hari ini' : ''],
                'sekolah'     => ['Sekolah', 'building', 'hijau', false, $tambahan['kabupaten'] . ' kabupaten'],
                'tim'         => ['Tim beregu', 'people', 'ungu', false, ''],
                'tagihan'     => ['Tagihan', 'receipt', 'biru', true, ''],
                'lunas'       => ['Lunas', 'check-circle', 'hijau', true, $persenLunas . '% dari tagihan'],
                'belum'       => ['Belum dibayar', 'hourglass-split', 'oranye', true, ''],
            ] as $kunci => [$label, $ikon, $warna, $uang, $catatan]) : ?>
                <div class="<?= $uang ? 'col-12 col-sm-6' : 'col-6' ?> col-xl-3">
                    <div class="kartu-dasbor kartu-angka h-100" data-angka="<?= $kunci ?>">
                        <div class="d-flex align-items-start justify-content-between gap-2">
                            <div class="min-w-0">
                                <div class="label-angka"><?= $label ?></div>
                                <div class="nilai-angka<?= $uang ? ' nilai-uang' : '' ?>"><?= $uang ? esc(rupiah($angka[$kunci])) : $angka[$kunci] ?></div>
                            </div>
                            <span class="ikon-angka ikon-<?= $warna ?>"><i class="bi bi-<?= $ikon ?>" aria-hidden="true"></i></span>
                        </div>
                        <?php if ($catatan !== '') : ?>
                            <div class="catatan-angka"><?= esc($catatan) ?></div>
                        <?php endif ?>
                        <?php if ($kunci === 'lunas') : ?>
                            <div class="progress mt-2 bilah-tipis" role="progressbar" aria-label="Bagian tagihan yang sudah lunas" aria-valuenow="<?= $persenLunas ?>" aria-valuemin="0" aria-valuemax="100">
                                <div class="progress-bar bg-success <?= lebar_bilah($persenLunas) ?>"></div>
                            </div>
                        <?php endif ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <div class="col-12 col-sm-6 col-xl-3">
                <a class="kartu-dasbor kartu-angka kartu-tautan h-100" href="<?= site_url('admin/rekap') ?>">
                    <div class="d-flex align-items-start justify-content-between gap-2">
                        <div>
                            <div class="label-angka">Rekap lengkap</div>
                            <div class="fw-semibold mt-1">Lihat rekap dan unduh Excel</div>
                        </div>
                        <span class="ikon-angka ikon-abu"><i class="bi bi-arrow-right" aria-hidden="true"></i></span>
                    </div>
                </a>
            </div>
        </div>

        <?php if ($angka['tim_tanpa_pembayar'] > 0) : ?>
            <div class="alert alert-warning mt-3 mb-0" id="tim-tanpa-pembayar">
                <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                <strong><?= (int) $angka['tim_tanpa_pembayar'] ?></strong> regu beregu tidak punya pembayar biaya regu.
                <a href="<?= site_url('admin/rekap/keuangan') ?>#judul-tim-tanpa-pembayar">Lihat daftarnya di rekap keuangan</a>.
            </div>
        <?php endif ?>
        <?php if ($usulanNama > 0) : ?>
            <div class="alert alert-info mt-3 mb-0" id="usulan-nama-menunggu">
                <i class="bi bi-chat-square-text" aria-hidden="true"></i>
                <strong><?= $usulanNama ?></strong> usulan nama sekolah dari pendaftar menunggu keputusan.
                <a href="<?= site_url('admin/sekolah/usulan') ?>">Periksa usulannya</a>.
            </div>
        <?php endif ?>
    </section>

    <section class="mb-4" aria-labelledby="judul-tambahan" id="angka-tambahan">
        <h2 id="judul-tambahan" class="judul-bagian-dasbor">Angka lain</h2>
        <div class="row row-cols-2 row-cols-md-3 row-cols-xxl-6 g-3">
            <?php foreach ([
                'perlu_diperiksa'      => ['Bukti bayar perlu diperiksa', 'patch-question', 'admin/pendaftaran'],
                // F8-02: pendaftaran dengan bukti bayar baru dari ofisial yang belum diperiksa.
                'bukti_baru'           => ['Bukti bayar baru belum diperiksa', 'bell', 'admin/pendaftaran?bukti=baru'],
                'pendaftaran_menunggu' => ['Pendaftaran menunggu', 'clock-history', ''],
                'pendaftaran_lunas'    => ['Pendaftaran lunas', 'patch-check', ''],
                'nomor_dada'           => ['Nomor atlet (BIB) terbit', '123', ''],
                'atlet_putra'          => ['Atlet putra', 'gender-male', ''],
                'atlet_putri'          => ['Atlet putri', 'gender-female', ''],
                'rata_atlet'           => ['Atlet per pendaftaran', 'calculator', ''],
                'pendaftaran_hari_ini' => ['Pendaftaran hari ini', 'calendar-check', ''],
                'atlet_hari_ini'       => ['Atlet hari ini', 'calendar-plus', ''],
                'kabupaten'            => ['Kabupaten', 'geo-alt', ''],
                'pengunjung_hari_ini'  => ['Pengunjung hari ini', 'eye', ''],
                'kunjungan_hari_ini'   => ['Halaman dibuka hari ini', 'window-stack', ''],
            ] as $kunci => [$label, $ikon, $alamat]) : ?>
                <div class="col">
                    <div class="kartu-dasbor kartu-kecil h-100<?= in_array($kunci, ['perlu_diperiksa', 'bukti_baru'], true) && $tambahan[$kunci] > 0 ? ' kartu-sorot' : '' ?>" data-tambahan="<?= $kunci ?>">
                        <i class="bi bi-<?= $ikon ?>" aria-hidden="true"></i>
                        <div class="nilai-kecil"><?= $angkaBiasa($tambahan[$kunci]) ?></div>
                        <div class="label-angka">
                            <?php if ($alamat !== '') : ?>
                                <a class="stretched-link" href="<?= site_url($alamat) ?>"><?= $label ?></a>
                            <?php else : ?>
                                <?= $label ?>
                            <?php endif ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <?php if ($angka['pendaftaran'] === 0) : ?>
    <section class="mb-4" aria-labelledby="judul-kosong">
        <div class="card kartu-kosong">
            <div class="card-body text-center px-3 py-5">
                <span class="ikon-bulat mb-3"><i class="bi bi-inbox" aria-hidden="true"></i></span>
                <h2 id="judul-kosong" class="h4">Belum ada pendaftaran</h2>
                <p class="text-body-secondary mb-0 mx-auto">
                    Pendaftaran dari sekolah akan muncul di sini setelah formulir pendaftaran dibuka.
                    <strong>Langkah berikutnya:</strong> pastikan semua butir di
                    <a href="<?= site_url('admin/pengecekan') ?>">Pengecekan</a> sudah bercentang.
                </p>
            </div>
        </div>
    </section>
    <?php endif ?>

    <section aria-labelledby="judul-grafik" id="grafik-dasbor">
        <h2 id="judul-grafik" class="judul-bagian-dasbor">Grafik</h2>
        <div class="row g-3">
            <div class="col-12 col-xxl-8">
                <div class="kartu-dasbor h-100">
                    <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-2">
                        <div>
                            <h3 class="judul-grafik">Tren <?= \App\Libraries\Panel\RingkasanDasbor::HARI_TREN ?> hari terakhir</h3>
                            <p class="keterangan-grafik">Pendaftaran dan atlet yang masuk per hari</p>
                        </div>
                        <div class="btn-group btn-group-sm" role="group" aria-label="Pilih isi grafik tren">
                            <button type="button" class="btn btn-outline-primary active" data-tren="jumlah" aria-pressed="true">Jumlah</button>
                            <button type="button" class="btn btn-outline-primary" data-tren="tagihan" aria-pressed="false">Tagihan</button>
                        </div>
                    </div>
                    <div class="wadah-grafik" id="grafik-tren"></div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xxl-4">
                <div class="kartu-dasbor h-100">
                    <h3 class="judul-grafik">Status pembayaran</h3>
                    <p class="keterangan-grafik">Jumlah pendaftaran; arahkan ke potongan untuk melihat rupiahnya</p>
                    <div class="wadah-grafik" id="grafik-status"></div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xxl-6">
                <div class="kartu-dasbor h-100">
                    <h3 class="judul-grafik">Atlet per kabupaten</h3>
                    <p class="keterangan-grafik">Putra dan putri</p>
                    <div class="wadah-grafik" id="grafik-kabupaten"></div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xxl-6">
                <div class="kartu-dasbor h-100">
                    <h3 class="judul-grafik">Atlet per kelompok umur</h3>
                    <p class="keterangan-grafik">Putra dan putri</p>
                    <div class="wadah-grafik" id="grafik-kelompok-umur"></div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-xxl-4">
                <div class="kartu-dasbor h-100">
                    <h3 class="judul-grafik">Jenjang sekolah</h3>
                    <p class="keterangan-grafik">Atlet per jenjang</p>
                    <div class="wadah-grafik" id="grafik-jenjang"></div>
                </div>
            </div>
            <div class="col-12 col-xxl-8">
                <div class="kartu-dasbor h-100">
                    <h3 class="judul-grafik">Nomor lomba terpopuler</h3>
                    <p class="keterangan-grafik">Sepuluh nomor dengan peserta terbanyak</p>
                    <div class="wadah-grafik" id="grafik-nomor-lomba"></div>
                </div>
            </div>
            <div class="col-12 col-xxl-7">
                <div class="kartu-dasbor h-100">
                    <h3 class="judul-grafik">Pengunjung halaman publik</h3>
                    <p class="keterangan-grafik">Pengunjung dan halaman dibuka per hari, <?= \App\Libraries\Panel\RingkasanDasbor::HARI_TREN ?> hari terakhir</p>
                    <div class="wadah-grafik" id="grafik-pengunjung"></div>
                </div>
            </div>
            <div class="col-12 col-xxl-5">
                <div class="kartu-dasbor h-100">
                    <h3 class="judul-grafik">Jam mendaftar</h3>
                    <p class="keterangan-grafik">Jam berapa sekolah biasanya mengirim pendaftaran</p>
                    <div class="wadah-grafik" id="grafik-jam"></div>
                </div>
            </div>
        </div>
    </section>
</div>

<script type="application/json" id="data-grafik-dasbor" data-nonce-gaya="<?= service('csp')->enabled() ? esc(service('csp')->getStyleNonce(), 'attr') : '' ?>"><?= json_encode($grafik, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?></script>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('vendor/apexcharts/apexcharts.min.js') ?>"></script>
<script src="<?= aset('js/dasbor.js') ?>"></script>
<?= $this->endSection() ?>
