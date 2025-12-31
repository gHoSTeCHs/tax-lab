<?php

namespace App\Models\Concerns;

use App\Models\Firm;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToFirm
{
    public static function bootBelongsToFirm(): void
    {
        static::addGlobalScope('firm', function (Builder $builder) {
            if (auth()->guard('firm')->check()) {
                $builder->where('firm_id', auth()->guard('firm')->user()->firm_id);
            }
        });

        static::creating(function ($model) {
            if (! $model->firm_id && auth()->guard('firm')->check()) {
                $model->firm_id = auth()->guard('firm')->user()->firm_id;
            }
        });
    }

    public function firm(): BelongsTo
    {
        return $this->belongsTo(Firm::class);
    }

    public function scopeForFirm(Builder $query, string $firmId): Builder
    {
        return $query->where('firm_id', $firmId);
    }
}
