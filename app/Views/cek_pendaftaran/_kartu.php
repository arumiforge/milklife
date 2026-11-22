<?php
/**
 * Satu kartu pendaftaran di halaman cek (tanpa tanggal lahir, K52): kepala (ID, tanggal, status,
 * tombol bukti), penanggung jawab, atlet, tim beregu, dan biaya. Angka biaya
 * adalah nilai tersimpan (rancangan 6.7). Tampilan Tailwind (F9-05); keadaan
 * kartu juga tertulis di `data-status-kartu` (lunas, menunggu, ditolak).
 *
 * @var array<string, mixed> $p      satu pendaftaran (App\Libraries\DataPendaftaran)
 * @var int                  $urutan nomor kartu, mulai 1
 * @var bool                 $banyak lebih dari satu pendaftaran ditampilkan
 * @var string               $kode   isian pencarian, tujuan kembali setelah unggah bukti bayar
 * @var string|null          $waTanya alamat WA tanya admin, atau null
 *
 * Atlet yang ditolak panitia (K116, K117) tampil di bagian "Perlu didaftarkan
 * ulang" (`cek_pendaftaran/_ditolak`) di atas tabel atlet. Bila semua atlet
 * ditolak, kartu berstatus "Semua atlet ditolak": tabel atlet, regu, rincian
 * biaya, dan kotak bukti bayar tidak ditampilkan, karena tidak ada tagihan.
 *
 * Pendaftaran yang belum Lunas punya kotak unggah bukti bayar (K83).
 */

use App\Libraries\Pendaftaran\KelasTampilan as K;

$idJudul = 'pendaftaran-' . $urutan;
$lunas   = $p['status'] === 'lunas';
// Semua atlet ditolak panitia (K117): tidak ada atlet dan tidak ada tagihan.
$ditolakSemua  = (bool) ($p['ditolak_semua'] ?? false);
$jumlahDitolak = count($p['atlet_ditolak'] ?? []);
$keadaan       = $lunas ? 'lunas' : ($ditolakSemua ? 'ditolak' : 'menunggu');
$gayaKeadaan   = [
    'lunas'    => ['border-t-green-600', 'bg-green-100 text-green-800', 'circle-check'],
    'menunggu' => ['border-t-oranye-500', 'bg-yellow-100 text-yellow-900', 'hourglass'],
    'ditolak'  => ['border-t-red-600', 'bg-red-100 text-red-800', 'circle-x'],
][$keadaan];
$jumlahBukti = (int) ($p['jumlah_bukti_bayar'] ?? 0);
// Nomor dada tampil setelah Lunas (K88); sebelumnya tanda pisah.
$dada = static fn (array $a): string => $lunas && ($a['nomor_dada'] ?? null) !== null
    ? '<strong class="text-primary-900">' . esc((string) $a['nomor_dada']) . '</strong>'
    : '<span class="text-gray-400" title="Nomor atlet (BIB) muncul setelah pembayaran Lunas">–</span>';
