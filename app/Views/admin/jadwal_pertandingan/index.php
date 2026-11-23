<?php
/**
 * Jadwal pertandingan di panel admin (F9-07), tata letak Tailwind `admin_tw`.
 * Data dari `Admin\JadwalPertandinganController::index()`.
 *
 * - Satu formulir untuk semua babak: tanggal (pilihan Tanggal, Bulan, Tahun
 *   dari `aset/js/pilih-tanggal.js`, K89, sama dengan halaman admin lain),
 *   jam mulai, dan lokasi. Urutan baris = urutan jadwal; babak yang belum dijadwalkan di akhir.
 * - "Isi berurutan" (`aset/js/admin-jadwal-pertandingan.js`) mengisi jam
 *   baris yang dicentang mulai jam tertentu dengan jeda tetap, tanpa menyimpan.
 * - Sakelar tampil untuk umum memakai pengendali Pengaturan > Halaman depan.
 * - Nomor acara (F11-07): "Beri nomor acara" memberi nomor berurutan menurut
 *   jadwal mulai nomor awal; kotak Nomor acara per babak untuk mengubahnya.
 *
 * @var CodeIgniter\View\View                    $this
 * @var list<array<string, mixed>>               $babak  `JadwalPertandingan::semua()`
 * @var list<string>                             $hari   'Y-m-d' yang punya babak
 * @var bool                                     $tampil sakelar `HalamanDepan.tampil_jadwal`
 * @var DateTimeInterface                        $kini
 * @var array<int|string, array<string, string>> $galat  id babak => kotak => pesan
 * @var array<int|string, mixed>                 $isian  isian yang dikembalikan saat ada galat
 * @var string                                   $acaraAwal nomor awal isian Beri nomor acara
 */

use App\Libraries\Hasil\JadwalPertandingan;

$kelasIsian = static fn (bool $salah): string => 'block min-h-11 w-full rounded-lg border p-2.5 text-base sm:text-sm focus:ring-4 '
    . ($salah ? 'border-red-500 bg-red-50 text-red-900 focus:border-red-500 focus:ring-red-100' : 'border-gray-300 bg-white text-gray-900 focus:border-primary-600 focus:ring-primary-100');
$nilai = static fn (array $b, string $kotak, string $asal): string => is_string($isian[$b['id']][$kotak] ?? null) ? $isian[$b['id']][$kotak] : $asal;

