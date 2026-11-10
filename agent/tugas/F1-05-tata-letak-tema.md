# F1-05 Tata letak dan tema

| | |
|---|---|
| Status | rencana |
| Tahap | 1 — Kerangka |
| Dibuat | 10 November 2026 |

## Tujuan

Halaman publik dan panel admin punya tata letak jadi dengan warna flyer, nyaman di ponsel, dan siap diisi halaman-halaman tahap berikutnya.

Acuan: rancangan bagian 9, 10, dan 12; flyer di `sumber/flyer/`; rencana implementasi Tahap 1 bagian "Aset dan tampilan" dan "Warna tema".

## Hasil

- [ ] `public/aset/css/tema.css`: warna flyer sebagai variabel CSS dan penyesuaian variabel Bootstrap (biru utama, biru tua, oranye untuk tombol utama, biru muda untuk bidang informasi, kartu putih); satu tema terang
- [ ] Kontras teks pada tombol dan bidang berwarna minimal 4,5 : 1
- [ ] Tata letak publik: kepala dengan nama acara, menu (Beranda, Pendaftaran, Cek Pendaftaran, Unduhan; yang halamannya belum ada tidak ditautkan), isi, footer sederhana; dirancang dari ponsel lebih dulu
- [ ] Tata letak admin: lebar penuh, menu samping di layar lebar, kepala halaman menempel di atas, menu menempel di bawah pada ponsel, tanpa teks footer; menampilkan `admin_nama` dan tombol Keluar (`POST /admin/keluar` dengan CSRF)
- [ ] Menu admin ditulis di satu tempat (dipakai menu samping dan menu bawah); Tahap 1 hanya berisi Dasbor
- [ ] Tata letak polos untuk halaman masuk dan galat: kartu di tengah dengan nama acara
- [ ] Halaman depan sementara: nama acara, tempat dan tanggal, keterangan "Pendaftaran sedang disiapkan"
- [ ] Dasbor sementara dengan keadaan kosong yang ramah
- [ ] Komponen "tanpa gambar": kotak ikon untuk gambar yang belum ada (bukan gambar tiruan)
- [ ] Bilah kemajuan tipis di atas halaman admin yang bisa dinyalakan dari JavaScript (`public/aset/js/`), untuk pemuatan data di tahap berikutnya
- [ ] Halaman galat 404 dan galat umum (mode produksi) berbahasa Indonesia yang ramah dan menyebut langkah berikutnya
- [ ] Bagian `judul`, `isi`, `skrip` dan pemakaian `aset()` tetap seperti kesepakatan; tidak ada yang dimuat dari luar
- [ ] Uji `tests/fitur/TataLetak*Test.php`: halaman depan memuat tata letak publik; dasbor (dengan sesi admin uji) memuat menu samping, menu bawah, `admin_nama`, dan formulir keluar ber-CSRF; halaman tak dikenal menampilkan 404 berbahasa Indonesia; tidak ada alamat `http` luar pada keluaran HTML
- [ ] Tangkapan layar halaman depan, dasbor, dan 404 pada lebar ponsel (390 piksel) dan desktop (1440 piksel) dilampirkan di laporan ke koordinator, tidak masuk git

## Selesai bila

Halaman depan, dasbor, dan 404 tampil rapi pada lebar 390 dan 1440 piksel tanpa gulir mendatar, dan `composer uji` lulus.

## Catatan

- Tidak memakai logo atau gambar dari flyer sebagai aset web; logo diunggah admin di Tahap 3. Flyer hanya acuan warna.
- Tanpa Node, tanpa CDN, tanpa fon daring: memakai fon sistem.
- Menu admin lain ditambahkan bersama halamannya di tahap berikutnya.
