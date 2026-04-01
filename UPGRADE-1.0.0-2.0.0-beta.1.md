# UPGRADE GUIDE: UnoPim `v1.0.0` -> `v2.0.0-beta.1`

> This guide helps you safely upgrade your UnoPim installation from `v1.0.0` to `v2.0.0-beta.1`. You can follow the **manual** steps or use the **automated upgrade script**.

> **This is a major upgrade.** UnoPim v2.0.0-beta.1 upgrades the underlying framework from Laravel 10 to Laravel 12 and introduces significant new features including AI Agent Chat and multi-platform MagicAI support. Please read this guide carefully before proceeding.

---

## Prerequisites

Before upgrading, ensure your environment meets the new requirements:

| Requirement | v1.0.0 | v2.0.0-beta.1 |
|---|---|---|
| PHP | >= 8.2 | **>= 8.3** |
| MySQL | >= 8.0 | >= 8.0 |
| Node.js | >= 18 | **>= 20** |
| Composer | >= 2.0 | >= 2.0 |

### Required PHP Extensions (unchanged)
`calendar`, `curl`, `intl`, `mbstring`, `openssl`, `pdo`, `pdo_mysql`, `tokenizer`

---

## High Impact Changes

### 1. Laravel 12 Framework Upgrade

UnoPim v2.0.0-beta.1 upgrades from Laravel 10 to Laravel 12. This is the most significant change and affects the application's bootstrap architecture.

#### Removed Files

The following files have been **removed** in Laravel 12. If you have customized any of these, you must migrate your customizations:

| Removed File | Replacement |
|---|---|
| `app/Console/Kernel.php` | Scheduling now in `bootstrap/app.php` via `withSchedule()` |
| `app/Http/Kernel.php` | Middleware now in `bootstrap/app.php` via `withMiddleware()` |
| `app/Exceptions/Handler.php` | Exception handling now in `bootstrap/app.php` via `withExceptions()` |
| `app/Http/Middleware/EncryptCookies.php` | Configured in `bootstrap/app.php` |
| `app/Http/Middleware/TrimStrings.php` | Configured in `bootstrap/app.php` |
| `app/Http/Middleware/TrustProxies.php` | Configured in `bootstrap/app.php` |
| `app/Http/Middleware/VerifyCsrfToken.php` | Handled by framework defaults |
| `app/Http/Middleware/RedirectIfAuthenticated.php` | Handled by framework defaults |
| `app/Http/Middleware/TrustHosts.php` | Handled by framework defaults |
| `app/Providers/AuthServiceProvider.php` | Consolidated into `AppServiceProvider` |
| `app/Providers/BroadcastServiceProvider.php` | Consolidated into `AppServiceProvider` |
| `app/Providers/EventServiceProvider.php` | Consolidated into `AppServiceProvider` |
| `app/Providers/RouteServiceProvider.php` | Routing now in `bootstrap/app.php` via `withRouting()` |

#### New Files

| New File | Purpose |
|---|---|
| `bootstrap/providers.php` | Centralized service provider registration |
| `config/ai.php` | Laravel AI SDK provider configuration |

#### If You Have Custom Middleware

If you registered custom middleware in `app/Http/Kernel.php`, move them to `bootstrap/app.php`:

**Before (v1.0.0):**
```php
// app/Http/Kernel.php
protected $middleware = [
    \App\Http\Middleware\YourCustomMiddleware::class,
];
```

**After (v2.0.0-beta.1):**
```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->append([
        \App\Http\Middleware\YourCustomMiddleware::class,
    ]);
})
```

#### If You Have Custom Scheduled Commands

If you added custom scheduled commands in `app/Console/Kernel.php`, move them to `bootstrap/app.php`:

**Before (v1.0.0):**
```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->command('your:command')->daily();
}
```

**After (v2.0.0-beta.1):**
```php
// bootstrap/app.php
->withSchedule(function (Schedule $schedule) {
    $schedule->command('your:command')->daily();
})
```

#### If You Have Custom Service Providers

If you registered custom service providers in `config/app.php`, move them to `bootstrap/providers.php`:

**Before (v1.0.0):**
```php
// config/app.php
'providers' => [
    // ...
    App\Providers\YourCustomProvider::class,
];
```

**After (v2.0.0-beta.1):**
```php
// bootstrap/providers.php
return [
    // ... existing providers
    App\Providers\YourCustomProvider::class,
];
```

### 2. MagicAI Provider Architecture Change

Individual AI provider service classes have been replaced with a unified adapter pattern.

#### Removed Classes

