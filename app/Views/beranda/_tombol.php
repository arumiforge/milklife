<?php
/**
 * Tombol Pendaftaran dan Cek Pendaftaran halaman depan, ajakan utama
 * halaman. Tampil di bagian paling atas bagian lomba, tepat di bawah kepala
 * (K75), dan tetap tampil walau bagian lomba belum ada. Di layar lebar kedua
 * tombol membagi dua lebar halaman; di ponsel bertumpuk selebar layar.
 *
 * Tombol tanpa alamat tampil nonaktif dengan `keterangan`-nya, atau "Segera"
 * bila tanpa keterangan (K39; Pendaftaran hanya menaut saat terbuka).
 *
 * @var list<array{kunci: string, label: string, alamat: string|null, utama: bool, keterangan?: string|null}> $tombol
 */

$ikon = ['pendaftaran' => 'pencil-square', 'cek' => 'search'];
?>
<div class="bagian-tombol">
    <div class="container">
        <nav class="tombol-utama" aria-label="Pendaftaran">
            <?php foreach ($tombol as $butir) : ?>
                <?php $kelas = 'btn btn-lg ' . ($butir['utama'] ? 'btn-primary' : 'btn-biru') . ' tombol-' . $butir['kunci']; ?>
                <?php $ikonTombol = isset($ikon[$butir['kunci']]) ? '<i class="bi bi-' . $ikon[$butir['kunci']] . '" aria-hidden="true"></i> ' : ''; ?>
                <?php if ($butir['alamat'] === null) : ?>
                    <a class="<?= $kelas ?> disabled tombol-nonaktif" role="link" aria-disabled="true">
                        <?= $ikonTombol ?><?= esc($butir['label']) ?>
                        <span class="tombol-segera"><?= esc($butir['keterangan'] ?? 'Segera') ?></span>
                    </a>
                <?php else : ?>
                    <a class="<?= $kelas ?>" href="<?= site_url($butir['alamat']) ?>"><?= $ikonTombol ?><?= esc($butir['label']) ?></a>
                <?php endif ?>
            <?php endforeach ?>
        </nav>
    </div>
</div>
