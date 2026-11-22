<?php
/**
 * Katalog komponen tampilan baru (Tahap 9, F9-01): palet, huruf, ikon Lucide,
 * semua potongan `app/Views/komponen/`, komponen interaktif Flowbite
 * (carousel berindikator, akordeon, tab, dropdown, tooltip, modal, laci), dan
 * contoh adegan Three.js. Untuk pemilik menilai gaya di staging.
 *
 * @var CodeIgniter\View\View $this
 */

$palet = [
    'Biru (utama)'     => ['bg-primary-50', 'bg-primary-100', 'bg-primary-300', 'bg-primary-500', 'bg-primary-700', 'bg-primary-900'],
    'Oranye (aksen)'   => ['bg-oranye-50', 'bg-oranye-100', 'bg-oranye-300', 'bg-oranye-500', 'bg-oranye-600', 'bg-oranye-800'],
    'Pendukung'        => ['bg-magenta-500', 'bg-langit-300', 'bg-langit-100', 'bg-green-600', 'bg-yellow-400', 'bg-red-700'],
];

// ikon-sprite: clipboard-check users school trophy medal timer calendar-days map-pin printer scan-qr-code circle-help phone download upload image search
$ikonContoh = ['clipboard-check', 'users', 'school', 'trophy', 'medal', 'timer', 'calendar-days', 'map-pin', 'printer', 'scan-qr-code', 'circle-help', 'phone', 'download', 'upload', 'image', 'search'];

