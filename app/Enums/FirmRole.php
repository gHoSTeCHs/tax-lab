<?php

namespace App\Enums;

enum FirmRole: string
{
    case PARTNER = 'partner';
    case MANAGER = 'manager';
    case ASSOCIATE = 'associate';
    case VIEWER = 'viewer';

    public function label(): string
    {
        return match($this) {
            self::PARTNER => 'Partner',
            self::MANAGER => 'Manager',
            self::ASSOCIATE => 'Associate',
            self::VIEWER => 'Viewer',
        };
    }

    public function hasFullClientAccess(): bool
    {
        return in_array($this, [self::PARTNER, self::MANAGER]);
    }

    public function canManageUsers(): bool
    {
        return $this === self::PARTNER;
    }

    public function canManageBilling(): bool
    {
        return $this === self::PARTNER;
    }

    public function canApproveReports(): bool
    {
        return in_array($this, [self::PARTNER, self::MANAGER]);
    }

    public function isReadOnly(): bool
    {
        return $this === self::VIEWER;
    }
}
