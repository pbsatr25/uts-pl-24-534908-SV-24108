<?php

namespace Tests\Feature;

use App\Models\Tugas;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_aman_dijalankan_berulang_tanpa_menduplikasi_data(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(2, User::count());
        $this->assertSame(5, Tugas::count());
        $this->assertTrue(User::where('email', 'admin@kepl.test')->exists());
    }
}
