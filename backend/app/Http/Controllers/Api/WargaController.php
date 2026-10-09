<?php

namespace App\Http\Controllers\Api;

use App\Models\LogAksesNik;
use App\Models\Warga;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class WargaController extends CrudController
{
    protected string $model = Warga::class;

    protected string $label = 'data warga';

    protected array $search = ['nama', 'nik', 'pekerjaan'];

    protected array $filters = ['jenis_kelamin', 'status', 'keluarga_id'];

    protected array $with = ['keluarga:id,no_kk,kepala_keluarga,alamat', 'mutasiTerakhir:id,warga_id,jenis,tanggal,keterangan'];

    protected function rules(?Model $record): array
    {
        return [
            'nik' => ['required', 'digits:16', Rule::unique('wargas', 'nik')->ignore($record?->id)],
            'nama' => ['required', 'string', 'max:120'],
            'jenis_kelamin' => ['required', Rule::in(['Laki-laki', 'Perempuan'])],
            'tanggal_lahir' => ['required', 'date', 'before_or_equal:today'],
            'pekerjaan' => ['nullable', 'string', 'max:100'],
            'telepon' => ['nullable', 'string', 'max:20'],
            'status' => ['required', Rule::in(['Aktif', 'Pindah', 'Meninggal'])],
            'keluarga_id' => ['nullable', 'exists:keluargas,id'],
            'hubungan_keluarga' => ['nullable', Rule::in(['Kepala Keluarga', 'Istri', 'Anak', 'Famili Lain'])],
        ];
    }

    /** NIK lengkap — hanya untuk peran yang boleh mengubah data (mis. form ubah & pengisian surat). */
    public function nik(Request $request, int $id): JsonResponse
    {
        $untuk = $request->validate(['untuk' => ['required', Rule::in(['ubah', 'surat'])]])['untuk'];
        $warga = Warga::findOrFail($id);
        LogAksesNik::create(['user_id' => $request->user()->id, 'warga_id' => $warga->id, 'keperluan' => $untuk]);

        return response()->json(['data' => ['nik' => $warga->nik]]);
    }

    /** Jejak audit: siapa membuka NIK lengkap siapa, dan untuk apa. */
    public function logNik(Request $request): JsonResponse
    {
        return response()->json(LogAksesNik::with(['user', 'warga'])->latest('id')->paginate(10));
    }

    /** Ringkasan jumlah warga untuk kartu statistik. */
    public function ringkasan(): JsonResponse
    {
        return response()->json(['data' => [
            'total' => Warga::count(),
            'laki_laki' => Warga::where('jenis_kelamin', 'Laki-laki')->count(),
            'perempuan' => Warga::where('jenis_kelamin', 'Perempuan')->count(),
            'aktif' => Warga::where('status', 'Aktif')->count(),
        ]]);
    }
}
