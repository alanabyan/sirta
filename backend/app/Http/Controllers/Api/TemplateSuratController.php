<?php

namespace App\Http\Controllers\Api;

use App\Models\TemplateSurat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class TemplateSuratController extends CrudController
{
    protected string $model = TemplateSurat::class;

    protected string $label = 'template surat';

    protected array $search = ['nama', 'kode'];

    protected string $orderBy = 'id';

    protected string $orderDir = 'asc';

    protected function rules(?Model $record): array
    {
        return [
            'kode' => ['required', 'string', 'max:40', Rule::unique('template_surats', 'kode')->ignore($record?->id)],
            'nama' => ['required', 'string', 'max:100'],
            'deskripsi' => ['nullable', 'string', 'max:255'],
            'isi' => ['required', 'string'],
        ];
    }
}
