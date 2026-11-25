# F11-23 Perintah `mlk deploy` dan `mlk pasang` di server Docker

| | |
|---|---|
| Tahap | 11 — Pelajaran hasil Seri 1 (tugas sisipan setelah penutupan) |
| Dibuat | 25 November 2026 |

## Tujuan

Pembaruan staging dan produksi di VPS cukup satu perintah, `mlk deploy <lingkungan>`, seperti `spmb deploy` aplikasi lain di server itu, dan pemasangan pertama cukup `mlk pasang <lingkungan>` (K189). Sekalian, flyer, banner, dan juknis bawaan ikut masuk image Docker, karena `data:awal` di staging gagal memasangnya.

## Hasil

- [x] `docker/mlk`, pola `spmb deploy`: `deploy staging [ref]` (image `milklife-app:<SHA>` dari `git archive`, cadangan, `git checkout --detach`, `up --wait`, `migrate --all`, `sekolah:impor`, cek kesehatan, kembali otomatis bila gagal, catatan deploy, pangkas image, `pembaruan:periksa`), `deploy produksi` (image staging tanpa membangun ulang, dengan konfirmasi), `rollback`, `status`, `pasang`, penerus `docker compose` dan `spark` per lingkungan, `flock`, dan pencatatan port di `/opt/apps/PORTS`.
- [x] `docker/compose.yaml`: app memakai `image: milklife-app:${TAG:-lokal}`; `docker/.env.example` menyebut `TAG` dan `TAG_SEBELUM`.
- [x] `.env` ditulis ulang di tempat (bukan `sed -i`), supaya container yang memasang `.env` sebagai berkas tetap membaca isi baru.
- [x] `.dockerignore`: `sumber/flyer` dan `sumber/juknis` ikut image. CI memeriksa semua berkas bawaan `data:awal` dan carousel ada di image (`docker/periksa-berkas-bawaan.php`) dan menjalankan `shellcheck docker/mlk`.
- [x] Pesan `data:awal` memisahkan berkas sumber yang tidak ada dari folder unggahan yang tidak bisa ditulisi.
- [x] Panduan produksi revisi 17 (bagian 2, 5, 10), rancangan revisi 45, K189, ATURAN, PETA-KODE.

## Berkas dan uji

`docker/mlk`, `docker/periksa-berkas-bawaan.php`, `.dockerignore`, `.github/workflows/uji.yml`, `app/Commands/DataAwal.php`. `docker/compose.yaml`, `docker/.env.example`. Uji b `DataAwalTest`; job CI "Image Docker". Skrip dicoba dengan `docker` dan `curl` tiruan: pasang, deploy staging, promosi ke produksi, migrate dan `up` gagal (kembali otomatis), rollback, kunci, dan pangkas image.

## Selesai bila

Job CI hijau. Di VPS: `ln -sf /opt/apps/milklife/staging/docker/mlk /usr/local/bin/mlk`, lalu `mlk deploy staging` dan `mlk deploy produksi` berakhir dengan "Selesai".
