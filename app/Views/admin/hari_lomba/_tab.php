<?php
/**
 * Tab halaman hari lomba (F8-03): Daftar ulang, Cetak BIB, Daftar peserta,
 * Jadwal pertandingan (F9-07), dan Seri dan lintasan (F9-08). Satu butir menu admin "Hari lomba" membuka semuanya. Di ponsel label
 * "Daftar peserta" dan "Jadwal pertandingan" dipendekkan supaya muat.
 *
 * @var string $aktif alamat tab yang sedang dibuka
 */
$tab = [
    'admin/daftar-ulang'      => ['Daftar ulang', 'qr-code-scan', null],
    'admin/nomor-atlet/cetak' => ['Cetak BIB', 'printer', null],
    'admin/daftar-peserta'    => ['Daftar peserta', 'list-ol', 'Peserta'],
    // Jadwal pertandingan (F9-07), halaman bertampilan baru.
    'admin/jadwal-pertandingan' => ['Jadwal pertandingan', 'calendar-week', 'Jadwal'],
    // Seri dan lintasan (F9-08), halaman bertampilan baru.
    'admin/seri' => ['Seri dan lintasan', 'list-ol', 'Seri'],
];
?>
<nav class="mb-4" aria-label="Halaman hari lomba">
    <ul class="nav nav-pills flex-nowrap overflow-auto gap-1">
        <?php foreach ($tab as $alamat => [$label, $ikon, $pendek]) : ?>
            <li class="nav-item">
                <a class="nav-link text-nowrap px-2 px-sm-3<?= $alamat === $aktif ? ' active' : '' ?>" href="<?= site_url($alamat) ?>"<?= $alamat === $aktif ? ' aria-current="page"' : '' ?>>
                    <i class="bi bi-<?= $ikon ?>" aria-hidden="true"></i>
                    <?php if ($pendek === null) : ?>
                        <?= esc($label) ?>
                    <?php else : ?>
                        <span class="d-none d-sm-inline"><?= esc($label) ?></span><span class="d-sm-none"><?= esc($pendek) ?></span>
                    <?php endif ?>
                </a>
            </li>
        <?php endforeach ?>
    </ul>
</nav>
