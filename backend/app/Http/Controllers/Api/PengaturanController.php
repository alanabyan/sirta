<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Aktivitas;
use App\Models\Pengaturan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** Identitas RT, kop surat, penandatangan, serta gambar tanda tangan & stempel. */
class PengaturanController extends Controller
{
    private const JENIS = ['tanda_tangan' => 'tanda_tangan_path', 'stempel' => 'stempel_path'];

    public function show(): JsonResponse
    {
        $p = Pengaturan::saatIni();

        // `versi` berubah setiap kali pengaturan disimpan → klien tahu kapan gambar perlu diunduh ulang.
        return response()->json(['data' => $p->toArray() + ['versi' => $p->updated_at->timestamp]]);
    }

    /** POST (bukan PUT) karena membawa berkas multipart. */
    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'kop' => ['required', 'string', 'max:500'],
            'kota' => ['required', 'string', 'max:60'],
            'penandatangan_nama' => ['required', 'string', 'max:120'],
            'penandatangan_jabatan' => ['required', 'string', 'max:120'],
            'tanda_tangan' => ['nullable', 'file', 'max:1024', 'extensions:png,jpg,jpeg', 'mimes:png,jpg,jpeg'],
            'stempel' => ['nullable', 'file', 'max:1024', 'extensions:png,jpg,jpeg', 'mimes:png,jpg,jpeg'],
            'hapus_tanda_tangan' => ['boolean'],
            'hapus_stempel' => ['boolean'],
        ], [
            '*.max' => 'Ukuran gambar maksimal 1 MB.',
            '*.mimes' => 'Gambar harus berformat PNG atau JPG.',
            '*.extensions' => 'Gambar harus berformat PNG atau JPG.',
        ]);

        $p = Pengaturan::saatIni();
        $p->fill(collect($data)->only(['kop', 'kota', 'penandatangan_nama', 'penandatangan_jabatan'])->all());

        foreach (self::JENIS as $jenis => $kolom) {
            if ($request->hasFile($jenis)) {
                $lama = $p->{$kolom};
                $p->{$kolom} = $request->file($jenis)->store('pengaturan'); // disk privat
                $lama && Storage::delete($lama);
            } elseif ($request->boolean("hapus_{$jenis}") && $p->{$kolom}) {
                Storage::delete($p->{$kolom});
                $p->{$kolom} = null;
            }
        }
        $p->touch();
        $p->save();
        Aktivitas::catat('Memperbarui identitas RT & tanda tangan', 'edit');

        $p = $p->fresh();

        return response()->json(['data' => $p->toArray() + ['versi' => $p->updated_at->timestamp], 'message' => 'Pengaturan disimpan.']);
    }

    /** Gambar hanya untuk pengurus yang berhak mencetak surat. */
    public function gambar(string $jenis)
    {
        abort_unless(isset(self::JENIS[$jenis]), 404);
        $path = Pengaturan::saatIni()->{self::JENIS[$jenis]};
        abort_unless($path && Storage::exists($path), 404, 'Gambar belum diunggah.');

        return Storage::response($path, null, ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
    }
}
