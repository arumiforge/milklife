<?php
/**
 * Halaman Bantuan (`/bantuan`, F9-04), tata letak baru Tailwind (`publik_tw`).
 *
 * Urutan: judul dan kotak cari, kartu kategori, panduan bergambar langkah
 * pendaftaran, tanya-jawab per kategori (akordeon Flowbite), lalu tombol WA
 * panitia. Pencarian langsung tanpa muat ulang di `aset/js/bantuan.js`: setiap
 * butir membawa `data-cari` (pertanyaan + jawaban polos, huruf kecil).
 * Alamat `/bantuan#faq-<id>` membuka tanya-jawab itu.
 *
 * Gambar G9-07 (kategori, kepala, tanya panitia) dan G9-03 (panduan) hanya
 * tampil bila berkasnya ada; selain itu ikon Lucide.
 *
 * @var CodeIgniter\View\View $this
 * @var string                $namaAcara
 * @var list<array<string, mixed>> $kategori lihat `IsiBantuan::kategori()`
 * @var list<array<string, mixed>> $panduan  lihat `IsiBantuan::panduan()`
 * @var array{wa_tanya: string|null, narahubung: list<array{nama: string, nomor: string, alamat: string}>} $kontak
 * @var string|null           $kepala   gambar kepala G9-07
 * @var string|null           $gambarWa gambar tanya panitia G9-07
 */

// Ikon pengganti ditulis langsung supaya terbawa ke sprite Lucide (`npm run ikon`).
$ikonKategori = [
    'sebelum'    => ikon('clipboard-check', 'size-7'),
    'mengisi'    => ikon('smartphone', 'size-7'),
    'pembayaran' => ikon('landmark', 'size-7'),
    'cek'        => ikon('file-search', 'size-7'),
    'hari_lomba' => ikon('flag', 'size-7'),
    'hasil'      => ikon('trophy', 'size-7'),
];
$ikonPanduan = [
    ikon('school', 'size-8'), ikon('user-round', 'size-8'), ikon('users', 'size-8'), ikon('calculator', 'size-8'),
    ikon('upload', 'size-8'), ikon('file-check', 'size-8'), ikon('qr-code', 'size-8'),
];
$jumlah = array_sum(array_map(static fn (array $k): int => count($k['butir']), $kategori));
$adaWa  = $kontak['wa_tanya'] !== null || $kontak['narahubung'] !== [];
?>
<?= $this->extend('tata_letak/publik_tw') ?>

<?= $this->section('judul') ?>Bantuan – <?= esc($namaAcara) ?><?= $this->endSection() ?>

<?= $this->section('isi') ?>
<section class="relative overflow-hidden border-b border-primary-100 bg-white" aria-labelledby="judul-bantuan">
    <?php if ($kepala !== null) : ?>
        <img src="<?= esc($kepala) ?>" alt="" class="pointer-events-none absolute inset-y-0 end-0 hidden h-full w-auto max-w-none object-cover opacity-90 md:block" width="1920" height="480">
    <?php endif ?>
    <div class="relative mx-auto max-w-screen-xl px-4 py-10 sm:py-14">
        <div class="max-w-2xl">
            <p class="text-sm font-semibold text-oranye-600"><?= esc($namaAcara) ?></p>
            <h1 id="judul-bantuan" class="mt-1 text-3xl font-extrabold text-primary-900 sm:text-4xl">Bantuan</h1>
            <p class="mt-3 text-lg text-gray-700">Jawaban untuk pertanyaan yang sering ditanyakan sekolah, dari mendaftar sampai hasil lomba.</p>

            <?php if ($jumlah > 0) : ?>
                <form class="mt-6" role="search" data-cari-bantuan>
                    <label for="cari-bantuan" class="mb-2 block font-semibold text-gray-900">Cari pertanyaan</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-4 text-gray-500"><?= ikon('search', 'size-5') ?></span>
                        <input type="search" id="cari-bantuan" class="block min-h-12 w-full rounded-xl border border-gray-300 bg-white ps-12 pe-4 text-base text-gray-900 shadow-lembut focus:border-primary-600 focus:ring-4 focus:ring-primary-100" placeholder="Contoh: bukti transfer, foto ofisial, KU 10" autocomplete="off" aria-describedby="hasil-cari-bantuan">
                    </div>
                    <p id="hasil-cari-bantuan" class="mt-2 min-h-6 text-sm text-gray-600" aria-live="polite"></p>
                </form>
            <?php endif ?>
        </div>
    </div>
