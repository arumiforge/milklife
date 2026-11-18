<?php
/**
 * Isi PDF hasil satu nomor (F8-04): satu tabel per babak, Final paling akhir.
 * Dibungkus `admin/hasil/pdf` oleh `BerkasHasil::pdf()`.
 *
 * @var array<string, mixed>       $nomor
 * @var list<array<string, mixed>> $babak `HasilLomba::susunan()`
 */

use App\Libraries\Hasil\BerkasHasil;
use App\Libraries\Hasil\Prestasi;

$beregu = $nomor['jenis'] === 'beregu';
?>
<?php if ($babak === []) : ?>
    <div class="kosong">Belum ada babak untuk nomor ini.</div>
<?php endif ?>
<?php foreach ($babak as $b) : ?>
    <h2><?= esc($b['nama']) ?><?= (int) $b['final'] === 1 ? ' <span class="kecil">(juara I–III dari babak ini)</span>' : '' ?></h2>
    <?php if ($b['baris'] === []) : ?>
        <div class="kosong">Belum ada peserta.</div>
    <?php else : ?>
        <table class="data">
            <thead>
                <tr>
                    <th class="tengah" width="14mm">Peringkat</th>
                    <?php if (! $beregu) : ?><th width="18mm">No. Atlet</th><?php endif ?>
                    <th><?= $beregu ? 'Regu' : 'Nama' ?></th>
                    <?php if (! $beregu) : ?><th>Sekolah</th><?php endif ?>
                    <th class="angka" width="22mm"><?= esc(Prestasi::JUDUL[$nomor['jenis_hasil']] ?? 'Prestasi') ?></th>
                    <th width="30mm">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($b['baris'] as $h) : ?>
                    <tr<?= (int) $b['final'] === 1 && $h['peringkat'] !== null && $h['peringkat'] <= 3 ? ' class="juara"' : '' ?>>
                        <td class="tengah"><?= $h['peringkat'] ?? '' ?></td>
                        <?php if (! $beregu) : ?><td><?= esc($h['nomor_dada']) ?></td><?php endif ?>
                        <td>
                            <?= esc($h['nama']) ?>
                            <?php if ($beregu && $h['anggota'] !== []) : ?>
                                <div class="kecil"><?= esc(implode(', ', array_map(static fn (array $a): string => trim($a['nomor_dada'] . ' ' . $a['nama']), $h['anggota']))) ?></div>
                            <?php endif ?>
                        </td>
                        <?php if (! $beregu) : ?><td><?= esc($h['sekolah']) ?></td><?php endif ?>
                        <td class="angka"><?= esc($h['prestasi_teks']) ?></td>
                        <td><?= esc(BerkasHasil::keterangan($h)) ?></td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    <?php endif ?>
<?php endforeach ?>
