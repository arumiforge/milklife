<?php
/**
 * Daftar menu admin, satu-satunya tempat menu ini ditulis.
 *
 * Dipakai dua kali oleh `tata_letak/admin.php`: di menu samping (layar lebar)
 * dan di menu bawah (ponsel). Markupnya sama; bentuknya diatur dari wadahnya
 * di `aset/css/admin.css` (`.menu-samping …` dan `.menu-bawah …`).
 *
 * Menu samping (`$tempatMenu = 'samping'`) punya daftar kedua yang menempel
 * di bagian bawah menu (K102): Pengecekan, lalu Halaman depan (tab baru).
 * Di ponsel keduanya tampil sebagai ikon di kepala halaman, karena menu
 * bawah sudah penuh.
 *
 * Butir aktif ditentukan dari alamat sekarang: alamat butir itu sendiri atau
 * halaman di bawahnya (`admin/pendaftaran/…` mengaktifkan "Pendaftaran").
 * Alamat lain yang ikut mengaktifkan butir ditulis di `aktif_juga`
 * (halaman Akun dibuka dari daftar pengaturan, jadi ikut menandai "Pengaturan").
 * Butir baru ditambahkan bersama halamannya; ikon dari Bootstrap Icons
 * (nama tanpa awalan `bi-`). Label pendek supaya muat di menu bawah;
 * bila perlu, `label_pendek` dipakai di menu bawah dan `label` di menu samping.
 *
 * Di rincian pendaftaran dan turunannya, butir aktif mengikuti asal
 * halamannya (`AsalRincian`, K117): dibuka dari Atlet, yang aktif Atlet.
 *
 * @var CodeIgniter\View\View $this
 * @var string|null           $tempatMenu `samping` atau kosong (menu bawah)
 */

$menuAdmin = [
    ['label' => 'Dasbor', 'ikon' => 'speedometer2', 'alamat' => 'admin/dasbor'],
    // Panel data (Tahap 6, F6-01).
    ['label' => 'Pendaftaran', 'ikon' => 'clipboard-check', 'alamat' => 'admin/pendaftaran'],
    ['label' => 'Atlet', 'ikon' => 'people', 'alamat' => 'admin/atlet'],
    // Atlet yang ditolak panitia, terpisah dari atlet terdaftar (K117).
    ['label' => 'Atlet ditolak', 'label_pendek' => 'Ditolak', 'ikon' => 'person-x', 'alamat' => 'admin/atlet-ditolak'],
    ['label' => 'Rekap', 'ikon' => 'bar-chart-line', 'alamat' => 'admin/rekap'],
    // Hari lomba (F8-03): daftar ulang, cetak BIB, daftar peserta.
    ['label' => 'Hari lomba', 'label_pendek' => 'Lomba', 'ikon' => 'flag', 'alamat' => 'admin/daftar-ulang', 'aktif_juga' => ['admin/nomor-atlet', 'admin/daftar-peserta']],
    // Data sekolah (K97).
    ['label' => 'Sekolah', 'ikon' => 'building', 'alamat' => 'admin/sekolah'],
    // Hasil lomba (F8-04, K130): butir sendiri di menu samping; tidak di
    // menu bawah ponsel (`hanya_samping`, K132–K133).
    ['label' => 'Hasil lomba', 'ikon' => 'trophy', 'alamat' => 'admin/hasil', 'hanya_samping' => true],
    ['label' => 'Pengaturan', 'ikon' => 'gear', 'alamat' => 'admin/pengaturan', 'aktif_juga' => ['admin/akun']],
];

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
        <?php if (($butir['hanya_samping'] ?? false) && ($tempatMenu ?? '') !== 'samping') {
            continue;
        } ?>
        <li>
            <a class="menu-admin-tautan" href="<?= site_url($butir['alamat']) ?>"<?= $butirAktif($butir) ? ' aria-current="page"' : '' ?><?= isset($butir['label_pendek']) && ($tempatMenu ?? '') !== 'samping' ? ' title="' . esc($butir['label'], 'attr') . '"' : '' ?>>
                <i class="bi bi-<?= esc($butir['ikon'], 'attr') ?>" aria-hidden="true"></i>
                <span class="menu-admin-label"><?= esc(($tempatMenu ?? '') === 'samping' ? $butir['label'] : ($butir['label_pendek'] ?? $butir['label'])) ?></span>
            </a>
        </li>
    <?php endforeach; ?>
</ul>
<?php if (($tempatMenu ?? '') === 'samping') : ?>
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
