<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warga extends Model
{
    protected $guarded = [];

    protected $appends = ['umur', 'nik_samar'];

    /** NIK lengkap tidak ikut dikirim ke klien; gunakan endpoint khusus bila memang perlu. */
    protected $hidden = ['nik'];

    public function getNikSamarAttribute(): ?string
    {
        return $this->nik ? substr($this->nik, 0, 3).str_repeat('*', max(strlen($this->nik) - 3, 0)) : null;
    }

    protected function casts(): array
    {
        return ['tanggal_lahir' => 'date:Y-m-d'];
    }

    public function getUmurAttribute(): int
    {
        return $this->tanggal_lahir ? $this->tanggal_lahir->age : 0;
    }

    public function keluarga()
    {
        return $this->belongsTo(Keluarga::class);
    }

    public function pengajuans()
    {
        return $this->hasMany(Pengajuan::class);
    }
}
