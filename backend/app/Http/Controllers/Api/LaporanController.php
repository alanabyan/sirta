<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Aktivitas;
use App\Models\Arsip;
use App\Models\Keluarga;
use App\Models\LogAksesNik;
use App\Models\Mutasi;
use App\Models\Pengajuan;
use App\Models\SuratKeluar;
use App\Models\SuratMasuk;
use App\Models\Warga;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/** Data laporan untuk diunduh sebagai Excel atau dicetak (PDF) oleh frontend. */
class LaporanController extends Controller
{
    /** Daftar seluruh warga. NIK disamarkan, kecuali diminta lengkap (dicatat dalam log audit). */
    public function warga(Request $request): JsonResponse
    {
        $lengkap = $request->boolean('nik');
        $rows = Warga::with('keluarga:id,kepala_keluarga,alamat')->orderBy('nama')->get();

        if ($lengkap) {
            $now = now();
            foreach ($rows->chunk(200) as $chunk) {
                LogAksesNik::insert($chunk->map(fn ($w) => [
                    'user_id' => $request->user()->id, 'warga_id' => $w->id, 'keperluan' => 'ekspor',
                    'created_at' => $now, 'updated_at' => $now,
                ])->all());
            }
            Aktivitas::catat("Mengekspor daftar {$rows->count()} warga dengan NIK lengkap", 'info');
        } else {
            Aktivitas::catat("Mengekspor daftar {$rows->count()} warga", 'info');
        }

        $data = $rows->map(fn ($w) => [
            'nama' => $w->nama,
            'nik' => $lengkap ? $w->nik : $w->nik_samar,
            'jenis_kelamin' => $w->jenis_kelamin,
            'tanggal_lahir' => $w->tanggal_lahir->toDateString(),
            'umur' => $w->umur,
            'pekerjaan' => $w->pekerjaan,
            'telepon' => $w->telepon,
            'status' => $w->status,
            'kepala_keluarga' => $w->keluarga?->kepala_keluarga,
            'hubungan' => $w->hubungan_keluarga,
            'alamat' => $w->keluarga?->alamat,
        ])->values();

        $aktif = $rows->where('status', 'Aktif');
        $kelompok = fn (int $a, int $b) => $aktif->filter(fn ($w) => $w->umur >= $a && $w->umur <= $b)->count();

        return response()->json(['data' => [
            'nik_lengkap' => $lengkap,
            'ringkasan' => [
                'total' => $rows->count(),
                'aktif' => $aktif->count(),
                'laki_laki' => $aktif->where('jenis_kelamin', 'Laki-laki')->count(),
                'perempuan' => $aktif->where('jenis_kelamin', 'Perempuan')->count(),
                'umur' => ['0–5 th' => $kelompok(0, 5), '6–17 th' => $kelompok(6, 17), '18–59 th' => $kelompok(18, 59), '60+ th' => $kelompok(60, 200)],
            ],
            'baris' => $data,
        ]]);
    }

    public function keluarga(): JsonResponse
    {
        $rows = Keluarga::withCount('anggota')->orderBy('kepala_keluarga')->get()->map(fn ($k) => [
            'no_kk' => $k->no_kk,
            'kepala_keluarga' => $k->kepala_keluarga,
            'alamat' => $k->alamat,
            'status_rumah' => $k->status_rumah,
            'anggota' => $k->anggota_count,
        ])->values();

        Aktivitas::catat("Mengekspor daftar {$rows->count()} keluarga", 'info');

        return response()->json(['data' => [
            'ringkasan' => ['total' => $rows->count(), 'anggota' => $rows->sum('anggota')],
            'baris' => $rows,
        ]]);
    }

