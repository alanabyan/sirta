<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuratKeluar extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['tanggal' => 'date:Y-m-d'];
    }

    public function template()
    {
        return $this->belongsTo(TemplateSurat::class, 'template_surat_id');
    }
}