$bagian = static fn (string $id, string $judul, string $keterangan): string => '<header class="mb-4"><h2 id="' . $id . '" class="text-xl font-bold text-gray-900">' . esc($judul) . '</h2><p class="text-sm text-gray-500">' . esc($keterangan) . '</p></header>';
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?>Katalog tampilan<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-12 p-4 sm:p-6">

    <section class="latar-merek relative overflow-hidden rounded-3xl border border-gray-200 p-6 sm:p-10" data-adegan-merek>
        <div data-adegan-cadangan class="pointer-events-none absolute inset-0 transition-opacity duration-700" aria-hidden="true"></div>
        <div class="relative max-w-xl">
            <?= komponen('lencana', ['label' => 'Tahap 9 · Flowbite Pro', 'warna' => 'oranye', 'ikon' => 'sparkles']) ?>
            <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-primary-900 sm:text-4xl">Katalog tampilan baru</h2>
            <p class="mt-3 text-gray-700">Semua komponen yang akan dipakai halaman publik dan panel admin. Halaman ini hanya contoh dan tidak mengubah data apa pun.</p>
            <div class="mt-6 flex flex-wrap gap-3">
                <?= komponen('tombol', ['label' => 'Lihat tombol', 'href' => '#tombol', 'ikon' => 'arrow-right', 'ikonKanan' => true]) ?>
                <?= komponen('tombol', ['label' => 'Komponen interaktif', 'href' => '#interaktif', 'varian' => 'kedua']) ?>
            </div>
        </div>
    </section>

    <section aria-labelledby="palet">
        <?= $bagian('palet', 'Warna', 'Palet MilkLife sebagai warna utama Flowbite Pro (primary) dan aksen oranye.') ?>
        <div class="grid gap-4 sm:grid-cols-3">
            <?php foreach ($palet as $nama => $kelasWarna) : ?>
                <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut">
                    <p class="mb-3 text-sm font-semibold text-gray-900"><?= esc($nama) ?></p>
                    <div class="grid grid-cols-6 gap-1.5">
                        <?php foreach ($kelasWarna as $k) : ?>
                            <span class="<?= $k ?> h-10 rounded-lg ring-1 ring-black/5" title="<?= esc($k) ?>"></span>
                        <?php endforeach ?>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
    </section>

    <section aria-labelledby="huruf">
        <?= $bagian('huruf', 'Huruf', 'Inter (OFL) untuk teks badan dan Barlow Condensed (OFL, K155) untuk judul tampilan, keduanya disimpan lokal.') ?>
        <div class="space-y-2 rounded-2xl border border-gray-200 bg-white p-6 shadow-lembut">
            <p class="text-4xl font-extrabold tracking-tight text-primary-900">Ayo daftarkan sekolahmu</p>
            <p class="text-2xl font-bold text-gray-900">Kelompok umur dan nomor lomba</p>
            <p class="text-lg font-semibold text-gray-800">Langkah 3 dari 5: data atlet</p>
            <p class="text-base text-gray-700">Isi nama lengkap sesuai rapor. NISN 10 angka, ada di kartu pelajar atau ditanyakan ke operator sekolah.</p>
            <p class="text-sm text-gray-500">Teks kecil untuk keterangan tambahan.</p>
        </div>
    </section>

    <section aria-labelledby="editorial" data-katalog-editorial>
        <?= $bagian('editorial', 'Gaya editorial (F10-05)', 'Barlow Condensed (OFL) untuk judul, alis oranye tanpa garis (F10-08), tombol utama berart, pemisah lintasan, bingkai foto, dan tekstur kertas. Art G10-11 tampil bila berkasnya sudah ada; tanpa berkas memakai warna padat.') ?>
        <?php // Kelas penanda `ada-*` di pembungkus supaya contoh art tampil walaupun tata letak admin tidak memasangnya. ?>
        <div class="<?= esc(trim('space-y-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-lembut ' . App\Libraries\GambarOpsional::penandaElemen())) ?>">
            <div>
                <p class="alis">Alis · alis</p>
                <p class="judul-tampilan mt-2">Judul tampilan · judul-tampilan</p>
                <p class="mt-1 text-gray-600">Judul Inggris (slogan, judul editorial) selalu diberi keterangan berbahasa Indonesia; tombol dan menu tetap Indonesia.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <?= komponen('tombol', ['label' => 'Daftarkan sekolah', 'ikon' => 'arrow-right', 'ikonKanan' => true]) ?>
                <?= komponen('tombol', ['label' => 'Daftar sekarang', 'varian' => 'oranye', 'ukuran' => 'besar']) ?>
                <span class="text-sm text-gray-500">tombol-utama-berart, tombol-berart-oranye</span>
            </div>
            <div>
                <div class="pemisah-lintasan" aria-hidden="true"></div>
                <p class="mt-1 text-sm text-gray-500">pemisah-lintasan</p>
            </div>
            <div class="grid gap-6 sm:grid-cols-2">
                <figure>
                    <div class="bingkai-foto">
                        <div class="aspect-[4/3] bg-linear-to-br from-primary-900 via-primary-700 to-oranye-500"></div>
                    </div>
                    <figcaption class="mt-2 text-sm text-gray-500">bingkai-foto</figcaption>
                </figure>
                <div class="tekstur-kertas flex flex-col justify-center p-6">
                    <p class="alis">Batas pendaftaran</p>
                    <p class="font-tampilan text-4xl font-extrabold text-oranye-700">30 November 2026</p>
                    <p class="mt-1 text-sm text-gray-600">tekstur-kertas, krem #F5EFE6</p>
                </div>
            </div>
        </div>
    </section>

    <section aria-labelledby="tombol">
        <?= $bagian('tombol', 'Tombol', 'Satu tombol utama per layar. Label berupa kata kerja. Tinggi paling kecil 44 piksel.') ?>
        <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-gray-200 bg-white p-6 shadow-lembut">
            <?= komponen('tombol', ['label' => 'Daftarkan sekolah', 'ikon' => 'arrow-right', 'ikonKanan' => true]) ?>
            <?= komponen('tombol', ['label' => 'Daftar sekarang', 'varian' => 'oranye']) ?>
            <?= komponen('tombol', ['label' => 'Unduh bukti PDF', 'varian' => 'kedua', 'ikon' => 'download']) ?>
            <?= komponen('tombol', ['label' => 'Hapus album', 'varian' => 'bahaya', 'ikon' => 'trash-2']) ?>
            <?= komponen('tombol', ['label' => 'Batal', 'varian' => 'hantu']) ?>
            <?= komponen('tombol', ['label' => 'Menyimpan…', 'nonaktif' => true, 'ikon' => 'loader-circle', 'kelas' => '[&_svg]:animate-spin']) ?>
            <?= komponen('tombol', ['label' => 'Kecil', 'ukuran' => 'kecil', 'varian' => 'kedua']) ?>
            <?= komponen('tombol', ['label' => 'Besar', 'ukuran' => 'besar']) ?>
        </div>
    </section>

    <section aria-labelledby="ikon">
        <?= $bagian('ikon', 'Ikon Lucide', 'Sprite lokal; ikon baru cukup ditulis ikon(\'nama\') lalu npm run ikon.') ?>
        <div class="grid grid-cols-4 gap-3 rounded-2xl border border-gray-200 bg-white p-6 shadow-lembut sm:grid-cols-8">
            <?php foreach ($ikonContoh as $n) : ?>
                <div class="flex flex-col items-center gap-1.5 rounded-xl p-2 text-center hover:bg-gray-50">
                    <?= ikon($n, 'size-6 text-primary-700') ?>
                    <span class="text-[11px] text-gray-500"><?= esc($n) ?></span>
                </div>
            <?php endforeach ?>
        </div>
    </section>

    <section aria-labelledby="kartu" class="grid gap-6 lg:grid-cols-2">
        <div class="lg:col-span-2"><?= $bagian('kartu', 'Kartu, pesan, dan lencana', 'Kartu berbingkai lembut, kotak pesan empat jenis, lencana keadaan.') ?></div>
        <?= komponen('kartu', [
            'judul'      => 'KU 10 · SD/MI',
            'keterangan' => 'Lahir 2016–2018',
            'ikon'       => 'users',
            'isi'        => '<p class="text-gray-700">Lari 60 m, Frog Jump, Turbo Throw, Kanga\'s Escape, Formula 1, dan Estafet 8 × 50 m.</p>',
            'kaki'       => komponen('lencana', ['label' => 'Rp 25.000 per atlet', 'warna' => 'biru']) . komponen('lencana', ['label' => 'Dibuka', 'warna' => 'hijau', 'ikon' => 'check']),
        ]) ?>
        <div class="space-y-3">
            <?= komponen('peringatan', ['jenis' => 'info', 'isi' => esc('Pendaftaran ditutup 30 November 2026 pukul 23.59.')]) ?>
            <?= komponen('peringatan', ['jenis' => 'sukses', 'judul' => 'Pendaftaran tersimpan', 'isi' => esc('Simpan ID pendaftaran untuk mengecek status.')]) ?>
            <?= komponen('peringatan', ['jenis' => 'peringatan', 'isi' => esc('Bukti transfer belum diunggah.')]) ?>
            <?= komponen('peringatan', ['jenis' => 'bahaya', 'isi' => esc('NISN ini sudah terdaftar. Hubungi panitia bila ada kesalahan.')]) ?>
            <div class="flex flex-wrap gap-2">
                <?php foreach (['Lunas' => 'hijau', 'Menunggu' => 'kuning', 'Ditolak' => 'merah', 'Draf' => 'abu', 'Baru' => 'oranye', 'Final' => 'biru'] as $l => $w) : ?>
                    <?= komponen('lencana', ['label' => $l, 'warna' => $w]) ?>
                <?php endforeach ?>
            </div>
        </div>
    </section>

    <section aria-labelledby="formulir">
        <?= $bagian('formulir', 'Formulir', 'Label selalu terlihat, teks bantuan di bawah, galat menyebut cara memperbaikinya.') ?>
        <div class="grid gap-5 rounded-2xl border border-gray-200 bg-white p-6 shadow-lembut sm:grid-cols-2">
            <?= komponen('isian', ['label' => 'Nama lengkap atlet', 'nama' => 'contoh_nama', 'wajib' => true, 'bantuan' => 'Sesuai rapor atau akta kelahiran.', 'atribut' => ['autocomplete' => 'off']]) ?>
            <?= komponen('isian', ['label' => 'NISN', 'nama' => 'contoh_nisn', 'nilai' => '12345', 'wajib' => true, 'galat' => 'NISN harus 10 angka. Lihat di kartu pelajar atau tanyakan ke operator sekolah.', 'atribut' => ['inputmode' => 'numeric']]) ?>
            <?= komponen('pilihan', ['label' => 'Kabupaten', 'nama' => 'contoh_kab', 'kosong' => 'Pilih kabupaten', 'opsi' => ['blora' => 'Kab. Blora', 'rembang' => 'Kab. Rembang', 'pati' => 'Kab. Pati', 'kudus' => 'Kab. Kudus', 'jepara' => 'Kab. Jepara']]) ?>
            <?= komponen('isian', ['label' => 'Catatan', 'nama' => 'contoh_catatan', 'tipe' => 'textarea', 'bantuan' => 'Boleh dikosongkan.']) ?>
            <?= komponen('sakelar', ['label' => 'Tampilkan halaman Peserta', 'nama' => 'contoh_sakelar', 'centang' => true, 'bantuan' => 'Bila mati, menu Peserta tidak tampil di halaman depan.']) ?>
        </div>
    </section>

    <section aria-labelledby="langkah-tabel" class="space-y-6">
        <?= $bagian('langkah-tabel', 'Langkah dan tabel', 'Penunjuk langkah wizard dan tabel sederhana.') ?>
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-lembut">
            <?= komponen('langkah', ['daftar' => ['Sekolah', 'Ofisial', 'Atlet', 'Biaya', 'Pembayaran'], 'aktif' => 3]) ?>
        </div>
        <?= komponen('tabel', [
            'judul'  => 'Contoh hasil Final',
            'kepala' => ['Peringkat', 'Nama', 'Sekolah', 'Prestasi'],
            'baris'  => [
                ['1', 'Atlet Contoh Satu', 'SD Negeri Contoh 1', '8,21'],
                ['2', 'Atlet Contoh Dua', 'MI Contoh Dua', '8,45'],
                ['3', 'Atlet Contoh Tiga', 'SD Contoh Tiga', ['html' => '8,47 ' . komponen('lencana', ['label' => 'diubah admin', 'warna' => 'abu'])]],
            ],
        ]) ?>
    </section>

    <section aria-labelledby="kerangka" class="space-y-4">
        <?= $bagian('kerangka', 'Kerangka memuat dan keadaan kosong', 'Kerangka hanya untuk bagian yang dimuat JavaScript; keadaan kosong memakai ilustrasi G9-04 bila sudah ada.') ?>
        <div class="grid gap-4 md:grid-cols-3">
            <?= komponen('kerangka', ['jenis' => 'kartu']) ?>
            <?= komponen('kerangka', ['jenis' => 'tabel', 'baris' => 4]) ?>
            <?= komponen('kerangka', ['jenis' => 'gambar']) ?>
        </div>
        <?= komponen('kosong', ['gambar' => 'gambar/keadaan/kosong-hasil.webp', 'ikon' => 'trophy', 'judul' => 'Hasil belum terbit', 'teks' => 'Hasil tampil di sini setelah panitia menerbitkannya. Coba buka lagi nanti.', 'aksi' => komponen('tombol', ['label' => 'Lihat jadwal', 'varian' => 'kedua', 'ikon' => 'calendar-days'])]) ?>
    </section>

    <section aria-labelledby="interaktif" class="space-y-6">
        <?= $bagian('interaktif', 'Komponen interaktif Flowbite', 'Carousel berindikator, akordeon, tab, dropdown, tooltip, modal.') ?>

        <div id="carousel-contoh" class="relative w-full" data-carousel="slide" data-carousel-interval="5000">
            <div class="relative h-56 overflow-hidden rounded-2xl sm:h-72">
                <?php foreach (['from-primary-900 to-primary-600' => 'Seri 2 – 2026', 'from-oranye-600 to-magenta-500' => 'Supersoccer Arena, Kudus', 'from-primary-700 to-langit-300' => '9–12 Desember 2026'] as $gradien => $teks) : ?>
                    <div class="hidden duration-700 ease-in-out" data-carousel-item>
                        <div class="<?= $gradien ?> absolute inset-0 flex items-end bg-linear-to-br p-6"><p class="text-2xl font-extrabold text-white sm:text-3xl"><?= esc($teks) ?></p></div>
                    </div>
                <?php endforeach ?>
            </div>
            <div class="absolute bottom-4 start-1/2 z-30 flex -translate-x-1/2 gap-2 rtl:translate-x-1/2">
                <?php for ($i = 0; $i < 3; $i++) : ?>
                    <button type="button" class="size-3 rounded-full" aria-current="<?= $i === 0 ? 'true' : 'false' ?>" aria-label="Gambar <?= $i + 1 ?>" data-carousel-slide-to="<?= $i ?>"></button>
                <?php endfor ?>
            </div>
            <button type="button" class="group absolute start-0 top-0 z-30 flex h-full cursor-pointer items-center justify-center px-3 focus:outline-none" data-carousel-prev>
                <span class="inline-flex size-11 items-center justify-center rounded-full bg-white/30 text-white group-hover:bg-white/50 group-focus:ring-4 group-focus:ring-white"><?= ikon('chevron-left', '', 'Sebelumnya') ?></span>
            </button>
            <button type="button" class="group absolute end-0 top-0 z-30 flex h-full cursor-pointer items-center justify-center px-3 focus:outline-none" data-carousel-next>
                <span class="inline-flex size-11 items-center justify-center rounded-full bg-white/30 text-white group-hover:bg-white/50 group-focus:ring-4 group-focus:ring-white"><?= ikon('chevron-right', '', 'Berikutnya') ?></span>
            </button>
        </div>

        <div id="akordeon-contoh" data-accordion="collapse" data-active-classes="bg-primary-50 text-primary-900" data-inactive-classes="text-gray-700" class="overflow-hidden rounded-2xl border border-gray-200 bg-white">
            <?php foreach ([
                ['Apakah harus punya akun untuk mendaftar?', 'Tidak. Ofisial cukup mengisi formulir pendaftaran; ID pendaftaran dipakai untuk mengecek status.'],
                ['Foto ofisial boleh dari HP?', 'Boleh. Foto wajah jelas, tanpa kacamata hitam; ukuran besar akan dikecilkan otomatis.'],
            ] as $no => [$tanya, $jawab]) : ?>
                <h3 id="akordeon-kepala-<?= $no ?>">
                    <button type="button" class="flex min-h-14 w-full items-center justify-between gap-3 border-b border-gray-200 px-5 text-start font-semibold" data-accordion-target="#akordeon-isi-<?= $no ?>" aria-expanded="<?= $no === 0 ? 'true' : 'false' ?>" aria-controls="akordeon-isi-<?= $no ?>">
                        <span><?= esc($tanya) ?></span><?= ikon('chevron-down', 'shrink-0') ?>
                    </button>
                </h3>
                <div id="akordeon-isi-<?= $no ?>" class="hidden" aria-labelledby="akordeon-kepala-<?= $no ?>">
                    <p class="border-b border-gray-200 px-5 py-4 text-gray-700"><?= esc($jawab) ?></p>
                </div>
            <?php endforeach ?>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut">
            <ul class="flex flex-wrap gap-1 border-b border-gray-200 text-sm font-semibold" id="tab-contoh" data-tabs-toggle="#tab-contoh-isi" data-tabs-active-classes="text-primary-700 border-primary-700" data-tabs-inactive-classes="text-gray-500 border-transparent hover:text-gray-700" role="tablist">
                <?php foreach (['putra' => 'Putra', 'putri' => 'Putri'] as $kunci => $label) : ?>
                    <li role="presentation"><button class="inline-flex min-h-11 items-center border-b-2 px-4" id="tab-<?= $kunci ?>" data-tabs-target="#isi-<?= $kunci ?>" type="button" role="tab" aria-controls="isi-<?= $kunci ?>" aria-selected="<?= $kunci === 'putra' ? 'true' : 'false' ?>"><?= $label ?></button></li>
                <?php endforeach ?>
            </ul>
            <div id="tab-contoh-isi" class="pt-4 text-gray-700">
                <div id="isi-putra" role="tabpanel" aria-labelledby="tab-putra">Daftar nomor lomba putra.</div>
                <div id="isi-putri" class="hidden" role="tabpanel" aria-labelledby="tab-putri">Daftar nomor lomba putri.</div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <?= komponen('tombol', ['label' => 'Pilihan unduhan', 'varian' => 'kedua', 'ikon' => 'chevron-down', 'ikonKanan' => true, 'atribut' => ['id' => 'tombol-dropdown', 'data-dropdown-toggle' => 'dropdown-contoh']]) ?>
            <div id="dropdown-contoh" class="z-10 hidden w-48 rounded-xl border border-gray-200 bg-white p-1 shadow-angkat">
                <a href="#interaktif" class="flex min-h-11 items-center gap-2 rounded-lg px-3 text-sm hover:bg-gray-100"><?= ikon('file-text') ?>PDF</a>
                <a href="#interaktif" class="flex min-h-11 items-center gap-2 rounded-lg px-3 text-sm hover:bg-gray-100"><?= ikon('sheet') ?>Excel</a>
            </div>
            <?= komponen('tombol', ['label' => 'Arahkan ke sini', 'varian' => 'hantu', 'ikon' => 'circle-help', 'atribut' => ['data-tooltip-target' => 'tooltip-contoh']]) ?>
            <div id="tooltip-contoh" role="tooltip" class="tooltip invisible absolute z-10 inline-block rounded-lg bg-gray-900 px-3 py-2 text-sm font-medium text-white opacity-0 shadow-xs transition-opacity">Tooltip berisi keterangan singkat.<div class="tooltip-arrow" data-popper-arrow></div></div>
            <?= komponen('tombol', ['label' => 'Buka modal', 'ikon' => 'square-arrow-out-up-right', 'atribut' => ['data-modal-target' => 'modal-contoh', 'data-modal-toggle' => 'modal-contoh']]) ?>
        </div>
        <?= komponen('modal', [
            'id'    => 'modal-contoh',
            'judul' => 'Tandai pendaftaran lunas?',
            'isi'   => '<p>Nomor atlet (BIB) dibuat untuk semua atlet di pendaftaran ini dan tidak bisa dipakai ulang.</p>',
            'kaki'  => komponen('tombol', ['label' => 'Batal', 'varian' => 'kedua', 'atribut' => ['data-modal-hide' => 'modal-contoh']]) . komponen('tombol', ['label' => 'Ya, tandai lunas', 'atribut' => ['data-modal-hide' => 'modal-contoh']]),
        ]) ?>
    </section>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script type="module" src="<?= aset('js/adegan-merek.js') ?>"></script>
<?= $this->endSection() ?>
