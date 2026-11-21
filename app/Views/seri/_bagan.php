<?php
/**
 * Bagan alur babak satu nomor (F9-08): Penyisihan seri 1…n → (Semifinal) →
 * Final, tiap babak dengan status, aturan lolos, dan peserta per seri beserta
 * tanda lolos Q/q. Dipakai halaman publik `jadwal/<id babak>` dan halaman
 * admin Seri dan lintasan per nomor. Tailwind, tanpa JavaScript.
 *
 *     <?= view('seri/_bagan', ['bagan' => $bagan, 'jenisHasil' => 'waktu', 'publik' => true, 'aktif' => 12], ['saveData' => false]) ?>
 *
 * @var list<array<string, mixed>> $bagan      `SeriLintasan::bagan()`
 * @var string                     $jenisHasil jenis hasil nomor
 * @var bool                       $publik     tautan ke halaman publik (true) atau admin (false)
 * @var int|null                   $aktif      id babak yang sedang dibuka
 * @var bool|null                  $lintasan   nomor berlintasan (false = urutan tampil)
 */

use App\Libraries\Hasil\JadwalPertandingan;
use App\Libraries\Hasil\SeriLintasan;

// ikon-sprite: clock circle-play circle-check arrow-right arrow-down lock
$warnaStatus = ['belum' => 'abu', 'berlangsung' => 'oranye', 'selesai' => 'hijau'];
$ikonStatus  = ['belum' => 'clock', 'berlangsung' => 'circle-play', 'selesai' => 'circle-check'];
$lintasan  ??= true;
$aktif     ??= null;
$jumlah      = count($bagan);
?>
<ol class="grid grid-cols-1 gap-3 lg:auto-cols-fr lg:grid-flow-col lg:grid-cols-none lg:gap-0" data-bagan>
    <?php foreach ($bagan as $i => $b) : ?>
        <?php
        $teksLolos = SeriLintasan::teksLolos($b['aturan'], $jenisHasil);
        $alamat    = $publik ? ($b['dikunci'] ? site_url('jadwal/' . $b['id']) : null) : site_url('admin/seri/babak/' . $b['id']);
        $ini       = $aktif === $b['id'];
        ?>
        <li class="flex min-w-0 flex-col lg:flex-row" data-bagan-babak="<?= $b['id'] ?>" data-status="<?= esc($b['status'], 'attr') ?>">
            <section class="min-w-0 flex-1 rounded-2xl border <?= $ini ? 'border-primary-600 ring-2 ring-primary-200' : 'border-gray-200' ?> bg-white p-4 shadow-lembut" aria-label="<?= esc($b['nama'], 'attr') ?>">
                <header class="flex flex-wrap items-start justify-between gap-2">
                    <h3 class="min-w-0 text-base font-bold break-words text-primary-900">
                        <?php if ($alamat !== null && ! $ini) : ?>
                            <a href="<?= esc($alamat, 'attr') ?>" class="underline hover:text-primary-700"><?= esc($b['nama']) ?></a>
                        <?php else : ?>
                            <?= esc($b['nama']) ?>
                        <?php endif ?>
                    </h3>
                    <?= komponen('lencana', ['label' => JadwalPertandingan::STATUS[$b['status']], 'warna' => $warnaStatus[$b['status']], 'ikon' => $ikonStatus[$b['status']]]) ?>
                </header>
                <p class="mt-1 text-sm text-gray-600">
                    <?= $b['tanggal'] === null ? 'Jadwal menyusul' : esc(JadwalPertandingan::namaHari($b['tanggal']) . ($b['jam'] === null ? '' : ', ' . $b['jam'] . ' WIB')) ?>
                    <?php if ($b['jumlah_seri'] > 1) : ?> · <?= $b['jumlah_seri'] ?> <?= $lintasan ? 'seri' : 'kelompok' ?><?php endif ?>
                </p>
                <?php if ($teksLolos !== '') : ?>
                    <p class="mt-1 text-sm font-semibold text-gray-800" data-aturan-lolos><?= esc($teksLolos) ?></p>
                <?php endif ?>

                <?php if ($b['seri'] === []) : ?>
                    <p class="mt-3 rounded-lg bg-gray-50 p-3 text-sm text-gray-600">
                        <?= $publik ? 'Daftar peserta diumumkan setelah panitia menyusunnya' . ($i > 0 ? ', biasanya setelah babak sebelumnya selesai.' : '.') : 'Belum ada peserta.' ?>
                    </p>
                <?php else : ?>
                    <div class="mt-3 space-y-3">
                        <?php foreach ($b['seri'] as $nomorSeri => $peserta) : ?>
                            <div>
                                <p class="text-xs font-bold tracking-wide text-oranye-700 uppercase"><?= $nomorSeri === 0 ? 'Belum dibagi' : ($lintasan ? 'Seri ' . $nomorSeri : 'Kelompok ' . $nomorSeri) ?></p>
                                <ul class="mt-1 divide-y divide-gray-100 rounded-lg border border-gray-100">
                                    <?php foreach ($peserta as $p) : ?>
                                        <?php $tanda = $b['tanda'][$p['kunci']] ?? null; ?>
                                        <li class="flex items-center gap-2 px-2 py-1.5 text-sm<?= $tanda !== null ? ' bg-green-50' : '' ?>" data-peserta-bagan="<?= esc($p['kunci'], 'attr') ?>">
                                            <span class="w-6 shrink-0 text-center font-bold text-primary-800"><?= $p['lintasan'] ?? '–' ?></span>
                                            <span class="min-w-0 flex-1">
                                                <span class="block font-semibold break-words text-gray-900"><?= esc($p['nama']) ?></span>
                                                <span class="block truncate text-xs text-gray-600"><?= esc($p['sekolah']) ?></span>
                                            </span>
                                            <?php if (($p['prestasi_teks'] ?? '') !== '') : ?><span class="shrink-0 text-xs font-semibold text-gray-700"><?= esc($p['prestasi_teks']) ?></span><?php endif ?>
                                            <?php if ($tanda !== null) : ?><span class="shrink-0 rounded bg-green-700 px-1.5 py-0.5 text-xs font-bold text-white" title="<?= $tanda === 'Q' ? 'Lolos: terbaik di serinya' : 'Lolos: prestasi terbaik berikutnya' ?>" data-tanda-lolos="<?= $tanda ?>"><?= $tanda ?></span><?php endif ?>
                                        </li>
                                    <?php endforeach ?>
                                </ul>
                            </div>
                        <?php endforeach ?>
                    </div>
                <?php endif ?>
            </section>
            <?php if ($i < $jumlah - 1) : ?>
                <span class="flex items-center justify-center py-1 text-gray-400 lg:px-2 lg:py-0" aria-hidden="true">
                    <?= ikon('arrow-down', 'size-5 lg:hidden') ?><?= ikon('arrow-right', 'hidden size-5 lg:block') ?>
                </span>
            <?php endif ?>
        </li>
    <?php endforeach ?>
</ol>
