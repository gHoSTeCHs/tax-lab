# TaxLab Implementation - Authentication System

## Overview

TaxLab uses three separate authentication guards for different user types:

| Guard | Model | Routes | Session Cookie |
|-------|-------|--------|----------------|
| admin | AdminUser | /admin/* | `taxlab_admin_session` |
| firm | FirmUser | /app/* | `taxlab_firm_session` |
| client | ClientPortalUser | /portal/* | `taxlab_portal_session` |

The existing Fortify setup will be adapted for the firm guard, while admin and client guards use custom implementations.

---

## Auth Configuration

**File:** `config/auth.php`

```php
<?php

return [
    'defaults' => [
        'guard' => 'firm',
        'passwords' => 'firm_users',
    ],

    'guards' => [
        'admin' => [
            'driver' => 'session',
            'provider' => 'admin_users',
        ],

        'firm' => [
            'driver' => 'session',
            'provider' => 'firm_users',
        ],

        'client' => [
            'driver' => 'session',
            'provider' => 'client_portal_users',
        ],
    ],

    'providers' => [
        'admin_users' => [
            'driver' => 'eloquent',
            'model' => App\Models\AdminUser::class,
        ],

        'firm_users' => [
            'driver' => 'eloquent',
            'model' => App\Models\FirmUser::class,
        ],

        'client_portal_users' => [
            'driver' => 'eloquent',
            'model' => App\Models\ClientPortalUser::class,
        ],
    ],

    'passwords' => [
        'admin_users' => [
            'provider' => 'admin_users',
            'table' => 'admin_password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],

        'firm_users' => [
            'provider' => 'firm_users',
            'table' => 'firm_password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],

        'client_portal_users' => [
            'provider' => 'client_portal_users',
            'table' => 'portal_password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),
];
```

---

## Session Configuration for Multiple Guards

**File:** `config/session.php` (key additions)

```php
<?php

return [
    'cookie' => env('SESSION_COOKIE', 'taxlab_session'),
];
```

### Guard-Specific Session Cookies

Create session middleware for each guard:

**File:** `app/Http/Middleware/AdminSession.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminSession
{
    public function handle(Request $request, Closure $next): Response
    {
        config(['session.cookie' => 'taxlab_admin_session']);

        return $next($request);
    }
}
```

**File:** `app/Http/Middleware/PortalSession.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PortalSession
{
    public function handle(Request $request, Closure $next): Response
    {
        config(['session.cookie' => 'taxlab_portal_session']);

        return $next($request);
    }
}
```

---

## Password Reset Token Tables

**File:** `database/migrations/xxxx_xx_xx_000080_create_password_reset_tables.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('firm_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('portal_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_password_reset_tokens');
        Schema::dropIfExists('firm_password_reset_tokens');
        Schema::dropIfExists('portal_password_reset_tokens');
    }
};
```

---

## Fortify Configuration for Firm Guard

**File:** `config/fortify.php`

```php
<?php

use Laravel\Fortify\Features;

return [
    'guard' => 'firm',

    'middleware' => ['web'],

    'auth_middleware' => 'auth',

    'passwords' => 'firm_users',

    'username' => 'email',

    'email' => 'email',

    'views' => true,

    'home' => '/app/dashboard',

    'prefix' => 'app',

    'domain' => null,

    'lowercase_usernames' => true,

    'limiters' => [
        'login' => 'login',
        'two-factor' => 'two-factor',
    ],

    'redirects' => [
        'login' => '/app/dashboard',
        'logout' => '/app/login',
        'password-confirmation' => '/app/dashboard',
        'register' => '/app/dashboard',
        'email-verification' => '/app/dashboard',
        'password-reset' => '/app/login',
    ],

    'features' => [
        Features::registration(),
        Features::resetPasswords(),
        Features::emailVerification(),
        Features::updateProfileInformation(),
        Features::updatePasswords(),
        Features::twoFactorAuthentication([
            'confirm' => true,
            'confirmPassword' => true,
        ]),
    ],
];
```

---

## Admin Authentication

### Admin Login Controller

**File:** `app/Http/Controllers/Admin/Auth/LoginController.php`

```php
<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Admin/Auth/Login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
```

### Admin Login Request

**File:** `app/Http/Requests/Admin/Auth/LoginRequest.php`

```php
<?php

namespace App\Http\Requests\Admin\Auth;

use App\Models\AdminUser;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (!Auth::guard('admin')->attempt(
            $this->only('email', 'password'),
            $this->boolean('remember')
        )) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $user = Auth::guard('admin')->user();

        if (!$user->isActive()) {
            Auth::guard('admin')->logout();

            throw ValidationException::withMessages([
                'email' => 'Your account has been deactivated.',
            ]);
        }

        if (!$user->two_factor_confirmed_at) {
            Auth::guard('admin')->logout();

            throw ValidationException::withMessages([
                'email' => 'Two-factor authentication must be enabled for admin accounts.',
            ]);
        }

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $this->ip(),
        ]);

        RateLimiter::clear($this->throttleKey());
    }

    public function ensureIsNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(
            Str::lower($this->string('email')) . '|' . $this->ip() . '|admin'
        );
    }
}
```

---

## Portal Authentication

### Portal Login Controller

**File:** `app/Http/Controllers/Portal/Auth/LoginController.php`

```php
<?php

namespace App\Http\Controllers\Portal\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Portal/Auth/Login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route('portal.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('client')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login');
    }
}
```

### Portal Login Request

**File:** `app/Http/Requests/Portal/Auth/LoginRequest.php`

```php
<?php

namespace App\Http\Requests\Portal\Auth;

use App\Models\ClientPortalUser;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (!Auth::guard('client')->attempt(
            $this->only('email', 'password'),
            $this->boolean('remember')
        )) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $user = Auth::guard('client')->user();

        if ($user->status !== 'active') {
            Auth::guard('client')->logout();

            throw ValidationException::withMessages([
                'email' => 'Your portal access has been deactivated.',
            ]);
        }

        if (!$user->firm->isActive() && !$user->firm->isOnTrial()) {
            Auth::guard('client')->logout();

            throw ValidationException::withMessages([
                'email' => 'The firm managing your account is currently inactive.',
            ]);
        }

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $this->ip(),
        ]);

        RateLimiter::clear($this->throttleKey());
    }

    public function ensureIsNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(
            Str::lower($this->string('email')) . '|' . $this->ip() . '|portal'
        );
    }
}
```

---

## Route Definitions

### Admin Routes

**File:** `routes/admin.php`

```php
<?php

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest:admin')->group(function () {
        Route::get('login', [LoginController::class, 'create'])->name('login');
        Route::post('login', [LoginController::class, 'store']);
    });

    Route::middleware(['auth:admin'])->group(function () {
        Route::post('logout', [LoginController::class, 'destroy'])->name('logout');
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    });
});
```

### Firm Routes (App)

**File:** `routes/app.php`

```php
<?php

use Illuminate\Support\Facades\Route;

Route::prefix('app')->name('app.')->group(function () {
    Route::middleware(['auth:firm', 'verified', 'tenant'])->group(function () {
        Route::get('dashboard', function () {
            return inertia('App/Dashboard');
        })->name('dashboard');
    });
});
```

### Portal Routes

**File:** `routes/portal.php`

```php
<?php

use App\Http\Controllers\Portal\Auth\LoginController;
use App\Http\Controllers\Portal\DashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('portal')->name('portal.')->group(function () {
    Route::middleware('guest:client')->group(function () {
        Route::get('login', [LoginController::class, 'create'])->name('login');
        Route::post('login', [LoginController::class, 'store']);
    });

    Route::middleware(['auth:client'])->group(function () {
        Route::post('logout', [LoginController::class, 'destroy'])->name('logout');
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    });
});
```

### Register Routes in RouteServiceProvider

**File:** `routes/web.php`

```php
<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return inertia('Welcome');
})->name('home');

require __DIR__.'/admin.php';
require __DIR__.'/app.php';
require __DIR__.'/portal.php';
require __DIR__.'/settings.php';
```

---

## Middleware Registration

**File:** `bootstrap/app.php`

```php
<?php

use App\Http\Middleware\AdminSession;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\PortalSession;
use App\Http\Middleware\ResolveTenant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
        ]);

        $middleware->alias([
            'tenant' => ResolveTenant::class,
            'admin.session' => AdminSession::class,
            'portal.session' => PortalSession::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

---

## Inertia Request Handler Updates

**File:** `app/Http/Middleware/HandleInertiaRequests.php`

```php
<?php

namespace App\Http\Middleware;

use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'name' => config('app.name'),
            'auth' => $this->getAuthData($request),
            'firm' => $this->getFirmData(),
            'sidebarOpen' => $request->cookie('sidebar_state') === 'true',
        ]);
    }

    protected function getAuthData(Request $request): array
    {
        $guards = ['admin', 'firm', 'client'];

        foreach ($guards as $guard) {
            if ($request->user($guard)) {
                return [
                    'user' => $request->user($guard),
                    'guard' => $guard,
                ];
            }
        }

        return [
            'user' => null,
            'guard' => null,
        ];
    }

    protected function getFirmData(): ?array
    {
        if (!TenantContext::check()) {
            return null;
        }

        $firm = TenantContext::get();

        return [
            'id' => $firm->id,
            'name' => $firm->name,
            'status' => $firm->status,
        ];
    }
}
```

---

## Role-Based Permissions

### Firm User Roles

| Role | Permissions |
|------|-------------|
| partner | Full access, team management, billing |
| manager | Manage clients, approve reports, manage associates |
| associate | Create/edit clients, calculations, reports |
| viewer | Read-only access |

### Role Enum

**File:** `app/Enums/FirmRole.php`

```php
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
        return match ($this) {
            self::PARTNER => 'Partner / Owner',
            self::MANAGER => 'Manager',
            self::ASSOCIATE => 'Associate',
            self::VIEWER => 'Viewer',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::PARTNER => 'The highest authority within a firm. Full control over firm operations, settings, billing, and all resources. Can invite and manage all users, access all tax clients, and delete firm account.',
            self::MANAGER => 'Senior staff responsible for overseeing work and team members. Manages assigned team members, accesses all tax clients, generates and approves reports. Cannot manage firm settings or billing.',
            self::ASSOCIATE => 'Day-to-day practitioners doing analysis work. Accesses assigned clients only, runs calculations and scenarios, generates reports (may require approval). Cannot access unassigned clients or manage users.',
            self::VIEWER => 'Read-only access for review purposes (interns, external auditors). Can only view assigned clients, calculations, and reports. Cannot create, edit, or delete anything.',
        };
    }

    public function level(): int
    {
        return match ($this) {
            self::PARTNER => 100,
            self::MANAGER => 80,
            self::ASSOCIATE => 60,
            self::VIEWER => 40,
        };
    }

    public function permissions(): array
    {
        return match ($this) {
            self::PARTNER => [
                'view_assigned_clients',
                'view_all_clients',
                'create_clients',
                'edit_own_clients',
                'edit_any_client',
                'archive_clients',
                'delete_clients',
                'import_clients',
                'export_client_data',
                'view_financials',
                'enter_financials',
                'edit_financials',
                'view_calculations',
                'run_calculations',
                'view_scenarios',
                'create_scenarios',
                'edit_scenarios',
                'delete_scenarios',
                'view_reports',
                'generate_reports',
                'approve_reports',
                'delete_reports',
                'view_optimizations',
                'update_optimization_status',
                'view_firm_settings',
                'edit_firm_settings',
                'manage_branding',
                'view_all_users',
                'invite_users',
                'edit_user_roles',
                'suspend_users',
                'delete_users',
                'manage_assignments',
                'view_billing',
                'manage_subscription',
                'update_payment',
                'view_firm_analytics',
                'view_personal_stats',
                'access_cpd_courses',
                'access_knowledge_base',
                'enable_portal_access',
                'manage_portal_users',
            ],
            self::MANAGER => [
                'view_assigned_clients',
                'view_all_clients',
                'create_clients',
                'edit_own_clients',
                'edit_any_client',
                'archive_clients',
                'delete_clients',
                'import_clients',
                'export_client_data',
                'view_financials',
                'enter_financials',
                'edit_financials',
                'view_calculations',
                'run_calculations',
                'view_scenarios',
                'create_scenarios',
                'edit_scenarios',
                'delete_scenarios',
                'view_reports',
                'generate_reports',
                'approve_reports',
                'delete_reports',
                'view_optimizations',
                'update_optimization_status',
                'view_all_users',
                'manage_assignments',
                'view_firm_analytics',
                'view_personal_stats',
                'access_cpd_courses',
                'access_knowledge_base',
                'enable_portal_access',
                'manage_portal_users',
            ],
            self::ASSOCIATE => [
                'view_assigned_clients',
                'create_clients',
                'edit_own_clients',
                'archive_own_clients',
                'export_assigned_client_data',
                'view_assigned_financials',
                'enter_assigned_financials',
                'edit_assigned_financials',
                'view_assigned_calculations',
                'run_assigned_calculations',
                'view_assigned_scenarios',
                'create_scenarios',
                'edit_own_scenarios',
                'delete_own_scenarios',
                'view_assigned_reports',
                'generate_reports',
                'view_assigned_optimizations',
                'update_assigned_optimization_status',
                'view_personal_stats',
                'access_cpd_courses',
                'access_knowledge_base',
            ],
            self::VIEWER => [
                'view_assigned_clients',
                'view_assigned_financials',
                'view_assigned_calculations',
                'view_assigned_scenarios',
                'view_assigned_reports',
                'view_assigned_optimizations',
                'view_personal_stats',
                'access_cpd_courses',
                'access_knowledge_base',
            ],
        };
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions());
    }

    public function canManageTeam(): bool
    {
        return $this === self::PARTNER;
    }

    public function canApproveReports(): bool
    {
        return match ($this) {
            self::PARTNER, self::MANAGER => true,
            default => false,
        };
    }

    public function canManageClients(): bool
    {
        return match ($this) {
            self::PARTNER, self::MANAGER, self::ASSOCIATE => true,
            default => false,
        };
    }

    public function canViewBilling(): bool
    {
        return $this === self::PARTNER;
    }

    public function canViewAllClients(): bool
    {
        return match ($this) {
            self::PARTNER, self::MANAGER => true,
            default => false,
        };
    }

    public function requiresClientAssignment(): bool
    {
        return match ($this) {
            self::ASSOCIATE, self::VIEWER => true,
            default => false,
        };
    }

    public function canModifyData(): bool
    {
        return match ($this) {
            self::PARTNER, self::MANAGER, self::ASSOCIATE => true,
            self::VIEWER => false,
        };
    }

    public static function managementRoles(): array
    {
        return [
            self::PARTNER,
            self::MANAGER,
        ];
    }

    public static function rolesRequiringAssignment(): array
    {
        return [
            self::ASSOCIATE,
            self::VIEWER,
        ];
    }

    public static function forSelect(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($role) => [$role->value => $role->label()])
            ->toArray();
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

### Admin Role Enum

**File:** `app/Enums/AdminRole.php`

```php
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
        return match ($this) {
            self::SUPER_ADMIN => 'Super Admin',
            self::ADMIN => 'Admin',
            self::SUPPORT => 'Support',
            self::CONTENT_MANAGER => 'Content Manager',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Full system access without restrictions. Manages other admin users, accesses all tenant data, modifies system configuration, manages billing and subscriptions, publishes tax rules, and can impersonate firm users.',
            self::ADMIN => 'Manages tenant accounts and can view tenant data for support purposes. Manages CPD content, views platform analytics. Cannot modify system configuration, manage other admin accounts, or impersonate users.',
            self::SUPPORT => 'View-only access to tenant data. Accesses support tools, views logs and activity. All actions logged for audit. Cannot modify any data.',
            self::CONTENT_MANAGER => 'Manages CPD courses and modules, knowledge base articles. Uploads and organizes content. Cannot access tenant data or admin settings.',
        };
    }

    public function level(): int
    {
        return match ($this) {
            self::SUPER_ADMIN => 100,
            self::ADMIN => 80,
            self::SUPPORT => 60,
            self::CONTENT_MANAGER => 50,
        };
    }

    public function permissions(): array
    {
        return match ($this) {
            self::SUPER_ADMIN => [
                'view_platform_dashboard',
                'manage_admin_users',
                'view_tenant_list',
                'view_tenant_details',
                'modify_tenant_settings',
                'suspend_activate_tenant',
                'impersonate_firm_user',
                'manage_subscription_plans',
                'configure_pricing',
                'manage_tax_legislation',
                'publish_tax_rules',
                'manage_cpd_courses',
                'manage_knowledge_base',
                'view_platform_analytics',
                'modify_system_settings',
                'view_audit_logs',
                'export_data',
            ],
            self::ADMIN => [
                'view_platform_dashboard',
                'view_tenant_list',
                'view_tenant_details',
                'modify_tenant_settings',
                'suspend_activate_tenant',
                'manage_tax_legislation',
                'manage_cpd_courses',
                'manage_knowledge_base',
                'view_platform_analytics',
                'view_audit_logs',
                'export_data',
            ],
            self::SUPPORT => [
                'view_platform_dashboard',
                'view_tenant_list',
                'view_tenant_details',
                'view_platform_analytics',
                'view_audit_logs',
            ],
            self::CONTENT_MANAGER => [
                'view_platform_dashboard',
                'manage_cpd_courses',
                'manage_knowledge_base',
            ],
        };
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions());
    }

    public function isSuperAdmin(): bool
    {
        return $this === self::SUPER_ADMIN;
    }

    public function canAccessTenants(): bool
    {
        return match ($this) {
            self::SUPER_ADMIN, self::ADMIN, self::SUPPORT => true,
            self::CONTENT_MANAGER => false,
        };
    }

    public function canModifyData(): bool
    {
        return match ($this) {
            self::SUPER_ADMIN, self::ADMIN, self::CONTENT_MANAGER => true,
            self::SUPPORT => false,
        };
    }

    public static function forSelect(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($role) => [$role->value => $role->label()])
            ->toArray();
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

### Portal Role Enum

**File:** `app/Enums/PortalRole.php`

```php
<?php

namespace App\Enums;

enum PortalRole: string
{
    case PRIMARY = 'primary';
    case VIEWER = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::PRIMARY => 'Primary Contact',
            self::VIEWER => 'Viewer',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::PRIMARY => 'Main representative for the tax client. Can view all reports and calculations, invite additional portal viewers, download documents, and send messages to the firm.',
            self::VIEWER => 'Read-only access to tax position. Can view reports shared with them. Cannot invite others or send messages. Useful for junior staff at client company.',
        };
    }

    public function level(): int
    {
        return match ($this) {
            self::PRIMARY => 100,
            self::VIEWER => 50,
        };
    }

    public function permissions(): array
    {
        return match ($this) {
            self::PRIMARY => [
                'view_tax_position_summary',
                'view_reports',
                'download_reports',
                'view_shared_scenarios',
                'view_optimization_recommendations',
                'send_messages_to_firm',
                'invite_viewers',
                'manage_portal_users',
            ],
            self::VIEWER => [
                'view_tax_position_summary',
                'view_reports',
                'download_reports',
                'view_shared_scenarios',
                'view_optimization_recommendations',
            ],
        };
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions());
    }

    public function canInviteOthers(): bool
    {
        return $this === self::PRIMARY;
    }

    public function canSendMessages(): bool
    {
        return $this === self::PRIMARY;
    }

    public static function forSelect(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($role) => [$role->value => $role->label()])
            ->toArray();
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

---

## Testing Authentication

### Test Helpers

**File:** `tests/Traits/AuthenticatesUsers.php`

```php
<?php

namespace Tests\Traits;

use App\Models\AdminUser;
use App\Models\ClientPortalUser;
use App\Models\Firm;
use App\Models\FirmUser;

trait AuthenticatesUsers
{
    protected function actingAsAdmin(?AdminUser $admin = null): static
    {
        $admin ??= AdminUser::factory()->create([
            'status' => 'active',
            'two_factor_confirmed_at' => now(),
        ]);

        return $this->actingAs($admin, 'admin');
    }

    protected function actingAsFirmUser(?FirmUser $user = null, ?Firm $firm = null): static
    {
        $firm ??= Firm::factory()->create(['status' => 'active']);
        $user ??= FirmUser::factory()->for($firm)->create(['status' => 'active']);

        return $this->actingAs($user, 'firm');
    }

    protected function actingAsPortalUser(?ClientPortalUser $user = null): static
    {
        $user ??= ClientPortalUser::factory()->create(['status' => 'active']);

        return $this->actingAs($user, 'client');
    }
}
```

### Authentication Test

```php
<?php

namespace Tests\Feature\Auth;

use App\Models\AdminUser;
use App\Models\Firm;
use App\Models\FirmUser;
use Tests\TestCase;

class MultiGuardAuthTest extends TestCase
{
    public function test_admin_can_login(): void
    {
        $admin = AdminUser::factory()->create([
            'status' => 'active',
            'two_factor_confirmed_at' => now(),
        ]);

        $response = $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($admin, 'admin');
        $response->assertRedirect('/admin/dashboard');
    }

    public function test_firm_user_can_login(): void
    {
        $firm = Firm::factory()->create(['status' => 'active']);
        $user = FirmUser::factory()->for($firm)->create(['status' => 'active']);

        $response = $this->post('/app/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user, 'firm');
    }

    public function test_guards_are_isolated(): void
    {
        $admin = AdminUser::factory()->create([
            'status' => 'active',
            'two_factor_confirmed_at' => now(),
        ]);

        $this->actingAs($admin, 'admin');

        $this->assertAuthenticated('admin');
        $this->assertGuest('firm');
        $this->assertGuest('client');
    }
}
```

---

## Next Steps

Once authentication is implemented, proceed to:
→ **05_SERVICE_LAYER.md** - Implement service layer patterns
