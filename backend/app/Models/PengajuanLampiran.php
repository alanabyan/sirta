<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengajuanLampiran extends Model
{
    protected $guarded = [];

    /** Lokasi berkas di server tidak perlu diketahui klien. */
    protected $hidden = ['path'];

    public function pengajuan()
    {
        return $this->belongsTo(Pengajuan::class);
    }
}
