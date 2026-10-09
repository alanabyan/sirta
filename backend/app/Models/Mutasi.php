<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Mutasi extends Model
{
    public const JENIS = [
        'lahir' => 'Lahir',
        'masuk' => 'Pindah masuk',
        'keluar' => 'Pindah keluar',
        'meninggal' => 'Meninggal',
    ];

    protected $guarded = [];

    protected $appends = ['jenis_label'];

    protected function casts(): array
    {
        return ['tanggal' => 'date:Y-m-d', 'dibatalkan_at' => 'datetime'];
    }

    public function getJenisLabelAttribute(): string
    {
        return self::JENIS[$this->jenis] ?? $this->jenis;
    }

    public function warga()
    {
        return $this->belongsTo(Warga::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class)->select('id', 'name');
    }

    public function scopeBerlaku($q)
    {
        return $q->whereNull('dibatalkan_at');
    }

    /**
     * Rekap mutasi satu bulan beserta saldo penduduk aktif.
     * Saldo dihitung mundur dari jumlah warga aktif saat ini, hanya berdasarkan mutasi yang tercatat.
     */
    public static function rekap(Carbon $awal, Carbon $akhir): array
    {
        $hitung = fn ($q) => $q->selectRaw('jenis, count(*) as n')->groupBy('jenis')->pluck('n', 'jenis');
        $bulan = $hitung(static::berlaku()->whereBetween('tanggal', [$awal->toDateString(), $akhir->toDateString()]));
        $sesudah = $hitung(static::berlaku()->where('tanggal', '>', $akhir->toDateString()));

        $masuk = ($bulan['lahir'] ?? 0) + ($bulan['masuk'] ?? 0);
        $keluar = ($bulan['keluar'] ?? 0) + ($bulan['meninggal'] ?? 0);
        $netSesudah = (($sesudah['lahir'] ?? 0) + ($sesudah['masuk'] ?? 0)) - (($sesudah['keluar'] ?? 0) + ($sesudah['meninggal'] ?? 0));

        $saldoAkhir = Warga::where('status', 'Aktif')->count() - $netSesudah;

        return [
            'lahir' => $bulan['lahir'] ?? 0,
            'masuk' => $bulan['masuk'] ?? 0,
            'keluar' => $bulan['keluar'] ?? 0,
            'meninggal' => $bulan['meninggal'] ?? 0,
            'saldo_awal' => $saldoAkhir - $masuk + $keluar,
            'saldo_akhir' => $saldoAkhir,
        ];
    }
}
