# F7-05 Cadangan dan panduan produksi

| | |
|---|---|
| Status | menunggu tinjauan |
| Tahap | 7 — Penyelesaian |
| Dibuat | 12 November 2026 |

## Tujuan

Pemilik bisa memasang web di komputer produksi Laragon tanpa bantuan, dan data pendaftaran tercadang setiap hari (K66).

## Hasil

- [x] `php spark cadangan:buat`: zip berisi `basisdata.sql`, `writable/unggahan/`, `public/unggahan/` ke `cadangan.folder`; menghapus cadangan lebih tua dari `cadangan.simpanHari` (14); menolak folder di dalam proyek; kode keluar bukan 0 bila gagal
- [x] `Config\Cadangan` dan bagian Cadangan di `.env.example`; sandi basis data tidak terlihat di baris perintah
- [x] `dokumen/draf/panduan-pemasangan-produksi.md`: Laragon dan `php.ini`, `.env` produksi, perintah pemasangan, `aset:minify`, virtual host Nginx ke `public/`, Cloudflare Tunnel, Penjadwal Tugas harian, pemulihan, pembaruan versi, daftar periksa sebelum membuka pendaftaran
- [x] `README.md`: tautan ke panduan dan baris Status diperbarui
- [ ] Setelah disetujui pemilik: panduan dipindahkan ke `dokumen/final/` dan PDF dibuat dengan `php spark dokumen:pdf` di PR yang sama

## Selesai bila

`composer uji` lulus lokal dan di CI. Uji mencakup masa simpan 14 hari (kelas murni), penolakan folder di dalam proyek, dan isi zip dari basis data uji. Panduan disetujui pemilik.

## Catatan

Rencana: `dokumen/draf/rencana-implementasi-tahap-7.md` bagian "Cadangan" dan "Panduan pemasangan produksi". Asumsi 3, 4, 8. Gelombang 2, setelah F7-01 dan F7-02 digabung (nama perintah minify dan isian `.env` produksi).

## Hasil pengerjaan

- `app/Commands/BuatCadangan.php` memanggil `App\Libraries\Cadangan\PembuatCadangan`; nama berkas dan masa simpan di kelas murni `App\Libraries\Cadangan\MasaSimpan`. Umur cadangan dibaca dari tanggal di nama berkas; yang berumur `simpanHari` hari atau lebih dihapus, jadi cadangan harian yang tersisa 14 berkas. Cadangan lama hanya dihapus setelah zip baru berhasil ditulis.
- `mysqldump` dipanggil dengan `--defaults-extra-file` (berkas opsi sementara berisi host, port, pengguna, sandi; dihapus setelah selesai), `--single-transaction`, `--no-tablespaces` (pengguna MySQL khusus tanpa hak `PROCESS` tetap bisa mencadangkan), `--default-character-set=utf8mb4`, dan `--result-file`.
- `cadangan.folder` harus sudah ada; perintah tidak membuat folder sendiri supaya salah ketik jalur tidak diam-diam membuat folder baru.
- Uji: `tests/unit/CadanganMasaSimpanTest.php` (masa simpan, nama berkas, folder di dalam proyek) dan `tests/basisdata/CadanganBuatTest.php` (isi zip dari basis data uji, penghapusan cadangan lama, gagal tanpa menghapus, penolakan folder, pengguna khusus dengan sandi berkarakter khusus, kode keluar perintah).
- Di luar pembagian berkas: `app/Libraries/MarkdownSederhana.php` sekarang mengenali blok kode berpagar dan nomor awal daftar bernomor, supaya PDF panduan (penuh perintah) terbaca dengan benar; ujinya di `tests/unit/MarkdownSederhanaTest.php`.

## Pertanyaan untuk pemilik

Ditulis juga di deskripsi PR; lihat bagian "Asumsi yang perlu ditinjau" di panduan.
