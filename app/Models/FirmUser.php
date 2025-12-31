<?php

namespace App\Models;

use App\Enums\FirmRole;
use App\Enums\UserStatus;
use App\Models\Concerns\BelongsToFirm;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class FirmUser extends Authenticatable
{
    use HasFactory, HasUlids, Notifiable, TwoFactorAuthenticatable;

    protected $fillable = [
        'firm_id',
        'email',
        'password',
        'name',
        'role',
        'status',
        'notification_preferences',
        'last_login_at',
        'last_login_ip',
        'invited_by',
        'invited_at',
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
            'role' => FirmRole::class,
            'status' => UserStatus::class,
            'notification_preferences' => 'array',
            'two_factor_confirmed_at' => 'datetime',
            'last_login_at' => 'datetime',
            'invited_at' => 'datetime',
        ];
    }

    public function firm(): BelongsTo
    {
        return $this->belongsTo(Firm::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(FirmUser::class, 'invited_by');
    }

    public function hasFullClientAccess(): bool
    {
        return $this->role->hasFullClientAccess();
    }

    public function canManageUsers(): bool
    {
        return $this->role->canManageUsers();
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }
}
