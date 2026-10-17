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

## Alur branch

```
main  <- hanya menerima Pull Request dari dev
 └── dev  <- menerima Pull Request dari feature/*
      └── feature/*  <- tempat pengembangan fitur
```

Branch `main` dan `dev` diproteksi: perubahan wajib lewat Pull Request dan check
`Backend Test` serta `Frontend Test` harus lulus sebelum merge.
