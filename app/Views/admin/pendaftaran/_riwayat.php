<?php
/**
 * Daftar riwayat tindakan admin (F8-01, K124), terbaru di atas. Dipakai
 * rincian pendaftaran dan halaman `admin/riwayat`.
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
    <p class="text-body-secondary mb-0 riwayat-kosong">Belum ada tindakan admin yang tercatat.</p>
<?php else : ?>
    <ol class="list-unstyled mb-0 riwayat-admin">
        <?php foreach ($riwayat as $r) : ?>
            <li class="border-top py-2 riwayat-baris" data-tindakan="<?= esc($r['tindakan'], 'attr') ?>">
                <div class="d-flex flex-wrap align-items-baseline gap-2">
                    <span class="badge rounded-pill text-bg-light border"><?= esc($r['label']) ?></span>
                    <?php if ($denganId) : ?>
                        <?php if (($r['pendaftaran_id'] ?? null) !== null) : ?>
                            <a class="fw-semibold" href="<?= site_url('admin/pendaftaran/' . (int) $r['pendaftaran_id']) ?>"><?= esc($r['id_pendaftaran']) ?></a>
                        <?php else : ?>
                            <span class="fw-semibold"><?= esc($r['id_pendaftaran']) ?></span>
                            <span class="small text-body-secondary">(sudah dihapus)</span>
                        <?php endif ?>
                    <?php elseif ($idSekarang !== null && $r['id_pendaftaran'] !== $idSekarang) : ?>
                        <span class="small text-body-secondary">dari <?= esc($r['id_pendaftaran']) ?></span>
                    <?php endif ?>
                    <span class="small text-body-secondary ms-auto text-nowrap"><?= esc(tanggal_id($r['dibuat_pada'], true)) ?> · <?= esc($r['nama_admin']) ?></span>
                </div>
                <div class="small mt-1"><?= esc($r['ringkasan']) ?></div>
            </li>
        <?php endforeach ?>
    </ol>
<?php endif ?>
