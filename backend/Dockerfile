# syntax=docker/dockerfile:1

# ===========================================================================
# Image backend Laravel 12 yang dilayani FrankenPHP (Caddy + PHP dalam satu binary),
# menggantikan `php artisan serve` maupun PHP-FPM + Nginx.
#
# Versi dikunci sampai patch (FrankenPHP 1.12.7, PHP 8.2.34, Alpine), bukan latest,
# supaya hasil build hari ini sama dengan hasil build bulan depan.
# Build context: folder backend/  ->  docker build -t uts-backend ./backend
# ===========================================================================
ARG FRANKENPHP_IMAGE=dunglas/frankenphp:1.12.7-php8.2.34-alpine

# ---------------------------------------------------------------------------
# Tahap 1 - vendor
# Memasang dependency Composer. Composer dan cache unduhannya hanya ada
# di tahap ini dan tidak ikut ke image akhir.
# ---------------------------------------------------------------------------
FROM ${FRANKENPHP_IMAGE} AS vendor

COPY --from=composer:2.8.12 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# composer.json dan composer.lock disalin dan dipasang SEBELUM kode aplikasi:
# layer `composer install` tetap diambil dari cache selama dependency tidak berubah,
# walaupun kode aplikasi berubah.
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress \
    --no-scripts --no-autoloader

# Baru setelah itu kode aplikasi disalin (berkas yang tidak perlu disaring .dockerignore).
COPY . .

RUN composer dump-autoload --no-dev --optimize --classmap-authoritative

# ---------------------------------------------------------------------------
# Tahap 2 - runtime
# Hanya FrankenPHP, ekstensi yang dibutuhkan, kode aplikasi, dan vendor/.
# ---------------------------------------------------------------------------
FROM ${FRANKENPHP_IMAGE} AS runtime

# pdo_mysql untuk MySQL di Docker Compose. opcache dan pdo_sqlite sudah bawaan image.
RUN install-php-extensions pdo_mysql

# Konfigurasi produksi PHP bawaan image (display_errors=Off, dsb) + pengaturan proyek.
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php/app.ini $PHP_INI_DIR/conf.d/zz-app.ini

# User biasa tanpa hak root. Caddy butuh menulis ke /config/caddy dan /data/caddy,
# dan Laravel butuh membuat .env di /app. Folder /app bawaan base image dimiliki root,
# sedangkan COPY --chown hanya mengubah pemilik isi yang disalin, bukan folder tujuannya.
RUN addgroup -S -g 1000 laravel \
    && adduser -S -D -H -u 1000 -G laravel -h /app laravel \
    && mkdir -p /app /config/caddy /data/caddy \
    && chown -R laravel:laravel /app /config /data

WORKDIR /app

# --chown langsung saat menyalin, bukan RUN chown -R terpisah,
# karena chown terpisah menggandakan seluruh vendor/ ke layer baru.
COPY --from=vendor --chown=laravel:laravel /app /app

RUN chmod +x docker/entrypoint.sh

# Hanya nilai yang TIDAK rahasia. APP_KEY, password basis data, dan sejenisnya
# diberikan saat container dijalankan (docker run -e / docker compose + .env),
# karena isi Dockerfile ikut tersimpan di metadata image dan bisa dibaca siapa pun.
#
# CADDY_SERVER_EXTRA_DIRECTIVES="log" menyalakan access log Caddy (mati secara bawaan),
# sehingga setiap request API tercatat di `docker logs` sebagai bukti dilayani FrankenPHP.
# Header Authorization otomatis disamarkan (REDACTED) oleh Caddy.
ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    SERVER_NAME=:8000 \
    SERVER_ROOT=/app/public \
    CADDY_GLOBAL_OPTIONS="admin off" \
    CADDY_SERVER_EXTRA_DIRECTIVES="log"

USER laravel

EXPOSE 8000

# Docker memanggil endpoint /up (health route bawaan Laravel) secara berkala.
# Tiga kali gagal berturut-turut = status unhealthy.
HEALTHCHECK --interval=10s --timeout=3s --start-period=30s --retries=3 \
    CMD wget -q --spider http://127.0.0.1:8000/up || exit 1

ENTRYPOINT ["/app/docker/entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
