<?php
/**
 * Menu panel admin untuk tata letak lama (`tata_letak/admin.php`, Bootstrap),
 * selama masih ada halaman yang belum pindah ke `admin_tw` (Tahap 9). Butirnya
 * dari `App\Libraries\Panel\MenuAdmin`, sumber yang sama dengan menu tata
 * letak baru (`tata_letak/_menu_admin_tw.php`), jadi isinya selalu sama.
 *
 * Dipakai dua kali oleh `tata_letak/admin.php`:
 * - menu samping (`$tempatMenu = 'samping'`): semua butir, berkelompok
 *   (Pendaftaran, Hari lomba, Hasil, Konten, Pengaturan). Di layar lebar menu
 *   ini tetap di kiri; di ponsel dan tablet menjadi sidebar (laci samping)
 *   yang dibuka tombol menu di kepala halaman (K131, K133). Daftar kedua
 *   menempel di bagian bawahnya (K102): Pengecekan, lalu Lihat halaman depan
 *   (tab baru).
 * - menu bawah yang menempel di ponsel dan tablet (`$tempatMenu` kosong):
 *   5 butir urusan pendaftaran (K132, K133).
 * Bentuknya diatur dari wadahnya di `aset/css/admin.css` (`.menu-samping …`
 * dan `.menu-bawah …`). Ikon Lucide (sprite), ukurannya juga di `admin.css`.
 *
 * Penanda sama dengan menu baru: `data-menu-kelompok`, `data-menu-tautan`,
 * `data-menu-akhir`.
 *
 * @var CodeIgniter\View\View $this
 * @var string|null           $tempatMenu `samping` atau kosong (menu bawah)
 */

use App\Libraries\Panel\MenuAdmin;

$diSamping   = ($tempatMenu ?? '') === 'samping';
$alamatAktif = MenuAdmin::alamatAktif();

$tautan = static function (array $butir, bool $pendek) use ($alamatAktif): string {
    return '<a class="menu-admin-tautan" data-menu-tautan="' . esc($butir['alamat'], 'attr') . '" href="' . site_url($butir['alamat']) . '"'
        . ($butir['alamat'] === $alamatAktif ? ' aria-current="page"' : '')
        . ($pendek && isset($butir['label_pendek']) ? ' title="' . esc($butir['label'], 'attr') . '"' : '') . '>'
        . ikon($butir['ikon'])
        . '<span class="menu-admin-label">' . esc($pendek ? ($butir['label_pendek'] ?? $butir['label']) : $butir['label']) . '</span></a>';
};
?>
<?php if (! $diSamping) : ?>
<ul class="menu-admin">
    <?php foreach (MenuAdmin::bawah() as $butir) : ?>
        <li><?= $tautan($butir, true) ?></li>
    <?php endforeach; ?>
</ul>
<?php else : ?>
    <?php foreach (MenuAdmin::kelompok() as $nama => $daftar) : ?>
<div class="menu-admin-kelompok" data-menu-kelompok="<?= esc($nama, 'attr') ?>">
        <?php if (count($daftar) > 1) : ?>
    <p class="menu-admin-kelompok-judul"><?= esc($nama) ?></p>
        <?php endif ?>
    <ul class="menu-admin">
        <?php foreach ($daftar as $butir) : ?>
        <li><?= $tautan($butir, false) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
    <?php endforeach; ?>
<ul class="menu-admin menu-admin-akhir" data-menu-akhir>
    <?php foreach (MenuAdmin::akhir() as $butir) : ?>
        <li><?= $tautan($butir, false) ?></li>
    <?php endforeach; ?>
    <li>
        <a class="menu-admin-tautan tombol-halaman-depan" href="<?= site_url('/') ?>" target="_blank" rel="noopener" data-menu-tautan="/" title="Buka halaman depan di tab baru">
            <?= ikon('external-link') ?>
            <span class="menu-admin-label">Lihat halaman depan</span>
            <span class="visually-hidden">(tab baru)</span>
        </a>
    </li>
</ul>
<?php endif ?>