    /** Laporan satu bulan: ringkasan, rekap layanan, daftar permohonan, dan surat masuk/keluar. */
    public function bulanan(Request $request): JsonResponse
    {
        $bulan = $request->validate(['bulan' => ['nullable', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/']])['bulan'] ?? now()->format('Y-m');
        $awal = Carbon::createFromFormat('Y-m-d', $bulan.'-01')->startOfMonth();
        $akhir = $awal->copy()->endOfMonth();

        $pengajuan = Pengajuan::with(['warga:id,nama', 'riwayat'])
            ->whereBetween('created_at', [$awal, $akhir])->orderBy('created_at')->get();

        $selesaiPada = fn (Pengajuan $p) => $p->riwayat->firstWhere('status', $p->status === 'Selesai' ? 'Selesai' : 'Ditolak')?->created_at;

        $rekap = collect(PengajuanController::LAYANAN)->map(function ($l) use ($pengajuan) {
            $g = $pengajuan->where('layanan', $l);
            $row = ['layanan' => $l, 'total' => $g->count()];
            foreach (Pengajuan::STATUS as $s) {
                $row[$s] = $g->where('status', $s)->count();
            }

            return $row;
        })->filter(fn ($r) => $r['total'] > 0)->values();

        $lama = $pengajuan->where('status', 'Selesai')->map(fn ($p) => $selesaiPada($p) ? $p->created_at->diffInHours($selesaiPada($p)) / 24 : null)->filter(fn ($x) => $x !== null);

        $masuk = SuratMasuk::whereBetween('tanggal', [$awal->toDateString(), $akhir->toDateString()])->orderBy('tanggal')->get();
        $keluar = SuratKeluar::whereBetween('tanggal', [$awal->toDateString(), $akhir->toDateString()])->orderBy('tanggal')->get();

        $mutasi = Mutasi::berlaku()->whereBetween('tanggal', [$awal->toDateString(), $akhir->toDateString()])->orderBy('tanggal')->orderBy('id')->get();

        return response()->json(['data' => [
            'bulan' => $bulan,
            'ringkasan' => [
                'warga_aktif' => Warga::where('status', 'Aktif')->count(),
                'keluarga' => Keluarga::count(),
                'permohonan' => $pengajuan->count(),
                'selesai' => $pengajuan->where('status', 'Selesai')->count(),
                'ditolak' => $pengajuan->where('status', 'Ditolak')->count(),
                'belum_selesai' => $pengajuan->whereIn('status', ['Menunggu Verifikasi', 'Diproses'])->count(),
                'mandiri' => $pengajuan->where('sumber', 'mandiri')->count(),
                'rata_hari' => $lama->isEmpty() ? null : round($lama->avg(), 1),
                'surat_masuk' => $masuk->count(),
                'surat_keluar' => $keluar->where('status', 'Diterbitkan')->count(),
                'arsip_baru' => Arsip::whereBetween('created_at', [$awal, $akhir])->count(),
            ],
            'mutasi_rekap' => Mutasi::rekap($awal, $akhir),
            'mutasi' => $mutasi->map(fn ($m) => ['tanggal' => $m->tanggal->toDateString(), 'jenis' => $m->jenis_label, 'nama' => $m->nama_warga, 'keterangan' => $m->keterangan])->values(),
            'rekap_layanan' => $rekap,
            'permohonan' => $pengajuan->map(fn ($p) => [
                'kode' => $p->kode,
                'tanggal' => $p->created_at->toDateString(),
                'pemohon' => $p->warga->nama,
                'layanan' => $p->layanan,
                'status' => $p->status,
                'sumber' => $p->sumber === 'mandiri' ? 'Mandiri' : 'Pengurus',
                'selesai' => optional($selesaiPada($p))->toDateString(),
                'catatan' => $p->status === 'Ditolak' ? $p->catatan : null,
            ])->values(),
            'surat_masuk' => $masuk->map(fn ($s) => ['nomor' => $s->nomor, 'tanggal' => $s->tanggal->toDateString(), 'pihak' => $s->pengirim, 'perihal' => $s->perihal, 'status' => $s->status])->values(),
            'surat_keluar' => $keluar->map(fn ($s) => ['nomor' => $s->nomor, 'tanggal' => $s->tanggal->toDateString(), 'pihak' => $s->tujuan, 'perihal' => $s->perihal, 'status' => $s->status])->values(),
        ]]);
    }
}
