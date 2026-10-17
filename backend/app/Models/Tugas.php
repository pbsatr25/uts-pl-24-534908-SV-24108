<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tugas extends Model
{
    public const PRIORITAS = ['rendah', 'sedang', 'tinggi'];

    public const STATUS = ['belum', 'proses', 'selesai'];

    /**
     * Nama tabel ditulis eksplisit agar Laravel tidak mencari bentuk jamak bahasa Inggris.
     */
    protected $table = 'tugas';

    /**
     * user_id sengaja tidak fillable: pemilik tugas selalu diambil dari user yang login.
     *
     * @var list<string>
     */
    protected $fillable = [
        'judul',
        'deskripsi',
        'prioritas',
        'tenggat',
        'status',
    ];

    /**
     * @var array<string, string>
     */
    protected $attributes = [
        'prioritas' => 'sedang',
        'status' => 'belum',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tenggat' => 'date',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Urutan daftar: tugas belum selesai lebih dulu, lalu tenggat terdekat
     * (tugas tanpa tenggat di akhir), lalu yang terbaru dibuat.
     *
     * @param  Builder<Tugas>  $query
     */
    public function scopeUrutanDaftar(Builder $query): void
    {
        $query->orderByRaw("CASE WHEN status = 'selesai' THEN 1 ELSE 0 END")
            ->orderByRaw('CASE WHEN tenggat IS NULL THEN 1 ELSE 0 END')
            ->orderBy('tenggat')
            ->orderByDesc('id');
    }
}
