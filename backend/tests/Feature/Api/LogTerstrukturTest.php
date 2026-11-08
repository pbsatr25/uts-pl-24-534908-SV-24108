<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Memastikan alur penting tercatat sebagai log terstruktur (siapa, aksi, sebab) dengan
 * tingkat yang sesuai, dan tidak pernah memuat password, token, atau data pribadi.
 */
class LogTerstrukturTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Transaksi test selalu di koneksi "sqlite", walaupun satu test mengganti koneksi
     * default ke "rusak". Tanpa ini rollback salah sasaran dan test berikutnya gagal.
     *
     * @var array<int, string>
     */
    protected $connectionsToTransact = ['sqlite'];

    protected function setUp(): void
    {
        parent::setUp();

        Log::spy();
    }

    public function test_menyimpan_tugas_dicatat_info_dengan_konteks_tanpa_isi_tugas(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $id = $this->postJson('/api/tugas', [
            'judul' => 'Rahasia pribadi',
            'deskripsi' => 'Nomor rekening 1234',
            'prioritas' => 'tinggi',
            'status' => 'belum',
        ])->assertCreated()->json('data.id');

        Log::shouldHaveReceived('info')->withArgs(
            fn (string $pesan, array $konteks) => $konteks['event'] === 'tugas.dibuat'
                && $konteks['user_id'] === $user->id
                && $konteks['tugas_id'] === $id
                && $konteks['aksi'] === 'menambah tugas'
                && ! str_contains(json_encode($konteks), 'Rahasia pribadi')
                && ! str_contains(json_encode($konteks), '1234')
        )->once();
    }

    public function test_validasi_gagal_dicatat_warning_berisi_nama_field_bukan_nilainya(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/tugas', ['judul' => '', 'prioritas' => 'darurat-rahasia'])
            ->assertUnprocessable();

        Log::shouldHaveReceived('warning')->withArgs(
            fn (string $pesan, array $konteks) => $konteks['event'] === 'validasi.gagal'
                && $konteks['user_id'] === $user->id
                && $konteks['aksi'] === 'tugas.store'
                && $konteks['field_gagal']['prioritas'] === ['In']
                && array_key_exists('judul', $konteks['field_gagal'])
                && ! str_contains(json_encode($konteks), 'darurat-rahasia')
        )->once();
    }

    public function test_login_gagal_dicatat_warning_tanpa_email_dan_password(): void
    {
        $user = User::factory()->create(['email' => 'budi@contoh.test']);

        $this->postJson('/api/login', ['email' => 'budi@contoh.test', 'password' => 'salah-sekali'])
            ->assertUnauthorized();

        Log::shouldHaveReceived('warning')->withArgs(
            fn (string $pesan, array $konteks) => $konteks['event'] === 'auth.login_gagal'
                && $konteks['user_id'] === $user->id
                && $konteks['sebab'] === 'password salah'
                && ! str_contains(json_encode($konteks), 'budi@contoh.test')
                && ! str_contains(json_encode($konteks), 'salah-sekali')
        )->once();
    }

    public function test_login_berhasil_tidak_mencatat_token(): void
    {
        $user = User::factory()->create(['password' => 'password']);

        $token = $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])
            ->assertOk()
            ->json('token');

        Log::shouldHaveReceived('info')->withArgs(
            fn (string $pesan, array $konteks) => $konteks['event'] === 'auth.login_berhasil'
                && ! str_contains(json_encode($konteks), $token)
                && ! str_contains(json_encode($konteks), $user->email)
        )->once();
    }

    public function test_basis_data_tidak_bisa_dihubungi_dicatat_error_dan_dibalas_503(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Koneksi default diarahkan ke basis data yang tidak ada. Koneksi test asli
        // dibiarkan utuh agar RefreshDatabase tetap bisa me-rollback transaksinya.
        config([
            'database.connections.rusak' => [
                'driver' => 'sqlite',
                'database' => storage_path('tidak-ada/basis-data.sqlite'),
                'prefix' => '',
            ],
            'database.default' => 'rusak',
        ]);
        $user->setConnection('rusak');

        $this->getJson('/api/tugas')
            ->assertStatus(503)
            ->assertJsonPath('message', 'Basis data sedang tidak dapat dihubungi. Coba lagi beberapa saat lagi.');

        Log::shouldHaveReceived('error')->withArgs(
            fn (string $pesan, array $konteks) => $konteks['event'] === 'db.gagal_terhubung'
                && $konteks['user_id'] === $user->id
                && $konteks['aksi'] === 'tugas.index'
                && $konteks['sebab'] !== ''
                && ! str_contains(json_encode($konteks), 'select')
        )->once();
    }
}
