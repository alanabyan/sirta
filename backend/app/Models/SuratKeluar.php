<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SuratKeluar extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::saving(function (self $s) {
            if ($s->status === 'Diterbitkan' && ! $s->kode_verifikasi) {
                $s->kode_verifikasi = Str::upper(Str::random(10));
                $s->diterbitkan_at = now();
                $s->diterbitkan_oleh = auth()->id();
            }
        });
    }

    protected function casts(): array
    {
        return ['tanggal' => 'date:Y-m-d', 'diterbitkan_at' => 'datetime'];
    }

    public function template()
    {
        return $this->belongsTo(TemplateSurat::class, 'template_surat_id');
    }
}
