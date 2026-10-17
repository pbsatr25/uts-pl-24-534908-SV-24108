<?php

namespace Tests\Feature\Api;

use App\Models\Tugas;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TugasTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /**
     * @param  array<string, mixed>  $atribut
     */
    private function buatTugas(User $pemilik, array $atribut = []): Tugas
    {
        return $pemilik->tugas()->create(array_merge([
            'judul' => 'Tugas contoh',
            'prioritas' => 'sedang',
            'status' => 'belum',
        ], $atribut));
    }

    public function test_semua_endpoint_tugas_tanpa_token_ditolak_401(): void
    {
        $tugas = $this->buatTugas($this->user);

        $this->getJson('/api/tugas')->assertUnauthorized();
        $this->postJson('/api/tugas', [])->assertUnauthorized();
        $this->getJson("/api/tugas/{$tugas->id}")->assertUnauthorized();
        $this->putJson("/api/tugas/{$tugas->id}", [])->assertUnauthorized();
        $this->deleteJson("/api/tugas/{$tugas->id}")->assertUnauthorized();

        $this->assertDatabaseHas('tugas', ['id' => $tugas->id]);
    }

    public function test_index_hanya_menampilkan_tugas_milik_user_dengan_urutan_yang_benar(): void
    {
        $this->buatTugas($this->user, ['judul' => 'Sudah selesai', 'status' => 'selesai', 'tenggat' => today()->subDay()]);
        $this->buatTugas($this->user, ['judul' => 'Tanpa tenggat']);
        $this->buatTugas($this->user, ['judul' => 'Tenggat lusa', 'tenggat' => today()->addDays(2)]);
        $this->buatTugas($this->user, ['judul' => 'Tenggat besok', 'tenggat' => today()->addDay()]);
        $this->buatTugas(User::factory()->create(), ['judul' => 'Milik orang lain']);

        Sanctum::actingAs($this->user);

        $this->getJson('/api/tugas')
            ->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonPath('data.*.judul', [
                'Tenggat besok',
                'Tenggat lusa',
                'Tanpa tenggat',
                'Sudah selesai',
            ]);
    }

    public function test_index_bisa_difilter_berdasarkan_status(): void
    {
        $this->buatTugas($this->user, ['judul' => 'A', 'status' => 'belum']);
        $this->buatTugas($this->user, ['judul' => 'B', 'status' => 'selesai']);

        Sanctum::actingAs($this->user);

        $this->getJson('/api/tugas?status=selesai')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.judul', 'B');

        $this->getJson('/api/tugas?status=ngawur')->assertUnprocessable();
    }

    public function test_store_membuat_tugas_dan_membalas_201(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/tugas', [
            'judul' => 'Belajar Docker Compose',
            'deskripsi' => 'Named volume dan healthcheck',
            'prioritas' => 'tinggi',
            'tenggat' => '2026-10-25',
            'status' => 'belum',
        ])
            ->assertCreated()
            ->assertJsonPath('message', 'Tugas berhasil ditambahkan.')
            ->assertJsonPath('data.judul', 'Belajar Docker Compose')
            ->assertJsonPath('data.tenggat', '2026-10-25');

        $this->assertDatabaseHas('tugas', [
            'judul' => 'Belajar Docker Compose',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_store_dengan_data_tidak_valid_membalas_422(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/tugas', [
            'judul' => '',
            'prioritas' => 'darurat',
            'tenggat' => 'bukan-tanggal',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['judul', 'prioritas', 'tenggat', 'status'])
            ->assertJsonPath('errors.judul.0', 'Judul wajib diisi.');

        $this->assertDatabaseCount('tugas', 0);
    }

    public function test_store_mengabaikan_user_id_dari_request(): void
    {
        $lain = User::factory()->create();
        Sanctum::actingAs($this->user);

        $this->postJson('/api/tugas', [
            'judul' => 'Coba titip user_id',
            'prioritas' => 'rendah',
            'status' => 'belum',
            'user_id' => $lain->id,
        ])->assertCreated();

        $this->assertDatabaseHas('tugas', ['judul' => 'Coba titip user_id', 'user_id' => $this->user->id]);
    }

    public function test_show_menampilkan_satu_tugas(): void
    {
        $tugas = $this->buatTugas($this->user, ['judul' => 'Detail saya']);
        Sanctum::actingAs($this->user);

        $this->getJson("/api/tugas/{$tugas->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $tugas->id)
            ->assertJsonPath('data.judul', 'Detail saya');
    }

    public function test_show_tugas_yang_tidak_ada_atau_milik_user_lain_membalas_404(): void
    {
        $milikLain = $this->buatTugas(User::factory()->create());
        Sanctum::actingAs($this->user);

        $this->getJson("/api/tugas/{$milikLain->id}")
            ->assertNotFound()
            ->assertExactJson(['message' => 'Data tidak ditemukan.']);

        $this->getJson('/api/tugas/999999')->assertNotFound();
    }

    public function test_update_dengan_put_mengubah_tugas(): void
    {
        $tugas = $this->buatTugas($this->user);
        Sanctum::actingAs($this->user);

        $this->putJson("/api/tugas/{$tugas->id}", [
            'judul' => 'Judul baru',
            'deskripsi' => null,
            'prioritas' => 'tinggi',
            'tenggat' => null,
            'status' => 'proses',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Tugas berhasil diperbarui.')
            ->assertJsonPath('data.status', 'proses');

        $this->assertDatabaseHas('tugas', ['id' => $tugas->id, 'judul' => 'Judul baru', 'prioritas' => 'tinggi']);
    }

    public function test_update_dengan_patch_cukup_mengirim_status(): void
    {
        $tugas = $this->buatTugas($this->user, ['judul' => 'Tetap sama']);
        Sanctum::actingAs($this->user);

        $this->patchJson("/api/tugas/{$tugas->id}", ['status' => 'selesai'])
            ->assertOk()
            ->assertJsonPath('data.status', 'selesai')
            ->assertJsonPath('data.judul', 'Tetap sama');
    }

    public function test_update_dengan_data_tidak_valid_membalas_422(): void
    {
        $tugas = $this->buatTugas($this->user);
        Sanctum::actingAs($this->user);

        $this->putJson("/api/tugas/{$tugas->id}", ['judul' => str_repeat('a', 151)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['judul', 'prioritas', 'status']);
    }

    public function test_update_tugas_milik_user_lain_membalas_404(): void
    {
        $milikLain = $this->buatTugas(User::factory()->create(), ['judul' => 'Asli']);
        Sanctum::actingAs($this->user);

        $this->patchJson("/api/tugas/{$milikLain->id}", ['judul' => 'Dibajak'])->assertNotFound();

        $this->assertDatabaseHas('tugas', ['id' => $milikLain->id, 'judul' => 'Asli']);
    }

    public function test_destroy_menghapus_tugas(): void
    {
        $tugas = $this->buatTugas($this->user);
        Sanctum::actingAs($this->user);

        $this->deleteJson("/api/tugas/{$tugas->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Tugas berhasil dihapus.');

        $this->assertDatabaseMissing('tugas', ['id' => $tugas->id]);

        $this->deleteJson("/api/tugas/{$tugas->id}")->assertNotFound();
    }

    public function test_destroy_tugas_milik_user_lain_membalas_404(): void
    {
        $milikLain = $this->buatTugas(User::factory()->create());
        Sanctum::actingAs($this->user);

        $this->deleteJson("/api/tugas/{$milikLain->id}")->assertNotFound();

        $this->assertDatabaseHas('tugas', ['id' => $milikLain->id]);
    }
}
