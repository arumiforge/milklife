# F10-04 Tabel berpaginasi dan dasbor

| | |
|---|---|
| Tahap | 10 — Identitas visual anime, panel admin, dan data terbaru |
| Dibuat | 22 November 2026 |
| Prioritas | P1 |

## Tujuan

Tidak ada tabel admin yang menampilkan semua baris sekaligus, dan angka dasbor tidak membingungkan (permintaan A6, A8).

## Hasil

- [x] Audit semua tabel di view admin (`grep -rn "<table\|komponen('tabel'" app/Views/admin`); daftar tabel dan cara paginasinya ditulis di deskripsi PR
- [x] Semua tabel berpaginasi, bawaan 25 baris: rekap, daftar peserta, nomor dada, sekolah (termasuk usulan nama dan nama desa), riwayat, tabel di Pengaturan (kelompok umur, nomor lomba, jadwal, kontak, unduhan, bantuan, galeri, tabel skor), hari lomba, dan tabel lain dari audit. Pakai DataTables sisi server (`Panel/TabelSisiServer`, `tabel-admin.js`) untuk tabel besar, paginasi CodeIgniter atau DataTables sisi klien untuk tabel kecil; tombol per baris dan pilih massal tetap bekerja
- [x] Ekspor Excel/PDF tetap memuat semua baris
- [x] Dasbor "Angka lain": definisi "Bukti bayar perlu diperiksa" dan "Bukti bayar baru belum diperiksa" di `Panel/RingkasanDasbor` diperiksa; satukan bila sama, atau label yang menyebut bedanya; penjelasan ditulis di PR

## Berkas dan uji

View di `app/Views/admin/`, `public/aset/js/tabel-admin.js`, pengendali yang perlu rute `/data`, `Panel/RingkasanDasbor.php`, `admin/dasbor.php`. Uji: `PanelRekapTest`, `PanelSekolahTest`, `PanelNomorDadaTest`, `HariLomba*`, `Pengaturan*`, `RingkasanDasborTest`, `PanelRiwayatAdminTest`.

## Selesai bila

Setiap tabel admin dengan lebih dari 25 baris data uji menampilkan paginasi di Chromium 360/1280; aksi per baris dan massal tetap bekerja; semua uji lulus.

## Catatan

Jangan mengubah tata letak atau menu (F10-03).

Rencana: `dokumen/draf/rencana-implementasi-tahap-10.md`; permintaan: `dokumen/draf/permintaan-tahap-10.md` (K153).
