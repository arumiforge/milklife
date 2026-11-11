# F2-08 Akun admin

| | |
|---|---|
| Status | menunggu tinjauan |
| Tahap | 2 — Pengaturan |
| Dibuat | 11 November 2026 |

## Tujuan

Admin yang sedang masuk bisa mengganti nama, nama pengguna, dan kata sandinya sendiri (K26).

## Hasil

- [x] `/admin/akun`: formulir nama dan nama pengguna; formulir kata sandi (sandi saat ini, sandi baru, ulangi)
- [x] Kata sandi saat ini wajib untuk mengganti nama pengguna dan kata sandi
- [x] Aturan `is_unique` nama pengguna di `AdminModel` mengecualikan baris sendiri
- [x] Setelah simpan, `admin_nama` di sesi ikut berubah; setelah ganti kata sandi, sesi dibuat ulang
- [x] Uji fitur dan uji model

## Selesai bila

`composer uji` lulus; admin bisa mengganti nama dan kata sandi lalu masuk lagi dengan kata sandi baru.

## Catatan

- Kesepakatan dan pembagian berkas: `dokumen/draf/rencana-implementasi-tahap-2.md`. Berkas di luar bagian tugas ini tidak diubah; bila perlu, lapor ke koordinator.

### Keputusan kecil saat pengerjaan

- **Kata sandi saat ini** wajib untuk mengganti nama pengguna dan kata sandi (asumsi 3), tetapi **tidak** untuk mengganti nama tampilan saja. Alasan: nama tampilan hanya muncul di kepala panel dan tidak dipakai untuk masuk, jadi orang yang kebetulan memegang sesi admin (misalnya komputer panitia yang lupa dikunci) tidak bisa mengunci pemilik akun keluar dengan mengganti nama tampilan; nama pengguna dan kata sandi bisa, karena itu dijaga. "Diganti" dinilai setelah nama pengguna dinormalkan, jadi `Admin.Uji` untuk akun `admin.uji` tidak dianggap diganti. Kotak "Kata sandi saat ini" di formulir pertama selalu tampil dengan keterangan "Hanya perlu diisi bila Anda mengganti nama pengguna".
- **Nama kotak**: formulir 1 `nama`, `nama_pengguna`, `sandi_akun`; formulir 2 `sandi_saat_ini`, `sandi_baru`, `ulangi_sandi`. Nama kotak sandi di formulir 1 dibedakan supaya `id` di satu halaman tidak kembar. Kotak kata sandi tidak pernah diisi ulang setelah isian salah; nama dan nama pengguna yang diketik dipertahankan. Formulir kata sandi membawa kotak nama pengguna tersembunyi (`autocomplete="username"`, tanpa `name`) supaya pengelola kata sandi peramban menyimpan pasangan yang benar.
- **Pembatasan percobaan**: setiap kiriman yang memeriksa kata sandi saat ini (ganti kata sandi, atau ganti nama pengguna) dihitung sebelum kata sandi diperiksa, dengan pembatas bawaan yang sama seperti halaman masuk: 5 percobaan, lalu satu percobaan baru tiap 12 detik. Kuncinya **per akun** (`sandi_akun_<id>`), bukan per alamat IP, karena yang dicegah adalah orang yang sudah memegang sesi menebak kata sandi; kedua formulir memakai ember yang sama. Saat terlampaui: status 429, kepala `Retry-After`, formulir tampil lagi dengan pesan "Terlalu banyak percobaan kata sandi. Tunggu N detik lagi, lalu coba sekali lagi." di kotak kata sandi. Mengganti nama tampilan saja tidak dihitung.
- **Kata sandi baru** memakai aturan model (8–72 karakter); ditolak juga bila sama dengan kata sandi saat ini, dan bila ulangannya tidak sama. Isian kata sandi saat ini lebih dari 255 karakter langsung dianggap salah (sama dengan batas halaman masuk).
- **Sesi**: setelah simpan nama, `admin_nama` di sesi diperbarui (kepala panel langsung memakai nama baru). Setelah ganti kata sandi, `session()->regenerate(true)`; isi sesi dipertahankan sehingga admin tetap masuk. Sesi lain milik akun yang sama (peramban lain) **tidak** ikut dikeluarkan; lihat "Ditunda".
- **Akun yang sudah dihapus** (lewat basis data) tetapi sesinya masih ada: ketiga rute mengosongkan sesi, membuat ID sesi baru, dan mengalihkan ke halaman masuk dengan pesan "Akun Anda tidak ditemukan lagi, jadi Anda dikeluarkan. …".
- **`AdminModel`**: aturan nama pengguna menjadi `is_unique[admin.nama_pengguna,id,{id}]` dengan aturan `id` (`permit_empty|is_natural_no_zero`) hanya untuk mengisi placeholder. `update()` ditimpa untuk mengingat ID satu baris yang diubah; `transformDataToArray()` (yang sudah menormalkan isian sebelum validasi, Catatan F1-04) menyisipkan `id` itu bila `nama_pengguna` ikut diubah. `id` dibuang `doProtectFields()` sesudah validasi (bukan bagian `$allowedFields`), jadi tidak pernah disimpan, juga bila pemanggil mengirim `id` sendiri. `update()` banyak baris sekaligus tidak mengecualikan baris mana pun. Saat `insert()` tidak ada `id`, placeholder tidak terisi dan aturan bawaan CodeIgniter mengabaikan pengecualiannya, jadi perilakunya sama seperti sebelumnya. `periksaIsian()` mendapat parameter ketiga pilihan `$idSendiri`; pemanggilan lama (`admin:buat`) tidak berubah. Callback `hashSandi` tidak diubah.
- **Uji**: `tests/fitur/AkunAdminTest.php` (halaman, belum masuk, CSRF, akun terhapus, ganti nama tanpa sandi, ganti nama pengguna dengan/tanpa sandi, nama pengguna milik sendiri dan milik admin lain, isian salah, ganti kata sandi lalu masuk dengan kata sandi baru dan lama ditolak, kata sandi salah/kosong/pendek/panjang/sama/ulangan beda, pembatasan per akun) dan tambahan di `tests/basisdata/AdminModelTest.php` (pengecualian baris sendiri, `id` tidak tersimpan, `periksaIsian` dengan `$idSendiri`). Uji Tahap 1 tidak diubah dan tetap lulus.

### Ditunda

- Mengeluarkan sesi lain milik akun yang sama setelah kata sandi diganti. Sesi disimpan di berkas dan tidak dicatat per akun, jadi perlu kolom atau penanda baru (misalnya versi kata sandi di tabel `admin` yang dicocokkan penjaga `AdminFilter`); itu migrasi dan berkas di luar bagian F2-08. Sesi lain tetap berakhir sendiri setelah 2 jam (`session.expiration`).
