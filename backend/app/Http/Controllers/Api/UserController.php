<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends CrudController
{
    protected string $model = User::class;

    protected string $label = 'pengguna';

    protected array $search = ['name', 'username'];

    protected array $filters = ['role'];

    protected function title(Model $record): string
    {
        return $record->name;
    }

    protected function rules(?Model $record): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'username' => ['required', 'regex:/^[A-Za-z0-9._-]+$/', 'max:50', Rule::unique('users', 'username')->ignore($record?->id)],
            'password' => [$record ? 'nullable' : 'required', 'string', 'min:8'],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'is_active' => ['boolean'],
        ];
    }

    protected function beforeSave(array $data, ?Model $record): array
    {
        if (empty($data['password'])) {
            unset($data['password']);
        }

        return $data;
    }

    public function update(Request $request, int $id): JsonResponse
    {
        if ($id === $request->user()->id && $request->has('is_active') && ! $request->boolean('is_active')) {
            return response()->json(['message' => 'Anda tidak dapat menonaktifkan akun sendiri.'], 422);
        }
        if ($id === $request->user()->id && $request->input('role') !== $request->user()->role) {
            return response()->json(['message' => 'Anda tidak dapat mengubah peran akun sendiri.'], 422);
        }

        return parent::update($request, $id);
    }

    public function destroy(int $id): JsonResponse
    {
        if ($id === request()->user()->id) {
            return response()->json(['message' => 'Anda tidak dapat menghapus akun sendiri.'], 422);
        }

        return parent::destroy($id);
    }
}
