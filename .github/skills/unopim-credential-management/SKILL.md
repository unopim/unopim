---
name: unopim-credential-management
description: >
  Implement credential storage, API connection testing, secure field handling,
  history tracking, and full CRUD for Unopim third-party connector modules.
  Covers Credential model with HistoryTrait and extras JSON, Contract interface,
  CredentialRepository, CredentialController with JsonResponse, FormRequest
  validation, DataGrid, and migration with DB_PREFIX. Use this skill when
  building the credentials section of any Unopim connector (WooCommerce,
  Shopify, Shopware, module, etc.).
version: "2.0.0"
tags: [unopim, credentials, laravel, connector, history, security, integration]
---

# Unopim Credential Management

## Overview

Credentials store API connection details for a third-party integration.
All patterns are derived from the WooCommerce connector reference implementation.

**Key rules:**
- Table prefix: DB_PREFIX from .env (default wk_) — never hardcode in code
- Migration folder: `Database/Migration/` (NOT `Migrations`)
- Every model uses `HistoryTrait` + implements `PresentableHistoryInterface`
- Sensitive fields (API secrets, passwords) go in `$auditExclude` — NOT `Crypt::encryptString()`
- Flexible extra config uses `extras` JSON column (single column, not many columns)
- Controllers return `JsonResponse` with `redirect_url` for store/update/delete
- Use dedicated `Http/Requests/CredentialForm.php` FormRequest — not inline `$request->validate()`
- A `Services/{ModuleName}Service.php` wraps all API calls

**Admin UI rule (critical):**
- For admin forms, use UnoPim components: `x-admin::form.control-group`, `.label`, `.control`, `.error`.
- Do not generate raw `<select>`, `<input>`, `<textarea>`, or `<label>` when component equivalents exist.
- Use translations for all user-facing form text.
- For dropdowns, use component select with `type="select"`, `:options="json_encode(...)"`, `track-by`, `label-by`, and Vue `@input` handling.

---

## 1. Migration

```php
<?php
// Database/Migration/2025_01_01_000000_{module}_credentials.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{module}_credentials', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->string('apiUrl');
            $table->string('consumerKey');
            $table->string('consumerSecret');          // stored plaintext; excluded from history
            $table->string('storeId')->nullable();
            $table->json('extras')->nullable();        // flexible JSON for additional config
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{module}_credentials');
    }
};
```

---

## 2. Contract Interface

```php
<?php
// src/Contracts/Credential.php

namespace Webkul\{ModuleName}\Contracts;

interface Credential
{
    // marker interface
}
```

---

## 3. Credential Model

```php
<?php
// src/Models/Credential.php

namespace Webkul\{ModuleName}\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\{ModuleName}\Contracts\Credential as CredentialContract;
use Webkul\HistoryControl\Interfaces\PresentableHistoryInterface;
use Webkul\HistoryControl\Traits\HistoryTrait;

class Credential extends Model implements CredentialContract, PresentableHistoryInterface
{
    use HasFactory, HistoryTrait;

    /**
     * Table — always DB_PREFIX.
     *
     * @var string
     */
    protected $table = '{module}_credentials';

    /**
     * @var array
     */
    protected $fillable = [
        'label',
        'apiUrl',
        'consumerKey',
        'consumerSecret',
        'storeId',
        'extras',
        'status',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'extras' => 'array',
        'status' => 'boolean',
    ];

    /**
     * Fields excluded from audit history.
     * Use this instead of Crypt::encryptString() for secrets.
     *
     * @var array
     */
    protected $auditExclude = [
        'consumerSecret',
    ];

    /**
     * Fields shown in history UI.
     *
     * @var array
     */
    protected $historyAuditable = [
        'label',
        'apiUrl',
        'consumerKey',
        'storeId',
        'status',
    ];
}
```

---

## 4. CredentialRepository

```php
<?php
// src/Repositories/CredentialRepository.php

namespace Webkul\{ModuleName}\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\{ModuleName}\Contracts\Credential;

class CredentialRepository extends Repository
{
    /**
     * Specify model class.
     */
    public function model(): string
    {
        return Credential::class;
    }
}
```

---

## 5. FormRequest

Never use inline `$request->validate()` in the controller.
Use a dedicated `Http/Requests/CredentialForm.php` class.

```php
<?php
// src/Http/Requests/CredentialForm.php

namespace Webkul\{ModuleName}\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CredentialForm extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $credentialId = $this->route('id');

        return [
            'label'          => 'required|string|max:255|unique:{module}_credentials,label' . ($credentialId ? ",{$credentialId}" : ''),
            'apiUrl'         => 'required|url',
            'consumerKey'    => 'required|string',
            'consumerSecret' => $credentialId ? 'nullable|string' : 'required|string',
            'status'         => 'required|boolean',
        ];
    }

    /**
     * Custom attribute names in messages.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'apiUrl'         => trans('{module-name}::app.credentials.api-url'),
            'consumerKey'    => trans('{module-name}::app.credentials.consumer-key'),
            'consumerSecret' => trans('{module-name}::app.credentials.consumer-secret'),
        ];
    }
}
```

---

## 6. CredentialController

