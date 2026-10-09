<?php

namespace App\Http\Controllers\Api;

use App\Models\Aktivitas;
use App\Models\Pengajuan;
use App\Models\PengajuanLampiran;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PengajuanController extends CrudController
{
    public const LAYANAN = [
        'Surat Pengantar', 'Surat Domisili', 'Surat Keterangan',
        'Surat Keterangan Usaha', 'Surat Pengantar Nikah',
    ];

    protected string $model = Pengajuan::class;

    protected string $label = 'pengajuan';

    protected array $filters = ['status', 'layanan'];

    protected array $with = ['warga:id,nama,nik'];

    protected array $withCount = ['lampirans'];

    protected function title(Model $record): string
    {
        return $record->kode;
    }

    /** Pencarian juga mencakup nama warga. */
    protected function query(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::query($request);

        if ($q = trim((string) $request->query('q', ''))) {
            $query->where(fn ($w) => $w->where('kode', 'like', "%{$q}%")
                ->orWhereHas('warga', fn ($x) => $x->where('nama', 'like', "%{$q}%")));
        }

        return $query;
    }

    protected function rules(?Model $record): array
    {
        return [
            'warga_id' => ['required', 'exists:wargas,id'],
            'layanan' => ['required', Rule::in(self::LAYANAN)],
            'keperluan' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['data' => Pengajuan::with(['warga.keluarga', 'riwayat.user:id,name', 'lampirans'])->findOrFail($id)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules(null));

        $pengajuan = DB::transaction(function () use ($data, $request) {
            $p = Pengajuan::create($data + ['kode' => Pengajuan::nextKode(), 'status' => 'Menunggu Verifikasi']);
            $p->riwayat()->create(['status' => 'Menunggu Verifikasi', 'catatan' => 'Pengajuan dibuat.', 'user_id' => $request->user()->id]);

            return $p;
        });
        Aktivitas::catat("Pengajuan {$pengajuan->kode} dibuat", 'plus');

        return response()->json(['data' => $pengajuan->load('warga:id,nama,nik'), 'message' => "Pengajuan {$pengajuan->kode} berhasil dibuat."], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $pengajuan = Pengajuan::findOrFail($id);
        abort_if(in_array($pengajuan->status, ['Selesai', 'Ditolak']), 422, 'Pengajuan yang sudah selesai atau ditolak tidak dapat diubah.');

        return parent::update($request, $id);
    }

    /** Lampiran bersifat sensitif (KK/KTP) — hanya bisa diambil lewat endpoint ber-login. */
    public function lampiran(int $id, int $lampiranId)
    {
        $l = PengajuanLampiran::where('pengajuan_id', $id)->findOrFail($lampiranId);
        abort_unless(Storage::exists($l->path), 404, 'Berkas tidak tersedia.');

        return Storage::response($l->path, $l->nama, ['Content-Type' => $l->mime, 'X-Content-Type-Options' => 'nosniff']);
    }

    protected function beforeDelete(Model $record): void
    {
        foreach ($record->lampirans as $l) {
            Storage::delete($l->path);
        }
    }

    /** Ubah status mengikuti alur: Menunggu Verifikasi → Diproses → Selesai, atau Ditolak. */
    public function ubahStatus(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['Diproses', 'Selesai', 'Ditolak'])],
            'catatan' => ['nullable', 'string', 'max:500', Rule::requiredIf($request->input('status') === 'Ditolak')],
        ], ['catatan.required' => 'Alasan penolakan wajib diisi.']);

        $pengajuan = Pengajuan::findOrFail($id);

        $alur = [
            'Menunggu Verifikasi' => ['Diproses', 'Ditolak'],
            'Diproses' => ['Selesai', 'Ditolak'],
        ];
        if (! in_array($data['status'], $alur[$pengajuan->status] ?? [], true)) {
            return response()->json(['message' => "Status tidak dapat diubah dari “{$pengajuan->status}” ke “{$data['status']}”."], 422);
        }

        DB::transaction(function () use ($pengajuan, $data, $request) {
            $pengajuan->update(['status' => $data['status'], 'catatan' => $data['catatan'] ?? $pengajuan->catatan]);
            $pengajuan->riwayat()->create(['status' => $data['status'], 'catatan' => $data['catatan'] ?? null, 'user_id' => $request->user()->id]);
        });
        Aktivitas::catat("Pengajuan {$pengajuan->kode} ditandai “{$data['status']}”", $data['status'] === 'Ditolak' ? 'x' : 'check');

        return response()->json([
            'data' => $pengajuan->fresh(['warga:id,nama,nik']),
            'message' => "Status {$pengajuan->kode} diperbarui menjadi {$data['status']}.",
        ]);
    }
}
