<?php
/**
 * Rincian satu pendaftaran (F6-02): sekolah, penanggung jawab beserta
 * fotonya (diperbesar di jendela, K76), atlet (NISN, tanggal lahir, kelompok umur, nomor lomba, nomor
 * dada), tim beregu, biaya, dan status. Data dari
 * `PendaftaranController::rincian()` (`DataPanelPendaftaran::rincian()`).
 *
 * Tindakan berisiko (tandai Lunas, kembali ke Menunggu, hapus) memakai
 * jendela konfirmasi `aset/js/konfirmasi.js`. Tata letak `admin_tw` (F9-15);
 * foto ofisial diperbesar di modal Flowbite (`komponen('modal')`).
 *
 * Tempat yang disisakan untuk tugas lain (ditandai komentar):
 * - F6-04: tombol Ubah di deretan tombol tindakan (menuju `admin/pendaftaran/<id>/ubah`).
 * - F6-03: satu baris include `admin/pendaftaran/_bukti_bayar` setelah kartu biaya.
 *
 * Regu tanpa pembayar (K68): peringatan di kartu regu yang ditandai, dan
 * jendela hapus menyebut regu yang akan kehilangan pembayarnya. Regu yang
 * intinya kurang dari jumlah inti juga diberi peringatan (K105).
 *
 * @var CodeIgniter\View\View                $this
 * @var array<string, mixed>                 $p
 * @var array<string, array<string, mixed>>  $timTanpaPembayar `TimTanpaPembayar::untukPendaftaran()`, kunci "nomor:regu"
 * @var string                               $kalimatTimHapus  `TimTanpaPembayar::kalimatDitinggalkan()`, boleh kosong
 * @var array<int, array<string, mixed>>     $kecocokan        K116: per id atlet, `TolakAtlet::formulir()` (`cocok`, `sesuai_id`, `sesuai`)
 * @var list<array<string, mixed>>           $atletDitolak     K116: `AtletDitolak::untukPendaftaran(semua: true)`
 * @var array{label: string, alamat: string} $asal             K117: menu asal halaman ini (`AsalRincian`), untuk jejak navigasi
 * @var list<array<string, mixed>>           $riwayat          F8-01: `RiwayatAdmin::untukPendaftaran()`, terbaru di atas
 *
 * Tolak atlet (K116): tombol "Tolak atlet" untuk pendaftaran Menunggu yang
 * punya atlet, tanda di kolom kelompok umur bila tidak cocok lagi dengan
 * pengaturan, dan daftar atlet yang sudah ditolak beserta keadaannya.
 */

use App\Libraries\Pendaftaran\Regu;

$alamat      = 'admin/pendaftaran/' . (int) $p['id'];
$lunas       = $p['status'] === 'lunas';
$namaSingkat = $p['id_pendaftaran'] . ' (' . $p['nama_sekolah'] . ')';
$adaNomor    = $p['jumlah_atlet'] > $p['tanpa_nomor_dada'];
$strip       = '<span class="text-gray-400">–</span>';
$kartu       = 'rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-5';
$judulKartu  = 'text-lg font-bold text-gray-900';
$th          = 'border-b border-gray-200 bg-gray-50 px-4 py-3 text-xs font-bold uppercase tracking-wide whitespace-nowrap text-gray-600';
$td          = 'px-4 py-3 align-top';
$lencanaRegu = 'inline-flex items-center rounded-md border border-gray-200 bg-gray-50 px-2 py-0.5 text-xs font-semibold text-gray-800';
$tautan      = 'font-semibold text-primary-700 underline hover:no-underline';
$dt          = 'font-semibold text-gray-900';
$dd          = 'mb-2 text-gray-700 sm:mb-0';

$kalimatLunas = 'Pendaftaran ' . $namaSingkat . ' akan ditandai Lunas. '
    . ($p['tanpa_nomor_dada'] > 0
        ? $p['tanpa_nomor_dada'] . ' atlet yang belum punya nomor atlet (BIB) akan mendapat nomor baru, dan nomor itu tidak bisa ditarik kembali.'
        : 'Semua atlet sudah punya nomor atlet (BIB).')
    . ' Pastikan transfernya sudah diperiksa.';

$kalimatMenunggu = 'Status pendaftaran ' . $namaSingkat . ' akan kembali menjadi Menunggu Konfirmasi Pembayaran. '
    . 'Nomor atlet (BIB) tetap dipegang atletnya dan tidak dihapus.';

