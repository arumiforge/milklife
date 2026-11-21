<?php
/**
 * Laci bantuan wizard (F9-04): Bootstrap offcanvas `#laci-bantuan`, dibuka
 * tombol Bantuan di judul tiap langkah (`_tombol_bantuan.php`). Berisi satu
 * kelompok per langkah (`data-bantuan-langkah`); `aset/js/bantuan.js`
 * menampilkan kelompok langkah yang tombolnya ditekan dan menyaring isinya
 * lewat kotak cari. Tanya-jawab dari Pengaturan > Bantuan
 * (`FaqModel::KATEGORI_LANGKAH`), gambar panduan G9-03 bila berkasnya ada.
 * Tampilan Bootstrap sampai wizard pindah ke Tailwind (F9-05).
 *
 * @var CodeIgniter\View\View $this
 */

use App\Libraries\Bantuan\IsiBantuan;

$isiBantuan = new IsiBantuan();
$kontak     = IsiBantuan::kontak();
$judul      = [1 => 'Data Sekolah', 2 => 'Ofisial', 3 => 'Atlet', 4 => 'Biaya', 5 => 'Pembayaran'];
?>
<div class="offcanvas offcanvas-end laci-bantuan" tabindex="-1" id="laci-bantuan" aria-labelledby="laci-bantuan-judul">
    <div class="offcanvas-header border-bottom">
        <h2 class="offcanvas-title h5 mb-0" id="laci-bantuan-judul"><i class="bi bi-question-circle text-primary" aria-hidden="true"></i> Bantuan</h2>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup bantuan"></button>
    </div>
    <div class="offcanvas-body">
        <p class="small text-body-secondary">Isian Anda tetap aman selama laci ini dibuka.</p>
        <label class="form-label fw-semibold" for="cari-laci-bantuan">Cari pertanyaan</label>
        <input type="search" class="form-control mb-3" id="cari-laci-bantuan" autocomplete="off" placeholder="Contoh: foto, NISN, transfer">

        <?php foreach ($judul as $langkah => $nama) : ?>
            <?php $butir = $isiBantuan->untukLangkah($langkah); ?>
            <?php $gambar = IsiBantuan::gambarLangkah($langkah); ?>
            <div data-bantuan-langkah="<?= $langkah ?>" hidden>
                <h3 class="h6 text-body-secondary">Langkah <?= $langkah ?>: <?= esc($nama) ?></h3>
                <?php if ($gambar !== null) : ?>
                    <img src="<?= esc($gambar) ?>" alt="" class="gambar-panduan mb-3" width="1200" height="900" loading="lazy">
                <?php endif ?>
                <?php if ($butir === []) : ?>
                    <p class="text-body-secondary">Belum ada tanya-jawab untuk langkah ini.</p>
                <?php else : ?>
                    <div class="accordion" id="laci-akordeon-<?= $langkah ?>">
                        <?php foreach ($butir as $b) : ?>
                            <?php $id = 'laci-' . $langkah . '-' . (int) $b['id']; ?>
                            <div class="accordion-item" data-butir-faq data-cari="<?= esc($b['cari'], 'attr') ?>">
                                <h4 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $id ?>" aria-expanded="false" aria-controls="<?= $id ?>">
                                        <?= esc($b['pertanyaan']) ?>
                                    </button>
                                </h4>
                                <div id="<?= $id ?>" class="accordion-collapse collapse" data-bs-parent="#laci-akordeon-<?= $langkah ?>">
                                    <div class="accordion-body small"><?= $b['jawaban'] ?></div>
                                </div>
                            </div>
                        <?php endforeach ?>
                    </div>
                <?php endif ?>
            </div>
        <?php endforeach ?>

        <p class="text-body-secondary mt-3" data-cari-kosong-laci hidden>Belum ada jawaban yang cocok. Coba kata lain, atau buka semua bantuan.</p>

        <div class="d-grid gap-2 mt-4">
            <a class="btn btn-outline-primary" href="<?= site_url('bantuan') ?>" target="_blank" rel="noopener">
                <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Buka semua bantuan
            </a>
            <?php if ($kontak['wa_tanya'] !== null) : ?>
                <a class="btn btn-success" href="<?= esc($kontak['wa_tanya'], 'attr') ?>" target="_blank" rel="noopener noreferrer">
                    <i class="bi bi-whatsapp" aria-hidden="true"></i> Tanya panitia lewat WA
                </a>
            <?php endif ?>
        </div>
    </div>
</div>
