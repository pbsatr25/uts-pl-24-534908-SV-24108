<?php

namespace App\Http\Controllers;

use App\Support\GangguanDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * GET /health: benar-benar memeriksa basis data dan cache, bukan sekadar membalas teks.
 *
 * - database: membuka koneksi lalu menjalankan SELECT 1.
 * - cache: menulis sebuah nilai acak, membacanya kembali, lalu menghapusnya.
 *
 * 200 bila semua bagian sehat, 503 bila ada yang bermasalah beserta nama bagiannya.
 * Dipakai healthcheck Docker (docker-compose.yml) dan bisa dibuka langsung di browser.
 */
class HealthController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $checks = [
            'database' => $this->periksaDatabase(),
            'cache' => $this->periksaCache(),
        ];

        $bermasalah = array_keys(array_filter($checks, fn (array $hasil) => $hasil['status'] !== 'ok'));
        $sehat = $bermasalah === [];

        if (! $sehat) {
            // Siapa: pemanggil /health (healthcheck Docker atau operator).
            // Apa: memeriksa kesehatan layanan. Sebab: pesan singkat per bagian yang gagal.
            Log::error('Health check gagal: layanan tidak sehat', [
                'event' => 'health.tidak_sehat',
                'pemanggil' => Str::limit((string) $request->userAgent(), 40, ''),
                'aksi' => 'memeriksa basis data dan cache',
                'bagian_bermasalah' => $bermasalah,
                'sebab' => array_map(
                    fn (string $bagian) => $checks[$bagian]['sebab'],
                    array_combine($bermasalah, $bermasalah),
                ),
            ]);
        }

        return response()->json([
            'status' => $sehat ? 'healthy' : 'unhealthy',
            'bermasalah' => $bermasalah,
            'checks' => $checks,
            'waktu' => now()->toIso8601String(),
        ], $sehat ? 200 : 503)->header('Cache-Control', 'no-store');
    }

    /**
     * @return array<string, mixed>
     */
    private function periksaDatabase(): array
    {
        $mulai = hrtime(true);
        $koneksi = DB::connection();

        try {
            $koneksi->select('select 1 as ok');

            return [
                'status' => 'ok',
                'driver' => $koneksi->getDriverName(),
                'latency_ms' => $this->durasi($mulai),
            ];
        } catch (Throwable $e) {
            $gangguan = GangguanDatabase::rangkum($e);

            return [
                'status' => 'gagal',
                'driver' => $koneksi->getDriverName(),
                'sebab' => $gangguan['sebab'],
                'sqlstate' => $gangguan['sqlstate'],
                'latency_ms' => $this->durasi($mulai),
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function periksaCache(): array
    {
        $mulai = hrtime(true);
        $namaStore = (string) config('cache.default');

        try {
            $store = Cache::store();
            $kunci = 'health:'.Str::uuid();
            $nilai = Str::random(16);

            $store->put($kunci, $nilai, 10);
            $terbaca = $store->get($kunci);
            $store->forget($kunci);

            if ($terbaca !== $nilai) {
                return [
                    'status' => 'gagal',
                    'store' => $namaStore,
                    'sebab' => 'Nilai yang ditulis ke cache tidak terbaca kembali.',
                    'latency_ms' => $this->durasi($mulai),
                ];
            }

            return [
                'status' => 'ok',
                'store' => $namaStore,
                'latency_ms' => $this->durasi($mulai),
            ];
        } catch (Throwable $e) {
            return [
                'status' => 'gagal',
                'store' => $namaStore,
                'sebab' => Str::limit(strtok($e->getMessage(), "\n") ?: class_basename($e), 200),
                'latency_ms' => $this->durasi($mulai),
            ];
        }
    }

    private function durasi(int $mulai): float
    {
        return round((hrtime(true) - $mulai) / 1e6, 2);
    }
}
