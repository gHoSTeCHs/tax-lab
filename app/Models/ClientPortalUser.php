<?php

namespace App\Models;

use App\Enums\PortalRole;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class ClientPortalUser extends Authenticatable
{
    use HasFactory, HasUlids, Notifiable;

    protected $fillable = [
        'firm_id',
        'email',
        'password',
        'name',
        'role',
        'status',
        'last_login_at',
        'invited_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => PortalRole::class,
            'status' => UserStatus::class,
            'last_login_at' => 'datetime',
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

    public function canInviteOthers(): bool
    {
        return $this->role->canInviteOthers();
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }
}
