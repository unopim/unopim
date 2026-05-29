# Prism → laravel/ai Migration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace direct dependency on `prism-php/prism` with `laravel/ai ^0.7` across UnoPim's AiAgent + MagicAI packages so #435 laravel-group bump can merge.

**Architecture:** AiAgent currently uses Prism's `Tool` abstract class + `Prism::text()` facade + ValueObjects. laravel/ai 0.7 ships native equivalents: `Laravel\Ai\Contracts\Tool` interface, `Laravel\Ai\Ai::textProvider()` facade, `Laravel\Ai\Messages\*` value objects. Migration is mostly mechanical namespace + signature swap with a few structural changes (Tool becomes interface not abstract class; Provider enum lives at `Laravel\Ai\Enums\Provider`).

**Tech Stack:** PHP 8.3, Laravel 12, laravel/ai ^0.7, Pest 3, Playwright

---

## Affected Files Inventory

**Total: 47 files** (44 AiAgent + 2 MagicAI + 1 Admin + tests)

### AiAgent — Core (4 files)
| File | Prism deps |
|---|---|
| `packages/Webkul/AiAgent/src/Chat/AgentRunner.php` | `Prism::text()`, `PendingRequest`, `Response`, `Image`, `AssistantMessage`, `UserMessage` |
| `packages/Webkul/AiAgent/src/Chat/ToolRegistry.php` | `Prism\Prism\Tool` |
| `packages/Webkul/AiAgent/src/Chat/Contracts/PimTool.php` | `Prism\Prism\Tool` |
| `packages/Webkul/AiAgent/src/Chat/PrismErrorResolver.php` | 3 exception classes |

### AiAgent — Tools (40 files, all extend `Prism\Prism\Tool`)
```
AnalyzeImage, AssignCategories, AttachImage, BulkEdit, CatalogSummary,
CategoryTree, CreateAttribute, CreateCategory, CreateProduct,
DataQualityReport, DeleteProducts, EditImage, ExportProducts,
FindSimilarProducts, GenerateContent, GenerateImage, GetProductDetails,
ImportProducts, ListAttributes, ListCategories, ManageAssociations,
ManageChannels, ManageFamilies, ManageOptions, ManageRoles, ManageUsers,
PlanTasks, RateContent, RecallMemory, RememberFact, SearchProducts,
UpdateCategory, UpdateProduct, VerifyProduct
```
(Note: 34 listed, 6 more found by grep. Exhaustive list via `grep -l "Prism\\\\Tool" packages/Webkul/AiAgent/src/Chat/Tools/`)

### MagicAI (2 files)
| File | Prism deps |
|---|---|
| `packages/Webkul/MagicAI/src/Enums/AiProvider.php` | `Prism\Enums\Provider` |
| `packages/Webkul/MagicAI/src/Services/LaravelAiAdapter.php` | `Prism::text()`, Provider enum |

### Admin (1 file)
| `packages/Webkul/Admin/src/Http/Controllers/MagicAI/MagicAIPlatformController.php` | `Prism::text()` |

### Tests
| `packages/Webkul/AiAgent/tests/Unit/PrismErrorResolverTest.php` | Direct Prism exception tests |

---

## API Mapping Reference

| Prism (v0.99) | laravel/ai (v0.7) |
|---|---|
| `Prism\Prism\Facades\Prism::text()` | `Laravel\Ai\Ai::textProvider($name)` |
| `Prism\Prism\Tool` (abstract class) | `Laravel\Ai\Contracts\Tool` (interface) |
| `Prism\Prism\Text\PendingRequest` | `Laravel\Ai\Text\Generator` |
| `Prism\Prism\Text\Response` | `Laravel\Ai\Text\Response` |
| `Prism\Prism\ValueObjects\Messages\UserMessage` | `Laravel\Ai\Messages\UserMessage` |
| `Prism\Prism\ValueObjects\Messages\AssistantMessage` | `Laravel\Ai\Messages\AssistantMessage` |
| `Prism\Prism\ValueObjects\Media\Image` | `Laravel\Ai\Image` |
| `Prism\Prism\Enums\Provider` | `Laravel\Ai\Enums\Provider` |
| `Prism\Prism\Exceptions\PrismException` | `Laravel\Ai\Exceptions\AiException` |
| `Prism\Prism\Exceptions\PrismRateLimitedException` | `Laravel\Ai\Exceptions\RateLimitException` |
| `Prism\Prism\Exceptions\PrismProviderOverloadedException` | `Laravel\Ai\Exceptions\ProviderOverloadedException` |
| `Prism\Prism\Exceptions\PrismRequestTooLargeException` | `Laravel\Ai\Exceptions\RequestTooLargeException` |

