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

# phpstan/phpstan hanya punya dist di api.github.com, yang ditolak proxy sesi.
# Isi cache Composer dari git commit yang sama (lihat catatan di bawah).
REF=$(php -r '$l=json_decode(file_get_contents("composer.lock"),true); foreach($l["packages-dev"] as $p) if($p["name"]==="phpstan/phpstan") echo $p["dist"]["reference"];')
CACHE=$(COMPOSER_ALLOW_SUPERUSER=1 composer config -g cache-files-dir)/phpstan/phpstan
KUNCI=$(php -r 'echo sha1("https://api.github.com/repos/phpstan/phpstan/zipball/".$argv[1]);' "$REF")
if [ -n "$REF" ] && [ ! -f "$CACHE/$KUNCI.zip" ]; then
  SEMENTARA=$(mktemp -d)
  git -C "$SEMENTARA" init -q
  git -C "$SEMENTARA" fetch -q --depth 1 https://github.com/phpstan/phpstan.git "$REF"
  mkdir -p "$CACHE"
  git -C "$SEMENTARA" archive --format=zip --prefix=phpstan/ -o "$CACHE/$KUNCI.zip" FETCH_HEAD
  rm -rf "$SEMENTARA"
fi

COMPOSER_ALLOW_SUPERUSER=1 composer install --no-interaction --no-progress --prefer-dist
```

Basis data dan sandi di atas hanya untuk uji sekali pakai di kontainer, bukan rahasia.

Paket `phpstan/phpstan` (F7-31) di Packagist hanya punya dist `api.github.com/.../zipball/<ref>`, tanpa source git, dan proxy sesi menolak `api.github.com` (403). Langkah di atas mengambil commit yang sama dari `github.com` dengan git, lalu menaruhnya di cache Composer dengan nama yang dicari Composer (sha1 URL dist). Bila `composer.lock` menaikkan versi PHPStan, langkah itu otomatis mengikuti `reference` baru. Di CI GitHub langkah ini tidak perlu.
