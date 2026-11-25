# F11-22 Ganti atlet seperti Seri 1 dengan dua sakelar

| | |
|---|---|
| Tahap | 11 — Pelajaran hasil Seri 1 (tugas sisipan setelah penutupan) |
| Dibuat | 25 November 2026 |

## Tujuan

Seperti Seri 1, panitia bisa mengganti atlet saat pertemuan teknik, saat sekolah mengambil nomor atlet, dan di hari lomba, termasuk setelah daftar seri dikunci. Admin bisa menutup atau membatasinya lewat sakelar bila aturan berubah (K188).

## Hasil

- [x] Pengaturan > Pendaftaran, bagian Ganti atlet: sakelar **Ganti atlet dibuka** dan **Boleh setelah seri dikunci**, keduanya bawaan menyala.
- [x] Sakelar pertama mati: formulir ganti atlet menyebut ganti atlet ditutup dan penyimpanan ditolak.
- [x] Sakelar kedua menyala: atlet di babak yang seri-nya sudah dikunci bisa diganti, dan penggantinya mengambil seri dan lintasan atlet lama. Mati: ditolak seperti sebelumnya.
- [x] Pesan berhasil mengingatkan untuk mencetak ulang daftar seri bila sudah dibagikan.
- [x] Tanya-jawab bawaan menyebut tiga kesempatan ganti atlet dan WA.
- [x] K188, ATURAN, PETA-KODE, rancangan revisi 45, dan panduan admin hari lomba revisi 3 beserta PDF-nya.

## Berkas dan uji

`app/Config/Pendaftaran.php`, `app/Libraries/Panel/GantiAtlet.php`, `app/Controllers/Admin/Pengaturan/PendaftaranController.php`, `app/Config/Routes.php`, `app/Views/admin/pengaturan/pendaftaran.php`, `app/Controllers/Admin/GantiAtletController.php`, `app/Views/admin/pendaftaran/ganti_atlet.php`, `app/Libraries/Bantuan/FaqBawaan.php`. Uji `vendor/bin/phpunit --filter 'PanelGantiAtlet|PengaturanPendaftaranTest'`.

## Selesai bila

Atlet di babak yang seri-nya sudah dikunci bisa diganti dengan bawaan sakelar, dan tempatnya di seri pindah ke atlet pengganti. Uji terkait lulus.

## Catatan

- Pemilik memilih dua sakelar tanpa tanggal, bukan tiga sakelar per momen bertanggal. Pertemuan teknik dan pengambilan nomor atlet sama-sama terjadi sebelum seri dikunci, jadi web tidak perlu membedakannya.
- Server yang tanya-jawabnya sudah terpasang tidak berubah otomatis; jawaban ganti atlet disunting admin di Bantuan.