$kalimatHapus = 'Pendaftaran ' . $p['id_pendaftaran'] . ' dari ' . $p['nama_sekolah'] . ' beserta ' . $p['jumlah_atlet']
    . ' atlet, ofisial, tim beregu, foto, dan bukti bayarnya akan dihapus. NISN atletnya bisa didaftarkan lagi.'
    . ($adaNomor ? ' Nomor atlet (BIB) yang sudah dibagikan tidak dipakai ulang.' : '')
    . ' Tindakan ini tidak bisa dibatalkan.'
    . ($kalimatTimHapus === '' ? '' : ' ' . $kalimatTimHapus);
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('tabel') ?>ya<?= $this->endSection() ?>

<?= $this->section('judul') ?>Pendaftaran <?= esc($p['id_pendaftaran']) ?><?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Rincian pendaftaran<?= $this->endSection() ?>

<?= $this->section('jejak') ?><?= view('admin/_jejak_tw', ['jejak' => [$asal, ['label' => $p['id_pendaftaran']]]], ['saveData' => false]) ?><?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-4 p-4 sm:px-6 sm:py-4" id="rincian-pendaftaran" data-id="<?= (int) $p['id'] ?>" data-status="<?= esc($p['status'], 'attr') ?>">
    <?php if (! $lunas && $p['atlet'] === [] && $atletDitolak !== []) : ?>
        <?php // Semua atlet ditolak (K117): sekolah melihat "Semua atlet ditolak" tanpa tagihan. ?>
        <div class="flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-900" role="status" id="peringatan-ditolak-semua">
            <?= ikon('user-x', 'mt-0.5 shrink-0') ?>
            <div>
                <strong>Semua atlet pendaftaran ini ditolak.</strong>
                Di Cek Pendaftaran statusnya "Semua atlet ditolak" dan sekolah tidak diminta membayar.
                Pendaftaran tetap disimpan beserta ofisial dan bukti bayarnya untuk urusan pengembalian uang.
            </div>
        </div>
    <?php endif ?>

    <section class="<?= $kartu ?>" aria-labelledby="judul-pendaftaran">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0 space-y-2">
                <h2 id="judul-pendaftaran" class="text-lg font-bold text-gray-900"><?= esc($p['id_pendaftaran']) ?></h2>
                <p class="text-lg text-gray-900"><?= esc($p['nama_sekolah']) ?></p>
                <?php if (! empty($usulanNama)) : ?>
                    <p class="text-sm text-gray-700" id="usulan-nama-pendaftaran">
                        <?= komponen('lencana', ['label' => 'Nama usulan pendaftar', 'warna' => 'biru']) ?>
                        Nama di data sekolah: <?= esc($usulanNama['nama_lama']) ?>.
                        <a class="<?= $tautan ?>" href="<?= site_url('admin/sekolah/usulan') ?>">Setujui atau tolak usulannya</a>.
                    </p>
                <?php endif ?>
                <p id="status-pendaftaran">
                    <?php if ($lunas) : ?>
                        <?= komponen('lencana', ['label' => 'Lunas', 'warna' => 'hijau', 'ikon' => 'circle-check']) ?>
                    <?php else : ?>
                        <?= komponen('lencana', ['label' => 'Menunggu Konfirmasi Pembayaran', 'warna' => 'kuning', 'ikon' => 'hourglass']) ?>
                    <?php endif ?>
                </p>
            </div>

            <div class="flex flex-wrap gap-2" id="tindakan-pendaftaran">
                <?php if ($lunas) : ?>
                    <form action="<?= site_url($alamat . '/menunggu') ?>" method="post"
                        data-konfirmasi="<?= esc($kalimatMenunggu, 'attr') ?>"
                        data-konfirmasi-judul="Kembalikan ke Menunggu?"
                        data-konfirmasi-tombol="Kembalikan ke Menunggu"
                        data-konfirmasi-jenis="utama">
                        <?= csrf_field() ?>
                        <?= komponen('tombol', ['label' => 'Kembalikan ke Menunggu', 'tipe' => 'submit', 'varian' => 'kedua', 'ikon' => 'rotate-ccw']) ?>
                    </form>
                <?php else : ?>
                    <form action="<?= site_url($alamat . '/lunas') ?>" method="post"
                        data-konfirmasi="<?= esc($kalimatLunas, 'attr') ?>"
                        data-konfirmasi-judul="Tandai Lunas?"
                        data-konfirmasi-tombol="Tandai Lunas"
                        data-konfirmasi-jenis="utama">
                        <?= csrf_field() ?>
                        <?= komponen('tombol', ['label' => 'Tandai Lunas', 'tipe' => 'submit', 'ikon' => 'circle-check']) ?>
                    </form>
                <?php endif ?>

                <?php if (! $lunas && $p['atlet'] !== []) : ?>
                    <?= komponen('tombol', ['label' => 'Tolak atlet', 'href' => site_url($alamat . '/tolak-atlet'), 'varian' => 'kedua', 'ikon' => 'user-x', 'kelas' => 'text-red-700!', 'atribut' => ['id' => 'tombol-tolak-atlet']]) ?>
                <?php endif ?>

                <?= komponen('tombol', ['label' => 'Ubah', 'href' => site_url($alamat . '/ubah'), 'varian' => 'kedua', 'ikon' => 'square-pen', 'atribut' => ['id' => 'tombol-ubah']]) ?>

                <form action="<?= site_url($alamat . '/hapus') ?>" method="post"
                    data-konfirmasi="<?= esc($kalimatHapus, 'attr') ?>"
                    data-konfirmasi-judul="Hapus pendaftaran?"
                    data-konfirmasi-tombol="Hapus pendaftaran">
                    <?= csrf_field() ?>
                    <?= komponen('tombol', ['label' => 'Hapus', 'tipe' => 'submit', 'varian' => 'kedua', 'ikon' => 'trash-2', 'kelas' => 'text-red-700!']) ?>
                </form>
            </div>
        </div>

        <dl class="mt-4 grid gap-x-4 text-sm sm:grid-cols-[12rem_1fr] sm:gap-y-1">
            <dt class="<?= $dt ?>">Tanggal daftar</dt>
            <dd class="<?= $dd ?>"><?= esc(tanggal_id($p['dibuat_pada'], true)) ?: $strip ?></dd>
            <dt class="<?= $dt ?>">Waktu lunas</dt>
            <dd class="<?= $dd ?>" id="waktu-lunas"><?= $lunas && $p['waktu_lunas'] !== null ? esc(tanggal_id($p['waktu_lunas'], true)) : '<span class="text-gray-500">Belum lunas</span>' ?></dd>
            <?php if ($p['gabungan_dari'] !== []): ?>
                <dt class="<?= $dt ?>">Gabungan dari</dt>
                <dd class="<?= $dd ?>" id="gabungan-dari"><?= esc(implode(', ', $p['gabungan_dari'])) ?></dd>
            <?php endif ?>
        </dl>
    </section>

    <div class="grid gap-4 xl:grid-cols-12">
        <section class="<?= $kartu ?> xl:col-span-5" aria-labelledby="judul-sekolah">
            <h2 id="judul-sekolah" class="<?= $judulKartu ?> mb-3">Sekolah</h2>
            <dl class="grid gap-x-4 sm:grid-cols-[9rem_1fr] sm:gap-y-1">
                <dt class="<?= $dt ?>">Nama</dt>
                <dd class="<?= $dd ?>"><?= esc($p['nama_sekolah']) ?></dd>
                <dt class="<?= $dt ?>">NPSN</dt>
                <dd class="<?= $dd ?>"><?= $p['npsn'] === null ? '<span class="text-gray-500">Tanpa NPSN</span>' : esc($p['npsn']) ?></dd>
                <dt class="<?= $dt ?>">Jenjang</dt>
                <dd class="<?= $dd ?>"><?= esc($p['jenjang_label']) ?></dd>
                <dt class="<?= $dt ?>">Kabupaten/Kota</dt>
                <dd class="<?= $dd ?>"><?= esc($p['kabupaten']) ?></dd>
            </dl>
        </section>

        <section class="<?= $kartu ?> xl:col-span-7" aria-labelledby="judul-penanggung-jawab">
            <h2 id="judul-penanggung-jawab" class="<?= $judulKartu ?> mb-3">Ofisial</h2>
            <?php if ($p['penanggung_jawab'] === []) : ?>
                <p class="text-gray-600">Belum ada ofisial.</p>
            <?php else : ?>
                <ul class="grid gap-4 sm:grid-cols-2">
                    <?php foreach ($p['penanggung_jawab'] as $orang) : ?>
                        <li class="flex items-start gap-3" data-penanggung-jawab data-id="<?= (int) $orang['id'] ?>">
                            <div class="w-24 shrink-0">
                                <?php if ($orang['ada_foto']) : ?>
                                    <?php $alamatFoto = site_url($alamat . '/foto/' . (int) $orang['id']); $idFoto = 'foto-pj-' . (int) $orang['id']; ?>
                                    <button type="button" class="group relative block w-full overflow-hidden rounded-lg border border-gray-200 focus:outline-none focus:ring-4 focus:ring-primary-200" data-modal-target="<?= $idFoto ?>" data-modal-toggle="<?= $idFoto ?>" title="Perbesar foto">
                                        <img class="block h-auto w-full" loading="lazy" src="<?= $alamatFoto ?>" alt="<?= esc('Foto ' . $orang['nama'], 'attr') ?>">
                                        <span class="absolute bottom-1 end-1 inline-flex size-7 items-center justify-center rounded-full bg-white/90 text-gray-700 shadow group-hover:text-primary-700" aria-hidden="true"><?= ikon('zoom-in', 'size-4') ?></span>
                                        <span class="sr-only">Perbesar foto <?= esc($orang['nama']) ?></span>
                                    </button>
                                    <?= komponen('modal', [
                                        'id'    => $idFoto,
                                        'judul' => 'Foto ' . $orang['nama'],
                                        'lebar' => 'lebar',
                                        'isi'   => '<div class="rounded-lg bg-gray-50 p-2 text-center"><img class="mx-auto h-auto max-h-[70vh] max-w-full rounded-lg" loading="lazy" src="' . $alamatFoto . '" alt="' . esc('Foto ' . $orang['nama'], 'attr') . '"></div>',
                                        'kaki'  => komponen('tombol', ['label' => 'Buka di tab baru', 'href' => $alamatFoto, 'varian' => 'kedua', 'ikon' => 'external-link', 'atribut' => ['target' => '_blank', 'rel' => 'noopener']])
                                            . komponen('tombol', ['label' => 'Tutup', 'atribut' => ['data-modal-hide' => $idFoto]]),
                                    ]) ?>
                                <?php else : ?>
                                    <?= view('tata_letak/_tanpa_gambar_tw', ['rasio' => '1x1', 'label' => 'Foto ' . $orang['nama']], ['saveData' => false]) ?>
                                <?php endif ?>
                            </div>
                            <div class="min-w-0">
                                <p class="mb-1 font-semibold text-gray-900"><?= esc($orang['nama']) ?></p>
                                <p class="flex items-center gap-1.5 whitespace-nowrap text-gray-700">
                                    <?= ikon('phone', 'size-4 text-gray-500') ?>
                                    <span class="sr-only">Nomor WA</span>
                                    <?= esc($orang['nomor_wa_tampil']) ?>
                                </p>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif ?>
        </section>
    </div>

    <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-lembut" aria-labelledby="judul-atlet">
        <div class="flex flex-wrap items-baseline justify-between gap-2 px-4 pb-3 pt-4 sm:px-5">
            <h2 id="judul-atlet" class="<?= $judulKartu ?>">Atlet <span class="font-normal text-gray-500">(<?= (int) $p['jumlah_atlet'] ?>)</span></h2>
            <?php if (! $lunas && $p['tanpa_nomor_dada'] > 0) : ?>
                <p class="text-sm text-gray-600">Nomor atlet (BIB) dibuat saat pendaftaran ditandai Lunas.</p>
            <?php elseif ($lunas && ($diurutkanPada = App\Libraries\Panel\UrutkanNomorDada::waktuTerakhir()) !== null) : ?>
                <?php // K111: nomor dada sudah diurutkan ulang per sekolah. ?>
                <p class="text-sm text-gray-600" id="keterangan-nomor-dada">
                    Nomor atlet (BIB) diurutkan ulang per sekolah pada <?= esc(tanggal_id($diurutkanPada)) ?>.
                    <a class="<?= $tautan ?>" href="<?= site_url('admin/atlet/nomor-dada') ?>">Urutkan nomor atlet (BIB)</a>
                </p>
            <?php endif ?>
        </div>
        <?php if ($p['atlet'] === []) : ?>
            <p class="px-4 pb-5 text-gray-600 sm:px-5">Pendaftaran ini belum punya atlet.</p>
        <?php else : ?>
            <div class="relative overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-700" id="tabel-atlet" data-tabel-klien>
                    <thead>
                        <tr>
                            <th scope="col" class="<?= $th ?> text-end">No</th>
                            <th scope="col" class="<?= $th ?>">Nama</th>
                            <th scope="col" class="<?= $th ?>">NISN</th>
                            <th scope="col" class="<?= $th ?>">Tanggal lahir</th>
                            <th scope="col" class="<?= $th ?>">Jenis kelamin</th>
                            <th scope="col" class="<?= $th ?>">Kelompok umur</th>
                            <th scope="col" class="<?= $th ?>">Nomor lomba</th>
                            <th scope="col" class="<?= $th ?>">Nomor atlet (BIB)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($p['atlet'] as $i => $atlet) : ?>
                            <tr data-id="<?= (int) $atlet['id'] ?>">
                                <td class="<?= $td ?> text-end text-gray-500"><?= $i + 1 ?></td>
                                <th scope="row" class="<?= $td ?> font-semibold text-gray-900"><?= esc($atlet['nama']) ?></th>
                                <td class="<?= $td ?> whitespace-nowrap"><?= esc($atlet['nisn']) ?></td>
                                <td class="<?= $td ?> whitespace-nowrap"><?= esc(tanggal_id($atlet['tanggal_lahir'])) ?></td>
                                <td class="<?= $td ?>"><?= esc($atlet['jenis_kelamin_label']) ?></td>
                                <td class="<?= $td ?>">
                                    <span class="whitespace-nowrap"><?= esc($atlet['kelompok_umur']) ?></span>
                                    <?php $cocok = $kecocokan[(int) $atlet['id']] ?? null; ?>
                                    <?php if ($cocok !== null && ! $cocok['cocok']) : ?>
                                        <div class="mt-1 flex items-start gap-1 text-sm text-red-800" data-ku-tidak-cocok>
                                            <?= ikon('triangle-alert', 'mt-0.5 size-4 shrink-0') ?>
                                            <span>Tidak cocok dengan pengaturan sekarang<?= $cocok['sesuai_id'] === null ? '' : ', seharusnya ' . esc($cocok['sesuai']) ?>.</span>
                                        </div>
                                    <?php endif ?>
                                </td>
                                <td class="<?= $td ?>">
                                    <?php if ($atlet['nomor_lomba'] === []) : ?>
                                        <?= $strip ?>
                                    <?php else : ?>
                                        <ul class="space-y-1">
                                            <?php foreach ($atlet['nomor_lomba'] as $nomor) : ?>
                                                <li>
                                                    <?= esc($nomor['nama']) ?>
                                                    <?php if ($nomor['jenis'] === 'beregu') : ?>
                                                        <span class="<?= $lencanaRegu ?>"><?= esc(Regu::label((string) $nomor['regu'])) ?> · <?= esc(Regu::labelPeran($nomor['peran'])) ?></span>
                                                    <?php endif ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif ?>
                                </td>
                                <td class="<?= $td ?>" data-nomor-dada>
                                    <?php if ($atlet['nomor_dada'] === null) : ?>
                                        <?= $strip ?><span class="sr-only">Belum ada nomor atlet (BIB)</span>
                                    <?php else : ?>
                                        <span class="text-lg font-bold text-gray-900"><?= esc($atlet['nomor_dada']) ?></span>
                                    <?php endif ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif ?>
    </section>

    <?php if ($atletDitolak !== []) : ?>
        <section class="overflow-hidden rounded-2xl border border-red-200 bg-white shadow-lembut" aria-labelledby="judul-atlet-ditolak" id="atlet-ditolak">
            <div class="px-4 pb-3 pt-4 sm:px-5">
                <h2 id="judul-atlet-ditolak" class="<?= $judulKartu ?>">Atlet ditolak <span class="font-normal text-gray-500">(<?= count($atletDitolak) ?>)</span></h2>
                <p class="text-sm text-gray-600">Tampil di Cek Pendaftaran sampai NISN-nya didaftarkan lagi. <a class="<?= $tautan ?>" href="<?= site_url('admin/atlet-ditolak') ?>">Lihat semua atlet ditolak</a></p>
            </div>
            <div class="relative overflow-x-auto">
                <table class="w-full text-left text-sm text-gray-700" id="tabel-atlet-ditolak" data-tabel-klien>
                    <thead>
                        <tr>
                            <th scope="col" class="<?= $th ?>">Nama</th>
                            <th scope="col" class="<?= $th ?>">NISN</th>
                            <th scope="col" class="<?= $th ?>">Tanggal lahir</th>
                            <th scope="col" class="<?= $th ?>">Kelompok umur</th>
                            <th scope="col" class="<?= $th ?>">Nomor lomba</th>
                            <th scope="col" class="<?= $th ?> min-w-64">Alasan</th>
                            <th scope="col" class="<?= $th ?>">Ditolak</th>
                            <th scope="col" class="<?= $th ?>">Keadaan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($atletDitolak as $d) : ?>
                            <tr data-id="<?= (int) $d['id'] ?>">
                                <th scope="row" class="<?= $td ?> font-semibold text-gray-900"><?= esc($d['nama']) ?></th>
                                <td class="<?= $td ?> whitespace-nowrap"><?= esc($d['nisn']) ?></td>
                                <td class="<?= $td ?> whitespace-nowrap"><?= esc(tanggal_id($d['tanggal_lahir'])) ?></td>
                                <td class="<?= $td ?>">
                                    <span class="whitespace-nowrap"><?= esc($d['kelompok_umur']) ?></span>
                                    <?php if ($d['sesuai_beda']) : ?>
                                        <div class="text-sm text-gray-500">Sesuai: <?= esc($d['kelompok_umur_sesuai']) ?></div>
                                    <?php endif ?>
                                </td>
                                <td class="<?= $td ?> min-w-36"><?= esc(implode(', ', array_column($d['nomor_lomba'], 'nama'))) ?: $strip ?></td>
                                <td class="<?= $td ?>"><?= nl2br(esc($d['alasan'])) ?></td>
                                <td class="<?= $td ?> whitespace-nowrap"><?= esc(tanggal_id($d['dibuat_pada'], true)) ?><br><span class="text-gray-500"><?= esc($d['ditolak_oleh'] ?? '') ?></span></td>
                                <td class="<?= $td ?>">
                                    <?php if ($d['didaftarkan_ulang'] !== null) : ?>
                                        <?= komponen('lencana', ['label' => 'Sudah didaftarkan ulang', 'warna' => 'hijau']) ?>
                                        <div class="mt-1 whitespace-nowrap"><?= esc($d['didaftarkan_ulang']) ?></div>
                                    <?php else : ?>
                                        <?= komponen('lencana', ['label' => 'Belum didaftarkan ulang', 'warna' => 'kuning']) ?>
                                    <?php endif ?>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif ?>

    <?php if ($p['beregu'] !== []) : ?>
        <section class="<?= $kartu ?>" aria-labelledby="judul-beregu">
            <h2 id="judul-beregu" class="<?= $judulKartu ?> mb-3">Regu beregu</h2>
            <div class="grid gap-4 lg:grid-cols-2">
                <?php foreach ($p['beregu'] as $tim) : ?>
                    <?php $kunci = (string) $tim['kunci'] ?>
                    <div class="h-full space-y-2 rounded-xl border border-gray-200 p-4" data-tim-beregu>
                        <div class="flex flex-wrap justify-between gap-2">
                            <h3 class="font-bold text-gray-900"><?= esc($tim['nomor_lomba']) ?></h3>
                            <span class="text-sm">
                                <?= (int) $tim['biaya_tim'] > 0
                                    ? '<span class="whitespace-nowrap">' . esc(rupiah((int) $tim['biaya_tim'])) . '</span>'
                                    : '<span class="text-gray-500">Tanpa biaya regu (tambahan anggota regu yang sudah terdaftar)</span>' ?>
                            </span>
                        </div>
                        <?php if (isset($timTanpaPembayar[$kunci])) : ?>
                            <div class="flex items-start gap-2 rounded-lg border border-yellow-200 bg-yellow-50 px-3 py-2 text-sm text-yellow-900" data-tim-tanpa-pembayar data-regu="<?= esc($kunci, 'attr') ?>">
                                <?= ikon('triangle-alert', 'mt-0.5 size-4 shrink-0') ?>
                                <p>
                                    Regu sekolah ini tidak punya pembayar biaya regu (<?= esc(rupiah((int) $timTanpaPembayar[$kunci]['tarif'])) ?>), karena pendaftaran yang membayarnya sudah dihapus atau diubah.
                                    Tagihkan lewat <a class="font-semibold underline hover:no-underline" href="<?= site_url($alamat . '/ubah') ?>">Ubah</a> dengan mencentang "Bebankan biaya regu ke pendaftaran ini".
                                </p>
                            </div>
                        <?php endif ?>
                        <?php if ($tim['kurang_inti']) : ?>
                            <div class="flex items-start gap-2 rounded-lg border border-yellow-200 bg-yellow-50 px-3 py-2 text-sm text-yellow-900" data-regu-kurang-inti data-regu="<?= esc($kunci, 'attr') ?>">
                                <?= ikon('triangle-alert', 'mt-0.5 size-4 shrink-0') ?>
                                <p>
                                    Regu ini baru punya <?= (int) $tim['inti_regu'] ?> atlet inti dari <?= (int) $tim['jumlah_inti'] ?>.
                                    Tandai atlet cadangan sebagai Inti lewat Ubah, atau hubungi sekolahnya.
                                </p>
                            </div>
                        <?php endif ?>
                        <p class="text-sm text-gray-600">
                            <?= count($tim['anggota']) ?> anggota di pendaftaran ini
                            <?php if ((int) $tim['anggota_regu'] !== count($tim['anggota'])) : ?>
                                · seluruh regu <?= (int) $tim['anggota_regu'] ?> atlet, <?= (int) $tim['inti_regu'] ?> inti
                            <?php endif ?>
                        </p>
                        <ol class="list-decimal space-y-1 ps-5 text-gray-800">
                            <?php foreach ($tim['anggota'] as $anggota) : ?>
                                <li>
                                    <?= esc($anggota['nama']) ?>
                                    <?php if ($anggota['peran'] === Regu::INTI) : ?>
                                        <span class="inline-flex items-center rounded-md bg-primary-100 px-2 py-0.5 text-xs font-semibold text-primary-800"><?= esc(Regu::labelPeran($anggota['peran'])) ?></span>
                                    <?php else : ?>
                                        <span class="<?= $lencanaRegu ?>"><?= esc(Regu::labelPeran($anggota['peran'])) ?></span>
                                    <?php endif ?>
                                    <span class="text-sm text-gray-500">(<?= esc($anggota['nisn']) ?><?= $anggota['nomor_dada'] === null ? '' : ', nomor atlet (BIB) ' . esc($anggota['nomor_dada']) ?>)</span>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif ?>

    <section class="<?= $kartu ?>" aria-labelledby="judul-biaya">
        <h2 id="judul-biaya" class="<?= $judulKartu ?> mb-3">Biaya</h2>
        <dl class="grid grid-cols-[1fr_auto] gap-x-4 gap-y-1 sm:grid-cols-[12rem_1fr]" id="biaya-pendaftaran">
            <dt class="text-gray-700">Subtotal individu</dt>
            <dd class="text-end text-gray-900 sm:text-start"><?= esc(rupiah($p['subtotal_individu'])) ?></dd>
            <dt class="text-gray-700">Subtotal beregu</dt>
            <dd class="text-end text-gray-900 sm:text-start"><?= esc(rupiah($p['subtotal_beregu'])) ?></dd>
            <dt class="font-bold text-gray-900">Total</dt>
            <dd class="text-end text-lg font-bold text-gray-900 sm:text-start"><?= esc(rupiah($p['total'])) ?></dd>
        </dl>
    </section>

    <?= view('admin/pendaftaran/_bukti_bayar', ['pendaftaran' => $p], ['saveData' => false]) ?>

    <section class="<?= $kartu ?>" aria-labelledby="judul-riwayat" id="riwayat">
        <h2 id="judul-riwayat" class="<?= $judulKartu ?>">Riwayat</h2>
        <p class="mb-2 text-sm text-gray-600">Tindakan admin pada pendaftaran ini, terbaru di atas.</p>
        <?= view('admin/pendaftaran/_riwayat', ['riwayat' => $riwayat, 'idSekarang' => (string) $p['id_pendaftaran']], ['saveData' => false]) ?>
    </section>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<?= $this->endSection() ?>
