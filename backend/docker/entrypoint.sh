#!/bin/sh
set -e   # berhenti bila ada perintah gagal

cd /app

# 1 - Berkas .env. Berkas .env milik laptop tidak ikut ke image (.dockerignore).
#     Nilai dari environment container (docker run -e / docker compose) selalu
#     menang atas isi .env, karena Laravel tidak menimpa env yang sudah ada.
if [ ! -f .env ]; then
    cp .env.example .env
fi

# 2 - APP_KEY. Di Docker Compose diberikan lewat .env proyek; bila tidak ada
#     (misalnya docker run tanpa -e APP_KEY), dibuat otomatis di dalam container.
if [ -z "${APP_KEY}" ] && ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --force
fi

# 3 - Basis data. SQLite cukup dibuat berkasnya; MySQL ditunggu sampai siap
#     menerima koneksi (maksimal 30 x 2 detik).
if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    touch database/database.sqlite
fi

percobaan=1
until php artisan migrate --force; do
    if [ "${percobaan}" -ge 30 ]; then
        echo "Basis data tidak dapat dihubungi, container dihentikan." >&2
        exit 1
    fi
    echo "Menunggu basis data siap (percobaan ${percobaan})..."
    percobaan=$((percobaan + 1))
    sleep 2
done

# 4 - Data awal (akun demo + tugas contoh). Seeder aman dijalankan berulang:
#     user memakai updateOrCreate, tugas contoh hanya dibuat saat tabel kosong.
php artisan db:seed --force

# 5 - Cache konfigurasi, route, dan view dibuat saat start (bukan saat build),
#     karena nilai environment baru diketahui saat container dijalankan.
php artisan optimize

# 6 - Jalankan perintah utama container (lihat CMD pada Dockerfile).
exec "$@"
