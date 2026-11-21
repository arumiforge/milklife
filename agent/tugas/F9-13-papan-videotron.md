# F9-13 Papan videotron

| | |
|---|---|
| Tahap | 9 — Rombak tampilan dan fitur publik hari lomba |
| Dibuat | 21 November 2026 |
| Prioritas | P1 |

## Tujuan

Penonton di arena melihat hasil terbaru, juara umum, dan jadwal berikutnya di videotron tanpa operator harus berpindah halaman.

## Hasil

- [ ] `/layar` tanpa masuk, hanya data terbit; berganti otomatis antara hasil terbaru, juara umum per jenjang, jadwal berikutnya, sedang berlangsung
- [ ] Data lewat `/layar/data` (JSON, bisa ditembolok), segar 30 detik, tetap menampilkan data terakhir bila jaringan putus
- [ ] Tata letak adaptif 16:9 dan 4:3, parameter `?skala=`, tombol layar penuh, latar Three.js dengan cadangan G9-06
- [ ] Pengaturan > Videotron: isi yang tampil dan lama tiap tampilan

## Berkas dan uji

`LayarController`, `app/Libraries/Hasil/` (data layar), views `layar/`, `public/aset/js/layar.js`. Uji baru `PapanVideotronTest`.

## Selesai bila

Diuji di 1920×1080, 1280×720, 1024×768 dan berjalan satu jam tanpa kebocoran memori yang terlihat; uji lulus.

## Catatan

Rencana: `dokumen/draf/rencana-implementasi-tahap-9.md`. K140 butir 13. Ukuran videotron dikonfirmasi pemilik ke vendor; ditanyakan lagi di PR.
