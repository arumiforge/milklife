# Pustaka tampilan

Berkas jadi dari pustaka luar, disimpan di sini supaya halaman tidak memuat apa pun dari internet (tanpa CDN, tanpa Node). Berkas di folder ini tidak disunting; untuk memperbarui, unduh versi baru dan ganti berkasnya.

| Pustaka | Versi | Lisensi | Asal unduhan |
|---|---|---|---|
| Bootstrap | 5.3.8 | MIT (`bootstrap/LICENSE`) | `https://registry.npmjs.org/bootstrap/-/bootstrap-5.3.8.tgz` |
| Bootstrap Icons | 1.13.2 | MIT (`bootstrap-icons/LICENSE`) | `https://registry.npmjs.org/bootstrap-icons/-/bootstrap-icons-1.13.2.tgz` |
| DataTables | 2.3.8 | MIT (`datatables/LICENSE`) | `https://registry.npmjs.org/datatables.net/-/datatables.net-2.3.8.tgz` |
| DataTables gaya Bootstrap 5 | 2.3.8 | MIT (lisensinya sama dengan DataTables) | `https://registry.npmjs.org/datatables.net-bs5/-/datatables.net-bs5-2.3.8.tgz` |
| jQuery | 3.7.1 | MIT (`jquery/LICENSE`) | `https://registry.npmjs.org/jquery/-/jquery-3.7.1.tgz` |

Arsip Bootstrap diunduh dengan `curl` pada 11 November 2026, arsip DataTables dan jQuery (Tahap 6, F6-01) pada 12 November 2026. Sidik `sha1` arsip sama dengan yang tercatat di registri npm:

| Arsip | sha1 |
|---|---|
| `bootstrap-5.3.8.tgz` | `6401a10057a22752d21f4e19055508980656aeed` |
| `bootstrap-icons-1.13.2.tgz` | `5f77e89f4bc80940185a3f1dab1b4878b9119b9c` |
| `datatables.net-2.3.8.tgz` | `55a8dbe3bd2196951c498ab79bf44602a2bf3229` |
| `datatables.net-bs5-2.3.8.tgz` | `7266636ff488429988ca664bc8cb0b7c5e48c563` |
| `jquery-3.7.1.tgz` | `083ef98927c9a6a74d05a6af02806566d16274de` |

## Berkas yang disalin

| Di sini | Dari arsip |
|---|---|
| `bootstrap/css/bootstrap.min.css` | `package/dist/css/bootstrap.min.css` |
| `bootstrap/js/bootstrap.bundle.min.js` | `package/dist/js/bootstrap.bundle.min.js` (sudah termasuk Popper) |
| `bootstrap/LICENSE` | `package/LICENSE` |
| `bootstrap-icons/bootstrap-icons.min.css` | `package/font/bootstrap-icons.min.css` |
| `bootstrap-icons/fonts/bootstrap-icons.woff2`, `.woff` | `package/font/fonts/` |
| `bootstrap-icons/LICENSE` | `package/LICENSE` |
| `datatables/js/dataTables.min.js` | `datatables.net`: `package/js/dataTables.min.js` |
| `datatables/js/dataTables.bootstrap5.min.js` | `datatables.net-bs5`: `package/js/dataTables.bootstrap5.min.js` |
| `datatables/css/dataTables.bootstrap5.min.css` | `datatables.net-bs5`: `package/css/dataTables.bootstrap5.min.css` |
| `datatables/LICENSE` | `datatables.net`: `package/License.txt` |
| `jquery/jquery.min.js` | `package/dist/jquery.min.js` |
| `jquery/LICENSE` | `package/LICENSE.txt` |

DataTables 2 membutuhkan jQuery, jadi jQuery ikut disimpan. Keduanya hanya dimuat di halaman admin yang memakai tabel data (lihat `tata_letak/admin.php`, bagian `tabel`).

Berkas peta sumber (`.map`) tidak disalin. Alat pengembang peramban mungkin menyebut berkas itu tidak ditemukan; halaman tetap berjalan normal.

## Cara memakai di tampilan

Selalu lewat fungsi `aset()`:

```php
<link rel="stylesheet" href="<?= aset('vendor/bootstrap/css/bootstrap.min.css') ?>">
<link rel="stylesheet" href="<?= aset('vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
<script src="<?= aset('vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
```

Tabel data admin (Tahap 6) tidak memuat aset DataTables sendiri; halamannya cukup mengisi bagian `tabel` (lihat `tata_letak/admin.php` dan `aset/js/tabel-admin.js`).
