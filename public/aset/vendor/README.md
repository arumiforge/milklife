# Pustaka tampilan

Berkas jadi dari pustaka luar, disimpan di sini supaya halaman tidak memuat apa pun dari internet (tanpa CDN) dan server tidak butuh Node. Berkas di folder ini tidak disunting. Pustaka lama (tabel pertama) diperbarui dengan mengunduh versi baru dan mengganti berkasnya; pustaka tampilan Tahap 9 (bagian "Tampilan Tahap 9" di bawah) disalin otomatis oleh `npm run vendor`.

| Pustaka | Versi | Lisensi | Asal unduhan |
|---|---|---|---|
| DataTables | 2.3.8 | MIT (`datatables/LICENSE`) | `https://registry.npmjs.org/datatables.net/-/datatables.net-2.3.8.tgz` |
| jQuery | 3.7.1 | MIT (`jquery/LICENSE`) | `https://registry.npmjs.org/jquery/-/jquery-3.7.1.tgz` |
| ApexCharts | 4.7.0 | MIT (`apexcharts/LICENSE`) | `https://registry.npmjs.org/apexcharts/-/apexcharts-4.7.0.tgz` |
| CSS Flowbite Dashboard (Tailwind 4) | 2.2.0 | lisensi Flowbite Pro milik pemilik (`https://flowbite.com/license/`) | `template-flowbites/flowbite-admin-dashboard-v2.2.0/static/app.css` di repo ini |

Arsip DataTables dan jQuery (Tahap 6, F6-01) pada 12 November 2026, arsip ApexCharts (K102) pada 15 November 2026. ApexCharts 4.7.0 adalah versi terakhir berlisensi MIT; versi 5.2 ke atas memakai lisensi ApexCharts sendiri. Sidik `sha1` arsip sama dengan yang tercatat di registri npm:

| Arsip | sha1 |
|---|---|
| `datatables.net-2.3.8.tgz` | `55a8dbe3bd2196951c498ab79bf44602a2bf3229` |
| `jquery-3.7.1.tgz` | `083ef98927c9a6a74d05a6af02806566d16274de` |
| `apexcharts-4.7.0.tgz` | `27e578a2fd66847d8ea425aeea1de56d6be42f21` |

## Berkas yang disalin

| Di sini | Dari arsip |
|---|---|
| `datatables/js/dataTables.min.js` | `datatables.net`: `package/js/dataTables.min.js` |
| `datatables/LICENSE` | `datatables.net`: `package/License.txt` |
| `jquery/jquery.min.js` | `package/dist/jquery.min.js` |
| `jquery/LICENSE` | `package/LICENSE.txt` |
| `apexcharts/apexcharts.min.js` | `package/dist/apexcharts.min.js` |
| `apexcharts/LICENSE` | `package/LICENSE` |
| `flowbite/flowbite-dasbor.css` | salinan apa adanya `template-flowbites/flowbite-admin-dashboard-v2.2.0/static/app.css` |

## Tampilan Tahap 9 (K140, K141)

Dipasang lewat `package.json` di akar repo (versi mengikuti `package.json` templat Flowbite Pro, dikunci `package-lock.json`) dan disalin `npm run bangun` (`alat/vendor.mjs`, `alat/ikon.mjs`). Versi terpasang tercatat di `VERSI-TAMPILAN.txt`. Hasil salinan di-commit; CI (job "Hasil bangun tampilan") gagal bila berbeda dari hasil bangun.

| Di sini | Paket npm | Lisensi |
|---|---|---|
| `flowbite/flowbite.min.js` | `flowbite` (`dist/flowbite.min.js`), JS komponen templat Pro: carousel, laci, modal, dropdown, tab, tooltip, akordeon, pemilih tanggal | MIT (`flowbite/LICENSE-flowbite.md`) |
| `three/three.module.min.js`, `three/three.core.min.js` | `three` (`build/`), modul ES; berkas pertama mengimpor yang kedua | MIT (`three/LICENSE`) |
| `inter/inter-latin-wght-normal.woff2`, `inter/inter-latin-ext-wght-normal.woff2` | `@fontsource-variable/inter`, huruf templat Pro | OFL 1.1 (`inter/LICENSE`) |
| `lucide/lucide.svg` | `lucide-static` (`icons/*.svg`), sprite berisi ikon yang dipakai saja (`npm run ikon`) | ISC (`lucide/LICENSE`) |

CSS tampilan baru bukan di folder ini: `public/aset/css/tw.css` dibangun `npm run css` dari `public/aset/tailwind/masukan.css` dengan Tailwind 4, plugin dan tema Flowbite, dan `flowbite-typography`, seperti `src/app.css` templat Pro.

ApexCharts dimuat di dasbor admin (`aset/js/dasbor.js`) dan di halaman Monitoring `arumi.php`. CSS Flowbite Dashboard lama hanya dimuat `arumi.php`; halaman bertampilan baru memakai `css/tw.css` (K140, K141). CSS itu sudah dipangkas Tailwind untuk kelas yang dipakai template, jadi kelas Tailwind yang tidak dipakai template tidak ada di dalamnya.

DataTables 2 membutuhkan jQuery, jadi jQuery ikut disimpan. Keduanya hanya dimuat di halaman admin yang memakai tabel data (lihat `tata_letak/admin_tw.php`, bagian `tabel`).

Berkas peta sumber (`.map`) tidak disalin. Alat pengembang peramban mungkin menyebut berkas itu tidak ditemukan; halaman tetap berjalan normal.

## Cara memakai di tampilan

Selalu lewat fungsi `aset()`:

```php
<script src="<?= aset('vendor/flowbite/flowbite.min.js') ?>"></script>
```

Bootstrap, Bootstrap Icons, dan gaya DataTables untuk Bootstrap dilepas di F9-17 setelah semua halaman pindah ke Tailwind (K140); ikon memakai sprite Lucide lewat `ikon()`.

Tabel data admin (Tahap 6) tidak memuat aset DataTables sendiri; halamannya cukup mengisi bagian `tabel` (lihat `tata_letak/admin_tw.php` dan `aset/js/tabel-admin.js`).
