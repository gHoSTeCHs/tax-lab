<?php

namespace App\Enums;

enum PortalRole: string
{
    case PRIMARY = 'primary';
    case VIEWER = 'viewer';

    public function label(): string
    {
        return match($this) {
            self::PRIMARY => 'Primary Contact',
            self::VIEWER => 'Viewer',
        };
    }

    public function canInviteOthers(): bool
    {
        return $this === self::PRIMARY;
    }

    public function canSendMessages(): bool
    {
        return $this === self::PRIMARY;
    }
}
