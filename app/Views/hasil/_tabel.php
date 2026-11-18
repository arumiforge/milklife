<?php
/**
 * Tabel hasil satu babak (hanya baca), dipakai halaman nomor admin dan
 * halaman hasil publik (F8-04).
 *
 * @var array<string, mixed>       $nomor `HasilLomba::nomor()`
 * @var array<string, mixed>       $babak satu butir `HasilLomba::semuaBabak()`
 * @var bool                       $admin tampilkan tanda "diubah admin"
 */
$beregu = $nomor['jenis'] === 'beregu';
?>
<div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
        <thead>
            <tr>
                <th scope="col" class="text-center">Per.</th>
                <?php if (! $beregu) : ?><th scope="col">No. Atlet</th><?php endif ?>
                <th scope="col"><?= $beregu ? 'Regu' : 'Nama' ?></th>
                <?php if (! $beregu) : ?><th scope="col" class="d-none d-md-table-cell">Sekolah</th><?php endif ?>
                <th scope="col">Prestasi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($babak['hasil'] as $h) : ?>
                <tr<?= $babak['final'] && $h['peringkat'] !== null && $h['peringkat'] <= 3 ? ' class="table-warning"' : '' ?>>
                    <td class="text-center fw-semibold"><?= $h['peringkat'] ?? '–' ?></td>
                    <?php if (! $beregu) : ?><td class="fw-semibold text-nowrap"><?= esc((string) $h['nomor_dada']) ?></td><?php endif ?>
                    <td>
                        <?= esc($h['nama']) ?>
                        <?php if (! $beregu) : ?><div class="small text-body-secondary d-md-none"><?= esc($h['sekolah']) ?></div><?php endif ?>
                        <?php if ($beregu && $h['anggota'] !== []) : ?><div class="small text-body-secondary"><?= esc(implode(', ', $h['anggota'])) ?></div><?php endif ?>
                        <?php if ($admin && $h['manual']) : ?><span class="badge rounded-pill text-bg-info">diubah admin</span><?php endif ?>
                    </td>
                    <?php if (! $beregu) : ?><td class="d-none d-md-table-cell"><?= esc($h['sekolah']) ?></td><?php endif ?>
                    <td class="text-nowrap"><?= esc($h['prestasi_teks']) ?><?= $h['keterangan'] !== null ? ' <span class="badge text-bg-secondary">' . esc($h['keterangan']) . '</span>' : '' ?></td>
                </tr>
            <?php endforeach ?>
            <?php if ($babak['hasil'] === []) : ?>
                <tr><td colspan="<?= $beregu ? 3 : 5 ?>" class="text-body-secondary">Belum ada peserta.</td></tr>
            <?php endif ?>
        </tbody>
    </table>
</div>
