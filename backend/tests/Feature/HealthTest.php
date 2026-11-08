<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_health_membalas_200_bila_basis_data_dan_cache_sehat(): void
    {
        $this->getJson('/health')
            ->assertOk()
            ->assertHeader('X-Request-Id')
            ->assertJsonPath('status', 'healthy')
            ->assertJsonPath('bermasalah', [])
            ->assertJsonPath('checks.database.status', 'ok')
            ->assertJsonPath('checks.cache.status', 'ok')
            ->assertJsonStructure([
                'status', 'bermasalah', 'waktu',
                'checks' => [
                    'database' => ['status', 'driver', 'latency_ms'],
                    'cache' => ['status', 'store', 'latency_ms'],
                ],
            ]);
    }

    public function test_health_membalas_503_dan_mencatat_error_bila_basis_data_tidak_bisa_dihubungi(): void
    {
        Log::spy();
        $this->rusakkanBasisData();

        $this->getJson('/health')
            ->assertStatus(503)
            ->assertJsonPath('status', 'unhealthy')
            ->assertJsonPath('bermasalah', ['database'])
            ->assertJsonPath('checks.database.status', 'gagal')
            ->assertJsonPath('checks.cache.status', 'ok');

        Log::shouldHaveReceived('error')->withArgs(
            fn (string $pesan, array $konteks) => $konteks['event'] === 'health.tidak_sehat'
                && $konteks['bagian_bermasalah'] === ['database']
                && isset($konteks['sebab']['database'])
        )->once();
    }

    public function test_health_membalas_503_bila_cache_bermasalah(): void
    {
        config([
            'cache.default' => 'rusak',
            'cache.stores.rusak' => ['driver' => 'driver-tidak-ada'],
        ]);

        $this->getJson('/health')
            ->assertStatus(503)
            ->assertJsonPath('bermasalah', ['cache'])
            ->assertJsonPath('checks.database.status', 'ok')
            ->assertJsonPath('checks.cache.status', 'gagal');
    }

    private function rusakkanBasisData(): void
    {
        config([
            'database.connections.rusak' => [
                'driver' => 'sqlite',
                'database' => storage_path('tidak-ada/basis-data.sqlite'),
                'prefix' => '',
            ],
            'database.default' => 'rusak',
        ]);
    }
}
