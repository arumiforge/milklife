<?php
/**
 * Bagian "Perlu didaftarkan ulang" di kartu Cek Pendaftaran (K116, K117).
 *
 * Ditulis untuk ofisial sekolah, bukan untuk panitia: yang tampil hanya apa
 * yang terjadi, alasannya, dan apa yang perlu dilakukan. Angka total sebelum
 * dan sesudah, waktu penolakan, dan nama admin hanya ada di panel admin.
 *
 * - Atlet yang belum didaftarkan ulang: satu kotak per atlet berisi data
 *   singkat (tanpa tanggal lahir, K52), yang didaftarkan, alasan panitia,
 *   dan kelompok umur serta nomor lomba yang bisa dipilih sekarang.
 * - Langkah berikutnya, tombol "Daftarkan ulang" dan "Hubungi panitia".
 * - Atlet yang sudah didaftarkan ulang cukup namanya, sebagai tanda bahwa
 *   pendaftaran ulangnya sudah masuk.
 *
 * @var array<string, mixed> $p            satu pendaftaran (App\Libraries\DataPendaftaran)
 * @var int                  $urutan       nomor kartu, untuk id unik
 * @var string|null          $waTanya      alamat WA tanya admin, atau null
 * @var bool                 $ditolakSemua semua atlet pendaftaran ini ditolak
 *
 * Tampilan Tailwind (F9-05); `data-beres` menandai bagian yang sudah tuntas.
 */

use App\Libraries\Pendaftaran\KelasTampilan as K;
$ditolak = $p['atlet_ditolak'] ?? [];
$ulang   = $p['atlet_didaftarkan_ulang'] ?? [];

if ($ditolak === [] && $ulang === []) {
    return;
}

