# F10-09 Halaman publik dan unduhan

| | |
|---|---|
| Tahap | 10 — Identitas visual anime, panel admin, dan data terbaru |
| Dibuat | 22 November 2026 |
| Prioritas | P2 |

## Tujuan

Unduhan bisa dilihat dulu sebelum diunduh, tanggal tutup lengkap dengan tahun, dan halaman publik lain berkarakter editorial dengan pilihan kata yang tepat (permintaan C19, C20, C22).

## Hasil

- [x] Unduhan: tombol "Lihat" untuk PDF dan gambar (modal Flowbite berisi `<iframe>`/`<img>` dari berkas sendiri di desktop, tab baru di ponsel) dan "Unduh"; berkas lain hanya "Unduh"; jenis dan ukuran berkas tidak tampil; rute pratinjau mengirim `Content-Disposition: inline` tanpa mengubah unduhan lama
- [x] "Daftar s.d. 30 November 2026" (dengan tahun) di semua tempat tanggal tutup tampil; judul hero tetap tanpa tahun
- [x] Jadwal, Hasil, Klasemen, Peserta, Galeri, Bantuan, Unduhan, Cek Pendaftaran, dan wizard memakai kelas editorial F10-05 di pita judul (judul Inggris + keterangan Indonesia, misalnya "Race Day" / "Jadwal pertandingan"), ikon polos, tanpa border penuh berwarna; perubahan visual saja
- [x] Tinjau pilihan kata tiap halaman publik (tombol kata kerja, kalimat pendek untuk guru); daftar perubahan di PR

## Berkas dan uji

`UnduhanController`, `unduhan.php`, `beranda/_daftar_unduhan.php`, `tata_letak/_judul_halaman_tw.php`, view publik terkait, `Routes.php` (blok F10-09). Uji: `HalamanUnduhanTest`, `HalamanDepan*`, `JadwalPertandinganTest`, `HasilLombaTest`, `PesertaPublikTest`, `GaleriTest`, `BantuanFaqTest`, `CekPendaftaranTest`, `Pendaftaran*`.

## Selesai bila

Pratinjau PDF dan gambar bekerja di Chromium 360/1280 tanpa galat CSP; **alur daftar → cek → bukti diuji di peramban dan perilakunya sama**; semua uji lulus.

## Catatan

Bergantung pada F10-05.

Rencana: `dokumen/draf/rencana-implementasi-tahap-10.md`; permintaan: `dokumen/draf/permintaan-tahap-10.md` (K153).
