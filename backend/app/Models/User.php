<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    public const ROLES = [
        'administrator' => 'Administrator',
        'ketua_rt' => 'Ketua RT',
        'sekretaris' => 'Sekretaris',
        'bendahara' => 'Bendahara',
        'operator' => 'Operator',
    ];

    protected $guarded = [];

    protected $hidden = ['password', 'remember_token'];

    protected $appends = ['role_label'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function getRoleLabelAttribute(): ?string
    {
        return self::ROLES[$this->role] ?? $this->role;
    }
}