// ikon-sprite: clock circle-play circle-check save check download trophy external-link list-ordered calendar-x
$warnaStatus = ['belum' => 'abu', 'berlangsung' => 'oranye', 'selesai' => 'hijau'];
$ikonStatus  = ['belum' => 'clock', 'berlangsung' => 'circle-play', 'selesai' => 'circle-check'];
$terjadwal   = count(array_filter($babak, static fn (array $b): bool => $b['tanggal'] !== null));
$rentangTanggal = JadwalPertandingan::rentangIsian($kini);
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?>Jadwal pertandingan<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-4 p-4 sm:px-6 sm:py-4">
    <p class="text-gray-700">
        Satu baris berisi satu babak di <a href="<?= site_url('admin/hasil') ?>" class="font-semibold text-primary-700 underline">Hasil lomba</a>. Karena itu, jadwal selalu sama dengan babak yang diisi juri.
        Babak tanpa tanggal belum tampil di halaman Jadwal. Status Selesai muncul sendiri setelah hasil nomornya diterbitkan.
    </p>

    <section id="sakelar-jadwal" class="rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-5" aria-labelledby="judul-sakelar-jadwal">
        <h2 id="judul-sakelar-jadwal" class="text-lg font-bold text-gray-900">Tampil untuk umum</h2>
        <p class="mt-1 text-sm text-gray-600">
            <?php if ($tampil) : ?>
                Halaman <a href="<?= site_url('jadwal') ?>" target="_blank" rel="noopener" class="font-semibold text-primary-700 underline">Jadwal</a> sedang terbuka dan ada di menu halaman depan.
            <?php else : ?>
                Halaman Jadwal masih tersembunyi. Isi dan periksa jadwalnya dulu, lalu nyalakan sakelar ini.
            <?php endif ?>
            Agenda di halaman depan tidak terpengaruh.
        </p>
        <form action="<?= site_url('admin/pengaturan/halaman-depan/jadwal') ?>" method="post" class="mt-3 flex flex-wrap items-center gap-3">
            <?= csrf_field() ?>
            <input type="hidden" name="kembali" value="jadwal-pertandingan">
            <?= komponen('sakelar', ['label' => 'Tampilkan halaman Jadwal pertandingan', 'nama' => 'tampil_jadwal', 'centang' => $tampil]) ?>
            <?= komponen('tombol', ['label' => 'Simpan sakelar', 'tipe' => 'submit', 'varian' => 'kedua', 'ikon' => 'check']) ?>
        </form>
    </section>

    <?php if ($babak === []) : ?>
        <?= komponen('kosong', ['padat' => true, 'gambar' => ['gambar/admin/kosong-admin.webp', 'gambar/keadaan/kosong-data.webp'], 'ikon' => 'calendar-x', 'judul' => 'Belum ada babak', 'teks' => 'Jadwal disusun dari babak tiap nomor lomba. Tambahkan babak (misalnya Penyisihan dan Final) di Hasil lomba, lalu kembali ke sini untuk mengisi tanggal dan jamnya.', 'aksi' => komponen('tombol', ['label' => 'Buka hasil lomba', 'href' => site_url('admin/hasil'), 'ikon' => 'trophy'])]) ?>
    <?php else : ?>
        <?php if ($hari !== []) : ?>
            <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-5" aria-labelledby="judul-unduh-jadwal">
                <h2 id="judul-unduh-jadwal" class="text-lg font-bold text-gray-900">Unduh jadwal per hari</h2>
                <p class="mt-1 text-sm text-gray-600">PDF untuk dicetak atau dibagikan ke grup ofisial. Simpan jadwal dulu supaya perubahan ikut tercetak.</p>
                <div class="mt-3 flex flex-wrap gap-2" data-unduh-hari>
                    <?php foreach ($hari as $h) : ?>
                        <?= komponen('tombol_unduh', ['jenis' => 'pdf', 'href' => site_url('admin/jadwal-pertandingan/' . $h . '/pdf'), 'label' => 'Unduh PDF ' . JadwalPertandingan::namaHari($h), 'teks' => 'Unduh PDF ' . JadwalPertandingan::namaHari($h)]) ?>
                    <?php endforeach ?>
                </div>
            </section>
        <?php endif ?>

        <section id="nomor-acara" class="rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-5" aria-labelledby="judul-nomor-acara">
            <h2 id="judul-nomor-acara" class="text-lg font-bold text-gray-900">Nomor acara</h2>
            <p class="mt-1 text-sm text-gray-600">Nomor acara dipakai pembawa acara, juri, dan lembar hasil, misalnya Acara 101. Nomor diberi berurutan menurut tanggal dan jam di bawah. Babak tanpa tanggal tidak diberi nomor. Nomor yang sudah ada akan diganti. Setelah itu, Anda masih bisa mengubah nomor tiap babak.</p>
            <form action="<?= site_url('admin/jadwal-pertandingan/nomor-acara') ?>" method="post" class="mt-3 flex flex-wrap items-end gap-3"
                data-konfirmasi="<?= esc('Semua ' . $terjadwal . ' babak yang punya tanggal akan diberi nomor acara baru berurutan menurut jadwal. Nomor acara yang sudah ada diganti.', 'attr') ?>" data-konfirmasi-judul="Beri nomor acara?" data-konfirmasi-tombol="Beri nomor acara" data-konfirmasi-jenis="utama">
                <?= csrf_field() ?>
                <div class="w-40">
                    <label for="acara-awal" class="mb-1.5 block text-sm font-semibold text-gray-900">Nomor awal</label>
                    <input type="number" id="acara-awal" name="awal" class="<?= $kelasIsian(false) ?>" value="<?= esc($acaraAwal, 'attr') ?>" min="1" max="<?= JadwalPertandingan::NOMOR_ACARA_MAKSIMAL ?>" inputmode="numeric" required>
                </div>
                <?= komponen('tombol', ['label' => 'Beri nomor acara', 'tipe' => 'submit', 'varian' => 'kedua', 'ikon' => 'list-ordered']) ?>
            </form>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-5" aria-labelledby="judul-isi-berurutan" data-isi-berurutan hidden>
            <h2 id="judul-isi-berurutan" class="text-lg font-bold text-gray-900">Isi berurutan</h2>
            <p class="mt-1 text-sm text-gray-600">Centang babak di tabel, lalu isi jam mulai pertama dan jeda antarbabak. Jam diisi dari atas ke bawah. Tanggal dan lokasi ikut diisi bila Anda menuliskannya. Periksa hasilnya, lalu tekan Simpan jadwal.</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-6 xl:items-end">
                <div class="sm:col-span-2">
                    <label for="urut-tanggal" class="mb-1.5 block text-sm font-semibold text-gray-900">Tanggal</label>
                    <input type="date" id="urut-tanggal" class="<?= $kelasIsian(false) ?>"<?= $rentangTanggal ?>>
                </div>
                <div>
                    <label for="urut-jam" class="mb-1.5 block text-sm font-semibold text-gray-900">Jam mulai pertama</label>
                    <input type="time" id="urut-jam" class="<?= $kelasIsian(false) ?>" value="07:00">
                </div>
                <div>
                    <label for="urut-jeda" class="mb-1.5 block text-sm font-semibold text-gray-900">Jeda (menit)</label>
                    <input type="number" id="urut-jeda" class="<?= $kelasIsian(false) ?>" value="15" min="0" max="600" step="5" inputmode="numeric">
                </div>
                <div>
                    <label for="urut-lokasi" class="mb-1.5 block text-sm font-semibold text-gray-900">Lokasi</label>
                    <input type="text" id="urut-lokasi" class="<?= $kelasIsian(false) ?>" maxlength="<?= JadwalPertandingan::LOKASI_MAKSIMAL ?>" placeholder="Boleh kosong">
                </div>
                <div>
                    <?= komponen('tombol', ['label' => 'Isi yang dicentang', 'ikon' => 'list-ordered', 'varian' => 'kedua', 'blok' => true, 'atribut' => ['data-tombol-isi-berurutan' => '']]) ?>
                </div>
            </div>
            <p class="mt-3 min-h-5 text-sm font-semibold text-primary-800" aria-live="polite" data-pesan-isi-berurutan></p>
        </section>

        <form action="<?= site_url('admin/jadwal-pertandingan') ?>" method="post" novalidate data-formulir-jadwal>
            <?= csrf_field() ?>
            <section class="rounded-2xl border border-gray-200 bg-white shadow-lembut" aria-labelledby="judul-semua-babak">
                <header class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 p-4 sm:p-5">
                    <div>
                        <h2 id="judul-semua-babak" class="text-lg font-bold text-gray-900">Semua babak</h2>
                        <p class="text-sm text-gray-600"><?= $terjadwal ?> dari <?= count($babak) ?> babak sudah punya tanggal. Waktu dalam WIB.</p>
                    </div>
                    <label class="hidden min-h-11 items-center gap-2 text-sm font-semibold text-gray-900" data-centang-semua-wadah>
                        <input type="checkbox" class="size-5 rounded border-gray-300 text-primary-700 focus:ring-primary-300" data-centang-semua>
                        Centang semua
                    </label>
                </header>

                <ol class="divide-y divide-gray-100">
                    <?php foreach ($babak as $b) : ?>
                        <?php
                        $g      = $galat[$b['id']] ?? [];
                        $awal   = 'babak-' . $b['id'];
                        $status = JadwalPertandingan::status($b, $kini);
                        ?>
                        <li id="jadwal-babak-<?= $b['id'] ?>" class="grid gap-3 p-4 sm:px-6 lg:grid-cols-12 lg:items-start" data-baris-jadwal="<?= $b['id'] ?>">
                            <div class="flex items-start gap-3 lg:col-span-4">
                                <input type="checkbox" class="mt-1 hidden size-5 shrink-0 rounded border-gray-300 text-primary-700 focus:ring-primary-300" aria-label="<?= esc('Centang ' . $b['nomor_lengkap'] . ' ' . $b['kelompok_umur'] . ' ' . $b['babak'], 'attr') ?>" data-centang-babak>
                                <div class="min-w-0">
                                    <p class="font-bold break-words text-gray-900"><?= esc($b['nomor']) ?> <span class="font-semibold text-gray-600"><?= esc($b['jenis_kelamin']) ?></span></p>
                                    <p class="text-sm text-gray-700"><?= esc($b['kelompok_umur']) ?> · <a href="<?= site_url('admin/hasil/babak/' . $b['id']) ?>" class="font-semibold text-primary-700 underline"><?= esc($b['babak']) ?></a></p>
                                    <p class="mt-1"><?= komponen('lencana', ['label' => JadwalPertandingan::STATUS[$status], 'warna' => $warnaStatus[$status], 'ikon' => $ikonStatus[$status]]) ?></p>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:col-span-8">
                                <div class="col-span-2 sm:col-span-3">
                                    <label for="<?= $awal ?>-tanggal" class="mb-1 block text-sm font-semibold text-gray-900">Tanggal</label>
                                    <input type="date" id="<?= $awal ?>-tanggal" name="babak[<?= $b['id'] ?>][tanggal]" class="<?= $kelasIsian(isset($g['tanggal'])) ?>"
                                        value="<?= esc($nilai($b, 'tanggal', (string) $b['tanggal']), 'attr') ?>"<?= $rentangTanggal ?> data-kotak="tanggal"<?= isset($g['tanggal']) ? ' aria-invalid="true" aria-describedby="' . $awal . '-tanggal-galat"' : '' ?>>
                                    <?php if (isset($g['tanggal'])) : ?><p id="<?= $awal ?>-tanggal-galat" class="mt-1 text-sm text-red-700"><?= esc($g['tanggal']) ?></p><?php endif ?>
                                </div>
                                <div>
                                    <label for="<?= $awal ?>-jam" class="mb-1 block text-sm font-semibold text-gray-900">Jam mulai</label>
                                    <input type="time" id="<?= $awal ?>-jam" name="babak[<?= $b['id'] ?>][jam_mulai]" class="<?= $kelasIsian(isset($g['jam_mulai'])) ?>"
                                        value="<?= esc($nilai($b, 'jam_mulai', (string) $b['jam']), 'attr') ?>" data-kotak="jam"<?= isset($g['jam_mulai']) ? ' aria-invalid="true" aria-describedby="' . $awal . '-jam-galat"' : '' ?>>
                                    <?php if (isset($g['jam_mulai'])) : ?><p id="<?= $awal ?>-jam-galat" class="mt-1 text-sm text-red-700"><?= esc($g['jam_mulai']) ?></p><?php endif ?>
                                </div>
                                <div>
                                    <label for="<?= $awal ?>-acara" class="mb-1 block text-sm font-semibold text-gray-900">Nomor acara</label>
                                    <input type="text" id="<?= $awal ?>-acara" name="babak[<?= $b['id'] ?>][nomor_acara]" class="<?= $kelasIsian(isset($g['nomor_acara'])) ?>"
                                        value="<?= esc($nilai($b, 'nomor_acara', (string) $b['nomor_acara']), 'attr') ?>" inputmode="numeric" maxlength="4" placeholder="Boleh kosong"<?= isset($g['nomor_acara']) ? ' aria-invalid="true" aria-describedby="' . $awal . '-acara-galat"' : '' ?>>
                                    <?php if (isset($g['nomor_acara'])) : ?><p id="<?= $awal ?>-acara-galat" class="mt-1 text-sm text-red-700"><?= esc($g['nomor_acara']) ?></p><?php endif ?>
                                </div>
                                <div class="col-span-2 sm:col-span-1">
                                    <label for="<?= $awal ?>-lokasi" class="mb-1 block text-sm font-semibold text-gray-900">Lokasi</label>
                                    <input type="text" id="<?= $awal ?>-lokasi" name="babak[<?= $b['id'] ?>][lokasi]" class="<?= $kelasIsian(isset($g['lokasi'])) ?>"
                                        value="<?= esc($nilai($b, 'lokasi', $b['lokasi']), 'attr') ?>" maxlength="<?= JadwalPertandingan::LOKASI_MAKSIMAL ?>" placeholder="Boleh kosong" data-kotak="lokasi"<?= isset($g['lokasi']) ? ' aria-invalid="true" aria-describedby="' . $awal . '-lokasi-galat"' : '' ?>>
                                    <?php if (isset($g['lokasi'])) : ?><p id="<?= $awal ?>-lokasi-galat" class="mt-1 text-sm text-red-700"><?= esc($g['lokasi']) ?></p><?php endif ?>
                                </div>
                            </div>
                        </li>
                    <?php endforeach ?>
                </ol>

                <footer class="sticky bottom-16 flex flex-wrap gap-2 border-t border-gray-200 bg-white/95 p-4 backdrop-blur sm:px-6 lg:bottom-0">
                    <?= komponen('tombol', ['label' => 'Simpan jadwal', 'tipe' => 'submit', 'ikon' => 'save']) ?>
                    <?php if ($tampil) : ?>
                        <?= komponen('tombol', ['label' => 'Lihat halaman Jadwal', 'href' => site_url('jadwal'), 'varian' => 'kedua', 'ikon' => 'external-link', 'atribut' => ['target' => '_blank', 'rel' => 'noopener']]) ?>
                    <?php endif ?>
                </footer>
            </section>
        </form>
    <?php endif ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/pilih-tanggal.js') ?>"></script>
<script src="<?= aset('js/admin-jadwal-pertandingan.js') ?>"></script>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<?= $this->endSection() ?>
