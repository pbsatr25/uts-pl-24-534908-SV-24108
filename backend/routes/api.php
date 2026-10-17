<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TugasController;
use Illuminate\Support\Facades\Route;

// Satu-satunya endpoint publik. Dibatasi 10 percobaan per menit untuk menahan tebak password.
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:10,1')
    ->name('api.login');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me'])->name('api.me');
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');

    // Parameter dinamai {tugas}; tanpa ini Laravel menjadikannya {tuga}.
    Route::apiResource('tugas', TugasController::class)
        ->parameters(['tugas' => 'tugas']);
});