$batasBukti = App\Models\BuktiBayarModel::batas();
$subjudul   = 'cek-subjudul mt-6 mb-3 flex items-center gap-2 font-bold text-primary-900';
$jumlah     = 'cek-jumlah rounded-full bg-primary-100 px-2 py-0.5 text-xs font-semibold text-primary-800';
$tabel      = 'w-full text-left text-sm text-gray-800 [&_td]:px-3 [&_td]:py-2.5 [&_th]:px-3 [&_th]:py-2.5';
// ikon-sprite: circle-check hourglass circle-x
?>
<article class="cek-kartu cek-kartu-<?= $keadaan ?> overflow-hidden rounded-2xl border border-gray-200 border-t-4 <?= $gayaKeadaan[0] ?> bg-white shadow-lembut" aria-labelledby="<?= $idJudul ?>" data-status-kartu="<?= $keadaan ?>">
    <div class="cek-kartu-kepala flex flex-wrap items-start justify-between gap-4 border-b border-gray-100 bg-primary-50/60 p-4 sm:px-6">
        <div class="min-w-0">
            <?php if ($banyak) : ?><p class="cek-kartu-urutan text-xs font-semibold uppercase tracking-wide text-oranye-700">Pendaftaran <?= $urutan ?></p><?php endif ?>
            <h3 class="cek-kartu-id text-xl font-extrabold tracking-wide break-all text-primary-900" id="<?= $idJudul ?>"><span class="sr-only">ID </span><?= esc($p['id_pendaftaran']) ?></h3>
            <?php if (($p['gabungan_dari'] ?? []) !== []) : ?><p class="mt-1 text-sm text-gray-600">Gabungan dari <?= esc(implode(', ', $p['gabungan_dari'])) ?></p><?php endif ?>
            <p class="mt-1 flex items-center gap-1.5 text-sm text-gray-600"><?= ikon('clock', 'size-4') ?> Didaftarkan <?= esc(tanggal_id($p['dibuat_pada'], true)) ?></p>
        </div>
        <div class="cek-kartu-tindakan flex flex-wrap items-center gap-2">
            <span class="cek-status inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-semibold <?= $gayaKeadaan[1] ?>" data-status="<?= $keadaan ?>">
                <?= ikon($gayaKeadaan[2], 'size-4') ?>
                <?= esc($p['status_label']) ?>
            </span>
            <?php if (! $ditolakSemua && $jumlahDitolak > 0) : ?>
                <a class="cek-status cek-status-ditolak inline-flex min-h-8 items-center gap-1.5 rounded-full bg-red-100 px-3 py-1 text-sm font-semibold text-red-800 hover:bg-red-200" href="#atlet-ditolak-<?= $urutan ?>">
                    <?= ikon('user-x', 'size-4') ?> <?= $jumlahDitolak ?> atlet ditolak
                </a>
            <?php endif ?>
            <a class="<?= K::KEDUA ?>" href="<?= esc(App\Libraries\BuktiPendaftaran::alamat($p), 'attr') ?>" target="_blank" rel="noopener">
                <?= ikon('printer', 'size-5') ?> <?= $ditolakSemua ? 'Cetak keterangan' : 'Cetak bukti' ?>
            </a>
        </div>
    </div>

    <div class="p-4 sm:px-6 sm:pb-6">
        <h4 class="<?= $subjudul ?> !mt-0"><?= ikon('id-card', 'size-5 text-primary-700') ?> Ofisial</h4>
        <ul class="cek-penanggung-jawab grid gap-2 sm:grid-cols-2">
            <?php foreach ($p['penanggung_jawab'] as $pj) : ?>
                <li class="flex flex-wrap items-center gap-x-3 gap-y-1 rounded-lg bg-gray-50 px-3 py-2"><?= ikon('circle-user', 'size-5 text-gray-400') ?> <strong class="text-gray-900"><?= esc($pj['nama']) ?></strong> <span class="flex items-center gap-1 text-sm text-gray-600"><?= ikon('message-circle', 'size-4 text-green-700') ?> <?= esc($pj['nomor_wa']) ?></span></li>
            <?php endforeach ?>
        </ul>

        <?= view('cek_pendaftaran/_ditolak', ['p' => $p, 'urutan' => $urutan, 'waTanya' => $waTanya ?? null, 'ditolakSemua' => $ditolakSemua], ['saveData' => false]) ?>

        <?php if (! $ditolakSemua) : ?>
        <h4 class="<?= $subjudul ?>"><?= ikon('users', 'size-5 text-primary-700') ?> Atlet <span class="<?= $jumlah ?>"><?= count($p['atlet']) ?></span></h4>
        <?php $keteranganNomorDada = App\Libraries\Panel\UrutkanNomorDada::keterangan((string) $p['status']); ?>
        <?php if ($keteranganNomorDada !== null) : ?>
            <p class="keterangan-nomor-dada mb-3 flex gap-2 rounded-lg bg-primary-100 px-3 py-2 text-sm text-primary-900" role="note"><?= ikon('info', 'size-4 mt-0.5') ?> <?= esc($keteranganNomorDada) ?></p>
        <?php endif ?>
        <div class="overflow-x-auto rounded-xl border border-gray-200">
            <table class="cek-tabel <?= $tabel ?>">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th scope="col">No</th>
                        <th scope="col">Nama</th>
                        <th scope="col">NISN</th>
                        <th scope="col">L/P</th>
                        <th scope="col" class="whitespace-nowrap">No. Atlet</th>
                        <th scope="col">Kelompok umur</th>
                        <th scope="col">Nomor lomba</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($p['atlet'] as $i => $a) : ?>
                        <tr class="hover:bg-gray-50">
                            <td class="text-gray-500"><?= $i + 1 ?></td>
                            <td class="font-semibold text-gray-900"><?= esc($a['nama']) ?></td>
                            <td><?= esc($a['nisn']) ?></td>
                            <td><abbr class="kategori kategori-<?= $a['jenis_kelamin'] === 'P' ? 'putri' : 'putra' ?> inline-flex size-7 items-center justify-center rounded-full text-xs font-bold no-underline <?= $a['jenis_kelamin'] === 'P' ? 'bg-magenta-500/10 text-magenta-600' : 'bg-primary-100 text-primary-800' ?>" title="<?= esc($a['jenis_kelamin_label'], 'attr') ?>"><?= esc($a['jenis_kelamin']) ?></abbr></td>
                            <td><?= $dada($a) ?></td>
                            <td class="whitespace-nowrap"><?= esc($a['kelompok_umur']) ?></td>
                            <td><?= esc(implode(', ', $a['nomor_lomba'])) ?></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
        <?php endif ?>

        <?php if ($p['beregu'] !== []) : ?>
            <h4 class="<?= $subjudul ?>"><?= ikon('flag', 'size-5 text-primary-700') ?> Regu beregu <span class="<?= $jumlah ?>"><?= count($p['beregu']) ?></span></h4>
            <ul class="cek-tim space-y-3">
                <?php foreach ($p['beregu'] as $tim) : ?>
                    <li class="rounded-xl border border-gray-200 p-3">
                        <div class="cek-tim-kepala flex flex-wrap items-center justify-between gap-2">
                            <strong class="text-primary-900"><?= esc($tim['nomor_lomba']) ?></strong>
                            <span class="cek-tim-biaya rounded-full bg-oranye-50 px-2.5 py-0.5 text-xs font-semibold text-oranye-800"><?= $tim['biaya_tim'] === null ? 'Tanpa biaya, regu sudah terdaftar' : esc(rupiah($tim['biaya_tim'])) ?></span>
                        </div>
                        <?php // Satu nama per baris dengan nomor dada (K88). ?>
                        <div class="mt-2 overflow-x-auto">
                            <table class="cek-tim-anggota <?= $tabel ?> [&_td]:py-1.5 [&_th]:py-1.5">
                                <thead class="border-b border-gray-200 text-xs uppercase text-gray-500"><tr><th scope="col">No</th><th scope="col">Nama</th><th scope="col">NISN</th><th scope="col">Peran</th><th scope="col" class="whitespace-nowrap">No. Atlet</th></tr></thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php foreach ($tim['anggota'] as $j => $anggota) : ?>
                                        <tr>
                                            <td class="text-gray-500"><?= $j + 1 ?></td>
                                            <td class="font-semibold text-gray-900"><?= esc($anggota['nama']) ?></td>
                                            <td><?= esc($anggota['nisn']) ?></td>
                                            <td><?= ($anggota['peran'] ?? '') === 'cadangan' ? 'Cadangan' : 'Inti' ?></td>
                                            <td><?= $dada($anggota) ?></td>
                                        </tr>
                                    <?php endforeach ?>
                                </tbody>
                            </table>
                        </div>
                    </li>
                <?php endforeach ?>
            </ul>
        <?php endif ?>

        <?php $dl = 'cek-biaya mt-6 rounded-xl bg-primary-50 p-4 text-sm [&>div]:flex [&>div]:justify-between [&>div]:gap-4 [&>div]:py-1 [&_dd]:text-end [&_dd]:font-semibold [&_dt]:text-gray-600'; ?>
        <?php $totalPolos = 'cek-biaya-total text-lg text-primary-900 [&_dd]:font-extrabold! [&_dt]:font-bold [&_dt]:text-primary-900!'; ?>
        <?php $total = $totalPolos . ' mt-1 border-t border-primary-200 pt-2!'; ?>
        <?php if ($ditolakSemua) : ?>
            <dl class="<?= $dl ?>">
                <div class="<?= $totalPolos ?>"><dt>Total</dt><dd><?= esc(rupiah($p['total'])) ?></dd></div>
                <div><dt>Keterangan</dt><dd>Pendaftaran ini tidak perlu dibayar</dd></div>
            </dl>
        <?php else : ?>
            <dl class="<?= $dl ?>">
                <div><dt>Subtotal individu</dt><dd><?= esc(rupiah($p['subtotal_individu'])) ?></dd></div>
                <div><dt>Subtotal beregu</dt><dd><?= esc(rupiah($p['subtotal_beregu'])) ?></dd></div>
                <div class="<?= $total ?>"><dt>Total</dt><dd><?= esc(rupiah($p['total'])) ?></dd></div>
            </dl>
        <?php endif ?>

        <?php if (! $lunas && ! $ditolakSemua) : ?>
            <div class="cek-unggah-bukti mt-6 rounded-xl border border-dashed border-gray-300 bg-oranye-50/50 p-4">
                <h4 class="<?= $subjudul ?> !mt-0"><?= ikon('receipt', 'size-5 text-primary-700') ?> Bukti bayar <span class="<?= $jumlah ?>"><?= $jumlahBukti ?></span></h4>
                <?php if ($jumlahBukti >= $batasBukti) : ?>
                    <p class="text-gray-800">Anda sudah mengirim <?= $jumlahBukti ?> gambar, jumlah paling banyak yang diterima. Panitia akan memeriksanya.</p>
                <?php else : ?>
                    <p class="mb-3 text-sm text-gray-600"><?= $jumlahBukti === 0 ? 'Belum ada bukti bayar.' : 'Sudah ' . $jumlahBukti . ' gambar terkirim.' ?> Unggah foto atau tangkapan layar bukti transfer di sini. Anda tidak perlu mengirimnya lewat WhatsApp.</p>
                    <form method="post" action="<?= esc(App\Libraries\BuktiPendaftaran::alamat($p) . '/bukti-bayar', 'attr') ?>" enctype="multipart/form-data"
                        data-konfirmasi-judul="Kirim bukti bayar?"
                        data-konfirmasi="<?= esc('Gambar ini dikirim ke panitia sebagai bukti transfer pendaftaran ' . $p['id_pendaftaran'] . '. Setelah terkirim, Anda tidak bisa menghapusnya.', 'attr') ?>"
                        data-konfirmasi-tombol="Kirim bukti" data-konfirmasi-jenis="utama">
                        <?= csrf_field() ?>
                        <input type="hidden" name="kembali" value="<?= esc($kode, 'attr') ?>">
                        <label class="sr-only" for="bukti-<?= $urutan ?>">Gambar bukti transfer <?= esc($p['id_pendaftaran']) ?></label>
                        <div class="flex flex-wrap gap-2">
                            <input class="<?= K::BERKAS ?> !w-auto min-w-0 flex-[1_1_14rem]" type="file" id="bukti-<?= $urutan ?>" name="bukti" accept="image/jpeg,image/png,image/webp" data-ukuran-maksimal="<?= App\Libraries\Panel\BerkasBuktiBayar::BATAS_BITA ?>" required>
                            <button class="<?= K::UTAMA ?> min-h-12" type="submit"><?= ikon('upload', 'size-5') ?> Unggah bukti bayar</button>
                        </div>
                        <div class="<?= K::BANTU ?>">JPG, PNG, atau WEBP, paling besar 10 MB.</div>
                    </form>
                <?php endif ?>
            </div>
        <?php endif ?>
    </div>
</article>
