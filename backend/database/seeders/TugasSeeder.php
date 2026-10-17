<?php

namespace Database\Seeders;

use App\Models\Tugas;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Data contoh hanya dibuat saat tabel masih kosong, supaya data yang sudah
 * dibuat pengguna tidak tertimpa setiap container dinyalakan ulang.
 */
class TugasSeeder extends Seeder
{
    public function run(): void
    {
        if (Tugas::query()->exists()) {
            return;
        }

        $admin = User::where('email', 'admin@kepl.test')->firstOrFail();
        $budi = User::where('email', 'budi@kepl.test')->firstOrFail();

        $admin->tugas()->createMany([
            [
                'judul' => 'Kerjakan laporan UTS KEPL',
                'deskripsi' => 'Langkah pengerjaan, screenshot, dan tautan repository.',
                'prioritas' => 'tinggi',
                'tenggat' => today()->addDays(7),
                'status' => 'proses',
            ],
            [
                'judul' => 'Export Postman Collection',
                'deskripsi' => 'Simpan di folder postman/ pada repository.',
                'prioritas' => 'sedang',
                'tenggat' => today()->addDays(2),
                'status' => 'belum',
            ],
            [
                'judul' => 'Pasang branch protection main dan dev',
                'deskripsi' => null,
                'prioritas' => 'tinggi',
                'tenggat' => today()->subDay(),
                'status' => 'selesai',
            ],
            [
                'judul' => 'Baca dokumentasi FrankenPHP',
                'deskripsi' => null,
                'prioritas' => 'rendah',
                'tenggat' => null,
                'status' => 'belum',
            ],
        ]);

        $budi->tugas()->create([
            'judul' => 'Tugas milik Budi (tidak terlihat oleh admin)',
            'prioritas' => 'sedang',
            'status' => 'belum',
        ]);
    }
}