| Removed Class | Replacement |
|---|---|
| `Webkul\MagicAI\Services\OpenAI` | `Webkul\MagicAI\Services\LaravelAiAdapter` |
| `Webkul\MagicAI\Services\Gemini` | `Webkul\MagicAI\Services\LaravelAiAdapter` |
| `Webkul\MagicAI\Services\Groq` | `Webkul\MagicAI\Services\LaravelAiAdapter` |
| `Webkul\MagicAI\Services\Ollama` | `Webkul\MagicAI\Services\LaravelAiAdapter` |

AI credentials previously stored in `core_config` are automatically migrated to the new `magic_ai_platforms` table during the database migration. **No manual action is required** for existing credentials.

If you have custom code that directly instantiates the old provider classes, update it to use `LaravelAiAdapter` instead.

### 3. ImageCache System Replacement

`Webkul\Core\ImageCache\ImageManager` has been replaced with the new `ImageCache` system.

| Removed Class | Replacement |
|---|---|
| `Webkul\Core\ImageCache\ImageManager` | `Webkul\Core\ImageCache\ImageCache` |

If you have custom code referencing `ImageManager`, update it to use `ImageCache`.

### 4. PHP 8.3 Requirement

PHP 8.2 is no longer supported. Ensure your server runs **PHP 8.3 or higher** before upgrading.

---

## Medium Impact Changes

### 5. Dependency Version Changes

| Package | v1.0.0 | v2.0.0-beta.1 |
|---|---|---|
| `laravel/framework` | ^10.0 | **^12.0** |
| `laravel/sanctum` | ^3.2 | **^4.0** |
| `diglactic/laravel-breadcrumbs` | ^8.0 | **^10.0** |
| `astrotomic/laravel-translatable` | ^11.0.0 | **^11.16.0** |
| `pestphp/pest` | ^2.0 | **^3.0** |
| `phpunit/phpunit` | ^10.0 | **^11.0** |
| `nunomaduro/collision` | ^7.0 | **^8.0** |

**New dependencies:**
| Package | Version | Purpose |
|---|---|---|
| `laravel/ai` | ^0.3.2 | Laravel AI SDK for multi-provider AI |
| `laravel/boost` | ^2.1 | Performance optimizations |

If you have custom packages that depend on Laravel 10 features, verify their compatibility with Laravel 12.

### 6. New Database Tables

The following tables are created during migration:

| Table | Purpose |
|---|---|
| `agent_conversations` | AI Agent chat conversation history |
| `agent_conversation_messages` | Individual messages within conversations |
| `magic_ai_platforms` | Multi-platform AI provider credentials |

### 7. Queue Configuration

UnoPim v2.0.0-beta.1 continues to use the `system`, `completeness`, and `default` queues introduced in v1.0.0. No changes required if you already have these configured.

### 8. Scheduled Commands

The following scheduled commands are now configured in `bootstrap/app.php`:

| Command | Schedule |
|---|---|
| `unopim:product:index` | Twice daily (00:01, 12:01) |
| `unopim:category:index` | Twice daily (00:01, 12:01) |
| `unopim:completeness:recalculate --all` | Daily at 02:00 |
| `unopim:dashboard:refresh` | Every 10 minutes |

If you had custom scheduling, ensure it's migrated to the new `withSchedule()` pattern.

---

## Low Impact Changes

### 9. New Environment Variables (Optional)

These environment variables are **optional** and only needed if you want to configure AI providers via environment:

```env
# AI Provider API Keys (configure via Admin Panel or .env)
OPENAI_API_KEY=
ANTHROPIC_API_KEY=
GEMINI_API_KEY=
GROQ_API_KEY=
DEEPSEEK_API_KEY=
MISTRAL_API_KEY=
XAI_API_KEY=
COHERE_API_KEY=
OPENROUTER_API_KEY=
VOYAGEAI_API_KEY=
JINA_API_KEY=

# Ollama (local AI)
OLLAMA_API_KEY=
OLLAMA_BASE_URL=http://localhost:11434

# Azure OpenAI
AZURE_OPENAI_API_KEY=
AZURE_OPENAI_URL=
AZURE_OPENAI_API_VERSION=2024-10-21
AZURE_OPENAI_DEPLOYMENT=gpt-4o
AZURE_OPENAI_EMBEDDING_DEPLOYMENT=text-embedding-3-small

# ElevenLabs (audio)
ELEVENLABS_API_KEY=
```

> **Note:** AI provider credentials can also be managed through the Admin Panel under **Configuration > MagicAI > Platforms**, which is the recommended approach.

### 10. New Admin Routes

New routes added for MagicAI platform management:

