<?php

use App\Http\Controllers\HealthController;
use App\Http\Middleware\TambahKonteksLog;
use App\Support\GangguanDatabase;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Identitas pelaku untuk log: hanya id user, tanpa email/nama (data pribadi).
 * Auth::hasUser() tidak memicu query, jadi aman dipanggil walaupun basis data sedang mati.
 */
$pelaku = fn (): ?int => Auth::hasUser() ? (int) Auth::id() : null;

/**
 * Nama aksi yang sedang dikerjakan: nama route (mis. "tugas.store"), atau nama perintah
 * Artisan bila terjadi di luar request HTTP (mis. "artisan migrate" di entrypoint).
 */
$aksi = function (Request $request): string {
    if ($nama = $request->route()?->getName()) {
        return $nama;
    }

    if (app()->runningInConsole()) {
        return 'artisan '.($_SERVER['argv'][1] ?? '');
    }

    return $request->method().' /'.ltrim($request->path(), '/');
};

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Health check yang benar-benar memeriksa basis data dan cache. Didaftarkan
            // di luar grup "web" dan "api", sehingga tidak menyentuh session/token yang
            // juga butuh basis data: /health tetap bisa membalas 503 saat MySQL mati.
            Route::get('/health', HealthController::class)->name('health');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // request_id, metode, dan path ikut tercatat di setiap baris log (lihat middleware).
        $middleware->append(TambahKonteksLog::class);

        // Backend ini tidak punya route "login" berbasis halaman. Tamu di /api/* tidak
        // di-redirect, sehingga balasannya 401 JSON, bukan 500 "Route [login] not defined".
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('api/*') ? null : '/');
    })
    ->withExceptions(function (Exceptions $exceptions) use ($pelaku, $aksi): void {
        // Semua error di /api/* dibalas JSON, walaupun klien lupa mengirim Accept: application/json.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson()
        );

        // LOG ERROR - gagal menghubungi / memakai basis data.
        // Laporan bawaan Laravel memuat SQL lengkap beserta nilai binding (mis. email),
        // jadi diganti log terstruktur yang hanya berisi kode dan sebab singkat dari driver.
        // `return false` menghentikan laporan bawaan agar SQL tidak ikut tercatat.
        $exceptions->report(function (PDOException $e) use ($pelaku, $aksi) {
            $gangguan = GangguanDatabase::rangkum($e);

            Log::error(
                $gangguan['koneksi_gagal'] ? 'Gagal menghubungi basis data' : 'Query basis data gagal',
                [
                    'event' => $gangguan['koneksi_gagal'] ? 'db.gagal_terhubung' : 'db.query_gagal',
                    'user_id' => $pelaku(),
                    'aksi' => $aksi(request()),
                    'sebab' => $gangguan['sebab'],
                    'sqlstate' => $gangguan['sqlstate'],
                    'kode_driver' => $gangguan['kode_driver'],
                ]
            );

            return false;
        });

        // LOG WARNING - validasi gagal. Yang dicatat hanya NAMA field dan aturan yang
        // dilanggar (mis. judul: Required), tidak pernah nilai yang dikirim klien,
        // karena nilainya bisa berupa password, email, atau isi tugas pribadi.
        $exceptions->render(function (ValidationException $e, Request $request) use ($pelaku, $aksi) {
            if ($request->is('api/*')) {
                Log::warning('Validasi input gagal', [
                    'event' => 'validasi.gagal',
                    'user_id' => $pelaku(),
                    'aksi' => $aksi($request),
                    'sebab' => 'input tidak memenuhi aturan validasi',
                    'field_gagal' => array_map(
                        fn (array $aturan) => array_keys($aturan),
                        $e->validator->failed(),
                    ),
                ]);
            }

            return null; // lanjut ke balasan 422 bawaan Laravel
        });

        // Basis data tidak bisa dihubungi: 503 dengan pesan jelas, bukan 500 tanpa keterangan.
        $exceptions->render(function (PDOException $e, Request $request) {
            if ($request->is('api/*') && GangguanDatabase::rangkum($e)['koneksi_gagal']) {
                return response()->json([
                    'message' => 'Basis data sedang tidak dapat dihubungi. Coba lagi beberapa saat lagi.',
                ], 503);
            }
        });

        // Data tidak ada, milik user lain, atau endpoint salah: pesan 404 singkat tanpa detail internal.
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Data tidak ditemukan.'], 404);
            }
        });
    })->create();
