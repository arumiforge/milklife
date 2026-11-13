<?php
/**
 * Satu kartu pendaftaran di halaman cek (tanpa tanggal lahir, K52): kepala (ID, tanggal, status,
 * tombol bukti), penanggung jawab, atlet, tim beregu, dan biaya. Angka biaya
 * adalah nilai tersimpan (rancangan 6.7).
 *
 * @var array<string, mixed> $p      satu pendaftaran (App\Libraries\DataPendaftaran)
 * @var int                  $urutan nomor kartu, mulai 1
 * @var bool                 $banyak lebih dari satu pendaftaran ditampilkan
 * @var string               $kode   isian pencarian, tujuan kembali setelah unggah bukti bayar
 *
 * Pendaftaran yang belum Lunas punya kotak unggah bukti bayar (K83).
 */
$idJudul = 'pendaftaran-' . $urutan;
$lunas   = $p['status'] === 'lunas';
$jumlahBukti = (int) ($p['jumlah_bukti_bayar'] ?? 0);
// Nomor dada tampil setelah Lunas (K88); sebelumnya tanda pisah.
$dada = static fn (array $a): string => $lunas && ($a['nomor_dada'] ?? null) !== null
    ? '<strong>' . esc((string) $a['nomor_dada']) . '</strong>'
    : '<span class="text-body-secondary" title="Nomor dada keluar setelah pembayaran Lunas">–</span>';
$batasBukti  = App\Models\BuktiBayarModel::BATAS;
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
                        <th scope="col">No. Dada</th>
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
                            <td><?= $dada($a) ?></td>
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
                        <?php // Satu nama per baris dengan nomor dada (K88). ?>
                        <table class="table table-sm align-middle mb-0 mt-2 cek-tim-anggota">
                            <thead><tr><th scope="col">No</th><th scope="col">Nama</th><th scope="col">NISN</th><th scope="col">No. Dada</th></tr></thead>
                            <tbody>
                                <?php foreach ($tim['anggota'] as $j => $anggota) : ?>
                                    <tr>
                                        <td class="text-body-secondary"><?= $j + 1 ?></td>
                                        <td class="fw-semibold"><?= esc($anggota['nama']) ?></td>
                                        <td><?= esc($anggota['nisn']) ?></td>
                                        <td><?= $dada($anggota) ?></td>
                                    </tr>
                                <?php endforeach ?>
                            </tbody>
                        </table>
                    </li>
                <?php endforeach ?>
            </ul>
        <?php endif ?>

        <dl class="cek-biaya">
            <div><dt>Subtotal individu</dt><dd><?= esc(rupiah($p['subtotal_individu'])) ?></dd></div>
            <div><dt>Subtotal beregu</dt><dd><?= esc(rupiah($p['subtotal_beregu'])) ?></dd></div>
            <div class="cek-biaya-total"><dt>Total</dt><dd><?= esc(rupiah($p['total'])) ?></dd></div>
        </dl>

        <?php if (! $lunas) : ?>
            <div class="cek-unggah-bukti">
                <h4 class="cek-subjudul"><i class="bi bi-receipt" aria-hidden="true"></i> Bukti bayar <span class="cek-jumlah"><?= $jumlahBukti ?></span></h4>
                <?php if ($jumlahBukti >= $batasBukti) : ?>
                    <p class="mb-0">Sudah <?= $batasBukti ?> gambar terkirim, batas paling banyak. Panitia akan memeriksanya.</p>
                <?php else : ?>
                    <p class="small text-body-secondary"><?= $jumlahBukti === 0 ? 'Belum ada bukti bayar.' : 'Sudah ' . $jumlahBukti . ' gambar terkirim.' ?> Unggah foto atau tangkapan layar bukti transfer di sini, tidak perlu dikirim lewat WA.</p>
                    <form method="post" action="<?= esc(App\Libraries\BuktiPendaftaran::alamat($p) . '/bukti-bayar', 'attr') ?>" enctype="multipart/form-data"
                        data-konfirmasi-judul="Kirim bukti bayar?"
                        data-konfirmasi="<?= esc('Gambar yang dipilih dikirim ke panitia sebagai bukti transfer pendaftaran ' . $p['id_pendaftaran'] . '. Setelah terkirim, gambar tidak bisa dihapus sendiri.', 'attr') ?>"
                        data-konfirmasi-tombol="Kirim bukti" data-konfirmasi-jenis="utama">
                        <?= csrf_field() ?>
                        <input type="hidden" name="kembali" value="<?= esc($kode, 'attr') ?>">
                        <label class="visually-hidden" for="bukti-<?= $urutan ?>">Gambar bukti transfer <?= esc($p['id_pendaftaran']) ?></label>
                        <div class="input-group">
                            <input class="form-control" type="file" id="bukti-<?= $urutan ?>" name="bukti" accept="image/jpeg,image/png,image/webp" data-ukuran-maksimal="<?= App\Libraries\Panel\BerkasBuktiBayar::BATAS_BITA ?>" required>
                            <button class="btn btn-primary" type="submit"><i class="bi bi-upload" aria-hidden="true"></i> Unggah</button>
                        </div>
                        <div class="form-text">JPG, PNG, atau WEBP, paling besar 10 MB.</div>
                    </form>
                <?php endif ?>
            </div>
        <?php endif ?>
    </div>
</article>
