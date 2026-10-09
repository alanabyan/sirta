<?php

namespace App\Http\Controllers\Api;

use App\Models\SuratKeluar;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SuratKeluarController extends CrudController
{
    protected string $model = SuratKeluar::class;

    protected string $label = 'surat keluar';

    protected array $search = ['nomor', 'tujuan', 'perihal'];

    protected array $filters = ['status'];

    protected array $with = ['template:id,nama'];

    protected string $orderBy = 'tanggal';

    protected function rules(?Model $record): array
    {
        return [
            'nomor' => ['nullable', 'string', 'max:80', Rule::unique('surat_keluars', 'nomor')->ignore($record?->id)],
            'tanggal' => ['required', 'date'],
            'tujuan' => ['required', 'string', 'max:150'],
            'perihal' => ['required', 'string', 'max:255'],
            'isi' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['Draft', 'Diterbitkan'])],
            'template_surat_id' => ['nullable', 'exists:template_surats,id'],
        ];
    }

    protected function beforeSave(array $data, ?Model $record): array
    {
        $berhak = in_array(auth()->user()?->role, ['administrator', 'ketua_rt', 'sekretaris'], true);
        if (! $berhak && ($data['status'] === 'Diterbitkan' || $record?->status === 'Diterbitkan')) {
            abort(403, 'Hanya Ketua RT, Sekretaris, atau Administrator yang dapat menerbitkan atau mengubah surat yang sudah terbit.');
        }

        // {{nomor}}, {{tanggal}}, dan {{ttd}} terisi saat cetak; sisanya harus sudah diisi sebelum terbit.
        if ($data['status'] === 'Diterbitkan' && preg_match_all('/\{\{(?!nomor\}\}|tanggal\}\}|ttd\}\})(\w+)\}\}/', $data['isi'] ?? '', $m)) {
            $sisa = implode(', ', array_map(fn ($k) => "{{{$k}}}", array_unique($m[1])));
            throw ValidationException::withMessages(['isi' => "Surat belum bisa diterbitkan: masih ada isian kosong ($sisa). Pilih warga atau isi manual."]);
        }

        if (empty($data['nomor'])) {
            $data['nomor'] = $record?->nomor ?? $this->nomorBerikutnya($data['tanggal']);
        }

        return $data;
    }

    /** Format: SK-042/RT03/X/2026 — urut per tahun. */
    private function nomorBerikutnya(string $tanggal): string
    {
        $date = \Illuminate\Support\Carbon::parse($tanggal);
        $roman = ['', 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][$date->month];
        $urut = SuratKeluar::whereYear('tanggal', $date->year)->pluck('nomor')->map(fn ($n) => (int) preg_replace('/^SK-0*(\d+).*$/', '$1', $n))->max() + 1;

        do {
            $nomor = sprintf('SK-%03d/RT03/%s/%d', $urut++, $roman, $date->year);
        } while (SuratKeluar::where('nomor', $nomor)->exists());

        return $nomor;
    }
}
