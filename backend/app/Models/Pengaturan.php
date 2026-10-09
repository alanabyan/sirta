<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengaturan extends Model
{
    protected $guarded = [];

    /** Lokasi berkas gambar tidak dikirim ke klien. */
    protected $hidden = ['tanda_tangan_path', 'stempel_path'];

    protected $appends = ['ada_tanda_tangan', 'ada_stempel'];

    public static function saatIni(): self
    {
        return static::first() ?? static::create([
            'kop' => "RUKUN TETANGGA 03 / RUKUN WARGA 20\nPerumahan Griya Kreasi Aqilla\nDesa Sukajaya, Kecamatan Cibitung, Kabupaten Bekasi",
            'kota' => 'Bekasi',
            'penandatangan_nama' => 'Ketua RT',
            'penandatangan_jabatan' => 'Ketua RT 03',
        ]);
    }

    public function getAdaTandaTanganAttribute(): bool
    {
        return (bool) $this->tanda_tangan_path;
    }

    public function getAdaStempelAttribute(): bool
    {
        return (bool) $this->stempel_path;
    }
}
