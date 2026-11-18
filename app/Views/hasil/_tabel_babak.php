<?php
/**
 * Tabel hasil satu babak (F8-04), dipakai halaman nomor di panel dan
 * halaman hasil publik. Di ponsel sekolah dan anggota regu tampil di bawah
 * nama. Juara I–III babak Final diberi medali.
 *
 * @var array<string, mixed> $nomor
 * @var array<string, mixed> $babak satu babak dari `HasilLomba::susunan()` (dengan `baris`)
 */

use App\Libraries\Hasil\BerkasHasil;
use App\Libraries\Hasil\Prestasi;

$beregu = $nomor['jenis'] === 'beregu';
$final  = (int) $babak['final'] === 1;
$medali = [1 => 'emas', 2 => 'perak', 3 => 'perunggu'];
?>
<div class="table-responsive">
    <table class="table table-sm align-middle mb-0 tabel-hasil" data-babak="<?= (int) $babak['id'] ?>">
        <caption class="visually-hidden">Hasil <?= esc($babak['nama']) ?></caption>
        <thead>
            <tr>
                <th scope="col" class="text-center">Per&shy;ingkat</th>
                <?php if (! $beregu) : ?><th scope="col" class="d-none d-sm-table-cell">No. Atlet</th><?php endif ?>
                <th scope="col"><?= $beregu ? 'Regu' : 'Nama' ?></th>
                <?php if (! $beregu) : ?><th scope="col" class="d-none d-md-table-cell">Sekolah</th><?php endif ?>
                <th scope="col" class="text-end"><?= esc(Prestasi::JUDUL[$nomor['jenis_hasil']] ?? 'Prestasi') ?></th>
                <th scope="col" class="d-none d-sm-table-cell">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($babak['baris'] as $h) : ?>
                <?php $juara = $final && $h['peringkat'] !== null && $h['peringkat'] <= 3; ?>
                <tr<?= $juara ? ' class="hasil-juara fw-semibold" data-juara="' . (int) $h['peringkat'] . '"' : '' ?>>
                    <td class="text-center text-nowrap">
                        <?php if ($juara) : ?>
                            <i class="bi bi-award-fill hasil-medali-<?= $medali[$h['peringkat']] ?>" aria-hidden="true"></i>
                        <?php endif ?>
                        <?= $h['peringkat'] ?? '<span class="text-body-secondary">–</span>' ?>
                    </td>
                    <?php if (! $beregu) : ?><td class="d-none d-sm-table-cell"><?= esc($h['nomor_dada']) ?></td><?php endif ?>
                    <td>
                        <?= esc($h['nama']) ?>
                        <?php if (! $beregu) : ?>
                            <span class="d-block d-md-none small text-body-secondary fw-normal"><?= esc(trim($h['nomor_dada'] . ' · ' . $h['sekolah'], ' ·')) ?></span>
                        <?php elseif ($h['anggota'] !== []) : ?>
                            <span class="d-block small text-body-secondary fw-normal"><?= esc(implode(', ', array_column($h['anggota'], 'nama'))) ?></span>
                        <?php endif ?>
                        <?php $ket = BerkasHasil::keterangan($h); ?>
                        <?php if ($ket !== '') : ?><span class="d-block d-sm-none small text-body-secondary fw-normal"><?= esc($ket) ?></span><?php endif ?>
                    </td>
                    <?php if (! $beregu) : ?><td class="d-none d-md-table-cell"><?= esc($h['sekolah']) ?></td><?php endif ?>
                    <td class="text-end text-nowrap"><?= $h['prestasi_teks'] !== '' ? esc($h['prestasi_teks']) : '<span class="text-body-secondary">–</span>' ?></td>
                    <td class="d-none d-sm-table-cell small"><?= esc(BerkasHasil::keterangan($h)) ?></td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</div>
