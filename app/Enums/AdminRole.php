<?php

namespace App\Enums;

enum AdminRole: string
{
    case SUPER_ADMIN = 'super_admin';
    case ADMIN = 'admin';
    case SUPPORT = 'support';
    case CONTENT_MANAGER = 'content_manager';

    public function label(): string
    {
        return match($this) {
            self::SUPER_ADMIN => 'Super Admin',
            self::ADMIN => 'Admin',
            self::SUPPORT => 'Support',
            self::CONTENT_MANAGER => 'Content Manager',
        };
    }

    public function hasPermission(string $permission): bool
    {
        return match($this) {
            self::SUPER_ADMIN => true,
            self::ADMIN => !in_array($permission, [
                'manage_admins',
                'impersonate_users',
                'modify_system_config',
                'publish_tax_rules',
            ]),
            self::SUPPORT => in_array($permission, [
                'view_tenants',
                'view_platform_analytics',
                'view_logs',
            ]),
            self::CONTENT_MANAGER => in_array($permission, [
                'manage_cpd',
                'manage_knowledge_base',
                'view_platform_dashboard',
            ]),
        };
    }
}
