<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pengaturan;
use App\Models\SuratKeluar;
use App\Support\Masker;
use Illuminate\Http\JsonResponse;

/** Publik: memeriksa keaslian surat lewat kode pada QR. */
class CekSuratController extends Controller
{
    public function __invoke(string $kode): JsonResponse
    {
        $s = SuratKeluar::where('kode_verifikasi', strtoupper(trim($kode)))->where('status', 'Diterbitkan')->first();

        if (! $s) {
            return response()->json(['message' => 'Surat dengan kode ini tidak ditemukan. Dokumen ini mungkin tidak asli.'], 404);
        }

        $p = Pengaturan::saatIni();

        return response()->json(['data' => [
            'nomor' => $s->nomor,
            'tanggal' => $s->tanggal->toDateString(),
            'perihal' => $s->perihal,
            'tujuan' => Masker::nama($s->tujuan),
            'penandatangan' => $p->penandatangan_nama,
            'jabatan' => $p->penandatangan_jabatan,
            'diterbitkan_at' => optional($s->diterbitkan_at)->toIso8601String(),
        ]]);
    }
}