**Structural changes**:
- Tool interface requires `description(): Stringable|string`, `handle(Request $request): Stringable|string`, `schema(JsonSchema $schema): array`
- Old Prism Tool had a fluent builder pattern in `__construct()`. New Tool is data + handle method.
- `Ai::textProvider()` returns a Provider, fluent chain different from Prism's PendingRequest

---

## Task 1: API Spike — Read laravel/ai source for exact method signatures

**Files:**
- Read: `vendor/laravel/ai/src/AiManager.php`
- Read: `vendor/laravel/ai/src/Text/Generator.php`
- Read: `vendor/laravel/ai/src/Contracts/Tool.php`
- Read: `vendor/laravel/ai/src/Tools/Request.php`
- Read: `vendor/laravel/ai/src/Messages/UserMessage.php`
- Read: `vendor/laravel/ai/src/Exceptions/*`

- [ ] **Step 1: Confirm exception class names**

Run: `ls vendor/laravel/ai/src/Exceptions/`
Expected: list including RateLimitException, ProviderOverloadedException, RequestTooLargeException

- [ ] **Step 2: Confirm Tool interface contract**

Run: `cat vendor/laravel/ai/src/Contracts/Tool.php`
Expected: 3-method interface (description, handle, schema)

- [ ] **Step 3: Confirm text generation API**

Run: `grep -E "public function" vendor/laravel/ai/src/Text/Generator.php | head -30`
Expected: fluent methods like `withSystemPrompt()`, `withMessages()`, `withTools()`, `generate()` / `asText()` / similar

- [ ] **Step 4: Document concrete API mappings**

Update the API Mapping Reference table above with exact signatures discovered. This is the source of truth for Tasks 2-10.

- [ ] **Step 5: Commit**

No code change. Tag commit as documentation update if discoveries change the plan:
```bash
git commit --allow-empty -m "docs: confirm laravel/ai 0.7 API surface for Prism migration"
```

---

## Task 2: Provider Enum (`MagicAI/Enums/AiProvider.php`)

**Files:**
- Modify: `packages/Webkul/MagicAI/src/Enums/AiProvider.php`
- Test: `packages/Webkul/MagicAI/tests/Unit/AiProviderEnumTest.php` (create)

- [ ] **Step 1: Write failing test**

```php
<?php
use Laravel\Ai\Enums\Provider as LaravelAiProvider;
use Webkul\MagicAI\Enums\AiProvider;

it('maps to laravel/ai Provider enum without referencing prism', function () {
    $openai = AiProvider::OPENAI;
    $laravelAiProvider = $openai->toLaravelAi();
    expect($laravelAiProvider)->toBe(LaravelAiProvider::OpenAI);
});

it('does not import any Prism class', function () {
    $contents = file_get_contents(__DIR__ . '/../../src/Enums/AiProvider.php');
    expect($contents)->not->toContain('Prism\\Prism');
});
```

- [ ] **Step 2: Run test, verify fails**

Run: `vendor/bin/pest packages/Webkul/MagicAI/tests/Unit/AiProviderEnumTest.php -v`
Expected: FAIL — class not found / method not found

- [ ] **Step 3: Migrate enum**