</section>

<div class="mx-auto max-w-screen-xl px-4">
    <?php if ($kategori !== []) : ?>
        <nav class="mt-8" aria-label="Kategori bantuan" data-bagian-kategori>
            <ul class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-3">
                <?php foreach ($kategori as $k) : ?>
                    <li>
                        <a href="#<?= esc($k['id'], 'attr') ?>" class="flex h-full min-h-11 flex-col gap-2 rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut transition hover:border-primary-300 hover:shadow-angkat focus:outline-none focus:ring-4 focus:ring-primary-200 sm:flex-row sm:items-center sm:gap-4">
                            <?php if ($k['gambar'] !== null) : ?>
                                <img src="<?= esc($k['gambar']) ?>" alt="" class="size-16 shrink-0 sm:size-20" width="480" height="480" loading="lazy">
                            <?php else : ?>
                                <span class="inline-flex size-12 shrink-0 items-center justify-center rounded-xl bg-primary-100 text-primary-700"><?= $ikonKategori[$k['kunci']] ?? ikon('circle-help', 'size-7') ?></span>
                            <?php endif ?>
                            <span>
                                <span class="block font-bold text-gray-900"><?= esc($k['label']) ?></span>
                                <span class="mt-0.5 hidden text-sm text-gray-600 sm:block"><?= esc($k['keterangan']) ?></span>
                                <span class="mt-0.5 block text-sm text-primary-700"><?= count($k['butir']) ?> pertanyaan</span>
                            </span>
                        </a>
                    </li>
                <?php endforeach ?>
            </ul>
        </nav>
    <?php endif ?>

    <section class="mt-12" aria-labelledby="judul-panduan" data-bagian-panduan>
        <h2 id="judul-panduan" class="text-2xl font-bold text-primary-900">Cara mendaftar</h2>
        <p class="mt-1 text-gray-600">Tidak perlu akun. Ikuti langkah ini dari HP atau komputer.</p>
        <ol class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <?php foreach ($panduan as $i => $p) : ?>
                <li class="flex flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-lembut">
                    <?php if ($p['gambar'] !== null) : ?>
                        <img src="<?= esc($p['gambar']) ?>" alt="" class="aspect-[4/3] w-full bg-primary-50 object-cover" width="1200" height="900" loading="lazy">
                    <?php else : ?>
                        <span class="flex aspect-[4/3] w-full items-center justify-center bg-primary-50 text-primary-700"><?= $ikonPanduan[$i] ?? ikon('circle-help', 'size-8') ?></span>
                    <?php endif ?>
                    <div class="flex gap-3 p-4">
                        <span class="inline-flex size-8 shrink-0 items-center justify-center rounded-full bg-oranye-600 text-sm font-bold text-white" aria-hidden="true"><?= (int) $p['nomor'] ?></span>
                        <div>
                            <h3 class="font-bold text-gray-900"><span class="sr-only">Langkah <?= (int) $p['nomor'] ?>: </span><?= esc($p['judul']) ?></h3>
                            <p class="mt-1 text-sm text-gray-700"><?= esc($p['keterangan']) ?></p>
                        </div>
                    </div>
                </li>
            <?php endforeach ?>
        </ol>
    </section>

    <div class="mt-12 space-y-10" data-daftar-faq>
        <?php if ($kategori === []) : ?>
            <?= komponen('kosong', ['gambar' => 'gambar/bantuan/tanya-panitia.webp', 'ikon' => 'message-circle', 'judul' => 'Tanya-jawab belum tersedia', 'teks' => 'Panitia sedang menyiapkan jawaban. Untuk sementara, tanyakan langsung lewat WA di bawah.']) ?>
        <?php endif ?>

        <?php foreach ($kategori as $k) : ?>
            <section id="<?= esc($k['id'], 'attr') ?>" class="scroll-mt-24" aria-labelledby="judul-<?= esc($k['id'], 'attr') ?>" data-kategori-faq>
                <h2 id="judul-<?= esc($k['id'], 'attr') ?>" class="mb-4 flex items-center gap-3 text-xl font-bold text-primary-900">
                    <span class="inline-flex size-10 items-center justify-center rounded-xl bg-primary-100 text-primary-700 [&_.ikon]:size-5"><?= $ikonKategori[$k['kunci']] ?? ikon('circle-help', 'size-5') ?></span>
                    <?= esc($k['label']) ?>
                </h2>
                <div id="akordeon-<?= esc($k['id'], 'attr') ?>" data-accordion="collapse" data-active-classes="bg-primary-50 text-primary-900" data-inactive-classes="text-gray-900" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-lembut">
                    <?php foreach ($k['butir'] as $b) : ?>
                        <?php $id = (int) $b['id']; ?>
                        <div id="faq-<?= $id ?>" class="scroll-mt-24 border-b border-gray-200 last:border-b-0" data-butir-faq data-cari="<?= esc($b['cari'], 'attr') ?>">
                            <h3 id="faq-kepala-<?= $id ?>">
                                <button type="button" class="flex min-h-14 w-full items-center justify-between gap-3 px-5 py-3 text-start font-semibold hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-inset focus:ring-primary-200" data-accordion-target="#faq-isi-<?= $id ?>" aria-expanded="false" aria-controls="faq-isi-<?= $id ?>">
                                    <span><?= esc($b['pertanyaan']) ?></span><?= ikon('chevron-down', 'size-5 shrink-0 transition-transform') ?>
                                </button>
                            </h3>
                            <div id="faq-isi-<?= $id ?>" class="hidden" aria-labelledby="faq-kepala-<?= $id ?>">
                                <div class="format max-w-none px-5 pt-1 pb-5 text-gray-700 format-primary">
                                    <?= $b['jawaban'] ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach ?>
                </div>
            </section>
        <?php endforeach ?>

        <div class="hidden" data-cari-kosong>
            <?= komponen('kosong', ['gambar' => 'gambar/keadaan/tidak-ketemu.webp', 'ikon' => 'search', 'judul' => 'Belum ada jawaban yang cocok', 'teks' => 'Coba kata lain yang lebih pendek, misalnya "foto", "transfer", atau "NISN". Atau tanyakan langsung ke panitia lewat WA di bawah.']) ?>
        </div>
    </div>

    <section class="mt-12 flex flex-col items-center gap-6 rounded-2xl border border-green-200 bg-green-50 p-6 text-center sm:flex-row sm:text-start" aria-labelledby="judul-tanya-panitia" id="tanya-panitia">
        <?php if ($gambarWa !== null) : ?>
            <img src="<?= esc($gambarWa) ?>" alt="" class="size-28 shrink-0" width="480" height="480" loading="lazy">
        <?php else : ?>
            <span class="inline-flex size-16 shrink-0 items-center justify-center rounded-2xl bg-green-100 text-green-800"><?= ikon('message-circle', 'size-8') ?></span>
        <?php endif ?>
        <div class="flex-1">
            <h2 id="judul-tanya-panitia" class="text-xl font-bold text-gray-900">Masih bingung? Tanya panitia</h2>
            <p class="mt-1 text-gray-700">
                <?= $adaWa ? 'Kirim pesan WA dan sebutkan nama sekolah serta ID pendaftaran bila sudah mendaftar.' : 'Nomor WA panitia belum diumumkan. Lihat petunjuk teknis di halaman Unduhan.' ?>
            </p>
            <?php if ($adaWa) : ?>
                <div class="mt-4 flex flex-wrap justify-center gap-3 sm:justify-start">
                    <?php if ($kontak['wa_tanya'] !== null) : ?>
                        <a href="<?= esc($kontak['wa_tanya'], 'attr') ?>" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-green-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-green-800 focus:outline-none focus:ring-4 focus:ring-green-300">
                            <?= ikon('message-circle') ?>Tanya lewat WA
                        </a>
                    <?php endif ?>
                    <?php foreach ($kontak['narahubung'] as $n) : ?>
                        <a href="<?= esc($n['alamat'], 'attr') ?>" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border border-green-300 bg-white px-5 py-2.5 text-sm font-semibold text-green-900 hover:bg-green-100 focus:outline-none focus:ring-4 focus:ring-green-200">
                            <?= ikon('phone') ?><?= esc($n['nama']) ?> (<?= esc($n['nomor']) ?>)
                        </a>
                    <?php endforeach ?>
                </div>
            <?php endif ?>
        </div>
    </section>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/bantuan.js') ?>"></script>
<?= $this->endSection() ?>
