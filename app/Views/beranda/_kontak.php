<?php
/**
 * Bagian kontak halaman depan (`#kontak`): narahubung, media sosial, tombol
 * tanya admin dan bagikan lewat WA.
 *
 * Bagian ini selalu tampil karena tombol "Bagikan ke WA" selalu ada;
 * daftar narahubung, media sosial, dan tombol tanya admin hanya tampil bila
 * isinya ada. Semua tautan keluar dibuka di tab baru. Teks admin di-escape.
 *
 * Data yang tersedia (dari `IsiHalamanDepan`):
 *
 * @var list<array{nama: string, nomor: string, alamat: string}> $narahubung
 *      `narahubung()`: `nomor` tampil '0812-3456-7890', `alamat` 'https://wa.me/62…'; [] bila belum ada.
 * @var list<array{label: string, alamat: string, alamat_ringkas: string, ikon: string}> $mediaSosial
 *      `mediaSosial()`: `alamat` http/https, `alamat_ringkas` 'instagram.com/contoh',
 *      `ikon` nama Bootstrap Icons tanpa "bi-"; [] bila belum ada.
 * @var string|null $waTanya   `waTanya()`: 'https://wa.me/62…?text=…', atau null bila nomornya belum diisi.
 * @var string      $waBagikan `waBagikan()`: 'https://wa.me/?text=…' (selalu ada).
 *
 * Kesepakatan (rencana Tahap 3, "Halaman depan"):
 * - `<section id="kontak" aria-labelledby="judul-kontak">` dengan `<h2 id="judul-kontak">`.
 * - Tombol tanya admin hanya bila `$waTanya !== null`; tombol bagikan selalu tampil.
 * - Tautan keluar dibuka di tab baru dengan `rel="noopener noreferrer"`.
 * - Gaya hanya di wilayah "Bagian jadwal, unduhan, kontak (F3-03)" di `tema.css`.
 */

$adaKontak = $narahubung !== [] || $waTanya !== null;
$tabBaru   = '<span class="visually-hidden"> (di tab baru)</span>';
?>
<section id="kontak" class="bagian-tambahan bagian-kontak" aria-labelledby="judul-kontak">
    <div class="container">
        <h2 id="judul-kontak" class="bagian-tambahan-judul"><i class="bi bi-chat-dots" aria-hidden="true"></i> Kontak</h2>
        <p class="bagian-tambahan-pengantar">
            <?php if ($adaKontak) : ?>
                Ada pertanyaan tentang lomba atau pendaftaran? Hubungi panitia lewat WhatsApp.
            <?php else : ?>
                Bantu sekolah lain mengetahui lomba ini dengan membagikan halaman ini.
            <?php endif ?>
        </p>

        <?php if ($narahubung !== [] || $mediaSosial !== []) : ?>
            <div class="kontak-kolom">
                <?php if ($narahubung !== []) : ?>
                    <div class="kontak-kelompok">
                        <h3 class="kontak-subjudul">Narahubung</h3>
                        <ul class="daftar-narahubung list-unstyled mb-0">
                            <?php foreach ($narahubung as $orang) : ?>
                                <li class="narahubung-butir">
                                    <span class="kontak-ikon"><i class="bi bi-person" aria-hidden="true"></i></span>
                                    <div class="narahubung-isi">
                                        <p class="narahubung-nama"><?= esc($orang['nama']) ?></p>
                                        <p class="narahubung-nomor"><?= esc($orang['nomor']) ?></p>
                                    </div>
                                    <a class="btn btn-outline-primary btn-sm narahubung-wa" href="<?= esc($orang['alamat'], 'attr') ?>" target="_blank" rel="noopener noreferrer">
                                        <i class="bi bi-whatsapp" aria-hidden="true"></i> Chat WA<span class="visually-hidden"> <?= esc($orang['nama']) ?> (di tab baru)</span>
                                    </a>
                                </li>
                            <?php endforeach ?>
                        </ul>
                    </div>
                <?php endif ?>

                <?php if ($mediaSosial !== []) : ?>
                    <div class="kontak-kelompok">
                        <h3 class="kontak-subjudul">Media sosial</h3>
                        <ul class="daftar-media-sosial list-unstyled mb-0">
                            <?php foreach ($mediaSosial as $media) : ?>
                                <li>
                                    <a class="media-sosial-tautan" href="<?= esc($media['alamat'], 'attr') ?>" target="_blank" rel="noopener noreferrer">
                                        <span class="kontak-ikon"><i class="bi bi-<?= esc($media['ikon'], 'attr') ?>" aria-hidden="true"></i></span>
                                        <span class="media-sosial-isi">
                                            <span class="media-sosial-label"><?= esc($media['label']) ?></span>
                                            <span class="media-sosial-alamat"><?= esc($media['alamat_ringkas']) ?></span>
                                        </span>
                                        <?= $tabBaru ?>
                                    </a>
                                </li>
                            <?php endforeach ?>
                        </ul>
                    </div>
                <?php endif ?>
            </div>
        <?php endif ?>

        <div class="kontak-tombol">
            <?php if ($waTanya !== null) : ?>
                <a class="btn btn-primary btn-lg kontak-tanya" href="<?= esc($waTanya, 'attr') ?>" target="_blank" rel="noopener noreferrer">
                    <i class="bi bi-whatsapp" aria-hidden="true"></i> Tanya admin lewat WA<?= $tabBaru ?>
                </a>
            <?php endif ?>
            <a class="btn btn-outline-primary btn-lg kontak-bagikan" href="<?= esc($waBagikan, 'attr') ?>" target="_blank" rel="noopener noreferrer">
                <i class="bi bi-share" aria-hidden="true"></i> Bagikan ke WA<?= $tabBaru ?>
            </a>
        </div>
    </div>
</section>
