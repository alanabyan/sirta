<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pengajuan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoint publik. Kode pengajuan berurutan sehingga mudah ditebak, maka pemohon
 * juga harus menyebut 4 digit terakhir NIK-nya. Semua kegagalan dijawab sama
 * agar orang luar tidak bisa memeriksa kode mana yang ada.
 */
class TrackingController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'kode' => ['required', 'string', 'max:30'],
            'nik4' => ['required', 'digits:4'],
        ]);

        $p = Pengajuan::with(['warga:id,nama,nik', 'riwayat'])->where('kode', strtoupper(trim($data['kode'])))->first();

        if (! $p || ! hash_equals(substr((string) $p->warga->nik, -4), $data['nik4'])) {
            return response()->json(['message' => 'Kode atau 4 digit terakhir NIK tidak cocok.'], 404);
        }

        return response()->json(['data' => [
            'kode' => $p->kode,
            'layanan' => $p->layanan,
            'status' => $p->status,
            'progress' => $p->progress,
            'pemohon' => $this->samarkan($p->warga->nama),
            'tanggal' => $p->created_at->toDateString(),
            'catatan' => $p->status === 'Ditolak' ? $p->catatan : null,
            'riwayat' => $p->riwayat->map(fn ($r) => [
                'status' => $r->status,
                'catatan' => $r->catatan,
                'waktu' => $r->created_at->toIso8601String(),
            ])->values(),
        ]]);
    }

    /** "Ahmad Fauzan" → "Ahmad F***" agar privasi pemohon terjaga. */
    private function samarkan(string $nama): string
    {
        $parts = preg_split('/\s+/', trim($nama));
        $first = array_shift($parts);

        return trim($first.' '.implode(' ', array_map(fn ($p) => mb_substr($p, 0, 1).'***', $parts)));
    }
}