Controllers must:
- Return `JsonResponse` with `redirect_url` for store/update/delete (not redirect())
- Guard with `bouncer()->hasPermission()` for ACL
- Use the FormRequest type-hint for automatic validation

```php
<?php
// src/Http/Controllers/CredentialController.php

namespace Webkul\{ModuleName}\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Webkul\{ModuleName}\DataGrids\Credential\CredentialDataGrid;
use Webkul\{ModuleName}\Http\Requests\CredentialForm;
use Webkul\{ModuleName}\Repositories\CredentialRepository;
use Webkul\{ModuleName}\Services\{ModuleName}Service;

class CredentialController extends Controller
{
    public function __construct(
        protected CredentialRepository $credentialRepository,
        protected {ModuleName}Service $service,
    ) {}

    /**
     * List credentials.
     */
    public function index()
    {
        if (request()->ajax()) {
            return app(CredentialDataGrid::class)->toJson();
        }

        return view('{module-name}::credentials.index');
    }

    /**
     * Create form.
     */
    public function create()
    {
        return view('{module-name}::credentials.create');
    }

    /**
     * Store a new credential.
     */
    public function store(CredentialForm $request): JsonResponse
    {
        $credential = $this->credentialRepository->create($request->validated());

        return new JsonResponse([
            'redirect_url' => route('{module-slug}.credentials.index'),
            'message'      => trans('{module-name}::app.credentials.create-success'),
        ]);
    }

    /**
     * Edit form.
     */
    public function edit(int $id)
    {
        $credential = $this->credentialRepository->findOrFail($id);

        return view('{module-name}::credentials.edit', compact('credential'));
    }

    /**
     * Update an existing credential.
     */
    public function update(CredentialForm $request, int $id): JsonResponse
    {
        $data = $request->validated();

        // Don't overwrite secret if left blank on edit
        if (empty($data['consumerSecret'])) {
            unset($data['consumerSecret']);
        }

        $this->credentialRepository->update($data, $id);

        return new JsonResponse([
            'redirect_url' => route('{module-slug}.credentials.index'),
            'message'      => trans('{module-name}::app.credentials.update-success'),
        ]);
    }

    /**
     * Delete a credential.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->credentialRepository->delete($id);

        return new JsonResponse([
            'message' => trans('{module-name}::app.credentials.delete-success'),
        ]);
    }

    /**
     * Test the API connection for a credential.
     */
    public function testConnection(): JsonResponse
    {
        $validated = request()->validate([
            'apiUrl'         => 'required|url',
            'consumerKey'    => 'required|string',
            'consumerSecret' => 'required|string',
        ]);

        try {
            $connected = $this->service->testConnection(
                $validated['apiUrl'],
                $validated['consumerKey'],
                $validated['consumerSecret'],
            );

            if ($connected) {
                return new JsonResponse(['message' => trans('{module-name}::app.credentials.test-success')]);
            }

            return new JsonResponse(
                ['error' => trans('{module-name}::app.credentials.test-failed')],
                422
            );
        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 422);
        }
    }
}
```

---

## 7. Service Class

A service class wraps all external API calls so controllers stay thin.

```php
<?php
// src/Services/{ModuleName}Service.php

namespace Webkul\{ModuleName}\Services;

use Webkul\{ModuleName}\Http\Client\ApiClient;
use Webkul\{ModuleName}\Models\Credential;

class {ModuleName}Service
{
    protected ApiClient $client;

    public function __construct(ApiClient $client)
    {
        $this->client = $client;
    }

    /**
     * Test if the API credentials are valid.
     */
    public function testConnection(string $apiUrl, string $key, string $secret): bool
    {
        $this->client->configure($apiUrl, $key, $secret);

        $response = $this->client->get('system_status');

        return isset($response['environment']);
    }

    /**
     * Set client from a stored credential.
     */
    public function useCredential(Credential $credential): static
    {
        $this->client->configure(
            $credential->apiUrl,
            $credential->consumerKey,
            $credential->consumerSecret,
        );

        return $this;
    }
}
```

---

## 8. Credential Presenter (HistoryControl)

```php
<?php
// src/Presenters/CredentialPresenter.php

namespace Webkul\{ModuleName}\Presenters;

use Webkul\HistoryControl\Presenters\BasePresenter;

class CredentialPresenter extends BasePresenter
{
    /**
     * Label shown on history timeline.
     */
    public function getTitle(): string
    {
        return trans('{module-name}::app.credentials.title');
    }
}
```

---

## 9. Checklist

- [ ] Table name uses DB_PREFIX (auto-added)
- [ ] Migration in `Database/Migration/` folder
- [ ] Model uses `HistoryTrait` + `PresentableHistoryInterface`
- [ ] Model has `$auditExclude` for secret fields (no `Crypt::encryptString`)
- [ ] Model has `extras` JSON column with `'extras' => 'array'` cast
- [ ] Contract interface exists in `src/Contracts/`
- [ ] `ModuleServiceProvider` lists the model in `$models[]`
- [ ] Dedicated `Http/Requests/CredentialForm.php` used (not inline validate)
- [ ] Controller returns `JsonResponse` with `redirect_url`
- [ ] Edit flow keeps existing secret when field left blank
- [ ] Service class created for API test connection logic
- [ ] Presenter created for history display
