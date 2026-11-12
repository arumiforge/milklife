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
<article class="card cek-kartu <?= $lunas ? 'cek-kartu-lunas' : 'cek-kartu-menunggu' ?>" aria-labelledby="<?= $idJudul ?>">
    <div class="card-header cek-kartu-kepala">
        <div>
            <?php if ($banyak) : ?><p class="cek-kartu-urutan">Pendaftaran <?= $urutan ?></p><?php endif ?>
            <h3 class="cek-kartu-id" id="<?= $idJudul ?>"><span class="visually-hidden">ID </span><?= esc($p['id_pendaftaran']) ?></h3>
            <p class="mb-0 small"><i class="bi bi-clock" aria-hidden="true"></i> Didaftarkan <?= esc(tanggal_id($p['dibuat_pada'], true)) ?></p>
        </div>
        <div class="cek-kartu-tindakan">
            <span class="badge rounded-pill cek-status <?= $lunas ? 'text-bg-success' : 'text-bg-warning' ?>">
                <i class="bi <?= $lunas ? 'bi-check-circle-fill' : 'bi-hourglass-split' ?>" aria-hidden="true"></i>
                <?= esc($p['status_label']) ?>
            </span>
            <a class="btn btn-primary" href="<?= esc(App\Libraries\BuktiPendaftaran::alamat($p), 'attr') ?>" target="_blank" rel="noopener">
                <i class="bi bi-printer" aria-hidden="true"></i> Cetak bukti
            </a>
        </div>
    </div>

    <div class="card-body">
        <h4 class="cek-subjudul"><i class="bi bi-person-badge" aria-hidden="true"></i> Penanggung jawab</h4>
        <ul class="cek-penanggung-jawab">
            <?php foreach ($p['penanggung_jawab'] as $pj) : ?>
                <li><i class="bi bi-person-circle" aria-hidden="true"></i> <strong><?= esc($pj['nama']) ?></strong> <span><i class="bi bi-whatsapp" aria-hidden="true"></i> <?= esc($pj['nomor_wa']) ?></span></li>
            <?php endforeach ?>
        </ul>

        <h4 class="cek-subjudul"><i class="bi bi-people" aria-hidden="true"></i> Atlet <span class="cek-jumlah"><?= count($p['atlet']) ?></span></h4>
        <div class="table-responsive">
            <table class="table table-hover align-middle cek-tabel">
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
                            <td class="text-body-secondary"><?= $i + 1 ?></td>
                            <td class="fw-semibold"><?= esc($a['nama']) ?></td>
                            <td><?= esc($a['nisn']) ?></td>
                            <td><abbr class="kategori kategori-<?= $a['jenis_kelamin'] === 'P' ? 'putri' : 'putra' ?>" title="<?= esc($a['jenis_kelamin_label'], 'attr') ?>"><?= esc($a['jenis_kelamin']) ?></abbr></td>
                            <td><?= esc($a['kelompok_umur']) ?></td>
                            <td><?= esc(implode(', ', $a['nomor_lomba'])) ?></td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>

        <?php if ($p['beregu'] !== []) : ?>
            <h4 class="cek-subjudul"><i class="bi bi-flag" aria-hidden="true"></i> Tim beregu <span class="cek-jumlah"><?= count($p['beregu']) ?></span></h4>
            <ul class="cek-tim">
                <?php foreach ($p['beregu'] as $tim) : ?>
                    <li>
                        <div class="cek-tim-kepala">
                            <strong><?= esc($tim['nomor_lomba']) ?></strong>
                            <span class="cek-tim-biaya"><?= $tim['biaya_tim'] === null ? 'tanpa biaya tim, tim sudah terdaftar' : esc(rupiah($tim['biaya_tim'])) ?></span>
                        </div>
                        <span class="cek-tim-anggota"><?= esc(implode(', ', array_column($tim['anggota'], 'nama'))) ?></span>
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
