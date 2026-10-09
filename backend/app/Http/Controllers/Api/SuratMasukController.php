<?php

namespace App\Http\Controllers\Api;

use App\Models\SuratMasuk;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class SuratMasukController extends CrudController
{
    protected string $model = SuratMasuk::class;

    protected string $label = 'surat masuk';

    protected array $search = ['nomor', 'pengirim', 'perihal'];

    protected array $filters = ['status'];

    protected string $orderBy = 'tanggal';

    protected function rules(?Model $record): array
    {
        return [
            'nomor' => ['required', 'string', 'max:80'],
            'tanggal' => ['required', 'date'],
            'pengirim' => ['required', 'string', 'max:150'],
            'perihal' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(['Baru', 'Diproses', 'Diarsipkan'])],
        ];
    }
}
