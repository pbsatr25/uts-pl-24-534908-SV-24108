<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Memberi setiap request sebuah request_id beserta metode dan path-nya. Nilai ini ikut
 * tercatat otomatis di setiap baris log selama request berjalan (bagian "extra" JSON),
 * sehingga beberapa baris log dari satu request bisa dihubungkan satu sama lain.
 *
 * Yang dicatat hanya metode dan path (tanpa query string), bukan header, body, atau IP.
 */
class TambahKonteksLog
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->headers->get('X-Request-Id');

        if (! is_string($requestId) || ! preg_match('/^[A-Za-z0-9-]{8,64}$/', $requestId)) {
            $requestId = (string) Str::uuid();
        }

        Context::add([
            'request_id' => $requestId,
            'metode' => $request->method(),
            'path' => '/'.ltrim($request->path(), '/'),
        ]);

        $response = $next($request);
        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}
