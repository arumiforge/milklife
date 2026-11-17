# Setup Sesi Cloud

Kontainer sesi agen di cloud selalu baru, jadi pustaka dan MySQL dipasang sekali di awal setiap sesi oleh **Setup script** di setelan lingkungan cloud, bukan oleh agen di tengah kerja (K62).

- Setelah persiapan, agen langsung menjalankan `composer uji`. `composer install` tidak dijalankan lagi selama `vendor/` ada dan `composer.lock` tidak berubah.
- Bila MySQL mati di awal sesi, cukup `service mysql start`.
- Composer berjalan sebagai root, jadi variabel lingkungan `COMPOSER_ALLOW_SUPERUSER=1` diisi di setelan lingkungan (Environment variables). Tanpa itu `composer uji` menolak jalan.
- Bila sesi mulai tanpa persiapan, jalankan isi skrip di bawah ini sekali dari folder di atas repo.

Isi Setup script yang dipakai:

```bash
#!/bin/bash
set -e

# 1. MySQL 8 untuk uji
if ! command -v mysqld >/dev/null 2>&1; then
  apt-get update -qq
  DEBIAN_FRONTEND=noninteractive apt-get install -y -qq mysql-server
fi
mkdir -p /var/run/mysqld && chown mysql:mysql /var/run/mysqld
service mysql start
for i in $(seq 1 30); do mysqladmin ping --silent && break; sleep 1; done

# root tanpa sandi lewat 127.0.0.1, sesuai .env.example
mysql -u root <<'SQL'
ALTER USER 'root'@'localhost' IDENTIFIED WITH caching_sha2_password BY '';
CREATE USER IF NOT EXISTS 'root'@'127.0.0.1' IDENTIFIED WITH caching_sha2_password BY '';
GRANT ALL PRIVILEGES ON *.* TO 'root'@'127.0.0.1' WITH GRANT OPTION;
CREATE DATABASE IF NOT EXISTS milklife_uji CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
SQL

# 2. Pustaka PHP dan .env
cd milklife
[ -f .env ] || cp .env.example .env
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-interaction --no-progress --prefer-dist
```

Basis data dan sandi di atas hanya untuk uji sekali pakai di kontainer, bukan rahasia.
