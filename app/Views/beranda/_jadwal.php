<?php
/**
 * Bagian jadwal halaman depan (`#jadwal`): sosialisasi, technical meeting,
 * dan jadwal lain yang ditandai tampil oleh admin, berurutan seperti di panel
 * admin.
 *
 * Tiap jadwal menjadi satu kartu: kotak tanggal, judul, waktu (WIB), tempat,
 * tautan (tab baru), dan keterangan (baris baru menjadi `<br>`). Teks admin
 * di-escape.
 *
 * Data yang tersedia (dari `IsiHalamanDepan::jadwal()`):
 *
 * @var list<array{id: int, judul: string, keterangan: string, waktu: string|null, tempat: string, tautan: string}> $jadwal
 *      Jadwal yang ditandai tampil, berurutan; [] bila belum ada.
 *      `waktu` 'Y-m-d H:i:s' (WIB) atau null (belum pasti); `keterangan`, `tempat`,
 *      `tautan` '' bila kosong; `tautan` selalu alamat http/https.
 *
 * Kesepakatan (rencana Tahap 3, "Halaman depan"):
 * - Bagian tidak ditampilkan bila `$jadwal === []`.
 * - `<section id="jadwal" aria-labelledby="judul-jadwal">` dengan `<h2 id="judul-jadwal">`.
 * - Waktu dengan `tanggal_id($waktu, true)` + " WIB"; null → "Waktu segera diumumkan".
 * - Tautan dibuka di tab baru dengan `rel="noopener noreferrer"`.
 * - Gaya hanya di wilayah "Bagian jadwal, unduhan, kontak (F3-03)" di `tema.css`.
 */

if ($jadwal === []) {
    return;
}
?>
<section id="jadwal" class="bagian-tambahan bagian-jadwal" aria-labelledby="judul-jadwal">
    <div class="container">
        <h2 id="judul-jadwal" class="bagian-tambahan-judul"><i class="bi bi-calendar-week" aria-hidden="true"></i> Jadwal</h2>

        <ol class="daftar-jadwal list-unstyled mb-0">
            <?php foreach ($jadwal as $butir) : ?>
                <?php
                // Kotak tanggal: angka hari dan tiga huruf awal bulan ("9", "Des").
                $tanggal = $butir['waktu'] === null ? null : explode(' ', tanggal_id($butir['waktu']));
                ?>
                <li class="jadwal-butir" data-id="<?= (int) $butir['id'] ?>">
                    <?php if ($tanggal === null || count($tanggal) < 3) : ?>
                        <span class="jadwal-tanggal jadwal-tanggal-kosong" aria-hidden="true"><i class="bi bi-hourglass-split"></i></span>
                    <?php else : ?>
                        <span class="jadwal-tanggal" aria-hidden="true">
                            <span class="jadwal-tanggal-hari"><?= esc($tanggal[0]) ?></span>
                            <span class="jadwal-tanggal-bulan"><?= esc(mb_substr($tanggal[1], 0, 3)) ?></span>
                        </span>
                    <?php endif ?>

                    <div class="jadwal-isi">
                        <h3 class="jadwal-judul"><?= esc($butir['judul']) ?></h3>

                        <ul class="jadwal-rincian list-unstyled">
                            <li class="jadwal-waktu">
                                <i class="bi bi-clock" aria-hidden="true"></i>
                                <?php if ($butir['waktu'] === null) : ?>
                                    <span>Waktu segera diumumkan</span>
                                <?php else : ?>
                                    <span><?= esc(tanggal_id($butir['waktu'], true)) ?> WIB</span>
                                <?php endif ?>
                            </li>
                            <?php if ($butir['tempat'] !== '') : ?>
                                <li class="jadwal-tempat">
                                    <i class="bi bi-geo-alt" aria-hidden="true"></i>
                                    <span><?= esc($butir['tempat']) ?></span>
                                </li>
                            <?php endif ?>
                        </ul>

                        <?php if ($butir['keterangan'] !== '') : ?>
                            <p class="jadwal-keterangan"><?= nl2br(esc($butir['keterangan']), false) ?></p>
                        <?php endif ?>

                        <?php if ($butir['tautan'] !== '') : ?>
                            <a class="btn btn-outline-primary btn-sm jadwal-tautan" href="<?= esc($butir['tautan'], 'attr') ?>" target="_blank" rel="noopener noreferrer">
                                <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Buka tautan<span class="visually-hidden"> <?= esc($butir['judul']) ?> (di tab baru)</span>
                            </a>
                        <?php endif ?>
                    </div>
                </li>
            <?php endforeach ?>
        </ol>
    </div>
</section>
