<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengajuan extends Model
{
    public const STATUS = ['Menunggu Verifikasi', 'Diproses', 'Selesai', 'Ditolak'];

    public const PROGRESS = ['Menunggu Verifikasi' => 35, 'Diproses' => 70, 'Selesai' => 100, 'Ditolak' => 0];

    protected $guarded = [];

    protected $appends = ['progress'];

    public function getProgressAttribute(): int
    {
        return self::PROGRESS[$this->status] ?? 0;
    }

    public function warga()
    {
        return $this->belongsTo(Warga::class);
    }

    public function suratKeluar()
    {
        return $this->hasOne(SuratKeluar::class);
    }

    public function lampirans()
    {
        return $this->hasMany(PengajuanLampiran::class);
    }

    public function riwayat()
    {
        return $this->hasMany(PengajuanRiwayat::class)->latest('id');
    }

    public static function nextKode(): string
    {
        $year = now()->year;
        $last = static::where('kode', 'like', "PL-$year-%")->orderByDesc('kode')->value('kode');
        $n = $last ? ((int) substr($last, -4)) + 1 : 1;

        return sprintf('PL-%d-%04d', $year, $n);
    }
}
