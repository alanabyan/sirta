<?php

namespace App\Http\Controllers\Api;

use App\Models\Pengurus;
use Illuminate\Database\Eloquent\Model;

class PengurusController extends CrudController
{
    protected string $model = Pengurus::class;

    protected string $label = 'pengurus';

    protected array $search = ['nama', 'jabatan'];

    protected string $orderBy = 'id';

    protected string $orderDir = 'asc';

    protected function rules(?Model $record): array
    {
        return [
            'nama' => ['required', 'string', 'max:120'],
            'jabatan' => ['required', 'string', 'max:100'],
            'periode' => ['required', 'string', 'max:20'],
            'kontak' => ['nullable', 'string', 'max:30'],
        ];
    }
}
