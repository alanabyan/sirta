<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogAksesNik extends Model
{
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class)->select('id', 'name', 'role');
    }

    public function warga()
    {
        return $this->belongsTo(Warga::class)->select('id', 'nama');
    }
}
