<?php

namespace App\Http\Requests;

use App\Models\Tugas;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TugasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * POST dan PUT wajib mengirim data lengkap. PATCH boleh mengirim sebagian field saja
     * (misalnya hanya status), tetapi field yang dikirim tetap divalidasi.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $wajib = $this->isMethod('PATCH') ? ['sometimes', 'required'] : ['required'];

        return [
            'judul' => [...$wajib, 'string', 'max:150'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'prioritas' => [...$wajib, Rule::in(Tugas::PRIORITAS)],
            'tenggat' => ['nullable', 'date'],
            'status' => [...$wajib, Rule::in(Tugas::STATUS)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'string' => ':attribute harus berupa teks.',
            'max' => ':attribute maksimal :max karakter.',
            'in' => ':attribute harus salah satu dari: :values.',
            'date' => ':attribute harus berupa tanggal yang valid (YYYY-MM-DD).',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'judul' => 'Judul',
            'deskripsi' => 'Deskripsi',
            'prioritas' => 'Prioritas',
            'tenggat' => 'Tenggat',
            'status' => 'Status',
        ];
    }
}
