<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Aktivitas extends Model
{
    protected $table = 'aktivitas';

    protected $guarded = [];

    public static function catat(string $deskripsi, string $ikon = 'info'): void
    {
        static::create(['user_id' => auth()->id(), 'deskripsi' => $deskripsi, 'ikon' => $ikon]);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
