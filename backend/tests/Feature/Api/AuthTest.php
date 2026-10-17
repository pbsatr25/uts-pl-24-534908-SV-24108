<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_berhasil_mengembalikan_token(): void
    {
        $user = User::factory()->create(['email' => 'admin@kepl.test']);

        $this->postJson('/api/login', [
            'email' => 'admin@kepl.test',
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonStructure(['message', 'token_type', 'token', 'user' => ['id', 'name', 'email']])
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.email', 'admin@kepl.test');

        $this->assertSame(1, $user->tokens()->count());
    }

    public function test_login_dengan_password_salah_ditolak_401(): void
    {
        User::factory()->create(['email' => 'admin@kepl.test']);

        $this->postJson('/api/login', [
            'email' => 'admin@kepl.test',
            'password' => 'salah-total',
        ])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Email atau password salah.');
    }

    public function test_login_tanpa_input_gagal_validasi_422(): void
    {
        $this->postJson('/api/login', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_endpoint_terlindungi_tanpa_token_ditolak_401(): void
    {
        $this->getJson('/api/me')
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Unauthenticated.']);

        // Tanpa header Accept: application/json pun tetap 401 JSON, bukan redirect atau 500.
        $this->get('/api/tugas')
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Unauthenticated.']);
    }

    public function test_me_dengan_token_mengembalikan_data_user(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonMissingPath('data.password');
    }

    public function test_logout_mencabut_token_sehingga_tidak_bisa_dipakai_lagi(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logout berhasil, token sudah dicabut.');

        $this->assertSame(0, $user->tokens()->count());

        // Guard menyimpan user dari request sebelumnya; dilupakan agar token dicek ulang.
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/me')->assertUnauthorized();
    }
}
