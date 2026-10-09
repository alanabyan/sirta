<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Arsip extends Model
{
    protected $guarded = [];

    protected $appends = ['ada_berkas'];

    public function getAdaBerkasAttribute(): bool
    {
        return (bool) $this->path;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
