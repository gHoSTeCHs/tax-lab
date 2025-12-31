<?php

namespace Database\Seeders;

use App\Enums\AdminRole;
use App\Enums\FirmRole;
use App\Enums\FirmStatus;
use App\Enums\PortalRole;
use App\Enums\UserStatus;
use App\Models\AdminUser;
use App\Models\ClientPortalUser;
use App\Models\Firm;
use App\Models\FirmUser;
use App\Models\Plan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class Module1Seeder extends Seeder
{
    public function run(): void
    {
        $this->createPlans();
        $this->createAdminUsers();
        $this->createFirms();
    }

    private function createPlans(): void
    {
        Plan::create([
            'name' => 'Professional',
            'slug' => 'professional',
            'description' => 'Perfect for small to medium firms',
            'monthly_price' => 4500000,
            'annual_price' => 45000000,
            'client_limit' => 100,
            'user_limit' => 3,
            'report_limit' => 1000,
            'features' => ['cpd_access', 'knowledge_base', 'client_portal'],
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->command->info('✓ Created plans');
    }

    private function createAdminUsers(): void
    {
        AdminUser::create([
            'email' => 'admin@taxlab.ng',
            'password' => 'password',
            'name' => 'Super Admin',
            'role' => AdminRole::SUPER_ADMIN,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->command->info('✓ Created admin users');
        $this->command->warn('   Email: admin@taxlab.ng | Password: password');
    }

    private function createFirms(): void
    {
        $plan = Plan::where('slug', 'professional')->first();

        $firm = Firm::create([
            'name' => 'Test Firm',
            'slug' => 'test-firm',
            'plan_id' => $plan->id,
            'status' => FirmStatus::TRIAL,
            'trial_ends_at' => now()->addDays(14),
            'email' => 'contact@testfirm.ng',
            'settings' => [],
            'branding' => [],
        ]);

        $partner = FirmUser::create([
            'firm_id' => $firm->id,
            'email' => 'partner@testfirm.ng',
            'password' => 'password',
            'name' => 'Test Partner',
            'role' => FirmRole::PARTNER,
            'status' => UserStatus::ACTIVE,
        ]);

        FirmUser::create([
            'firm_id' => $firm->id,
            'email' => 'associate@testfirm.ng',
            'password' => 'password',
            'name' => 'Test Associate',
            'role' => FirmRole::ASSOCIATE,
            'status' => UserStatus::ACTIVE,
            'invited_by' => $partner->id,
        ]);

        ClientPortalUser::create([
            'firm_id' => $firm->id,
            'email' => 'client@company.ng',
            'password' => 'password',
            'name' => 'Test Client',
            'role' => PortalRole::PRIMARY,
            'status' => UserStatus::ACTIVE,
            'invited_by' => $partner->id,
        ]);

        $this->command->info('✓ Created test firm and users');
        $this->command->warn('   Firm User - Email: partner@testfirm.ng | Password: password');
        $this->command->warn('   Firm User - Email: associate@testfirm.ng | Password: password');
        $this->command->warn('   Portal User - Email: client@company.ng | Password: password');
    }
}
