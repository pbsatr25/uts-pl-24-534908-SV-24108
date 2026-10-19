<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TugasRequest;
use App\Http\Resources\TugasResource;
use App\Models\Tugas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
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

        return (new TugasResource($data))
            ->additional(['message' => 'Tugas berhasil diperbarui.']);
    }

    public function destroy(Request $request, string $tugas): JsonResponse
    {
        $this->milikUser($request, $tugas)->delete();

        return response()->json(['message' => 'Tugas berhasil dihapus.']);
    }

    private function milikUser(Request $request, string $id): Tugas
    {
        return $request->user()->tugas()->findOrFail($id);
    }
}
