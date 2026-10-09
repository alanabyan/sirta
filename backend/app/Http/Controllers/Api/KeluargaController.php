<?php

namespace App\Http\Controllers\Api;

use App\Models\Keluarga;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class KeluargaController extends CrudController
{
    protected string $model = Keluarga::class;

    protected string $label = 'data keluarga';

    protected array $search = ['no_kk', 'kepala_keluarga', 'alamat'];

    protected array $filters = ['status_rumah'];

    protected array $withCount = ['anggota'];

    protected function title(Model $record): string
    {
        return $record->kepala_keluarga;
    }

    protected function rules(?Model $record): array
    {
        return [
            'no_kk' => ['required', 'digits:16', Rule::unique('keluargas', 'no_kk')->ignore($record?->id)],
            'kepala_keluarga' => ['required', 'string', 'max:120'],
            'alamat' => ['required', 'string', 'max:255'],
            'status_rumah' => ['required', Rule::in(['Milik sendiri', 'Kontrak', 'Rumah dinas'])],
        ];
    }

    /** Detail keluarga lengkap dengan anggotanya. */
    public function show(int $id): JsonResponse
    {
        return response()->json(['data' => Keluarga::withCount('anggota')->with('anggota')->findOrFail($id)]);
    }
}
