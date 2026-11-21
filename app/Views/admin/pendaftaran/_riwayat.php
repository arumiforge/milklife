<?php
/**
 * Daftar riwayat tindakan admin (F8-01, K124), terbaru di atas. Dipakai
 * rincian pendaftaran dan halaman `admin/riwayat` (tampilan Tailwind, F9-15).
 *
 * @var list<array<string, mixed>> $riwayat     `RiwayatAdmin::untukPendaftaran()` atau `cari()`
 * @var string|null                 $idSekarang ID pendaftaran yang sedang dibuka; baris dari ID lain
 *                                              (pendaftaran yang digabung ke sini) diberi keterangan.
 * @var bool                        $denganId   Tampilkan ID pendaftaran di setiap baris (halaman cari).
 */

$idSekarang ??= null;
$denganId   ??= false;
?>
<?php if ($riwayat === []) : ?>
    <p class="text-sm text-gray-600" data-riwayat-kosong>Belum ada tindakan admin yang tercatat.</p>
<?php else : ?>
    <ol class="divide-y divide-gray-100 border-t border-gray-100" data-riwayat-admin>
        <?php foreach ($riwayat as $r) : ?>
            <li class="py-2.5" data-tindakan="<?= esc($r['tindakan'], 'attr') ?>">
                <div class="flex flex-wrap items-baseline gap-x-2 gap-y-1">
                    <span class="inline-flex items-center rounded-md border border-gray-200 bg-gray-50 px-2 py-0.5 text-xs font-semibold text-gray-800"><?= esc($r['label']) ?></span>
                    <?php if ($denganId) : ?>
                        <?php if (($r['pendaftaran_id'] ?? null) !== null) : ?>
                            <a class="font-semibold text-primary-700 hover:underline" href="<?= site_url('admin/pendaftaran/' . (int) $r['pendaftaran_id']) ?>"><?= esc($r['id_pendaftaran']) ?></a>
                        <?php else : ?>
                            <span class="font-semibold text-gray-900"><?= esc($r['id_pendaftaran']) ?></span>
                            <span class="text-sm text-gray-500">(sudah dihapus)</span>
                        <?php endif ?>
                    <?php elseif ($idSekarang !== null && $r['id_pendaftaran'] !== $idSekarang) : ?>
                        <span class="text-sm text-gray-500">dari <?= esc($r['id_pendaftaran']) ?></span>
                    <?php endif ?>
                    <span class="ms-auto whitespace-nowrap text-sm text-gray-500"><?= esc(tanggal_id($r['dibuat_pada'], true)) ?> · <?= esc($r['nama_admin']) ?></span>
                </div>
                <div class="mt-1 text-sm text-gray-800"><?= esc($r['ringkasan']) ?></div>
            </li>
        <?php endforeach ?>
    </ol>
<?php endif ?>
