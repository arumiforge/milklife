# F7-08 Data sekolah dan menu Sekolah di panel

| | |
|---|---|
| Status | menunggu tinjauan |
| Tahap | 7 — Penyelesaian |
| Dibuat | 14 November 2026 |

## Tujuan

Data sekolah diperbarui dari Sekolah Kita (sekolah.data.kemendikdasmen.go.id), dan admin bisa melihat serta menyunting data sekolah di panel supaya sekolah bernama umum ("MIS MUHAMMADIYAH") bisa dibedakan (K97).

## Hasil

- [x] `data/sekolah/sekolah.json` diperbarui: 4.766 sekolah (37 sekolah aktif baru, 125 nama mengikuti ejaan Sekolah Kita). Hasil mentah di `data/sekolah/mentah/`, penggabung `data/sekolah/gabung_sekolahkita.py`, cara di `data/README.md`
- [x] Menu **Sekolah** di panel (`admin/sekolah`): tabel dengan saringan kabupaten, jenjang, nama kembar, dan sudah disunting; jumlah "Nama sama"; unduh Excel
- [x] Formulir ubah (`admin/sekolah/<id>/ubah`): nama, bentuk, jenjang, status, alamat, desa, kecamatan, kabupaten; NPSN tetap; daftar sekolah lain bernama sama; pilihan mengganti nama di pendaftaran yang sudah masuk
- [x] Nama baru yang sama dengan sekolah lain ditolak; nama lama yang masih kembar boleh tetap (supaya alamat saja bisa dibetulkan)
- [x] Kolom `sekolah.disunting_pada`: `php spark sekolah:impor` tidak menimpa sekolah yang disunting admin dan menyebut jumlahnya
- [x] Wizard langkah 1 menampilkan desa dan kecamatan di bawah nama sekolah yang ditemukan
- [x] Menu bawah ponsel tetap terbaca dengan enam butir (layar 360 piksel)
- [x] Perbaikan bug: NPSN kiriman dengan spasi di tengah ("2031 7000") kini dibaca sama di semua pemeriksaan (susulan, kuota, kunci, SLB), bukan hanya saat menyimpan

## Selesai bila

`composer uji` lulus lokal dan di CI. Uji baru: `tests/fitur/PanelSekolahTest.php`, `ImporSekolahTest::testSekolahYangDisuntingAdminTidakDitimpa`, `SekolahModelTest::testLokasiMenggabungkanDesaKecamatanKabupaten`, `PendaftaranSekolahTest::testLetakSekolahIkutDikirimUntukMembedakanNamaUmum`, dan butir NPSN berspasi di `PendaftaranPemeriksaTest`.

## Catatan

- Sekolah Kita memakai nama yang sama persis dengan data lama, jadi nama kembar hanya bisa dibereskan lewat penyuntingan admin.
- Penelusuran Sekolah Kita dilakukan lewat peramban di halaman daftar sekolahnya (100 kartu per halaman), bukan lewat skrip; `ambil_sekolah.py` (Data Referensi) tetap ada.
- Rancangan final dan panduan pemasangan produksi diperbarui di F7-09 atas permintaan pemilik (K100).
- Tidak termasuk: menambah atau menghapus sekolah dari panel.
