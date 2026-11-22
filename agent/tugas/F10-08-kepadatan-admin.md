# F10-08 Kepadatan panel admin

| | |
|---|---|
| Tahap | 10 — Identitas visual anime, panel admin, dan data terbaru |
| Dibuat | 22 November 2026 |
| Prioritas | P2 |

## Tujuan

Panitia melihat lebih banyak isi di satu layar tanpa kesan sesak (permintaan A7).

## Hasil

- [ ] Analisis ruang kosong tiap halaman admin (tangkapan 1280 px sebelum dan sesudah) dengan daftar temuan di deskripsi PR
- [ ] Kurangi yang berlebihan: jarak antarkartu, padding kartu, kepala halaman, ruang kosong ilustrasi, ukuran huruf judul halaman, isian formulir dalam kisi dua/tiga kolom di desktop
- [ ] Tidak mengubah perilaku, nama tombol, atau urutan isian
- [ ] Ponsel tetap nyaman (target sentuh ≥ 44 px)

## Berkas dan uji

View di `app/Views/admin/`, `app/Views/komponen/` bila jarak bawaannya berlebihan untuk admin (tanpa mengubah tampilan publik). Uji: semua uji admin (`--filter 'Panel|Pengaturan|HariLomba|Hasil|Dasbor|TataLetak'`).

## Selesai bila

Tangkapan sebelum dan sesudah menunjukkan isi lebih padat; tanpa gulir mendatar di 360/390/1280; semua uji lulus.

## Catatan

Bergantung pada F10-03 dan F10-04.

Rencana: `dokumen/draf/rencana-implementasi-tahap-10.md`; permintaan: `dokumen/draf/permintaan-tahap-10.md` (K153).
