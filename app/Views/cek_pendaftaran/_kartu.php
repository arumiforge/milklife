<?php
/**
 * Satu kartu pendaftaran di halaman cek (tanpa tanggal lahir dan nomor dada, K52): kepala (ID, tanggal, status,
 * tombol bukti), penanggung jawab, atlet, tim beregu, dan biaya. Angka biaya
 * adalah nilai tersimpan (rancangan 6.7).
 *
 * @var array<string, mixed> $p      satu pendaftaran (App\Libraries\DataPendaftaran)
 * @var int                  $urutan nomor kartu, mulai 1
 * @var bool                 $banyak lebih dari satu pendaftaran ditampilkan
 */
$idJudul = 'pendaftaran-' . $urutan;
$lunas   = $p['status'] === 'lunas';
?>
<article class="card cek-kartu" aria-labelledby="<?= $idJudul ?>">
    <div class="card-header cek-kartu-kepala">
        <div>
            <h3 class="h5 mb-1" id="<?= $idJudul ?>">
                <?= $banyak ? 'Pendaftaran ' . $urutan . ' · ' : '' ?>ID <?= esc($p['id_pendaftaran']) ?>
            </h3>
            <p class="mb-0 small">Didaftarkan <?= esc(tanggal_id($p['dibuat_pada'], true)) ?></p>
        </div>
        <div class="cek-kartu-tindakan">
            <span class="badge <?= $lunas ? 'text-bg-success' : 'text-bg-warning' ?>">
                <i class="bi <?= $lunas ? 'bi-check-circle-fill' : 'bi-hourglass-split' ?>" aria-hidden="true"></i>
                <?= esc($p['status_label']) ?>
            </span>
            <a class="btn btn-sm btn-outline-primary" href="<?= site_url('bukti/' . rawurlencode($p['id_pendaftaran'])) ?>" target="_blank" rel="noopener">
                <i class="bi bi-printer" aria-hidden="true"></i> Cetak bukti
            </a>
        </div>
    </div>

    <div class="card-body">
        <h4 class="h6">Penanggung jawab</h4>
        <ul class="cek-penanggung-jawab">
            <?php foreach ($p['penanggung_jawab'] as $pj) : ?>
                <li><?= esc($pj['nama']) ?> · <?= esc($pj['nomor_wa']) ?></li>
            <?php endforeach ?>
        </ul>

        <h4 class="h6">Atlet (<?= count($p['atlet']) ?>)</h4>
        <div class="table-responsive">
            <table class="table table-sm table-striped align-middle cek-tabel">
                <thead>
                    <tr>
                        <th scope="col">No</th>
                        <th scope="col">Nama</th>
                        <th scope="col">NISN</th>
                        <th scope="col">L/P</th>
                        <th scope="col">Kelompok umur</th>
                        <th scope="col">Nomor lomba</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($p['atlet'] as $i => $a) : ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= esc($a['nama']) ?></td>
                            <td><?= esc($a['nisn']) ?></td>
                            <td><abbr title="<?= esc($a['jenis_kelamin_label'], 'attr') ?>"><?= esc($a['jenis_kelamin']) ?></abbr></td>
                            <td><?= esc($a['kelompok_umur']) ?></td>
                            <td><?= esc(implode(', ', $a['nomor_lomba'])) ?></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>

        <?php if ($p['beregu'] !== []) : ?>
            <h4 class="h6">Tim beregu</h4>
            <ul class="cek-tim">
                <?php foreach ($p['beregu'] as $tim) : ?>
                    <li>
                        <strong><?= esc($tim['nomor_lomba']) ?></strong>
                        (<?= $tim['biaya_tim'] === null ? 'tanpa biaya tim, tim sudah terdaftar' : esc(rupiah($tim['biaya_tim'])) ?>):
                        <?= esc(implode(', ', array_column($tim['anggota'], 'nama'))) ?>
                    </li>
                <?php endforeach ?>
            </ul>
        <?php endif ?>

        <dl class="cek-biaya">
            <div><dt>Subtotal individu</dt><dd><?= esc(rupiah($p['subtotal_individu'])) ?></dd></div>
            <div><dt>Subtotal beregu</dt><dd><?= esc(rupiah($p['subtotal_beregu'])) ?></dd></div>
            <div class="cek-biaya-total"><dt>Total</dt><dd><?= esc(rupiah($p['total'])) ?></dd></div>
        </dl>
    </div>
</article>
