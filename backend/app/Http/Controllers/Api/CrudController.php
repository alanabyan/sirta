<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Aktivitas;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Basis CRUD generik: pencarian (?q=), filter kolom, paginasi (?per_page= / ?all=1),
 * serta pencatatan aktivitas sistem untuk setiap perubahan data.
 */
abstract class CrudController extends Controller
{
    /** @var class-string<Model> */
    protected string $model;

    /** Nama entitas untuk log aktivitas, mis. "data warga". */
    protected string $label;

    /** Kolom yang dicari oleh ?q= */
    protected array $search = [];

    /** Kolom yang boleh difilter lewat query string (?status=...) */
    protected array $filters = [];

    protected array $with = [];

    protected array $withCount = [];

    protected string $orderBy = 'id';

    protected string $orderDir = 'desc';

    abstract protected function rules(?Model $record): array;

    /** Kolom yang dipakai untuk menyebut data pada log aktivitas. */
    protected function title(Model $record): string
    {
        return (string) ($record->nama ?? $record->nomor ?? $record->getKey());
    }

    protected function query(Request $request): Builder
    {
        $query = ($this->model)::query()->with($this->with)->withCount($this->withCount);

        if ($q = trim((string) $request->query('q', ''))) {
            $query->where(function (Builder $w) use ($q) {
                foreach ($this->search as $column) {
                    $w->orWhere($column, 'like', "%{$q}%");
                }
            });
        }

        foreach ($this->filters as $column) {
            if ($request->filled($column)) {
                $query->where($column, $request->query($column));
            }
        }

        return $query->orderBy($this->orderBy, $this->orderDir);
    }

    public function index(Request $request): JsonResponse
    {
        $query = $this->query($request);

        if ($request->boolean('all')) {
            return response()->json(['data' => $query->get()]);
        }

        $perPage = min(max((int) $request->query('per_page', 10), 1), 100);

        return response()->json($query->paginate($perPage));
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['data' => $this->find($id)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules(null), $this->messages());
        $record = ($this->model)::create($this->beforeSave($data, null));
        Aktivitas::catat("Menambahkan {$this->label} “{$this->title($record)}”", 'plus');

        return response()->json(['data' => $this->find($record->getKey()), 'message' => 'Data berhasil disimpan.'], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $record = $this->find($id);
        $data = $request->validate($this->rules($record), $this->messages());
        $record->update($this->beforeSave($data, $record));
        Aktivitas::catat("Memperbarui {$this->label} “{$this->title($record)}”", 'edit');

        return response()->json(['data' => $this->find($id), 'message' => 'Data berhasil diperbarui.']);
    }

    public function destroy(int $id): JsonResponse
    {
        $record = $this->find($id);
        $title = $this->title($record);
        $this->beforeDelete($record);
        $record->delete();
        Aktivitas::catat("Menghapus {$this->label} “{$title}”", 'trash');

        return response()->json(['message' => 'Data berhasil dihapus.']);
    }

    protected function find(int $id): Model
    {
        return ($this->model)::query()->with($this->with)->withCount($this->withCount)->findOrFail($id);
    }

    protected function messages(): array
    {
        return [];
    }

    protected function beforeSave(array $data, ?Model $record): array
    {
        return $data;
    }

    protected function beforeDelete(Model $record): void {}
}
