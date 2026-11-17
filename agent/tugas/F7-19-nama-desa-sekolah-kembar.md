# F7-19 Tambahkan nama desa ke sekolah bernama sama

| | |
|---|---|
| Status | menunggu tinjauan |
| Tahap | 7 — Penyelesaian |
| Dibuat | 17 November 2026 |

## Tujuan

Admin bisa membedakan semua sekolah bernama sama sekaligus dengan menambahkan nama desanya, tanpa menyunting satu per satu. Pendaftaran yang sudah memakai nama lama ikut memakai nama baru, dan nama tiap sekolah tetap bisa diubah lagi (K114).

## Hasil

- [x] Tombol **Tambahkan nama desa ke nama yang sama** di menu Sekolah (tampil bila ada nama kembar), juga ditautkan dari kotak peringatan nama kembar
- [x] Halaman `admin/sekolah/nama-desa`: daftar nama baru (NPSN, nama sekarang, nama baru, sumber pembeda, letak, banyak pendaftaran bernama lama) dengan kotak centang, bawaannya tercentang, dan kotak centang semua (`aset/js/centang-semua.js`)
- [x] Aturan nama baru di `App\Libraries\Panel\NamaDesaSekolah::rencanakan()`: nama + desa (huruf besar bila nama huruf besar); bila desa kosong, sudah tertulis di nama, atau sama dengan sekolah lain bernama sama, dipakai kecamatan, lalu kabupaten
- [x] Sekolah yang letaknya tidak bisa membedakan, yang nama barunya sama dengan sekolah lain, atau yang nama barunya terlalu panjang tampil di bagian **Perlu diubah sendiri** dengan tombol Ubah
- [x] Terapkan (dengan jendela konfirmasi): nama sekolah diganti dan ditandai disunting (tidak ditimpa `sekolah:impor`); pendaftaran ber-NPSN itu yang masih memakai nama lama ikut diganti; nama usulan pendaftar yang belum diputuskan tidak diubah. Nama baru dihitung ulang di server, jadi sekolah yang sudah tidak kembar tidak diubah
- [x] Nama tiap sekolah tetap bisa diubah lagi lewat Ubah (pesan sukses menunjukkan caranya)
- [x] Dokumen: rancangan revisi 15, panduan produksi, KONTEKS, KEPUTUSAN K114

## Selesai bila

`composer uji` lulus lokal dan di CI. Uji baru: `tests/unit/PanelNamaDesaSekolahTest.php` (aturan nama baru) dan `tests/fitur/PanelNamaDesaSekolahHalamanTest.php` (halaman, terapkan, centang, CSRF, ubah lagi per sekolah).

## Catatan

- Dengan data 14 November 2026: dari 501 sekolah bernama kembar, 495 mendapat nama baru (374 dari desa, 116 dari kecamatan, 5 dari kabupaten) dan 6 perlu diubah sendiri.
- Kecamatan dan kabupaten dipakai karena banyak nama kembar sudah memuat nama desanya, misalnya "SD NEGERI 1 TAHUNAN" di desa Tahunan (Rembang dan Jepara).
