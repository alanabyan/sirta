<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Aktivitas;
use App\Models\Pengajuan;
use App\Models\Warga;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Pengajuan mandiri oleh warga (tanpa login). Identitas diverifikasi dengan
 * NIK + tanggal lahir yang cocok dengan data warga RT. Semua ketidakcocokan
 * dijawab sama agar endpoint ini tidak bisa dipakai menebak NIK.
 */
class AjukanController extends Controller
{
    private const BATAS_ANTREAN = 3;

    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nik' => ['required', 'digits:16'],
            'tanggal_lahir' => ['required', 'date_format:Y-m-d'],
            'layanan' => ['required', Rule::in(PengajuanController::LAYANAN)],
            'keperluan' => ['required', 'string', 'min:5', 'max:1000'],
            'lampiran' => ['nullable', 'array', 'max:3'],
            'lampiran.*' => ['file', 'max:2048', 'extensions:jpg,jpeg,png,pdf', 'mimes:jpg,jpeg,png,pdf'],
        ], [
            'lampiran.max' => 'Lampiran maksimal 3 berkas.',
            'lampiran.*.max' => 'Setiap lampiran maksimal 2 MB.',
            'lampiran.*.mimes' => 'Lampiran harus berupa foto (JPG/PNG) atau PDF.',
            'lampiran.*.extensions' => 'Lampiran harus berupa foto (JPG/PNG) atau PDF.',
            'lampiran.*.uploaded' => 'Lampiran gagal diunggah. Pastikan ukurannya di bawah 2 MB.',
            'keperluan.required' => 'Jelaskan keperluan Anda.',
            'keperluan.min' => 'Keperluan terlalu singkat.',
        ]);

        $warga = Warga::where('nik', $data['nik'])->where('status', 'Aktif')->first();

        if (! $warga || $warga->tanggal_lahir->toDateString() !== $data['tanggal_lahir']) {
            return response()->json(['message' => 'NIK dan tanggal lahir tidak cocok dengan data warga RT 03. Hubungi pengurus RT bila Anda merasa sudah terdaftar.'], 422);
        }

        $menunggu = Pengajuan::where('warga_id', $warga->id)->where('status', 'Menunggu Verifikasi')->count();
        if ($menunggu >= self::BATAS_ANTREAN) {
            return response()->json(['message' => 'Anda masih memiliki '.$menunggu.' permohonan yang menunggu verifikasi. Mohon tunggu hingga diproses pengurus.'], 422);
        }

        $files = $request->file('lampiran', []);

        $pengajuan = DB::transaction(function () use ($warga, $data, $files) {
            $p = Pengajuan::create([
                'kode' => Pengajuan::nextKode(),
                'warga_id' => $warga->id,
                'layanan' => $data['layanan'],
                'keperluan' => $data['keperluan'],
                'status' => 'Menunggu Verifikasi',
                'sumber' => 'mandiri',
            ]);
            $p->riwayat()->create(['status' => 'Menunggu Verifikasi', 'catatan' => 'Diajukan sendiri oleh warga'.($files ? ' dengan '.count($files).' lampiran.' : '.')]);
            foreach ($files as $f) {
                $p->lampirans()->create([
                    'nama' => $f->getClientOriginalName(),
                    'path' => $f->store('lampiran'), // disk privat
                    'mime' => $f->getMimeType() ?: 'application/octet-stream',
                    'ukuran' => $f->getSize(),
                ]);
            }

            return $p;
        });
        Aktivitas::catat("Pengajuan {$pengajuan->kode} diajukan mandiri oleh warga", 'plus');

        return response()->json([
            'data' => ['kode' => $pengajuan->kode, 'layanan' => $pengajuan->layanan, 'nik4' => substr($warga->nik, -4)],
            'message' => 'Permohonan berhasil diajukan.',
        ], 201);
    }
}
