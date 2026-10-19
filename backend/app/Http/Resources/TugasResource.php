<?php

namespace App\Http\Resources;

use App\Models\Tugas;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Tugas
 */
class TugasResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'judul' => $this->judul,
            'deskripsi' => $this->deskripsi,
            'prioritas' => $this->prioritas,
            'tenggat' => $this->tenggat?->toDateString(),
            'status' => $this->status,
            'terlambat' => $this->tenggat !== null
                && $this->status !== 'selesai'
                && $this->tenggat->isBefore(today()),
            'dibuat_pada' => $this->created_at?->toIso8601String(),
            'diperbarui_pada' => $this->updated_at?->toIso8601String(),
        ];
    }
}