| Method | Route | Purpose |
|---|---|---|
| GET | `/admin/magic-ai/platform` | List platforms |
| POST | `/admin/magic-ai/platform` | Create platform |
| POST | `/admin/magic-ai/platform/test-connection` | Test API connection |
| POST | `/admin/magic-ai/platform/fetch-models` | Fetch provider models |
| GET | `/admin/magic-ai/platform/{id}` | Edit platform |
| PUT | `/admin/magic-ai/platform/{id}` | Update platform |
| DELETE | `/admin/magic-ai/platform/{id}` | Delete platform |
| POST | `/admin/magic-ai/platform/{id}/default` | Set default platform |

### 11. Translation Updates

All 30+ locale files have been updated with new translation keys. If you have custom translations, merge the new keys from the `en_US` locale file as reference.

---

## MANUAL UPGRADE STEPS

### 1. **Backup Your Current Project**

Before starting the upgrade process:

* **Backup your full project**
* **Backup your database** using `mysqldump` or your preferred method:

```bash
mysqldump -u your_db_user -p your_db_name > backup.sql
```
* **Stop processes** - wait for all queue jobs to finish or stop the queue worker safely.

**Make sure to keep these backups safe in case something goes wrong during the upgrade.**

### 2. **Verify PHP Version**

Ensure you are running PHP 8.3 or higher:

```bash
php -v
```

If not, upgrade PHP before proceeding.

### 3. **Download the Release**

* Visit the [UnoPim GitHub releases page for v2.0.0-beta.1](https://github.com/unopim/unopim/archive/refs/tags/v2.0.0-beta.1.zip)
* Download the `.zip` file for release `v2.0.0-beta.1`
* Extract the contents to a folder on your system.

### 4. **Copy Necessary Files**

Copy the following files from your existing UnoPim project to the newly extracted version:

* `.env` file
* `storage/` folder (to keep your data intact like images)

This ensures your environment settings and any uploaded files stay intact during the upgrade.

### 5. **Review Custom Code**

If you have customized any of the removed files (Kernel, Middleware, Providers), migrate your customizations as described in the [High Impact Changes](#1-laravel-12-framework-upgrade) section above.

### 6. **Install Dependencies**

Navigate to the extracted folder, and install the necessary dependencies with Composer:

```bash
composer install
```

### 7. **Run Database Migrations**

Run the following command to apply database schema updates and migrate AI credentials:

```bash
php artisan migrate
```

This will:
- Create `agent_conversations` and `agent_conversation_messages` tables
- Create `magic_ai_platforms` table and migrate existing AI credentials from `core_config`
- Update `magic_ai_prompts` table with new type and purpose columns

### 8. **Clear Cache & Link Storage**

```bash
php artisan optimize:clear
php artisan storage:link
```

### 9. **Restart the Queue Worker**

If you are using Supervisor to manage the `queue:work` command, restart the relevant service:

```bash
sudo supervisorctl restart unopim-worker
```

**Queue command (unchanged from v1.0.0):**
```bash
php artisan queue:work --queue=system,completeness,default
```

### 10. **Rebuild Elasticsearch Indexes** (if applicable)

If you use Elasticsearch, rebuild the indexes:

```bash
php artisan unopim:elastic:clear
php artisan unopim:product:index
php artisan unopim:category:index
```

---

## AUTOMATED UPGRADE SCRIPT

1. **Verify PHP 8.3** is installed on your system.
2. **Download the script** `upgrade_1.0.0_to_2.0.0.sh` and keep a backup of your database and project before starting.
3. **Execute the script** - place it in the root directory of your UnoPim project and run:

```bash
chmod +x upgrade_1.0.0_to_2.0.0.sh
./upgrade_1.0.0_to_2.0.0.sh
```

4. **Review custom code** - after the script completes, review any customizations you had in removed files (Kernel, Middleware, Providers) and migrate them as described in the [High Impact Changes](#1-laravel-12-framework-upgrade) section.

5. **Restart Queue/Supervisor**

```bash
sudo supervisorctl restart unopim-worker
```

---

## Upgrade Complete!

After following these steps, your UnoPim should be successfully upgraded to version `v2.0.0-beta.1`. Test your application thoroughly to make sure everything works as expected.

### Post-Upgrade Checklist

- [ ] Application loads without errors
- [ ] Admin panel is accessible
- [ ] Products are listed and editable
- [ ] MagicAI features work (if previously configured)
- [ ] AI Agent chat is accessible (optional new feature)
- [ ] Import/Export operations function correctly
- [ ] Queue workers are processing jobs
- [ ] Elasticsearch indexing works (if applicable)
- [ ] Custom middleware/providers are migrated (if any)
