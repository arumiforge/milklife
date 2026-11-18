<?php
/**
 * Tabel klasemen sekolah per jenjang (F8-05), dipakai halaman admin
 * `admin/klasemen` dan publik `hasil/klasemen`. Rincian piala tiap sekolah
 * bisa dibuka (`<details>`). Di admin, piala dari nomor yang belum terbit
 * diberi tanda.
 *
 * @var array<string, array<string, mixed>> $klasemen `Klasemen::hitung()`
 * @var bool                                $admin
 */
$romawi = [1 => 'I', 2 => 'II', 3 => 'III'];
?>
<?php foreach ($klasemen as $jenjang) : ?>
    <section class="card mb-4" aria-labelledby="klasemen-<?= esc($jenjang['jenjang'], 'attr') ?>">
        <div class="card-body">
            <h2 class="h5 fw-bold" id="klasemen-<?= esc($jenjang['jenjang'], 'attr') ?>"><?= esc($jenjang['label']) ?></h2>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col" class="text-center">Per.</th>
                            <th scope="col">Sekolah</th>
                            <th scope="col" class="text-center"><span class="visually-hidden">Juara </span>I</th>
                            <th scope="col" class="text-center"><span class="visually-hidden">Juara </span>II</th>
                            <th scope="col" class="text-center"><span class="visually-hidden">Juara </span>III</th>
                            <th scope="col" class="text-center d-none d-sm-table-cell">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($jenjang['baris'] as $b) : ?>
                            <tr<?= $b['juara_umum'] !== null ? ' class="table-warning"' : '' ?>>
                                <td class="text-center fw-semibold"><?= $b['peringkat'] ?></td>
                                <td>
                                    <?= esc($b['sekolah']) ?>
                                    <?php if ($b['juara_umum'] !== null) : ?>
                                        <span class="badge rounded-pill text-bg-warning">Juara umum <?= $romawi[$b['juara_umum']] ?></span>
                                    <?php endif ?>
                                    <details class="small text-body-secondary">
                                        <summary>Rincian <?= $b['jumlah'] ?> piala</summary>
                                        <ul class="mb-0 ps-3">
                                            <?php foreach ($b['piala'] as $p) : ?>
                                                <li>
                                                    Juara <?= $romawi[$p['peringkat']] ?> <?= esc($p['kelompok_umur'] . ' ' . $p['nomor']) ?> – <?= esc($p['nama']) ?>
                                                    <?php if ($admin && ! $p['terbit']) : ?><span class="badge text-bg-secondary">belum terbit</span><?php endif ?>
                                                </li>
                                            <?php endforeach ?>
                                        </ul>
                                    </details>
                                </td>
                                <td class="text-center"><?= $b['emas'] ?></td>
                                <td class="text-center"><?= $b['perak'] ?></td>
                                <td class="text-center"><?= $b['perunggu'] ?></td>
                                <td class="text-center d-none d-sm-table-cell"><?= $b['jumlah'] ?></td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
<?php endforeach ?>
