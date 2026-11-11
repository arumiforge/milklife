# Pustaka tampilan

Berkas jadi dari pustaka luar, disimpan di sini supaya halaman tidak memuat apa pun dari internet (tanpa CDN, tanpa Node). Berkas di folder ini tidak disunting; untuk memperbarui, unduh versi baru dan ganti berkasnya.

| Pustaka | Versi | Lisensi | Asal unduhan |
|---|---|---|---|
| Bootstrap | 5.3.8 | MIT (`bootstrap/LICENSE`) | `https://registry.npmjs.org/bootstrap/-/bootstrap-5.3.8.tgz` |
| Bootstrap Icons | 1.13.2 | MIT (`bootstrap-icons/LICENSE`) | `https://registry.npmjs.org/bootstrap-icons/-/bootstrap-icons-1.13.2.tgz` |

Arsip diunduh dengan `curl` pada 11 November 2026. Sidik `sha1` arsip sama dengan yang tercatat di registri npm:

| Arsip | sha1 |
|---|---|
| `bootstrap-5.3.8.tgz` | `6401a10057a22752d21f4e19055508980656aeed` |
| `bootstrap-icons-1.13.2.tgz` | `5f77e89f4bc80940185a3f1dab1b4878b9119b9c` |

## Berkas yang disalin

| Di sini | Dari arsip |
|---|---|
| `bootstrap/css/bootstrap.min.css` | `package/dist/css/bootstrap.min.css` |
| `bootstrap/js/bootstrap.bundle.min.js` | `package/dist/js/bootstrap.bundle.min.js` (sudah termasuk Popper) |
| `bootstrap/LICENSE` | `package/LICENSE` |
| `bootstrap-icons/bootstrap-icons.min.css` | `package/font/bootstrap-icons.min.css` |
| `bootstrap-icons/fonts/bootstrap-icons.woff2`, `.woff` | `package/font/fonts/` |
| `bootstrap-icons/LICENSE` | `package/LICENSE` |

Berkas peta sumber (`.map`) tidak disalin. Alat pengembang peramban mungkin menyebut berkas itu tidak ditemukan; halaman tetap berjalan normal.

## Cara memakai di tampilan

Selalu lewat fungsi `aset()`:

```php
<link rel="stylesheet" href="<?= aset('vendor/bootstrap/css/bootstrap.min.css') ?>">
<link rel="stylesheet" href="<?= aset('vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
<script src="<?= aset('vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
```
