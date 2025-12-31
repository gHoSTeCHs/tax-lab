# TaxLab Implementation - Project Setup

## Overview

This document covers the remaining configuration needed to prepare the existing Laravel 12 project for multi-tenant operation.

## Current Configuration

The project currently uses:
- **Database**: SQLite (development convenience)
- **Cache**: Database driver
- **Session**: Database driver
- **Queue**: Database driver

## Target Configuration

| Service | Driver | Purpose |
|---------|--------|---------|
| Database | MySQL 8.x | Primary data storage |
| Cache | Database | Application caching |
| Session | Database | Session management |
| Queue | Database | Background job processing |

> **Note:** Redis can be added later for improved performance. The database driver is sufficient for initial development and smaller deployments.

---

## Step 1: MySQL Configuration

### 1.1 Create Database

```sql
CREATE DATABASE taxlab CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'taxlab'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON taxlab.* TO 'taxlab'@'localhost';
FLUSH PRIVILEGES;
```

### 1.2 Update Environment Variables

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=taxlab
DB_USERNAME=taxlab
DB_PASSWORD=your_secure_password
```

### 1.3 MySQL-Specific Configuration

Update `config/database.php` if needed:

```php
'mysql' => [
    'driver' => 'mysql',
    'url' => env('DB_URL'),
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'database' => env('DB_DATABASE', 'taxlab'),
    'username' => env('DB_USERNAME', 'taxlab'),
    'password' => env('DB_PASSWORD', ''),
    'unix_socket' => env('DB_SOCKET', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'prefix_indexes' => true,
    'strict' => true,
    'engine' => 'InnoDB',
    'options' => extension_loaded('pdo_mysql') ? array_filter([
        PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
        PDO::ATTR_EMULATE_PREPARES => true,
    ]) : [],
],
```

---

## Step 2: Cache Configuration (Database Driver)

### 2.1 Create Cache Table

The cache table migration should already exist. If not:

```bash
php artisan make:cache-table
php artisan migrate
```

### 2.2 Update Environment Variables

```env
CACHE_STORE=database
```

### 2.3 Cache Configuration

Update `config/cache.php` if needed:

```php
<?php

return [
    'default' => env('CACHE_STORE', 'database'),

    'stores' => [
        'database' => [
            'driver' => 'database',
            'connection' => env('DB_CONNECTION', 'mysql'),
            'table' => env('CACHE_TABLE', 'cache'),
            'lock_connection' => env('CACHE_LOCK_CONNECTION'),
            'lock_table' => env('CACHE_LOCK_TABLE'),
        ],

        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
            'lock_path' => storage_path('framework/cache/data'),
        ],

        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],
    ],

    'prefix' => env('CACHE_PREFIX', 'taxlab_cache_'),
];
```

---

## Step 3: Session Configuration (Database Driver)

### 3.1 Ensure Sessions Table Exists

The sessions table should already exist from the default Laravel setup. Verify it exists:

```bash
php artisan migrate:status
```

### 3.2 Update Session Config

Update `config/session.php`:

```php
<?php

return [
    'driver' => env('SESSION_DRIVER', 'database'),

    'lifetime' => env('SESSION_LIFETIME', 120),

    'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', false),

    'encrypt' => env('SESSION_ENCRYPT', false),

    'files' => storage_path('framework/sessions'),

    'connection' => env('SESSION_CONNECTION'),

    'table' => env('SESSION_TABLE', 'sessions'),

    'store' => env('SESSION_STORE'),

    'lottery' => [2, 100],

    'cookie' => env('SESSION_COOKIE', 'taxlab_session'),

    'path' => env('SESSION_PATH', '/'),

    'domain' => env('SESSION_DOMAIN'),

    'secure' => env('SESSION_SECURE_COOKIE', null),

    'http_only' => env('SESSION_HTTP_ONLY', true),

    'same_site' => env('SESSION_SAME_SITE', 'lax'),

    'partitioned' => env('SESSION_PARTITIONED_COOKIE', false),
];
```

---

## Step 4: Queue Configuration (Database Driver)

### 4.1 Create Queue Tables

```bash
php artisan make:queue-table
php artisan make:queue-batches-table
php artisan make:queue-failed-table
php artisan migrate
```

### 4.2 Update Queue Config

Update `config/queue.php`:

```php
<?php

