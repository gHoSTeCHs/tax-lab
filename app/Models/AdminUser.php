<?php

namespace App\Models;

use App\Enums\AdminRole;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class AdminUser extends Authenticatable
{
    use HasFactory, HasUlids, Notifiable, TwoFactorAuthenticatable;

    protected $fillable = [
        'email',
        'password',
        'name',
        'role',
        'status',
        'password_changed_at',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => AdminRole::class,
            'status' => UserStatus::class,
            'two_factor_confirmed_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    public function hasPermission(string $permission): bool
    {
        return $this->role->hasPermission($permission);
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === AdminRole::SUPER_ADMIN;
    }
}
