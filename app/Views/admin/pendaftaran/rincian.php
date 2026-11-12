<?php
/**
 * Rincian satu pendaftaran (F6-02): sekolah, penanggung jawab beserta
 * fotonya (diperbesar di jendela, K76), atlet (NISN, tanggal lahir, kelompok umur, nomor lomba, nomor
 * dada), tim beregu, biaya, dan status. Data dari
 * `PendaftaranController::rincian()` (`DataPanelPendaftaran::rincian()`).
 *
 * Tindakan berisiko (tandai Lunas, kembali ke Menunggu, hapus) memakai
 * jendela konfirmasi `aset/js/konfirmasi.js`.
 *
 * Tempat yang disisakan untuk tugas lain (ditandai komentar):
 * - F6-04: tombol Ubah di deretan tombol tindakan (menuju `admin/pendaftaran/<id>/ubah`).
 * - F6-03: satu baris include `admin/pendaftaran/_bukti_bayar` setelah kartu biaya.
 *
 * Tim tanpa pembayar (K68): peringatan di kartu tim yang ditandai, dan
 * jendela hapus menyebut tim yang akan kehilangan pembayarnya.
 *
 * @var CodeIgniter\View\View                $this
 * @var array<string, mixed>                 $p
 * @var array<int, array<string, mixed>>     $timTanpaPembayar `TimTanpaPembayar::untukPendaftaran()`
 * @var string                               $kalimatTimHapus  `TimTanpaPembayar::kalimatDitinggalkan()`, boleh kosong
 */

$alamat      = 'admin/pendaftaran/' . (int) $p['id'];
$lunas       = $p['status'] === 'lunas';
$namaSingkat = $p['id_pendaftaran'] . ' (' . $p['nama_sekolah'] . ')';
$adaNomor    = $p['jumlah_atlet'] > $p['tanpa_nomor_dada'];
$strip       = '<span class="text-body-secondary">–</span>';

$kalimatLunas = 'Pendaftaran ' . $namaSingkat . ' akan ditandai Lunas. '
    . ($p['tanpa_nomor_dada'] > 0
        ? $p['tanpa_nomor_dada'] . ' atlet yang belum punya nomor dada akan mendapat nomor baru, dan nomor itu tidak bisa ditarik kembali.'
        : 'Semua atlet sudah punya nomor dada.')
    . ' Pastikan transfernya sudah diperiksa.';

$kalimatMenunggu = 'Status pendaftaran ' . $namaSingkat . ' akan kembali menjadi Menunggu Konfirmasi Pembayaran. '
    . 'Nomor dada atlet tetap dipegang dan tidak dihapus.';

$kalimatHapus = 'Pendaftaran ' . $p['id_pendaftaran'] . ' dari ' . $p['nama_sekolah'] . ' beserta ' . $p['jumlah_atlet']
    . ' atlet, penanggung jawab, tim beregu, foto, dan bukti bayarnya akan dihapus. NISN atletnya bisa didaftarkan lagi.'
    . ($adaNomor ? ' Nomor dada yang sudah dibagikan tidak dipakai ulang.' : '')
    . ' Tindakan ini tidak bisa dibatalkan.'
    . ($kalimatTimHapus === '' ? '' : ' ' . $kalimatTimHapus);
