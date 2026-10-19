# uts-pl-24-534908-SV-24108

[![CI/CD](https://github.com/pbsatr25/uts-pl-24-534908-SV-24108/actions/workflows/ci.yml/badge.svg)](https://github.com/pbsatr25/uts-pl-24-534908-SV-24108/actions/workflows/ci.yml)

Ujian Tengah Semester mata kuliah **Konstruksi dan Evolusi Perangkat Lunak** (SVPL261501),
Sekolah Vokasi Universitas Gadjah Mada.

| Keterangan | Isi |
| --- | --- |
| Nama | Prihastomo Budi Satrio |
| NIM | 24/534908/SV/24108 |
| Kelas | BB |
| Aplikasi | To-Do List (pengelola daftar tugas) |

## Struktur repository

| Folder | Isi |
| --- | --- |
| [`backend/`](backend/) | RESTful API Laravel 12 (PHP 8.2) + Laravel Sanctum |
| [`frontend/`](frontend/) | Aplikasi Vue 3 + Vue Router |
| [`postman/`](postman/) | Postman Collection hasil export |
| [`.github/workflows/ci.yml`](.github/workflows/ci.yml) | Workflow GitHub Actions: job `Backend Test` dan `Frontend Test` |

## Endpoint API

Semua request dan response berformat JSON. Selain `POST /api/login`, setiap endpoint wajib
mengirim header `Authorization: Bearer <token>`; tanpa token atau dengan token yang sudah dicabut
dibalas **401** `{"message":"Unauthenticated."}`.

| Metode | Endpoint | Keterangan | Status |
| --- | --- | --- | --- |
| POST | `/api/login` | Tukar email + password dengan token (maks. 10 percobaan/menit) | 200, 401, 422 |
| GET | `/api/me` | Data user pemilik token | 200 |
| POST | `/api/logout` | Cabut token yang sedang dipakai | 200 |
| GET | `/api/tugas` | Daftar tugas milik user (opsional `?status=belum\|proses\|selesai`) | 200 |
| POST | `/api/tugas` | Tambah tugas | **201**, 422 |
| GET | `/api/tugas/{id}` | Detail tugas | 200, 404 |
| PUT/PATCH | `/api/tugas/{id}` | Ubah tugas (PATCH boleh sebagian field) | 200, 404, 422 |
| DELETE | `/api/tugas/{id}` | Hapus tugas | 200, 404 |

Field tugas: `judul` (wajib, maks. 150), `deskripsi` (opsional, maks. 1000),
`prioritas` (`rendah|sedang|tinggi`), `tenggat` (tanggal `YYYY-MM-DD`, opsional),
`status` (`belum|proses|selesai`). Tugas milik user lain dibalas **404**, sama seperti
tugas yang tidak ada.

Akun demo dari seeder: `admin@kepl.test` / `password` (dan `budi@kepl.test` / `password`).

Postman: import [`postman/UTS-PL-To-Do-API.postman_collection.json`](postman/UTS-PL-To-Do-API.postman_collection.json),
sesuaikan variabel `base_url`, lalu **Run collection**. Token dari request Login otomatis
disimpan dan dipakai request berikutnya.

## Menjalankan di lokal

Backend (PHP 8.2 dan Composer 2):

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve          # http://127.0.0.1:8000/api
php artisan test
```

Frontend (Node.js 24):

```bash
cd frontend
npm ci
npm run dev                # http://localhost:5173 (backend harus jalan di port 8000)
npm run lint
npm run test:unit -- --run
npm run build
```

Halaman frontend:

| Path | Isi |
| --- | --- |
| `/login` | Form login; token disimpan di `localStorage` |
| `/tugas` | Daftar tugas, filter status, tandai selesai, hapus |
| `/tugas/tambah`, `/tugas/:id/ubah` | Form tambah dan ubah tugas, error 422 tampil per field |

Semua request lewat satu instance Axios ([`frontend/src/lib/http.js`](frontend/src/lib/http.js)) yang
menambahkan header `Authorization: Bearer <token>` di setiap request. Alamat API diambil dari
environment variable `VITE_API_URL` (contoh di [`frontend/.env.example`](frontend/.env.example)),
bukan ditulis di komponen. Nilainya `/api`: saat `npm run dev` diteruskan proxy Vite ke
`http://127.0.0.1:8000`, dan di Docker diteruskan Nginx ke service `backend`.

## Image Docker

| Image | Dockerfile | Isi |
| --- | --- | --- |
| Backend | [`backend/Dockerfile`](backend/Dockerfile) | Multi-stage `vendor` → `runtime`, base `dunglas/frankenphp:1.12.7-php8.2.34-alpine`. Laravel dilayani **FrankenPHP** di port 8000, berjalan sebagai user `laravel` (bukan root) |
| Frontend | [`frontend/Dockerfile`](frontend/Dockerfile) | Multi-stage: `node:24.21.0-alpine3.24` menjalankan `npm ci` + `npm run build`, lalu hasil `dist/` disajikan `nginxinc/nginx-unprivileged:1.31.6-alpine3.24` di port 8080 (tanpa `node_modules`) |

`composer.json` + `composer.lock` (dan `package.json` + `package-lock.json`) disalin dan dipasang
sebelum kode aplikasi, sehingga layer dependency tetap diambil dari cache selama dependency tidak
berubah. Berkas yang tidak perlu masuk build context disaring oleh `.dockerignore` di masing-masing
folder. Tidak ada nilai rahasia di Dockerfile: `APP_KEY` dan kredensial basis data diberikan saat
container dijalankan.

```bash
docker build -t uts-backend ./backend
docker build -t uts-frontend --build-arg VITE_API_URL=/api ./frontend
```

Access log Caddy dinyalakan (`CADDY_SERVER_EXTRA_DIRECTIVES="log"`), sehingga setiap request API
tercatat di `docker logs` sebagai bukti dilayani FrankenPHP; header `Authorization` otomatis
disamarkan menjadi `REDACTED`.

## Menjalankan seluruh sistem dengan Docker Compose

```
Browser ──:8080──► frontend (Nginx) ──/api──► backend:8000 (FrankenPHP) ──► db:3306 (MySQL)
```

| Service | Image | Port di laptop | Keterangan |
| --- | --- | --- | --- |
| `db` | `mysql:8.4.11` | tidak dibuka | Data di named volume `db-data` |
| `backend` | build `backend/` | 8000 | Terhubung ke host `db` (nama service) |
| `frontend` | build `frontend/` | 8080 | Meneruskan `/api` ke `http://backend:8000` (nama service) |

```bash
cp .env.example .env        # lalu isi APP_KEY, DB_PASSWORD, DB_ROOT_PASSWORD
docker compose up -d --build
docker compose ps           # ketiga service berstatus (healthy)
sh scripts/smoke-test.sh    # uji login dan CRUD lewat http://localhost:8080
```

Buka `http://localhost:8080` dan login dengan akun demo. Kredensial basis data dibaca dari `.env`
(tidak di-commit; contohnya di [`.env.example`](.env.example)). `docker compose down` menghapus
container tetapi data tetap tersimpan di volume; `docker compose down -v` ikut menghapus volume.

## Alur branch

```
main  <- hanya menerima Pull Request dari dev
 └── dev  <- menerima Pull Request dari feature/*
      └── feature/*  <- tempat pengembangan fitur
```

Branch `main` dan `dev` diproteksi: perubahan wajib lewat Pull Request dan check
`Backend Test` serta `Frontend Test` harus lulus sebelum merge.