$label = 'cek-ditolak-label text-xs font-semibold uppercase tracking-wide text-gray-500';
?>
<section class="cek-ditolak<?= $ditolak === [] ? ' cek-ditolak-beres border-green-200 bg-green-50' : ' border-red-200 bg-red-50/60' ?> mt-6 scroll-mt-24 rounded-xl border p-4" id="atlet-ditolak-<?= $urutan ?>" aria-labelledby="judul-ditolak-<?= $urutan ?>"<?= $ditolak === [] ? ' data-beres' : '' ?>>
    <?php if ($ditolak !== []) : ?>
        <div class="cek-ditolak-kepala flex items-start gap-3">
            <span class="inline-flex shrink-0 text-red-700" aria-hidden="true"><?= ikon('user-x', 'size-8') ?></span>
            <div>
                <h4 class="cek-ditolak-judul font-bold text-red-900" id="judul-ditolak-<?= $urutan ?>">
                    <?= $ditolakSemua ? 'Semua atlet di pendaftaran ini ditolak panitia' : count($ditolak) . ' atlet perlu didaftarkan ulang' ?>
                </h4>
                <p class="mt-0.5 text-sm text-gray-700">
                    <?php if ($ditolakSemua) : ?>
                        Pendaftaran ini tidak perlu dibayar. Daftarkan ulang atlet di bawah ini lewat menu Pendaftaran.
                    <?php else : ?>
                        Atlet di bawah ini tidak lagi termasuk pendaftaran ini dan tidak ditagih. Atlet lain tetap terdaftar seperti biasa.
                    <?php endif ?>
                </p>
            </div>
        </div>

        <ul class="cek-ditolak-daftar mt-4 space-y-3">
            <?php foreach ($ditolak as $d) : ?>
                <li class="cek-ditolak-atlet space-y-3 rounded-xl border border-red-100 bg-white p-3">
                    <div class="cek-ditolak-nama">
                        <strong class="block text-gray-900"><?= esc($d['nama']) ?></strong>
                        <span class="text-sm text-gray-600">NISN <?= esc($d['nisn']) ?> · <?= esc($d['jenis_kelamin_label']) ?> · lahir <?= (int) $d['tahun_lahir'] ?></span>
                    </div>

                    <div class="cek-ditolak-banding flex flex-wrap items-stretch gap-2 text-sm">
                        <div class="cek-ditolak-lama min-w-0 flex-[1_1_10rem] rounded-lg bg-gray-50 p-2.5">
                            <p class="<?= $label ?>">Didaftarkan di</p>
                            <p><strong><?= esc($d['kelompok_umur']) ?></strong></p>
                            <?php if ($d['nomor_lomba'] !== []) : ?>
                                <p class="text-gray-600"><?= esc(implode(', ', array_column($d['nomor_lomba'], 'nama'))) ?></p>
                            <?php endif ?>
                        </div>
                        <?php if ($d['sesuai_beda'] && $d['kelompok_umur_sesuai'] !== null) : ?>
                            <span class="cek-ditolak-panah flex items-center text-gray-400" aria-hidden="true"><?= ikon('arrow-right', 'size-5') ?></span>
                            <div class="cek-ditolak-baru min-w-0 flex-[1_1_10rem] rounded-lg bg-green-50 p-2.5">
                                <p class="<?= $label ?> !text-green-800">Daftarkan ulang di</p>
                                <p><strong><?= esc($d['kelompok_umur_sesuai']) ?></strong></p>
                                <?php if ($d['nomor_sesuai'] !== []) : ?>
                                    <p class="text-gray-600">Pilihan nomor: <?= esc(implode(', ', array_column($d['nomor_sesuai'], 'nama'))) ?></p>
                                <?php endif ?>
                            </div>
                        <?php endif ?>
                    </div>

                    <div class="cek-ditolak-alasan text-sm">
                        <p class="<?= $label ?>">Alasan dari panitia</p>
                        <p class="text-gray-900"><?= nl2br(esc($d['alasan'])) ?></p>
                    </div>
                </li>
            <?php endforeach ?>
        </ul>

        <div class="cek-ditolak-langkah mt-4 text-sm">
            <p class="<?= $label ?>">Yang perlu dilakukan</p>
            <ol class="mt-1 mb-3 list-decimal space-y-1 ps-5 text-gray-800">
                <li>Buka menu Pendaftaran dan daftarkan atletnya lagi di kelompok umur yang disarankan. NISN yang sama boleh dipakai.</li>
                <li>Bila biaya untuk atlet ini sudah terlanjur ditransfer, hubungi panitia untuk penyelesaiannya.</li>
            </ol>
            <div class="flex flex-wrap gap-2">
                <a class="<?= K::UTAMA ?>" href="<?= site_url('pendaftaran') ?>"><?= ikon('pencil-line', 'size-5') ?> Daftarkan ulang</a>
                <?php if (($waTanya ?? null) !== null) : ?>
                    <a class="<?= K::TOMBOL ?> min-h-11 border border-green-700 bg-white px-5 py-2.5 text-sm text-green-800 hover:bg-green-50 focus:ring-green-200" href="<?= esc($waTanya, 'attr') ?>" target="_blank" rel="noopener"><?= ikon('message-circle', 'size-5') ?> Hubungi panitia</a>
                <?php endif ?>
            </div>
        </div>
    <?php else : ?>
        <div class="cek-ditolak-kepala flex items-start gap-3">
            <span class="inline-flex shrink-0 text-green-700" aria-hidden="true"><?= ikon('circle-check', 'size-8') ?></span>
            <div>
                <h4 class="cek-ditolak-judul font-bold text-green-900" id="judul-ditolak-<?= $urutan ?>">Atlet yang ditolak sudah didaftarkan ulang</h4>
                <p class="mt-0.5 text-sm text-gray-700"><?= $ditolakSemua ? 'Pendaftaran ini tidak perlu dibayar. Data dan tagihan atletnya ada di pendaftaran yang baru.' : 'Tidak ada lagi yang perlu dilakukan untuk atlet yang ditolak.' ?></p>
            </div>
        </div>
    <?php endif ?>

    <?php if ($ulang !== []) : ?>
        <div class="cek-ditolak-ulang mt-4 text-sm">
            <p class="<?= $label ?>">Sudah didaftarkan ulang</p>
            <ul class="mt-1 space-y-1">
                <?php foreach ($ulang as $u) : ?>
                    <li class="flex flex-wrap items-center gap-1.5"><?= ikon('circle-check', 'size-4 text-green-700') ?> <?= esc($u['nama']) ?> <span class="text-gray-500">· NISN <?= esc($u['nisn']) ?></span></li>
                <?php endforeach ?>
            </ul>
        </div>
    <?php endif ?>
</section>
