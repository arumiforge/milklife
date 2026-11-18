<?php
/**
 * Daftar menu admin, satu-satunya tempat menu ini ditulis.
 *
 * Dipakai dua kali oleh `tata_letak/admin.php`:
 * - menu samping (`$tempatMenu = 'samping'`): semua butir. Di layar lebar
 *   menu ini tetap di kiri; di ponsel dan tablet menjadi sidebar (laci
 *   samping) yang dibuka tombol menu di kepala halaman (K131, K133).
 * - menu bawah yang menempel di ponsel dan tablet (`$tempatMenu` kosong):
 *   hanya butir bertanda `bawah`, yaitu 5 butir urusan pendaftaran (K132,
 *   K133). Butir lain di ponsel hanya lewat sidebar.
 * Markupnya sama; bentuknya diatur dari wadahnya di `aset/css/admin.css`
 * (`.menu-samping …` dan `.menu-bawah …`).
 *
 * Menu samping punya daftar kedua yang menempel di bagian bawah menu (K102):
 * Pengecekan, lalu Halaman depan (tab baru).
 *
 * Butir aktif ditentukan dari alamat sekarang: alamat butir itu sendiri atau
 * halaman di bawahnya (`admin/pendaftaran/…` mengaktifkan "Pendaftaran").
 * Alamat lain yang ikut mengaktifkan butir ditulis di `aktif_juga`
 * (halaman Akun dibuka dari daftar pengaturan, jadi ikut menandai "Pengaturan").
 * Butir baru ditambahkan bersama halamannya dan otomatis masuk sidebar; tanpa
 * `bawah` butir itu tidak masuk menu bawah. Ikon dari Bootstrap Icons
 * (nama tanpa awalan `bi-`). Bila perlu, `label_pendek` dipakai di menu
 * bawah dan `label` di menu samping.
 *
 * Di rincian pendaftaran dan turunannya, butir aktif mengikuti asal
 * halamannya (`AsalRincian`, K117): dibuka dari Atlet, yang aktif Atlet.
 *
 * @var CodeIgniter\View\View $this
 * @var string|null           $tempatMenu `samping` atau kosong (menu bawah)
 */

// `bawah` => true: ikut menu bawah ponsel. Tepat 5 butir urusan pendaftaran (K133).
$menuAdmin = [
    ['label' => 'Dasbor', 'ikon' => 'speedometer2', 'alamat' => 'admin/dasbor', 'bawah' => true],
    // Panel data (Tahap 6, F6-01).
    ['label' => 'Pendaftaran', 'ikon' => 'clipboard-check', 'alamat' => 'admin/pendaftaran', 'bawah' => true],
    ['label' => 'Atlet', 'ikon' => 'people', 'alamat' => 'admin/atlet', 'bawah' => true],
    // Atlet yang ditolak panitia, terpisah dari atlet terdaftar (K117).
    ['label' => 'Atlet ditolak', 'label_pendek' => 'Ditolak', 'ikon' => 'person-x', 'alamat' => 'admin/atlet-ditolak', 'bawah' => true],
    ['label' => 'Rekap', 'ikon' => 'bar-chart-line', 'alamat' => 'admin/rekap', 'bawah' => true],
    // Hari lomba (F8-03): daftar ulang, cetak BIB, daftar peserta.
    ['label' => 'Hari lomba', 'label_pendek' => 'Lomba', 'ikon' => 'flag', 'alamat' => 'admin/daftar-ulang', 'aktif_juga' => ['admin/nomor-atlet', 'admin/daftar-peserta']],
    // Hasil lomba (F8-04), butir sendiri (K130), hanya di sidebar (K132).
    ['label' => 'Hasil lomba', 'label_pendek' => 'Hasil', 'ikon' => 'trophy', 'alamat' => 'admin/hasil'],
    // Data sekolah (K97).
    ['label' => 'Sekolah', 'ikon' => 'building', 'alamat' => 'admin/sekolah'],
    ['label' => 'Pengaturan', 'ikon' => 'gear', 'alamat' => 'admin/pengaturan', 'aktif_juga' => ['admin/akun']],
];

$diSamping = ($tempatMenu ?? '') === 'samping';

if (! $diSamping) {
    $menuAdmin = array_filter($menuAdmin, static fn (array $butir): bool => ! empty($butir['bawah']));
}

// Bagian bawah menu samping (K102).
$menuAkhir = [
    ['label' => 'Pengecekan', 'ikon' => 'clipboard2-pulse', 'alamat' => 'admin/pengecekan'],
];

// Rincian pendaftaran yang dibuka dari menu lain menandai menu asalnya (K117).
$menuAsal = App\Libraries\Panel\AsalRincian::menuAktif();

$butirAktif = static function (array $butir) use ($menuAsal): bool {
    if ($menuAsal !== null) {
        return $butir['alamat'] === $menuAsal;
    }

    foreach ([$butir['alamat'], ...($butir['aktif_juga'] ?? [])] as $alamat) {
        if (url_is($alamat) || url_is($alamat . '/*')) {
            return true;
        }
    }

    return false;
};
?>
<ul class="menu-admin">
    <?php foreach ($menuAdmin as $butir) : ?>
        <li>
            <a class="menu-admin-tautan" href="<?= site_url($butir['alamat']) ?>"<?= $butirAktif($butir) ? ' aria-current="page"' : '' ?><?= isset($butir['label_pendek']) && ! $diSamping ? ' title="' . esc($butir['label'], 'attr') . '"' : '' ?>>
                <i class="bi bi-<?= esc($butir['ikon'], 'attr') ?>" aria-hidden="true"></i>
                <span class="menu-admin-label"><?= esc($diSamping ? $butir['label'] : ($butir['label_pendek'] ?? $butir['label'])) ?></span>
            </a>
        </li>
    <?php endforeach; ?>
</ul>
<?php if ($diSamping) : ?>
<ul class="menu-admin menu-admin-akhir">
    <?php foreach ($menuAkhir as $butir) : ?>
        <li>
            <a class="menu-admin-tautan" href="<?= site_url($butir['alamat']) ?>"<?= $butirAktif($butir) ? ' aria-current="page"' : '' ?>>
                <i class="bi bi-<?= esc($butir['ikon'], 'attr') ?>" aria-hidden="true"></i>
                <span class="menu-admin-label"><?= esc($butir['label']) ?></span>
            </a>
        </li>
    <?php endforeach; ?>
    <li>
        <a class="menu-admin-tautan tombol-halaman-depan" href="<?= site_url('/') ?>" target="_blank" rel="noopener" title="Buka halaman depan di tab baru">
            <i class="bi bi-house-door" aria-hidden="true"></i>
            <span class="menu-admin-label">Halaman depan</span>
            <i class="bi bi-box-arrow-up-right ms-auto small" aria-hidden="true"></i>
            <span class="visually-hidden">(tab baru)</span>
        </a>
    </li>
</ul>
<?php endif ?>
