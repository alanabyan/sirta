<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Aktivitas;
use App\Models\Arsip;
use App\Models\Keluarga;
use App\Models\Pengajuan;
use App\Models\SuratMasuk;
use App\Models\Warga;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    /** Hitungan ringan untuk penanda menu; dipanggil berkala oleh frontend. */
    public function antrean(): JsonResponse
    {
        return response()->json(['data' => [
            'perlu_verifikasi' => Pengajuan::where('status', 'Menunggu Verifikasi')->count(),
            'surat_baru' => SuratMasuk::where('status', 'Baru')->count(),
        ]]);
    }

    public function __invoke(): JsonResponse
    {
        $bulan = collect(range(5, 0))->map(function ($i) {
            $d = now()->startOfMonth()->subMonths($i);

            return [
                'label' => $d->translatedFormat('M'),
                'total' => Pengajuan::whereYear('created_at', $d->year)->whereMonth('created_at', $d->month)->count(),
            ];
        })->values();

        $status = collect(Pengajuan::STATUS)->mapWithKeys(fn ($s) => [$s => Pengajuan::where('status', $s)->count()]);

        return response()->json(['data' => [
            'kpi' => [
                'warga' => Warga::where('status', 'Aktif')->count(),
                'keluarga' => Keluarga::count(),
                'pengajuan_aktif' => Pengajuan::whereIn('status', ['Menunggu Verifikasi', 'Diproses'])->count(),
                'perlu_verifikasi' => Pengajuan::where('status', 'Menunggu Verifikasi')->count(),
                'surat_baru' => SuratMasuk::where('status', 'Baru')->count(),
                'arsip' => Arsip::count(),
            ],
            'per_bulan' => $bulan,
            'status' => $status,
            'terbaru' => Pengajuan::with('warga:id,nama')->latest('id')->limit(5)->get(),
            'aktivitas' => Aktivitas::with('user:id,name')->latest('id')->limit(8)->get(),
        ]]);
    }
}
