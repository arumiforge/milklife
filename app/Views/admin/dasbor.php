<?php
/**
 * Dasbor admin (K102): kartu ringkasan data dan grafik interaktif
 * (ApexCharts, `aset/js/dasbor.js`), tata letak `admin_tw` (F9-14). Keadaan
 * pendaftaran, kelengkapan pengaturan, dan gambar kepala ada di Pengecekan;
 * di sini hanya lencana keadaan dan peringatan bila pengaturan belum lengkap.
 * Data dari `DasborController::index()`.
 *
 * Kartu utama `#angka-pendaftaran [data-angka]` dari `Rekap::ringkasan()`
 * (F6-06), kartu tambahan `#angka-tambahan [data-tambahan]` dari
 * `RingkasanDasbor::angka()`; angkanya di `[data-nilai]`. Data grafik dikirim
 * sebagai JSON di `#data-grafik-dasbor`; setiap wadah grafik (`[data-grafik]`)
 * berisi kerangka sampai `dasbor.js` menggambarnya.
 *
 * Kartu keadaan: label kecil dan lencana, kalimat keadaan sebagai baris utama,
 * tombol Pengecekan di bawahnya, dan ilustrasi sapaan G9-08/G10-08
 * `admin/dasbor-sapaan.webp` di panel kanan rata bawah mulai `md`, hanya bila
 * berkasnya ada (F9-19, disusun ulang F10-10).
 *
 * ikon-sprite: hourglass calendar-days lock-open lock clipboard-list circle-alert
 * ikon-sprite: file-text user-round school users receipt circle-check arrow-right
 * ikon-sprite: file-search bell clock badge-check hash user calculator calendar-check
 * ikon-sprite: calendar-plus map-pin eye app-window triangle-alert message-square-text inbox
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
use App\Libraries\Panel\RingkasanDasbor;

[$warnaKeadaan, $ikonKeadaan] = [
    BukaTutupPendaftaran::DISIAPKAN    => ['kuning', 'hourglass'],
    BukaTutupPendaftaran::BELUM_DIBUKA => ['biru', 'calendar-days'],
    BukaTutupPendaftaran::TERBUKA      => ['hijau', 'lock-open'],
    BukaTutupPendaftaran::DITUTUP      => ['abu', 'lock'],
][$keadaan];

$angkaBiasa  = static fn (int|float $n): string => number_format($n, is_float($n) && floor($n) !== $n ? 1 : 0, ',', '.');
$persenLunas = $angka['tagihan'] > 0 ? (int) round($angka['lunas'] / $angka['tagihan'] * 100) : 0;

$warnaIkon = [
    'biru'   => 'bg-primary-100 text-primary-700',
    'oranye' => 'bg-oranye-100 text-oranye-700',
    'hijau'  => 'bg-green-100 text-green-700',
    'ungu'   => 'bg-purple-100 text-purple-700',
    'abu'    => 'bg-gray-100 text-gray-600',
];

$kartuGrafik = static fn (string $id, string $judul, string $keterangan, string $kelas, string $tombol = ''): string => '<div class="flex flex-col rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-5 ' . $kelas . '">'
    . '<div class="mb-2 flex flex-wrap items-start justify-between gap-2"><div class="min-w-0">'
    . '<h3 class="text-base font-bold text-gray-900">' . esc($judul) . '</h3>'
    . '<p class="text-sm text-gray-500">' . esc($keterangan) . '</p></div>' . $tombol . '</div>'
    . '<div id="' . $id . '" class="min-h-[300px] min-w-0" data-grafik>' . komponen('kerangka', ['jenis' => 'grafik', 'label' => 'Memuat grafik ' . $judul . '…']) . '</div>'
    . '</div>';
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?>Dasbor<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Dasbor<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-4 p-4 sm:px-6 sm:py-4">
    <?php
    // Kartu keadaan (F10-10): label kecil dan lencana, kalimat keadaan sebagai baris utama, tombol di bawahnya;
    // ilustrasi sapaan G10-08 di panel kanan, rata bawah, mulai md (hanya bila berkasnya ada).
    $sapaan = gambar_opsional('admin/dasbor-sapaan.webp', ['class' => 'hidden h-24 w-auto max-w-full object-contain md:block lg:h-28', 'data-gambar-admin' => 'dasbor-sapaan']);
    ?>
    <section class="flex overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-lembut" aria-label="Keadaan pendaftaran">
        <div class="min-w-0 flex-1 p-4 sm:p-5">
            <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Keadaan pendaftaran</p>
                <span data-keadaan="<?= esc($keadaan, 'attr') ?>"><?= komponen('lencana', ['label' => $labelKeadaan, 'warna' => $warnaKeadaan, 'ikon' => $ikonKeadaan]) ?></span>
            </div>
            <p class="mt-2 max-w-2xl text-lg font-bold leading-snug text-balance text-gray-900 sm:text-xl" data-kalimat-keadaan><?= esc($kalimat) ?></p>
            <?php if (! $semuaLengkap) : ?>
                <p class="mt-3 flex items-start gap-2 text-sm text-yellow-800" id="peringatan-kelengkapan">
                    <?= ikon('circle-alert', 'mt-0.5 size-4 shrink-0') ?>
                    <span>Pengaturan wajib baru <?= $jumlahLengkap ?> dari <?= $jumlahButir ?> yang lengkap.
                    <a class="font-semibold underline hover:no-underline" href="<?= site_url('admin/pengecekan') ?>#kelengkapan-pengaturan">Lihat yang belum lengkap</a>.</span>
                </p>
            <?php endif ?>
            <div class="mt-4">
                <?= komponen('tombol', ['label' => 'Buka Pengecekan', 'href' => site_url('admin/pengecekan'), 'varian' => 'kedua', 'ukuran' => 'kecil', 'ikon' => 'clipboard-list']) ?>
            </div>
        </div>
        <?php if ($sapaan !== '') : ?>
            <div class="hidden w-48 shrink-0 items-end justify-center bg-linear-to-l from-primary-50 to-white px-4 pt-3 md:flex lg:w-60" aria-hidden="true"><?= $sapaan ?></div>
        <?php endif ?>
    </section>

    <section aria-labelledby="judul-angka" id="angka-pendaftaran">
        <h2 id="judul-angka" class="mb-3 text-lg font-bold text-gray-900">Ringkasan pendaftaran</h2>
        <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
            <?php foreach ([
                'pendaftaran' => ['Pendaftaran', 'file-text', 'biru', false, $tambahan['pendaftaran_hari_ini'] > 0 ? '+' . $tambahan['pendaftaran_hari_ini'] . ' hari ini' : ''],
                'atlet'       => ['Atlet', 'user-round', 'oranye', false, $tambahan['atlet_hari_ini'] > 0 ? '+' . $tambahan['atlet_hari_ini'] . ' hari ini' : ''],
                'sekolah'     => ['Sekolah', 'school', 'hijau', false, $tambahan['kabupaten'] . ' kabupaten'],
                'tim'         => ['Tim beregu', 'users', 'ungu', false, ''],
                'tagihan'     => ['Tagihan', 'receipt', 'biru', true, ''],
                'lunas'       => ['Lunas', 'circle-check', 'hijau', true, $persenLunas . '% dari tagihan'],
                'belum'       => ['Belum dibayar', 'hourglass', 'oranye', true, ''],
            ] as $kunci => [$label, $ikon, $warna, $uang, $catatan]) : ?>
                <div class="<?= $uang ? 'col-span-2 sm:col-span-1' : '' ?> rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut" data-angka="<?= $kunci ?>">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-500"><?= $label ?></p>
                            <p class="mt-1 font-extrabold tracking-tight text-gray-900 <?= $uang ? 'text-xl sm:text-2xl' : 'text-2xl sm:text-3xl' ?>" data-nilai><?= $uang ? esc(rupiah($angka[$kunci])) : $angka[$kunci] ?></p>
                        </div>
                        <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl <?= $warnaIkon[$warna] ?>"><?= ikon($ikon) ?></span>
                    </div>
                    <?php if ($catatan !== '') : ?>
                        <p class="mt-1 text-sm text-gray-500"><?= esc($catatan) ?></p>
                    <?php endif ?>
                    <?php if ($kunci === 'lunas') : ?>
                        <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-gray-200" role="progressbar" aria-label="Bagian tagihan yang sudah lunas" aria-valuenow="<?= $persenLunas ?>" aria-valuemin="0" aria-valuemax="100">
                            <div class="h-full rounded-full bg-green-600 <?= lebar_tw($persenLunas) ?>"></div>
                        </div>
                    <?php endif ?>
                </div>
            <?php endforeach; ?>
            <a class="group col-span-2 rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut hover:border-primary-300 hover:bg-primary-50 sm:col-span-1" href="<?= site_url('admin/rekap') ?>">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Rekap lengkap</p>
                        <p class="mt-1 font-semibold text-primary-800">Lihat rekap dan unduh Excel</p>
                    </div>
                    <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl <?= $warnaIkon['abu'] ?> group-hover:bg-primary-100 group-hover:text-primary-700"><?= ikon('arrow-right') ?></span>
                </div>
            </a>
        </div>

        <?php if ($angka['tim_tanpa_pembayar'] > 0) : ?>
            <div class="mt-3" id="tim-tanpa-pembayar">
                <?= komponen('peringatan', ['jenis' => 'peringatan', 'isi' => '<strong>' . (int) $angka['tim_tanpa_pembayar'] . '</strong> regu beregu tidak punya pembayar biaya regu. <a class="font-semibold underline hover:no-underline" href="' . site_url('admin/rekap/keuangan') . '#judul-tim-tanpa-pembayar">Lihat daftarnya di rekap keuangan</a>.']) ?>
            </div>
        <?php endif ?>
        <?php if ($usulanNama > 0) : ?>
            <div class="mt-3" id="usulan-nama-menunggu">
                <?= komponen('peringatan', ['jenis' => 'info', 'isi' => '<strong>' . $usulanNama . '</strong> usulan nama sekolah dari pendaftar menunggu keputusan. <a class="font-semibold underline hover:no-underline" href="' . site_url('admin/sekolah/usulan') . '">Periksa usulannya</a>.']) ?>
            </div>
        <?php endif ?>
    </section>

    <section aria-labelledby="judul-tambahan" id="angka-tambahan">
        <h2 id="judul-tambahan" class="mb-3 text-lg font-bold text-gray-900">Angka lain</h2>
        <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-5 2xl:grid-cols-6">
            <?php foreach ([
                // F10-04: dua angka bukti bayar berbeda; keterangannya menyebut bedanya.
                'perlu_diperiksa'      => ['Menunggu, bukti sudah ada', 'file-search', 'admin/pendaftaran?status=menunggu&bukti=ada', 'Tinggal diperiksa lalu ditandai Lunas'],
                // F8-02: pendaftaran dengan bukti bayar baru dari ofisial yang belum diperiksa.
                'bukti_baru'           => ['Bukti bayar baru belum diperiksa', 'bell', 'admin/pendaftaran?bukti=baru', 'Belum ditandai "Sudah diperiksa", termasuk di pendaftaran Lunas'],
                'pendaftaran_menunggu' => ['Pendaftaran menunggu', 'clock', ''],
                'pendaftaran_lunas'    => ['Pendaftaran lunas', 'badge-check', ''],
                'nomor_dada'           => ['Nomor atlet (BIB) terbit', 'hash', ''],
                'atlet_putra'          => ['Atlet putra', 'user', ''],
                'atlet_putri'          => ['Atlet putri', 'user-round', ''],
                'rata_atlet'           => ['Atlet per pendaftaran', 'calculator', ''],
                'pendaftaran_hari_ini' => ['Pendaftaran hari ini', 'calendar-check', ''],
                'atlet_hari_ini'       => ['Atlet hari ini', 'calendar-plus', ''],
                'kabupaten'            => ['Kabupaten', 'map-pin', ''],
                'pengunjung_hari_ini'  => ['Pengunjung hari ini', 'eye', ''],
                'kunjungan_hari_ini'   => ['Halaman dibuka hari ini', 'app-window', ''],
            ] as $kunci => $kartuTambahan) : ?>
                <?php [$label, $ikon, $alamat] = $kartuTambahan; $keterangan = $kartuTambahan[3] ?? ''; ?>
                <?php $sorot = in_array($kunci, ['perlu_diperiksa', 'bukti_baru'], true) && $tambahan[$kunci] > 0; ?>
                <div class="relative rounded-2xl border p-3 sm:px-4 <?= $sorot ? 'border-oranye-300 bg-oranye-50' : 'border-gray-200 bg-white' ?>" data-tambahan="<?= $kunci ?>"<?= $sorot ? ' data-sorot' : '' ?>>
                    <?= ikon($ikon, $sorot ? 'text-oranye-600' : 'text-gray-400') ?>
                    <p class="mt-1 text-2xl font-extrabold text-gray-900" data-nilai><?= $angkaBiasa($tambahan[$kunci]) ?></p>
                    <p class="text-sm font-medium text-gray-600">
                        <?php if ($alamat !== '') : ?>
                            <a class="text-primary-800 underline after:absolute after:inset-0 hover:no-underline" href="<?= site_url($alamat) ?>"><?= $label ?></a>
                        <?php else : ?>
                            <?= $label ?>
                        <?php endif ?>
                    </p>
                    <?php if ($keterangan !== '') : ?>
                        <p class="mt-1 text-xs text-gray-600" data-keterangan><?= esc($keterangan) ?></p>
                    <?php endif ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <?php if ($angka['pendaftaran'] === 0) : ?>
    <section aria-labelledby="judul-kosong" data-dasbor-kosong>
        <div class="flex flex-col items-center rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-6 text-center">
            <span class="mb-4 inline-flex size-14 items-center justify-center rounded-2xl bg-primary-100 text-primary-700"><?= ikon('inbox', 'size-7') ?></span>
            <h2 id="judul-kosong" class="text-lg font-bold text-gray-900">Belum ada pendaftaran</h2>
            <p class="mt-1 text-gray-600">
                Pendaftaran dari sekolah akan muncul di sini setelah formulir pendaftaran dibuka.
                <strong>Langkah berikutnya:</strong> pastikan semua butir di
                <a class="font-semibold text-primary-700 underline hover:no-underline" href="<?= site_url('admin/pengecekan') ?>">Pengecekan</a> sudah bercentang.
            </p>
        </div>
    </section>
    <?php endif ?>

    <section aria-labelledby="judul-grafik" id="grafik-dasbor">
        <h2 id="judul-grafik" class="mb-3 text-lg font-bold text-gray-900">Grafik</h2>
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 2xl:grid-cols-12">
            <?= $kartuGrafik('grafik-tren', 'Tren ' . RingkasanDasbor::HARI_TREN . ' hari terakhir', 'Pendaftaran dan atlet yang masuk per hari', 'md:col-span-2 2xl:col-span-8',
                '<div class="inline-flex rounded-lg border border-gray-200 p-0.5" role="group" aria-label="Pilih isi grafik tren">'
                . '<button type="button" class="min-h-11 rounded-md px-3 text-sm font-semibold text-gray-700 hover:bg-gray-100 aria-pressed:bg-primary-700 aria-pressed:text-white" data-tren="jumlah" aria-pressed="true">Jumlah</button>'
                . '<button type="button" class="min-h-11 rounded-md px-3 text-sm font-semibold text-gray-700 hover:bg-gray-100 aria-pressed:bg-primary-700 aria-pressed:text-white" data-tren="tagihan" aria-pressed="false">Tagihan</button>'
                . '</div>') ?>
            <?= $kartuGrafik('grafik-status', 'Status pembayaran', 'Jumlah pendaftaran; arahkan ke potongan untuk melihat rupiahnya', '2xl:col-span-4') ?>
            <?= $kartuGrafik('grafik-kabupaten', 'Atlet per kabupaten', 'Putra dan putri', '2xl:col-span-6') ?>
            <?= $kartuGrafik('grafik-kelompok-umur', 'Atlet per kelompok umur', 'Putra dan putri', '2xl:col-span-6') ?>
            <?= $kartuGrafik('grafik-jenjang', 'Jenjang sekolah', 'Atlet per jenjang', '2xl:col-span-4') ?>
            <?= $kartuGrafik('grafik-nomor-lomba', 'Nomor lomba terpopuler', 'Sepuluh nomor dengan peserta terbanyak', 'md:col-span-2 2xl:col-span-8') ?>
            <?= $kartuGrafik('grafik-pengunjung', 'Pengunjung halaman publik', 'Pengunjung dan halaman dibuka per hari, ' . RingkasanDasbor::HARI_TREN . ' hari terakhir', 'md:col-span-2 2xl:col-span-7') ?>
            <?= $kartuGrafik('grafik-jam', 'Jam mendaftar', 'Jam berapa sekolah biasanya mengirim pendaftaran', 'md:col-span-2 2xl:col-span-5') ?>
        </div>
    </section>
</div>

<script type="application/json" id="data-grafik-dasbor" data-nonce-gaya="<?= service('csp')->enabled() ? esc(service('csp')->getStyleNonce(), 'attr') : '' ?>"><?= json_encode($grafik, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?></script>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('vendor/apexcharts/apexcharts.min.js') ?>"></script>
<script src="<?= aset('js/dasbor.js') ?>"></script>
<?= $this->endSection() ?>
