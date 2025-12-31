<?php

namespace App\Enums;

enum FirmStatus: string
{
    case TRIAL = 'trial';
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::TRIAL => 'Trial',
            self::ACTIVE => 'Active',
            self::SUSPENDED => 'Suspended',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function canAccessPlatform(): bool
    {
        return in_array($this, [self::TRIAL, self::ACTIVE]);
    }
}
