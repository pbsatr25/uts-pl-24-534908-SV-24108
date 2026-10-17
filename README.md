# uts-pl-24-534908-SV-24108

[![CI/CD](https://github.com/budisatrio32/uts-pl-24-534908-SV-24108/actions/workflows/ci.yml/badge.svg)](https://github.com/budisatrio32/uts-pl-24-534908-SV-24108/actions/workflows/ci.yml)

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
| [`backend/`](backend/) | RESTful API Laravel 12 (PHP 8.2) |
| [`frontend/`](frontend/) | Aplikasi Vue 3 + Vue Router |
| [`.github/workflows/ci.yml`](.github/workflows/ci.yml) | Workflow GitHub Actions: job `Backend Test` dan `Frontend Test` |

## Menjalankan di lokal

Backend (PHP 8.2 dan Composer 2):

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan serve          # http://127.0.0.1:8000
php artisan test
```

Frontend (Node.js 24):

```bash
cd frontend
npm ci
npm run dev                # http://localhost:5173
npm run lint
npm run test:unit -- --run
npm run build
```

## Alur branch

```
main  <- hanya menerima Pull Request dari dev
 └── dev  <- menerima Pull Request dari feature/*
      └── feature/*  <- tempat pengembangan fitur
```

Branch `main` dan `dev` diproteksi: perubahan wajib lewat Pull Request dan check
`Backend Test` serta `Frontend Test` harus lulus sebelum merge.
