<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TugasRequest;
use App\Http\Resources\TugasResource;
use App\Models\Tugas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Semua query lewat $request->user()->tugas(), sehingga tugas milik user lain
 * dibalas 404 seolah-olah tidak ada (keberadaannya tidak bocor).
 */
class TugasController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate(['status' => ['nullable', Rule::in(Tugas::STATUS)]]);

        $tugas = $request->user()->tugas()
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->urutanDaftar()
            ->get();

        return TugasResource::collection($tugas);
    }

    public function store(TugasRequest $request): JsonResponse
    {
        $tugas = $request->user()->tugas()->create($request->validated());

        // LOG INFO - data baru tersimpan. Judul dan deskripsi tidak dicatat karena
        // isinya ditulis bebas oleh user dan bisa memuat data pribadi.
        $this->catat('Tugas baru disimpan', 'tugas.dibuat', 'menambah tugas', $request, $tugas);

        return (new TugasResource($tugas))
            ->additional(['message' => 'Tugas berhasil ditambahkan.'])
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, string $tugas): TugasResource
    {
        return new TugasResource($this->milikUser($request, $tugas));
    }

    public function update(TugasRequest $request, string $tugas): TugasResource
    {
        $data = $this->milikUser($request, $tugas);
        $data->update($request->validated());

        $this->catat('Tugas diperbarui', 'tugas.diubah', 'mengubah tugas', $request, $data, [
            'field_diubah' => array_keys($data->getChanges()),
        ]);

        return (new TugasResource($data))
            ->additional(['message' => 'Tugas berhasil diperbarui.']);
    }

    public function destroy(Request $request, string $tugas): JsonResponse
    {
        $data = $this->milikUser($request, $tugas);
        $data->delete();

        $this->catat('Tugas dihapus', 'tugas.dihapus', 'menghapus tugas', $request, $data);

        return response()->json(['message' => 'Tugas berhasil dihapus.']);
    }

    private function milikUser(Request $request, string $id): Tugas
    {
        return $request->user()->tugas()->findOrFail($id);
    }

    /**
     * Log terstruktur untuk perubahan data: siapa (user_id), mengerjakan apa (aksi),
     * dan data mana (tugas_id). Hanya id dan nilai pilihan tetap (prioritas, status).
     *
     * @param  array<string, mixed>  $tambahan
     */
    private function catat(string $pesan, string $event, string $aksi, Request $request, Tugas $tugas, array $tambahan = []): void
    {
        Log::info($pesan, [
            'event' => $event,
            'user_id' => $request->user()->id,
            'aksi' => $aksi,
            'tugas_id' => $tugas->id,
            'prioritas' => $tugas->prioritas,
            'status' => $tugas->status,
            ...$tambahan,
        ]);
    }
}
