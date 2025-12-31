<?php

namespace App\Models;

use App\Enums\FirmStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Firm extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'plan_id',
        'status',
        'trial_ends_at',
        'email',
        'phone',
        'address',
        'settings',
        'branding',
    ];

    protected function casts(): array
    {
        return [
            'status' => FirmStatus::class,
            'trial_ends_at' => 'datetime',
            'settings' => 'array',
            'branding' => 'array',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(FirmUser::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(FirmInvitation::class);
    }

    public function portalUsers(): HasMany
    {
        return $this->hasMany(ClientPortalUser::class);
    }

    public function canAccessPlatform(): bool
    {
        return $this->status->canAccessPlatform();
    }

    public function isOnTrial(): bool
    {
        return $this->status === FirmStatus::TRIAL &&
               $this->trial_ends_at &&
               $this->trial_ends_at->isFuture();
    }
}
