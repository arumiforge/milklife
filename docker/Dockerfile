# Image aplikasi MilkLife untuk staging dan produksi (K182).
#
# Dibangun di server dari kode hasil `git pull`, lewat docker/compose.yaml:
#   docker compose -f docker/compose.yaml build app
#
# Isi: FrankenPHP 1.14 dengan PHP 8.5.13 (mode biasa, bukan worker mode),
# ekstensi PHP yang dipakai aplikasi, klien MySQL 8.4 untuk `cadangan:buat`,
# pustaka Composer tanpa alat uji, dan CSS/JS kecil hasil `aset:minify`.
# CSS Tailwind sudah dibangun dengan Node 24 dan di-commit (K141), jadi
# image tidak memerlukan Node.
#
# `.env`, unggahan, sesi, log, dan cadangan TIDAK masuk image: semuanya
# dipasang dari host atau volume saat container berjalan (compose.yaml).

ARG FRANKENPHP_IMAGE=dunglas/frankenphp:1.14.0-php8.5.13-bookworm

# ---------------------------------------------------------------------------
# Dasar: PHP 8.5 + ekstensi + klien MySQL 8.4
# ---------------------------------------------------------------------------
FROM ${FRANKENPHP_IMAGE} AS dasar

# mbstring, fileinfo, dan OPcache sudah ada di image FrankenPHP.
RUN install-php-extensions intl mysqli gd zip exif

# `cadangan:buat` menjalankan mysqldump. Klien bawaan Debian adalah milik
# MariaDB (mariadb-dump), jadi klien MySQL 8.4 LTS diambil dari repo APT
# resmi MySQL. Kunci RPM-GPG-KEY-mysql-2025 berlaku sampai 23 Oktober 2027.
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends ca-certificates curl gnupg; \
    curl -fsSL https://repo.mysql.com/RPM-GPG-KEY-mysql-2025 \
        | gpg --dearmor -o /usr/share/keyrings/mysql.gpg; \
    echo 'deb [signed-by=/usr/share/keyrings/mysql.gpg] https://repo.mysql.com/apt/debian bookworm mysql-8.4-lts' \
        > /etc/apt/sources.list.d/mysql.list; \
    apt-get update; \
    apt-get install -y --no-install-recommends mysql-community-client; \
    apt-get purge -y --auto-remove gnupg; \
    rm -rf /var/lib/apt/lists/*; \
    mysqldump --version

COPY docker/php.ini "$PHP_INI_DIR/conf.d/zz-milklife.ini"

# ---------------------------------------------------------------------------
# Pustaka Composer (tanpa alat uji)
# ---------------------------------------------------------------------------
FROM dasar AS pustaka

# Composer 2 dipasang oleh skrip install-php-extensions bawaan image.
RUN install-php-extensions @composer
WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist \
        --no-scripts --no-autoloader

COPY . .
RUN composer dump-autoload --no-dev --optimize \
    && php spark aset:minify \
    && mkdir -p writable/unggahan public/unggahan

# ---------------------------------------------------------------------------
# Image akhir
# ---------------------------------------------------------------------------
FROM dasar AS aplikasi

WORKDIR /app
COPY --from=pustaka --chown=root:root /app /app
COPY docker/Caddyfile /etc/frankenphp/Caddyfile

# Kode hanya bisa dibaca; yang bisa ditulis hanya writable/, public/unggahan/,
# dan folder cadangan. Server web berjalan sebagai www-data, bukan root.
RUN mkdir -p /cadangan \
    && chown -R www-data:www-data /app/writable /app/public/unggahan /cadangan /config/caddy /data/caddy

USER www-data

# Caddy hanya mendengar HTTP di port 8080 di jaringan internal compose;
# HTTPS dipasang Cloudflare (K31, K182).
EXPOSE 8080

# Pemeriksaan bawaan image FrankenPHP memakai API admin Caddy yang di sini
# dimatikan; cukup pastikan server web menjawab.
HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD curl -fsS -o /dev/null http://127.0.0.1:8080/favicon.ico || exit 1

CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