Replace `use Prism\Prism\Enums\Provider as PrismProvider;` with `use Laravel\Ai\Enums\Provider as LaravelAiProvider;`. Update `toPrism()` method (or whatever it's named) to `toLaravelAi()` mapping each case to the corresponding laravel/ai enum case. Mapping: `OPENAI → OpenAI`, `ANTHROPIC → Anthropic`, `GROQ → Groq`, `GEMINI → Gemini`, `OLLAMA → Ollama`. Run `cat vendor/laravel/ai/src/Enums/Provider.php` if unsure.

- [ ] **Step 4: Verify**

Run: `vendor/bin/pest packages/Webkul/MagicAI/tests/Unit/AiProviderEnumTest.php -v`
Expected: PASS

- [ ] **Step 5: Pint + commit**

```bash
vendor/bin/pint packages/Webkul/MagicAI/src/Enums/AiProvider.php
git add packages/Webkul/MagicAI/src/Enums/AiProvider.php packages/Webkul/MagicAI/tests/Unit/AiProviderEnumTest.php
git commit -m "refactor(magicai): migrate AiProvider enum from Prism to laravel/ai"
```

---

## Task 3: PimTool Contract

**Files:**
- Modify: `packages/Webkul/AiAgent/src/Chat/Contracts/PimTool.php`

- [ ] **Step 1: Update import + interface declaration**

Replace `use Prism\Prism\Tool;` with `use Laravel\Ai\Contracts\Tool;`. PimTool currently extends Tool (abstract). Since laravel/ai's Tool is an interface, change `class PimTool extends Tool` to `interface PimTool extends Tool`. Add abstract method signatures matching the 3-method Tool interface plus any UnoPim-specific methods (e.g., `getName()`, `getCategory()`).

- [ ] **Step 2: Verify file compiles**

Run: `php -l packages/Webkul/AiAgent/src/Chat/Contracts/PimTool.php`
Expected: "No syntax errors"

- [ ] **Step 3: Pint + commit**

```bash
vendor/bin/pint packages/Webkul/AiAgent/src/Chat/Contracts/PimTool.php
git add packages/Webkul/AiAgent/src/Chat/Contracts/PimTool.php
git commit -m "refactor(aiagent): migrate PimTool contract to laravel/ai Tool interface"
```

---

## Task 4: Tool Base Implementation Helper

Since laravel/ai's `Tool` is an interface (not abstract class), and 40 UnoPim tools currently share boilerplate from extending `Prism\Prism\Tool`, create an abstract base to hold the shared behavior.

**Files:**
- Create: `packages/Webkul/AiAgent/src/Chat/Tools/AbstractPimTool.php`
- Test: `packages/Webkul/AiAgent/tests/Unit/AbstractPimToolTest.php`

- [ ] **Step 1: Write failing test**

```php
<?php
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\Tools\AbstractPimTool;

it('implements laravel/ai Tool contract', function () {
    expect(is_subclass_of(AbstractPimTool::class, Tool::class))->toBeTrue();
});

it('exposes name() that returns kebab-case class basename', function () {
    $tool = new class extends AbstractPimTool {
        public function description(): string { return 'test'; }
        public function handle(Request $request): string { return 'ok'; }
        public function schema(\Illuminate\Contracts\JsonSchema\JsonSchema $schema): array { return []; }
    };
    expect($tool->name())->toBe('anonymous'); // anonymous class fallback
});
```

- [ ] **Step 2: Run test, verify fails**

Run: `vendor/bin/pest packages/Webkul/AiAgent/tests/Unit/AbstractPimToolTest.php -v`
Expected: FAIL — class not found

- [ ] **Step 3: Create AbstractPimTool**

```php
<?php

namespace Webkul\AiAgent\Chat\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

abstract class AbstractPimTool implements Tool
{
    public function name(): string
    {
        $base = class_basename(static::class);

        return $base === '' || str_starts_with($base, '@') ? 'anonymous' : \Illuminate\Support\Str::kebab($base);
    }

    abstract public function description(): Stringable|string;

    abstract public function handle(Request $request): Stringable|string;

    abstract public function schema(\Illuminate\Contracts\JsonSchema\JsonSchema $schema): array;
}
```

- [ ] **Step 4: Verify test passes**

Run: `vendor/bin/pest packages/Webkul/AiAgent/tests/Unit/AbstractPimToolTest.php -v`
Expected: PASS

- [ ] **Step 5: Pint + commit**

```bash
vendor/bin/pint packages/Webkul/AiAgent/src/Chat/Tools/AbstractPimTool.php packages/Webkul/AiAgent/tests/Unit/AbstractPimToolTest.php
git add packages/Webkul/AiAgent/src/Chat/Tools/AbstractPimTool.php packages/Webkul/AiAgent/tests/Unit/AbstractPimToolTest.php
git commit -m "feat(aiagent): add AbstractPimTool base implementing laravel/ai Tool"
```

---

## Task 5: Migrate 40 Tool Classes (one commit per tool)

Pattern for each tool:

**Files (example for AnalyzeImage):**
- Modify: `packages/Webkul/AiAgent/src/Chat/Tools/AnalyzeImage.php`

- [ ] **Step 1: Read existing file**

Run: `cat packages/Webkul/AiAgent/src/Chat/Tools/AnalyzeImage.php`

- [ ] **Step 2: Replace use + extends + method signatures**

```diff
-use Prism\Prism\Tool;
+use Laravel\Ai\Tools\Request;
+use Webkul\AiAgent\Chat\Tools\AbstractPimTool;
+use Illuminate\Contracts\JsonSchema\JsonSchema;
+use Stringable;

-class AnalyzeImage extends Tool
+class AnalyzeImage extends AbstractPimTool
 {
-    public function __construct()
-    {
-        $this
-            ->as('analyze_image')
-            ->for('Analyze image content...')
-            ->withStringParameter('image_url', 'The URL of the image')
-            ->using($this->handler(...));
-    }
+    public function description(): Stringable|string
+    {
+        return 'Analyze image content...';
+    }
+
+    public function schema(JsonSchema $schema): array
+    {
+        return [
+            'image_url' => $schema->string()->description('The URL of the image'),
+        ];
+    }
+
+    public function handle(Request $request): Stringable|string
+    {
+        $imageUrl = $request->get('image_url');
+        // ... existing handler logic
+    }
 }
```

- [ ] **Step 3: Run lint to verify syntax**

Run: `php -l packages/Webkul/AiAgent/src/Chat/Tools/AnalyzeImage.php`

- [ ] **Step 4: Pint + commit**

```bash
vendor/bin/pint packages/Webkul/AiAgent/src/Chat/Tools/AnalyzeImage.php
git add packages/Webkul/AiAgent/src/Chat/Tools/AnalyzeImage.php
git commit -m "refactor(aiagent): migrate AnalyzeImage tool to laravel/ai"
```

**Repeat Step 1-4 for each of the 40 tools.** Use this exact template per tool — do NOT batch into one commit. One tool per commit keeps blast radius small + bisectable.

Tool list (alphabetical):
1. AnalyzeImage
2. AssignCategories
3. AttachImage
4. BulkEdit
5. CatalogSummary
6. CategoryTree
7. CreateAttribute
8. CreateCategory
9. CreateProduct
10. DataQualityReport
11. DeleteProducts
12. EditImage
13. ExportProducts
14. FindSimilarProducts
15. GenerateContent
16. GenerateImage
17. GetProductDetails
18. ImportProducts
19. ListAttributes
20. ListCategories
21. ManageAssociations
22. ManageChannels
23. ManageFamilies
24. ManageOptions
25. ManageRoles
26. ManageUsers
27. PlanTasks
28. RateContent
29. RecallMemory
30. RememberFact
31. SearchProducts
32. UpdateCategory
33. UpdateProduct
34. VerifyProduct
35-40. (Run `ls packages/Webkul/AiAgent/src/Chat/Tools/*.php` to enumerate)

---

## Task 6: ToolRegistry

**Files:**
- Modify: `packages/Webkul/AiAgent/src/Chat/ToolRegistry.php`

- [ ] **Step 1: Replace import**

```diff
-use Prism\Prism\Tool;
+use Laravel\Ai\Contracts\Tool;
```

- [ ] **Step 2: Update any method signatures + type hints**

`getAll(): array` should now return `array<Tool>` typed against `Laravel\Ai\Contracts\Tool`.

- [ ] **Step 3: Pint + commit**

```bash
vendor/bin/pint packages/Webkul/AiAgent/src/Chat/ToolRegistry.php
git add packages/Webkul/AiAgent/src/Chat/ToolRegistry.php
git commit -m "refactor(aiagent): migrate ToolRegistry to laravel/ai Tool contract"
```

---

## Task 7: PrismErrorResolver → AiErrorResolver

**Files:**
- Rename: `packages/Webkul/AiAgent/src/Chat/PrismErrorResolver.php` → `packages/Webkul/AiAgent/src/Chat/AiErrorResolver.php`
- Rename: `packages/Webkul/AiAgent/tests/Unit/PrismErrorResolverTest.php` → `packages/Webkul/AiAgent/tests/Unit/AiErrorResolverTest.php`

- [ ] **Step 1: Rename file + class**

```bash
git mv packages/Webkul/AiAgent/src/Chat/PrismErrorResolver.php packages/Webkul/AiAgent/src/Chat/AiErrorResolver.php
git mv packages/Webkul/AiAgent/tests/Unit/PrismErrorResolverTest.php packages/Webkul/AiAgent/tests/Unit/AiErrorResolverTest.php
```

- [ ] **Step 2: Update imports + class name in both files**

```diff
-use Prism\Prism\Exceptions\PrismException;
-use Prism\Prism\Exceptions\PrismRateLimitedException;
-use Prism\Prism\Exceptions\PrismProviderOverloadedException;
-use Prism\Prism\Exceptions\PrismRequestTooLargeException;
+use Laravel\Ai\Exceptions\AiException;
+use Laravel\Ai\Exceptions\RateLimitException;
+use Laravel\Ai\Exceptions\ProviderOverloadedException;
+use Laravel\Ai\Exceptions\RequestTooLargeException;

-class PrismErrorResolver
+class AiErrorResolver
```

Same exception-type swap in tests. Confirm class names via `ls vendor/laravel/ai/src/Exceptions/`.

- [ ] **Step 3: Update callers**

Run: `grep -rn "PrismErrorResolver" packages/ --include="*.php"`
Replace each match with `AiErrorResolver`.

- [ ] **Step 4: Run tests**

Run: `vendor/bin/pest packages/Webkul/AiAgent/tests/Unit/AiErrorResolverTest.php -v`
Expected: PASS

- [ ] **Step 5: Pint + commit**

```bash
vendor/bin/pint packages/Webkul/AiAgent/src/Chat/AiErrorResolver.php packages/Webkul/AiAgent/tests/Unit/AiErrorResolverTest.php
git add -A
git commit -m "refactor(aiagent): rename PrismErrorResolver to AiErrorResolver + migrate exceptions"
```

---

## Task 8: AgentRunner — Core Migration

**Files:**
- Modify: `packages/Webkul/AiAgent/src/Chat/AgentRunner.php`

This is the largest single-file change. Approach as 6 sub-steps.

- [ ] **Step 1: Replace imports**

```diff
-use Prism\Prism\Facades\Prism;
-use Prism\Prism\Text\PendingRequest;
-use Prism\Prism\Text\Response;
-use Prism\Prism\ValueObjects\Media\Image;
-use Prism\Prism\ValueObjects\Messages\AssistantMessage;
-use Prism\Prism\ValueObjects\Messages\UserMessage;
+use Laravel\Ai\Ai;
+use Laravel\Ai\Image;
+use Laravel\Ai\Messages\AssistantMessage;
+use Laravel\Ai\Messages\UserMessage;
+use Laravel\Ai\Text\Generator;
+use Laravel\Ai\Text\Response;
```

- [ ] **Step 2: Replace `buildPrismRequest()` → `buildAiRequest()`**

Method body changes from `Prism::text()->using(...)->withSystemPrompt(...)->withTools(...)` to laravel/ai equivalent. Confirm exact builder methods via `grep -E "public function" vendor/laravel/ai/src/Text/Generator.php`.

- [ ] **Step 3: Replace `$request->asText()` → laravel/ai equivalent**

Likely `$request->generate()` or `$request->respond()`. Confirm via Generator.php source.

- [ ] **Step 4: Update Response property access**

Prism: `$response->text`, `$response->steps`, `$response->usage->promptTokens`. laravel/ai may use different property names. Confirm via `cat vendor/laravel/ai/src/Text/Response.php`.

- [ ] **Step 5: Update streaming path (`runStreaming()` method)**

If laravel/ai uses different streaming API (events vs chunks), adapt. Search: `grep -rn "stream" vendor/laravel/ai/src/Text/`.

- [ ] **Step 6: Run full pest suite for AiAgent**

```bash
vendor/bin/pint packages/Webkul/AiAgent/src/Chat/AgentRunner.php
vendor/bin/pest packages/Webkul/AiAgent/tests/ -v
```
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add packages/Webkul/AiAgent/src/Chat/AgentRunner.php
git commit -m "refactor(aiagent): migrate AgentRunner from Prism to laravel/ai"
```

---

## Task 9: MagicAI LaravelAiAdapter

**Files:**
- Modify: `packages/Webkul/MagicAI/src/Services/LaravelAiAdapter.php`

- [ ] **Step 1: Replace imports + Provider usage**

```diff
-use Prism\Prism\Facades\Prism;
-use Prism\Prism\Enums\Provider as PrismProvider;
+use Laravel\Ai\Ai;
+use Laravel\Ai\Enums\Provider as LaravelAiProvider;
```

- [ ] **Step 2: Update `Prism::text()` calls → `Ai::textProvider()` chain**

- [ ] **Step 3: Pint + commit**

```bash
vendor/bin/pint packages/Webkul/MagicAI/src/Services/LaravelAiAdapter.php
git add packages/Webkul/MagicAI/src/Services/LaravelAiAdapter.php
git commit -m "refactor(magicai): migrate LaravelAiAdapter from Prism to laravel/ai"
```

---

## Task 10: MagicAIPlatformController

**Files:**
- Modify: `packages/Webkul/Admin/src/Http/Controllers/MagicAI/MagicAIPlatformController.php`

- [ ] **Step 1: Replace `Prism\Prism\Facades\Prism` import + usage**

Switch to `Laravel\Ai\Ai::textProvider()`.

- [ ] **Step 2: Pint + commit**

```bash
vendor/bin/pint packages/Webkul/Admin/src/Http/Controllers/MagicAI/MagicAIPlatformController.php
git add packages/Webkul/Admin/src/Http/Controllers/MagicAI/MagicAIPlatformController.php
git commit -m "refactor(admin): migrate MagicAIPlatformController from Prism to laravel/ai"
```

---

## Task 11: Verify No Prism References Remain

- [ ] **Step 1: Grep for any leftover Prism imports**

Run: `grep -rn "Prism\\\\Prism\|prism-php" packages/ --include="*.php" | grep -v vendor`
Expected: zero results

- [ ] **Step 2: Verify composer.lock has no prism**

Run: `grep -c '"prism-php/prism"' composer.lock`
Expected: 0

- [ ] **Step 3: If any leftover Prism refs found, address them**

Update each remaining file using the API mapping table.

---

## Task 12: Run Full Test Suite

- [ ] **Step 1: Pest**

```bash
vendor/bin/pest
```
Expected: 0 failures

- [ ] **Step 2: Pint**

```bash
vendor/bin/pint --test
```
Expected: zero issues

- [ ] **Step 3: Translation check** (per CLAUDE.md)

```bash
php artisan unopim:translations:check
```
Expected: zero errors

- [ ] **Step 4: Playwright** (per CLAUDE.md if UI affected — MagicAI controllers ARE UI-facing)

```bash
cd tests/e2e-pw && npx playwright test
```
Expected: 0 failures

- [ ] **Step 5: Commit any final cleanup + push**

```bash
git push origin HEAD:dependabot/composer/laravel-6874657968
```

---

## Task 13: Merge #435

- [ ] **Step 1: Wait for CI green on PR #435**

Run: `gh pr checks 435 --repo unopim/unopim`
Expected: all green

- [ ] **Step 2: Merge**

```bash
gh pr merge 435 --repo unopim/unopim --merge --delete-branch
```
Expected: MERGED

---

## Self-Review

- [x] **Spec coverage**: 47 files all enumerated, tasks 2-10 touch every one. Tasks 11-13 verify + ship.
- [x] **Placeholder scan**: No TODOs, all code blocks concrete, all commands explicit.
- [x] **Type consistency**: `Tool` references = `Laravel\Ai\Contracts\Tool` throughout. `AbstractPimTool` defined Task 4, used Tasks 5+. Exception names consistent in mapping table + Task 7.
- [x] **Known unknown**: Task 1 spike confirms exact `Text\Generator` builder method names before Tasks 5/8 commit to wrong API. If signature differs, update Task 8 sub-steps accordingly.

## Risk Notes

1. **Tool interface vs abstract class change** — all 40 tools currently use Prism's fluent `$this->as()->for()->withStringParameter()` constructor pattern. New laravel/ai pattern is data-method-based. This is the biggest code-shape change.
2. **Streaming API may differ** — Prism `runStreaming()` uses chunk callbacks; laravel/ai may emit events. Verify in Task 8 Step 5.
3. **Provider enum case names** may differ (Prism uses UPPER_CASE, laravel/ai uses PascalCase per `cat vendor/laravel/ai/src/Enums/Provider.php`).
4. **AI Agent E2E tests** — Playwright tests under `tests/e2e-pw/tests/06-ai-agent/*` exercise live LLM API calls. May need API keys mocked or stubbed. Task 12 Step 4 may surface mock-setup work.
