<?php

namespace App\Http\Controllers\Api;

use App\Models\Aktivitas;
use App\Models\Arsip;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ArsipController extends CrudController
{
    protected string $model = Arsip::class;

    protected string $label = 'arsip';

    protected array $search = ['nama'];

    protected array $filters = ['kategori'];

    protected array $with = ['user:id,name'];

    public const KATEGORI = ['Surat', 'Kependudukan', 'Keuangan', 'Kegiatan'];

    protected function rules(?Model $record): array
    {
        return [
            'nama' => [$record ? 'required' : 'nullable', 'string', 'max:200'],
            'kategori' => ['required', Rule::in(self::KATEGORI)],
            'file' => ['nullable', 'file', 'max:10240', 'extensions:pdf,doc,docx,xls,xlsx,csv,txt,jpg,jpeg,png,zip', 'mimes:pdf,doc,docx,xls,xlsx,csv,txt,jpg,jpeg,png,zip'],
        ];
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules(null) + ['ukuran' => ['nullable', 'integer', 'min:0']]);
        $file = $request->file('file');

        if (! $file && empty($data['nama'])) {
            return response()->json(['message' => 'Pilih file atau isi nama dokumen.', 'errors' => ['nama' => ['Nama dokumen wajib diisi bila tidak ada file.']]], 422);
        }

        $arsip = Arsip::create([
            'nama' => $data['nama'] ?? $file->getClientOriginalName(),
            'kategori' => $data['kategori'],
            'ukuran' => $file ? $file->getSize() : ($data['ukuran'] ?? 0),
            'path' => $file?->store('arsip'), // disk privat: hanya bisa diunduh lewat endpoint ber-login
            'user_id' => $request->user()->id,
        ]);
        Aktivitas::catat("Mengunggah arsip “{$arsip->nama}”", 'archive');

        return response()->json(['data' => $arsip->load('user:id,name'), 'message' => 'Arsip berhasil ditambahkan.'], 201);
    }

    protected function beforeSave(array $data, ?Model $record): array
    {
        unset($data['file']);

        return $data;
    }

    protected function beforeDelete(Model $record): void
    {
        if ($record->path) {
            Storage::delete($record->path);
        }
    }

    public function unduh(int $id)
    {
        $arsip = Arsip::findOrFail($id);
        abort_unless($arsip->path && Storage::exists($arsip->path), 404, 'Berkas tidak tersedia.');

        return Storage::download($arsip->path, $arsip->nama);
    }

    public function ringkasan(): JsonResponse
    {
        $perKategori = Arsip::selectRaw('kategori, count(*) as total')->groupBy('kategori')->pluck('total', 'kategori');

        return response()->json(['data' => [
            'total' => Arsip::count(),
            'ukuran' => (int) Arsip::sum('ukuran'),
            'per_kategori' => $perKategori,
        ]]);
    }
}
