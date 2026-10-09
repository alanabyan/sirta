<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuratMasuk extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['tanggal' => 'date:Y-m-d'];
    }
}
