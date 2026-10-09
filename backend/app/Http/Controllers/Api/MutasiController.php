<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Aktivitas;
use App\Models\Mutasi;
use App\Models\Warga;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Pencatatan mutasi penduduk: lahir, pindah masuk, pindah keluar, meninggal. */
class MutasiController extends Controller
{
    private function periode(Request $request): array
    {
        $bulan = $request->validate(['bulan' => ['nullable', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/']])['bulan'] ?? null;
        if (! $bulan) {
            return [null, null, null];
        }
        $awal = Carbon::createFromFormat('Y-m-d', $bulan.'-01')->startOfMonth();

        return [$bulan, $awal, $awal->copy()->endOfMonth()];
    }

    public function index(Request $request): JsonResponse
    {
        [, $awal, $akhir] = $this->periode($request);
        $q = trim((string) $request->query('q', ''));

        $query = Mutasi::with(['warga:id,nama,status', 'user'])
            ->when($awal, fn ($x) => $x->whereBetween('tanggal', [$awal->toDateString(), $akhir->toDateString()]))
            ->when($request->filled('jenis'), fn ($x) => $x->where('jenis', $request->query('jenis')))
            ->when($q, fn ($x) => $x->where('nama_warga', 'like', "%{$q}%"))
            ->orderByDesc('tanggal')->orderByDesc('id');

        return response()->json($query->paginate(min(max((int) $request->query('per_page', 10), 1), 100)));
    }

    public function ringkasan(Request $request): JsonResponse
    {
        [$bulan, $awal, $akhir] = $this->periode($request);
        $awal ??= now()->startOfMonth();
        $akhir ??= now()->endOfMonth();

        return response()->json(['data' => Mutasi::rekap($awal, $akhir)]);
    }

    public function store(Request $request): JsonResponse
    {
        $jenis = $request->input('jenis');
        $baru = in_array($jenis, ['lahir', 'masuk'], true);

        $data = $request->validate([
            'jenis' => ['required', Rule::in(array_keys(Mutasi::JENIS))],
            'tanggal' => ['required', 'date', 'before_or_equal:today'],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'warga_id' => [$baru ? 'nullable' : 'required', 'exists:wargas,id'],
            'warga' => [$baru ? 'required' : 'nullable', 'array'],
            'warga.nama' => [$baru ? 'required' : 'nullable', 'string', 'max:120'],
            'warga.nik' => [$jenis === 'masuk' ? 'required' : 'nullable', 'digits:16', Rule::unique('wargas', 'nik')],
            'warga.jenis_kelamin' => [$baru ? 'required' : 'nullable', Rule::in(['Laki-laki', 'Perempuan'])],
            'warga.tanggal_lahir' => [$baru ? 'required' : 'nullable', 'date', 'before_or_equal:today'],
            'warga.pekerjaan' => ['nullable', 'string', 'max:100'],
            'warga.telepon' => ['nullable', 'string', 'max:20'],
            'warga.keluarga_id' => ['nullable', 'exists:keluargas,id'],
            'warga.hubungan_keluarga' => ['nullable', Rule::in(['Kepala Keluarga', 'Istri', 'Anak', 'Famili Lain'])],
        ], [
            'warga.nik.unique' => 'NIK ini sudah terdaftar pada warga lain.',
            'warga.nik.required' => 'NIK wajib diisi untuk warga pindah masuk.',
            'warga_id.required' => 'Pilih warga yang bersangkutan.',
        ]);

        $mutasi = DB::transaction(function () use ($data, $baru, $request) {
            if ($baru) {
                $w = Warga::create(($data['warga'] ?? []) + ['status' => 'Aktif']);
                $nama = $w->nama;
                $sebelum = null;
            } else {
                $w = Warga::lockForUpdate()->findOrFail($data['warga_id']);
                if ($w->status !== 'Aktif') {
                    throw ValidationException::withMessages(['warga_id' => "{$w->nama} tidak berstatus Aktif, sehingga tidak dapat dicatat mutasinya."]);
                }
                $nama = $w->nama;
                $sebelum = $w->status;
                $w->update(['status' => $data['jenis'] === 'meninggal' ? 'Meninggal' : 'Pindah']);
            }

            return Mutasi::create([
                'warga_id' => $w->id, 'nama_warga' => $nama, 'jenis' => $data['jenis'], 'tanggal' => $data['tanggal'],
                'keterangan' => $data['keterangan'] ?? null, 'status_sebelum' => $sebelum, 'user_id' => $request->user()->id,
            ]);
        });

        Aktivitas::catat("Mencatat mutasi “{$mutasi->jenis_label}” untuk {$mutasi->nama_warga}", 'edit');

        return response()->json(['data' => $mutasi->load(['warga:id,nama,status', 'user']), 'message' => "Mutasi {$mutasi->jenis_label} atas nama {$mutasi->nama_warga} tercatat."], 201);
    }

    public function batal(Request $request, int $id): JsonResponse
    {
        $alasan = $request->validate(['alasan' => ['required', 'string', 'max:255']], ['alasan.required' => 'Alasan pembatalan wajib diisi.'])['alasan'];
        $m = Mutasi::with('warga')->findOrFail($id);

        if ($m->dibatalkan_at) {
            return response()->json(['message' => 'Mutasi ini sudah dibatalkan.'], 422);
        }

        DB::transaction(function () use ($m, $alasan, $request) {
            $w = $m->warga;
            if ($w) {
                if (in_array($m->jenis, ['keluar', 'meninggal'], true)) {
                    $w->update(['status' => $m->status_sebelum ?: 'Aktif']);
                } else {
                    // Lahir/masuk: warga dibuat oleh mutasi ini. Hapus hanya bila belum dipakai di tempat lain.
                    if ($w->pengajuans()->exists()) {
                        throw ValidationException::withMessages(['alasan' => "{$w->nama} sudah memiliki permohonan, sehingga data warganya tidak dapat dihapus. Ubah statusnya lewat mutasi lain."]);
                    }
                    $w->delete(); // warga_id pada catatan ini menjadi null; nama tersimpan di nama_warga
                }
            }
            $m->update(['dibatalkan_at' => now(), 'dibatalkan_oleh' => $request->user()->id, 'alasan_batal' => $alasan]);
        });
        Aktivitas::catat("Membatalkan mutasi “{$m->jenis_label}” {$m->nama_warga}", 'trash');

        return response()->json(['data' => $m->fresh(['warga:id,nama,status', 'user']), 'message' => 'Mutasi dibatalkan dan data warga dipulihkan.']);
    }
}