return [
    'default' => env('QUEUE_CONNECTION', 'database'),

    'connections' => [
        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver' => 'database',
            'connection' => env('DB_CONNECTION', 'mysql'),
            'table' => env('QUEUE_TABLE', 'jobs'),
            'queue' => env('QUEUE_NAME', 'default'),
            'retry_after' => env('QUEUE_RETRY_AFTER', 90),
            'after_commit' => false,
        ],
    ],

    'batching' => [
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => 'job_batches',
    ],

    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => 'failed_jobs',
    ],
];
```

### 4.3 Running Queue Workers

For development:
```bash
php artisan queue:listen --tries=3
```

For production, use a process manager like Supervisor:

**File:** `/etc/supervisor/conf.d/taxlab-worker.conf`

```ini
[program:taxlab-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/taxlab/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/taxlab/storage/logs/worker.log
stopwaitsecs=3600
```

---

## Step 5: Additional Packages

### 5.1 Spatie Laravel Permission

```bash
composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

Update the published migration to use different tables per guard (see 04_AUTHENTICATION.md).

### 5.2 ULID Support

Laravel 12 has built-in ULID support. Configure models to use ULIDs:

```php
<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Concerns\HasUlids;

trait HasUlid
{
    use HasUlids;

    public function uniqueIds(): array
    {
        return ['id'];
    }
}
```

---

## Step 6: Environment Files

### 6.1 Development (.env)

```env
APP_NAME=TaxLab
APP_ENV=local
APP_KEY=base64:your_generated_key
APP_DEBUG=true
APP_TIMEZONE=Africa/Lagos
APP_URL=http://taxlab.test

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_NG

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=taxlab
DB_USERNAME=taxlab
DB_PASSWORD=your_password

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

CACHE_STORE=database

MAIL_MAILER=log
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@taxlab.ng"
MAIL_FROM_NAME="${APP_NAME}"

VITE_APP_NAME="${APP_NAME}"
```

### 6.2 Production (.env.production)

```env
APP_ENV=production
APP_DEBUG=false

SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=strict

# Add production-specific values
```

---

## Step 7: Composer Scripts Update

Update `composer.json` scripts:

```json
{
    "scripts": {
        "post-autoload-dump": [
            "Illuminate\\Foundation\\ComposerScripts::postAutoloadDump",
            "@php artisan package:discover --ansi"
        ],
        "post-update-cmd": [
            "@php artisan vendor:publish --tag=laravel-assets --ansi --force"
        ],
        "post-root-package-install": [
            "@php -r \"file_exists('.env') || copy('.env.example', '.env');\""
        ],
        "post-create-project-cmd": [
            "@php artisan key:generate --ansi",
            "@php -r \"file_exists('database/database.sqlite') || touch('database/database.sqlite');\"",
            "@php artisan migrate --graceful --ansi"
        ],
        "setup": [
            "@composer install",
            "@php artisan key:generate --ansi",
            "@php artisan migrate --ansi",
            "npm install",
            "npm run build"
        ],
        "dev": [
            "Composer\\Config::disableProcessTimeout",
            "npx concurrently -k -c \"#93c5fd,#c4b5fd,#86efac\" \"php artisan serve\" \"php artisan queue:listen --tries=1\" \"npm run dev\" --names=server,queue,vite"
        ],
        "test": "php artisan test",
        "lint": "php ./vendor/bin/pint"
    }
}
```

---

## Future: Redis Migration

When ready to migrate to Redis for improved performance:

### Install Redis

```bash
# Install PHP Redis extension
sudo apt install php-redis

# Or use Predis
composer require predis/predis
```

### Update Environment

```env
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### Add Laravel Horizon (Optional)

```bash
composer require laravel/horizon
php artisan horizon:install
```

---

## Verification Checklist

After completing setup:

- [ ] `php artisan migrate` runs without errors
- [ ] `php artisan tinker` can query the database
- [ ] Cache works: `php artisan tinker` → `Cache::put('test', 'works', 60); Cache::get('test');`
- [ ] Queue processing works: `php artisan queue:work --once`
- [ ] Sessions persist correctly between requests

---

## Next Steps

Once project setup is complete, proceed to:
→ **02_DATABASE_SCHEMA.md** - Create multi-tenant database migrations
