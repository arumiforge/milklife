<?php
/**
 * Bagian "Perlu didaftarkan ulang" di kartu Cek Pendaftaran (K116, K117).
 *
 * Ditulis untuk ofisial sekolah, bukan untuk panitia: yang tampil hanya apa
 * yang terjadi, alasannya, dan apa yang perlu dilakukan. Angka total sebelum
 * dan sesudah, waktu penolakan, dan nama admin hanya ada di panel admin.
 *
 * - Atlet yang belum didaftarkan ulang: satu kotak per atlet berisi data
 *   singkat (tanpa tanggal lahir, K52), yang didaftarkan, alasan panitia,
 *   dan kelompok umur serta nomor lomba yang bisa dipilih sekarang.
 * - Langkah berikutnya, tombol "Daftarkan ulang" dan "Hubungi panitia".
 * - Atlet yang sudah didaftarkan ulang cukup namanya, sebagai tanda bahwa
 *   pendaftaran ulangnya sudah masuk.
 *
 * @var array<string, mixed> $p            satu pendaftaran (App\Libraries\DataPendaftaran)
 * @var int                  $urutan       nomor kartu, untuk id unik
 * @var string|null          $waTanya      alamat WA tanya admin, atau null
 * @var bool                 $ditolakSemua semua atlet pendaftaran ini ditolak
 */
$ditolak = $p['atlet_ditolak'] ?? [];
$ulang   = $p['atlet_didaftarkan_ulang'] ?? [];

if ($ditolak === [] && $ulang === []) {
    return;
}
?>
<section class="cek-ditolak<?= $ditolak === [] ? ' cek-ditolak-beres' : '' ?>" id="atlet-ditolak-<?= $urutan ?>" aria-labelledby="judul-ditolak-<?= $urutan ?>">
    <?php if ($ditolak !== []) : ?>
        <div class="cek-ditolak-kepala">
            <span class="cek-ditolak-ikon" aria-hidden="true"><i class="bi bi-person-x"></i></span>
            <div>
                <h4 class="cek-ditolak-judul" id="judul-ditolak-<?= $urutan ?>">
                    <?= $ditolakSemua ? 'Semua atlet di pendaftaran ini ditolak panitia' : count($ditolak) . ' atlet perlu didaftarkan ulang' ?>
                </h4>
                <p class="mb-0">
                    <?php if ($ditolakSemua) : ?>
                        Pendaftaran ini tidak perlu dibayar. Daftarkan ulang atlet di bawah ini lewat menu Pendaftaran.
                    <?php else : ?>
                        Atlet di bawah ini tidak lagi termasuk pendaftaran ini dan tidak ditagih. Atlet lain tetap terdaftar seperti biasa.
                    <?php endif ?>
                </p>
            </div>
        </div>

        <ul class="cek-ditolak-daftar">
            <?php foreach ($ditolak as $d) : ?>
                <li class="cek-ditolak-atlet">
                    <div class="cek-ditolak-nama">
                        <strong><?= esc($d['nama']) ?></strong>
                        <span>NISN <?= esc($d['nisn']) ?> · <?= esc($d['jenis_kelamin_label']) ?> · lahir <?= (int) $d['tahun_lahir'] ?></span>
                    </div>

                    <div class="cek-ditolak-banding">
                        <div class="cek-ditolak-lama">
                            <p class="cek-ditolak-label">Didaftarkan di</p>
                            <p class="mb-0"><strong><?= esc($d['kelompok_umur']) ?></strong></p>
                            <?php if ($d['nomor_lomba'] !== []) : ?>
                                <p class="mb-0 small"><?= esc(implode(', ', array_column($d['nomor_lomba'], 'nama'))) ?></p>
                            <?php endif ?>
                        </div>
                        <?php if ($d['sesuai_beda'] && $d['kelompok_umur_sesuai'] !== null) : ?>
                            <span class="cek-ditolak-panah" aria-hidden="true"><i class="bi bi-arrow-right"></i></span>
                            <div class="cek-ditolak-baru">
                                <p class="cek-ditolak-label">Daftarkan ulang di</p>
                                <p class="mb-0"><strong><?= esc($d['kelompok_umur_sesuai']) ?></strong></p>
                                <?php if ($d['nomor_sesuai'] !== []) : ?>
                                    <p class="mb-0 small">Pilihan nomor: <?= esc(implode(', ', array_column($d['nomor_sesuai'], 'nama'))) ?></p>
                                <?php endif ?>
                            </div>
                        <?php endif ?>
                    </div>

                    <div class="cek-ditolak-alasan">
                        <p class="cek-ditolak-label">Alasan dari panitia</p>
                        <p class="mb-0"><?= nl2br(esc($d['alasan'])) ?></p>
                    </div>
                </li>
            <?php endforeach ?>
        </ul>

        <div class="cek-ditolak-langkah">
            <p class="cek-ditolak-label">Yang perlu dilakukan</p>
            <ol class="mb-3">
                <li>Buka menu Pendaftaran dan daftarkan atletnya lagi di kelompok umur yang disarankan. NISN yang sama boleh dipakai.</li>
                <li>Bila biaya untuk atlet ini sudah terlanjur ditransfer, hubungi panitia untuk penyelesaiannya.</li>
            </ol>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-primary" href="<?= site_url('pendaftaran') ?>"><i class="bi bi-pencil-square" aria-hidden="true"></i> Daftarkan ulang</a>
                <?php if (($waTanya ?? null) !== null) : ?>
                    <a class="btn btn-outline-success" href="<?= esc($waTanya, 'attr') ?>" target="_blank" rel="noopener"><i class="bi bi-whatsapp" aria-hidden="true"></i> Hubungi panitia</a>
                <?php endif ?>
            </div>
        </div>
    <?php else : ?>
        <div class="cek-ditolak-kepala">
            <span class="cek-ditolak-ikon" aria-hidden="true"><i class="bi bi-check2-circle"></i></span>
            <div>
                <h4 class="cek-ditolak-judul" id="judul-ditolak-<?= $urutan ?>">Atlet yang ditolak sudah didaftarkan ulang</h4>
                <p class="mb-0"><?= $ditolakSemua ? 'Pendaftaran ini tidak perlu dibayar. Data dan tagihan atletnya ada di pendaftaran yang baru.' : 'Tidak ada lagi yang perlu dilakukan untuk atlet yang ditolak.' ?></p>
            </div>
        </div>
    <?php endif ?>

    <?php if ($ulang !== []) : ?>
        <div class="cek-ditolak-ulang">
            <p class="cek-ditolak-label">Sudah didaftarkan ulang</p>
            <ul class="mb-0">
                <?php foreach ($ulang as $u) : ?>
                    <li><i class="bi bi-check-circle-fill" aria-hidden="true"></i> <?= esc($u['nama']) ?> <span class="text-body-secondary">· NISN <?= esc($u['nisn']) ?></span></li>
                <?php endforeach ?>
            </ul>
        </div>
    <?php endif ?>
</section>