?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?>Pendaftaran <?= esc($p['id_pendaftaran']) ?><?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Rincian Pendaftaran<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid px-3 px-lg-4" id="rincian-pendaftaran" data-id="<?= (int) $p['id'] ?>" data-status="<?= esc($p['status'], 'attr') ?>">
    <a class="d-inline-flex align-items-center gap-1 mb-3" href="<?= site_url('admin/pendaftaran') ?>">
        <i class="bi bi-arrow-left" aria-hidden="true"></i> Kembali ke daftar pendaftaran
    </a>

    <section class="card mb-4" aria-labelledby="judul-pendaftaran">
        <div class="card-body p-3 p-lg-4">
            <div class="d-flex flex-column flex-lg-row align-items-lg-start justify-content-between gap-3">
                <div>
                    <h2 id="judul-pendaftaran" class="h4 mb-1"><?= esc($p['id_pendaftaran']) ?></h2>
                    <p class="mb-2 fs-5"><?= esc($p['nama_sekolah']) ?></p>
                    <p class="mb-0" id="status-pendaftaran">
                        <?php if ($lunas) : ?>
                            <span class="badge rounded-pill text-bg-success fs-6"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Lunas</span>
                        <?php else : ?>
                            <span class="badge rounded-pill text-bg-warning fs-6"><i class="bi bi-hourglass-split" aria-hidden="true"></i> Menunggu Konfirmasi Pembayaran</span>
                        <?php endif ?>
                    </p>
                </div>

                <div class="d-flex flex-wrap gap-2" id="tindakan-pendaftaran">
                    <?php if ($lunas) : ?>
                        <form action="<?= site_url($alamat . '/menunggu') ?>" method="post" class="m-0"
                            data-konfirmasi="<?= esc($kalimatMenunggu, 'attr') ?>"
                            data-konfirmasi-judul="Kembalikan ke Menunggu?"
                            data-konfirmasi-tombol="Kembalikan ke Menunggu"
                            data-konfirmasi-jenis="utama">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-outline-primary">
                                <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                                Kembalikan ke Menunggu
                            </button>
                        </form>
                    <?php else : ?>
                        <form action="<?= site_url($alamat . '/lunas') ?>" method="post" class="m-0"
                            data-konfirmasi="<?= esc($kalimatLunas, 'attr') ?>"
                            data-konfirmasi-judul="Tandai Lunas?"
                            data-konfirmasi-tombol="Tandai Lunas"
                            data-konfirmasi-jenis="utama">
                            <?= csrf_field() ?>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check2-circle" aria-hidden="true"></i>
                                Tandai Lunas
                            </button>
                        </form>
                    <?php endif ?>

                    <a class="btn btn-outline-primary" href="<?= site_url($alamat . '/ubah') ?>" id="tombol-ubah">
                        <i class="bi bi-pencil-square" aria-hidden="true"></i>
                        Ubah
                    </a>

                    <form action="<?= site_url($alamat . '/hapus') ?>" method="post" class="m-0"
                        data-konfirmasi="<?= esc($kalimatHapus, 'attr') ?>"
                        data-konfirmasi-judul="Hapus pendaftaran?"
                        data-konfirmasi-tombol="Hapus pendaftaran">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-outline-danger">
                            <i class="bi bi-trash" aria-hidden="true"></i>
                            Hapus
                        </button>
                    </form>
                </div>
            </div>

            <dl class="row small mt-3 mb-0">
                <dt class="col-sm-4 col-lg-3 fw-semibold">Tanggal daftar</dt>
                <dd class="col-sm-8 col-lg-9"><?= esc(tanggal_id($p['dibuat_pada'], true)) ?: $strip ?></dd>
                <dt class="col-sm-4 col-lg-3 fw-semibold">Waktu lunas</dt>
                <dd class="col-sm-8 col-lg-9 mb-0" id="waktu-lunas"><?= $lunas && $p['waktu_lunas'] !== null ? esc(tanggal_id($p['waktu_lunas'], true)) : '<span class="text-body-secondary">Belum lunas</span>' ?></dd>
            </dl>
        </div>
    </section>

    <div class="row g-4 mb-4">
        <section class="col-12 col-xl-5" aria-labelledby="judul-sekolah">
            <div class="card h-100">
                <div class="card-body p-3 p-lg-4">
                    <h2 id="judul-sekolah" class="h5 mb-3">Sekolah</h2>
                    <dl class="row mb-0">
                        <dt class="col-sm-4 fw-semibold">Nama</dt>
                        <dd class="col-sm-8"><?= esc($p['nama_sekolah']) ?></dd>
                        <dt class="col-sm-4 fw-semibold">NPSN</dt>
                        <dd class="col-sm-8"><?= $p['npsn'] === null ? '<span class="text-body-secondary">Tanpa NPSN</span>' : esc($p['npsn']) ?></dd>
                        <dt class="col-sm-4 fw-semibold">Jenjang</dt>
                        <dd class="col-sm-8"><?= esc($p['jenjang_label']) ?></dd>
                        <dt class="col-sm-4 fw-semibold">Kabupaten/Kota</dt>
                        <dd class="col-sm-8 mb-0"><?= esc($p['kabupaten']) ?></dd>
                    </dl>
                </div>
            </div>
        </section>

        <section class="col-12 col-xl-7" aria-labelledby="judul-penanggung-jawab">
            <div class="card h-100">
                <div class="card-body p-3 p-lg-4">
                    <h2 id="judul-penanggung-jawab" class="h5 mb-3">Penanggung jawab</h2>
                    <?php if ($p['penanggung_jawab'] === []) : ?>
                        <p class="text-body-secondary mb-0">Belum ada penanggung jawab.</p>
                    <?php else : ?>
                        <ul class="list-unstyled row g-3 mb-0">
                            <?php foreach ($p['penanggung_jawab'] as $orang) : ?>
                                <li class="col-12 col-sm-6 d-flex gap-3 align-items-start penanggung-jawab" data-id="<?= (int) $orang['id'] ?>">
                                    <div class="flex-shrink-0" style="width: 6rem;">
                                        <?php if ($orang['ada_foto']) : ?>
                                            <?php $alamatFoto = site_url($alamat . '/foto/' . (int) $orang['id']); $idFoto = 'foto-pj-' . (int) $orang['id']; ?>
                                            <button type="button" class="btn p-0 border-0 foto-pj" data-bs-toggle="modal" data-bs-target="#<?= $idFoto ?>" title="Perbesar foto">
                                                <img class="img-fluid rounded border" loading="lazy" src="<?= $alamatFoto ?>" alt="<?= esc('Foto ' . $orang['nama'], 'attr') ?>">
                                                <span class="foto-pj-perbesar" aria-hidden="true"><i class="bi bi-zoom-in"></i></span>
                                                <span class="visually-hidden">Perbesar foto <?= esc($orang['nama']) ?></span>
                                            </button>
                                            <div class="modal fade" id="<?= $idFoto ?>" tabindex="-1" aria-labelledby="<?= $idFoto ?>-judul" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered modal-foto-pj">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h3 class="modal-title h5" id="<?= $idFoto ?>-judul">Foto <?= esc($orang['nama']) ?></h3>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                                        </div>
                                                        <div class="modal-body text-center bg-body-tertiary">
                                                            <img class="foto-pj-besar rounded" loading="lazy" src="<?= $alamatFoto ?>" alt="<?= esc('Foto ' . $orang['nama'], 'attr') ?>">
                                                        </div>
                                                        <div class="modal-footer">
                                                            <a class="btn btn-outline-primary" href="<?= $alamatFoto ?>" target="_blank" rel="noopener">
                                                                <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Buka di tab baru
                                                            </a>
                                                            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Tutup</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php else : ?>
                                            <?= view('tata_letak/_tanpa_gambar', ['rasio' => '1x1', 'label' => 'Foto ' . $orang['nama']], ['saveData' => false]) ?>
                                        <?php endif ?>
                                    </div>
                                    <div>
                                        <p class="fw-semibold mb-1"><?= esc($orang['nama']) ?></p>
                                        <p class="mb-0 text-nowrap">
                                            <i class="bi bi-whatsapp" aria-hidden="true"></i>
                                            <span class="visually-hidden">Nomor WA</span>
                                            <?= esc($orang['nomor_wa_tampil']) ?>
                                        </p>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif ?>
                </div>
            </div>
        </section>
    </div>

    <section class="card mb-4" aria-labelledby="judul-atlet">
        <div class="card-body p-0">
            <div class="d-flex flex-wrap align-items-baseline justify-content-between gap-2 px-3 px-lg-4 pt-3 pt-lg-4 pb-2">
                <h2 id="judul-atlet" class="h5 mb-0">Atlet <span class="text-body-secondary fw-normal">(<?= (int) $p['jumlah_atlet'] ?>)</span></h2>
                <?php if (! $lunas && $p['tanpa_nomor_dada'] > 0) : ?>
                    <p class="small text-body-secondary mb-0">Nomor dada dibuat saat pendaftaran ditandai Lunas.</p>
                <?php endif ?>
            </div>
            <?php if ($p['atlet'] === []) : ?>
                <p class="text-body-secondary px-3 px-lg-4 pb-4 mb-0">Pendaftaran ini belum punya atlet.</p>
            <?php else : ?>
                <div class="tabel-gulir">
                    <table class="table align-middle mb-0" id="tabel-atlet">
                        <thead>
                            <tr>
                                <th scope="col" class="ps-3 ps-lg-4 text-end">No</th>
                                <th scope="col">Nama</th>
                                <th scope="col">NISN</th>
                                <th scope="col">Tanggal lahir</th>
                                <th scope="col">Jenis kelamin</th>
                                <th scope="col">Kelompok umur</th>
                                <th scope="col">Nomor lomba</th>
                                <th scope="col" class="pe-3 pe-lg-4">Nomor dada</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($p['atlet'] as $i => $atlet) : ?>
                                <tr data-id="<?= (int) $atlet['id'] ?>">
                                    <td class="ps-3 ps-lg-4 text-end"><?= $i + 1 ?></td>
                                    <th scope="row" class="fw-semibold"><?= esc($atlet['nama']) ?></th>
                                    <td class="text-nowrap"><?= esc($atlet['nisn']) ?></td>
                                    <td class="text-nowrap"><?= esc(tanggal_id($atlet['tanggal_lahir'])) ?></td>
                                    <td><?= esc($atlet['jenis_kelamin_label']) ?></td>
                                    <td class="text-nowrap"><?= esc($atlet['kelompok_umur']) ?></td>
                                    <td>
                                        <?php if ($atlet['nomor_lomba'] === []) : ?>
                                            <?= $strip ?>
                                        <?php else : ?>
                                            <ul class="list-unstyled mb-0">
                                                <?php foreach ($atlet['nomor_lomba'] as $nomor) : ?>
                                                    <li>
                                                        <?= esc($nomor['nama']) ?>
                                                        <?php if ($nomor['jenis'] === 'beregu') : ?>
                                                            <span class="badge rounded-pill text-bg-light border">Beregu</span>
                                                        <?php endif ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif ?>
                                    </td>
                                    <td class="pe-3 pe-lg-4 nomor-dada">
                                        <?php if ($atlet['nomor_dada'] === null) : ?>
                                            <?= $strip ?><span class="visually-hidden">Belum ada nomor dada</span>
                                        <?php else : ?>
                                            <span class="fw-bold fs-5"><?= esc($atlet['nomor_dada']) ?></span>
                                        <?php endif ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif ?>
        </div>
    </section>

    <?php if ($p['beregu'] !== []) : ?>
        <section class="card mb-4" aria-labelledby="judul-beregu">
            <div class="card-body p-3 p-lg-4">
                <h2 id="judul-beregu" class="h5 mb-3">Tim beregu</h2>
                <div class="row g-3">
                    <?php foreach ($p['beregu'] as $tim) : ?>
                        <?php $nomorId = (int) $tim['nomor_lomba_id'] ?>
                        <div class="col-12 col-lg-6">
                            <div class="border rounded p-3 h-100 tim-beregu">
                                <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                                    <h3 class="h6 mb-0"><?= esc($tim['nomor_lomba']) ?></h3>
                                    <span class="text-nowrap">
                                        <?= (int) $tim['biaya_tim'] > 0
                                            ? esc(rupiah((int) $tim['biaya_tim']))
                                            : '<span class="text-body-secondary">Tanpa biaya tim (tambahan anggota tim yang sudah terdaftar)</span>' ?>
                                    </span>
                                </div>
                                <?php if (isset($timTanpaPembayar[$nomorId])) : ?>
                                    <div class="alert alert-warning small py-2 px-3 mb-2 tim-tanpa-pembayar" data-nomor="<?= (int) $nomorId ?>">
                                        <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                                        Tim sekolah ini tidak punya pembayar biaya tim (<?= esc(rupiah((int) $timTanpaPembayar[$nomorId]['tarif'])) ?>), karena pendaftaran yang membayarnya sudah dihapus atau diubah.
                                        Tagihkan lewat <a href="<?= site_url($alamat . '/ubah') ?>">Ubah</a> dengan mencentang "Bebankan biaya tim ke pendaftaran ini".
                                    </div>
                                <?php endif ?>
                                <p class="mb-1 small text-body-secondary"><?= count($tim['anggota']) ?> anggota</p>
                                <ol class="mb-0 ps-3">
                                    <?php foreach ($tim['anggota'] as $anggota) : ?>
                                        <li>
                                            <?= esc($anggota['nama']) ?>
                                            <span class="text-body-secondary small">(<?= esc($anggota['nisn']) ?><?= $anggota['nomor_dada'] === null ? '' : ', nomor dada ' . esc($anggota['nomor_dada']) ?>)</span>
                                        </li>
                                    <?php endforeach; ?>
                                </ol>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif ?>

    <section class="card mb-4" aria-labelledby="judul-biaya">
        <div class="card-body p-3 p-lg-4">
            <h2 id="judul-biaya" class="h5 mb-3">Biaya</h2>
            <dl class="row mb-0" id="biaya-pendaftaran">
                <dt class="col-7 col-sm-4 fw-normal">Subtotal individu</dt>
                <dd class="col-5 col-sm-8 text-end text-sm-start"><?= esc(rupiah($p['subtotal_individu'])) ?></dd>
                <dt class="col-7 col-sm-4 fw-normal">Subtotal beregu</dt>
                <dd class="col-5 col-sm-8 text-end text-sm-start"><?= esc(rupiah($p['subtotal_beregu'])) ?></dd>
                <dt class="col-7 col-sm-4 fw-bold">Total</dt>
                <dd class="col-5 col-sm-8 text-end text-sm-start fw-bold fs-5 mb-0"><?= esc(rupiah($p['total'])) ?></dd>
            </dl>
        </div>
    </section>

    <?= view('admin/pendaftaran/_bukti_bayar', ['pendaftaran' => $p], ['saveData' => false]) ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<?= $this->endSection() ?>
