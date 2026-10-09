<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Keluarga extends Model
{
    protected $guarded = [];

    public function anggota()
    {
        return $this->hasMany(Warga::class);
    }
}
