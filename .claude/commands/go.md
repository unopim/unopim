# UNOPIM GO - Intelligent Orchestration Engine

> **Version:** 1.0 | **Project:** UnoPim PIM
> **Purpose:** Transform any task into structured, multi-phase execution with intelligent skill/agent orchestration for the UnoPim modular monolith

---

## ACTIVATION

When user invokes `/go $ARGUMENTS`, execute the orchestration protocol below.

---

## PHASE 0: CONTEXT HARVESTING (Silent - No Output)

### 0.1 Load Project Intelligence
```yaml
memory:
  - MEMORY.md                           # Project identity, key paths, architecture patterns
  - docs/index.md                       # Master documentation index
  - docs/architecture.md                # 6-layer system architecture
  - docs/data-models.md                 # 30+ database tables, JSON structures
  - docs/api-contracts.md               # 40+ REST API endpoints
  - docs/component-inventory.md         # 95+ UI components

pattern_docs:
  - docs/patterns-data-external.md      # Database grammars, Eloquent, repos, external services
  - docs/patterns-infrastructure.md     # Concord modules, ServiceProviders, DataGrid, events
  - docs/patterns-domain.md             # Product/Attribute/Category/User/DataTransfer/MagicAI
  - docs/patterns-application.md        # Controllers, routes, ACL, menus, Form Requests
  - docs/patterns-middleware.md         # HTTP Kernel, auth guards, SecureHeaders, locale
  - docs/patterns-client-designsystem.md # Vue.js 3, Blade, Tailwind, icons, dark mode

project_skills:
  - .claude/commands/unopim-patterns.md # Master reference (all layers)
  - .claude/commands/unopim-data.md     # DATA/EXTERNAL layer
  - .claude/commands/unopim-infra.md    # INFRASTRUCTURE layer
  - .claude/commands/unopim-domain.md   # DOMAIN layer
  - .claude/commands/unopim-app.md      # APPLICATION layer
  - .claude/commands/unopim-middleware.md # MIDDLEWARE layer
  - .claude/commands/unopim-client.md   # CLIENT/Design System layer
```

### 0.2 Classify Task Complexity
Silently analyze `$ARGUMENTS` and classify:

| Complexity | Criteria | Planning Depth | Agent Strategy |
|------------|----------|----------------|----------------|
| **TRIVIAL** | Single file, < 50 lines, clear fix | Minimal | Direct execution |
| **SIMPLE** | 1-3 files, well-defined scope | Light | Single agent |
| **MODERATE** | 3-7 files, cross-cutting concern | Standard | 2-3 agents |
| **COMPLEX** | 7+ files, multi-layer changes | Full SPARC | 3-5 agents |
| **EPIC** | System-wide, architectural change | Extended SPARC | 5+ agents (swarm) |

### 0.3 Detect Task Domain
Map task to relevant skill clusters:

```yaml
domain_detection:
  product:
    triggers: [product, sku, variant, configurable, simple, product type, product values, super_attribute]
    skills: [unopim-domain, unopim-data, unopim-app]
    docs: [patterns-domain.md → Product Domain, patterns-data-external.md → Product Model]
    key_files:
      - packages/Webkul/Product/src/Models/Product.php
      - packages/Webkul/Product/src/Repositories/ProductRepository.php
      - packages/Webkul/Product/src/Type/AbstractType.php
      - packages/Webkul/Admin/src/Http/Controllers/Catalog/ProductController.php
      - packages/Webkul/Admin/src/DataGrids/Catalog/ProductDataGrid.php

  attribute:
    triggers: [attribute, family, group, option, swatch, attribute_type, filterable, translatable]
    skills: [unopim-domain, unopim-data, unopim-infra]
    docs: [patterns-domain.md → Attribute Domain]
    key_files:
      - packages/Webkul/Attribute/src/Models/Attribute.php
      - packages/Webkul/Attribute/src/Repositories/AttributeRepository.php
      - packages/Webkul/Admin/src/Http/Controllers/Catalog/AttributeController.php

  category:
    triggers: [category, tree, nested set, parent, _lft, _rgt, category_field]
    skills: [unopim-domain, unopim-data]
    docs: [patterns-domain.md → Category Domain]
    key_files:
      - packages/Webkul/Category/src/Models/Category.php
      - packages/Webkul/Category/src/Repositories/CategoryRepository.php
      - packages/Webkul/Admin/src/Http/Controllers/Catalog/CategoryController.php

  channel_locale:
    triggers: [channel, locale, currency, i18n, translation, multi-language, locale_specific, channel_specific]
    skills: [unopim-data, unopim-middleware, unopim-domain]
    docs: [patterns-data-external.md → Channel/Locale Models, patterns-middleware.md → Locale Middleware]
    key_files:
      - packages/Webkul/Core/src/Models/Channel.php
      - packages/Webkul/Core/src/Models/Locale.php
      - packages/Webkul/Admin/src/Http/Controllers/Settings/ChannelController.php

  auth_permissions:
    triggers: [permission, role, acl, bouncer, auth, guard, session, login, passport, oauth, api key, scope]
    skills: [unopim-middleware, unopim-app]
    docs: [patterns-middleware.md → Auth/Bouncer, patterns-application.md → ACL]
    key_files:
      - packages/Webkul/User/src/Http/Middleware/Bouncer.php
      - packages/Webkul/AdminApi/src/Http/Middleware/ScopeMiddleware.php
      - packages/Webkul/Admin/src/Config/acl.php
      - packages/Webkul/User/src/Models/Admin.php
      - packages/Webkul/User/src/Models/Role.php

  data_transfer:
    triggers: [import, export, csv, excel, job, batch, queue, data_transfer]
    skills: [unopim-domain, unopim-infra]
    docs: [patterns-domain.md → DataTransfer Domain]
    key_files:
      - packages/Webkul/DataTransfer/src/
      - packages/Webkul/Admin/src/Http/Controllers/Settings/DataTransfer/

  datagrid:
    triggers: [datagrid, grid, table, filter, column, sort, paginate, mass action, export grid]
    skills: [unopim-infra, unopim-app, unopim-client]
    docs: [patterns-infrastructure.md → DataGrid, patterns-client-designsystem.md → DataGrid Filter]
    key_files:
      - packages/Webkul/DataGrid/src/DataGrid.php
      - packages/Webkul/Admin/src/DataGrids/

  frontend_ui:
    triggers: [vue, blade, component, ui, form, modal, dropdown, dark mode, tailwind, icon, template, page]
    skills: [unopim-client]
    docs: [patterns-client-designsystem.md]
    key_files:
      - packages/Webkul/Admin/src/Resources/views/components/
      - packages/Webkul/Admin/src/Resources/assets/js/app.js
      - packages/Webkul/Admin/tailwind.config.js

  api:
    triggers: [api, rest, endpoint, v1, json, oauth, token, api controller]
    skills: [unopim-app, unopim-middleware]
    docs: [patterns-application.md → API Controllers/Routes, patterns-middleware.md → API Auth]
    key_files:
      - packages/Webkul/AdminApi/src/Http/Controllers/API/
      - packages/Webkul/AdminApi/src/Routes/V1/

  database:
    triggers: [migration, schema, query, grammar, json_extract, eloquent, model, repository, mysql, postgresql]
    skills: [unopim-data, unopim-infra]
    docs: [patterns-data-external.md → Database/Repository]
    key_files:
      - packages/Webkul/Core/src/Helpers/Database/GrammarQueryManager.php
      - packages/Webkul/Core/src/Eloquent/Repository.php

  event_history:
    triggers: [event, listener, dispatch, history, audit, version, webhook, notification]
    skills: [unopim-infra, unopim-app]
    docs: [patterns-infrastructure.md → Events/History]
    key_files:
      - packages/Webkul/HistoryControl/src/
      - packages/Webkul/Notification/src/
      - packages/Webkul/Webhook/src/

  magic_ai:
    triggers: [ai, openai, groq, gemini, ollama, llm, translate, magic, content generation]
    skills: [unopim-domain]
    docs: [patterns-domain.md → MagicAI Domain]
    key_files:
      - packages/Webkul/MagicAI/src/

  testing:
    triggers: [test, pest, phpunit, playwright, e2e, spec, coverage, fixture]
    skills: [unopim-patterns]
    key_files:
      - tests/
      - packages/Webkul/*/tests/

  bug_fix:
    triggers: [bug, fix, error, broken, crash, issue, debug, 500, 404, 403, exception]
    skills: [relevant domain skills based on error context]
    strategy: Read error → identify domain → load domain skill → fix

  security:
    triggers: [security, xss, csrf, injection, header, cors, sanitize, purify]
    skills: [unopim-middleware]
    docs: [patterns-middleware.md → SecureHeaders]
```

---

## PHASE 1: SPECIFICATION (S in SPARC)

### 1.1 Output Task Understanding
```markdown
## Task Analysis

**Request:** $ARGUMENTS

**Classification:**
- Complexity: [TRIVIAL|SIMPLE|MODERATE|COMPLEX|EPIC]
- Domain: [detected domains]
- Estimated Scope: [packages/files affected]

**Activated Skills:**
- [List relevant /unopim-* skills that will be loaded]

**Agent Strategy:**
- Primary: [main agent type]
- Support: [supporting agents if needed]
```

### 1.2 Clarifying Questions (If Needed)
Only ask if critical ambiguity exists. Use AskUserQuestion tool:
```markdown
**Clarification Needed:**
1. [Specific question about scope/requirements]
> Reply with answers or say "proceed with assumptions"
```

### 1.3 Requirements Extraction
```markdown
**Functional Requirements:**
- [ ] FR1: [requirement]
- [ ] FR2: [requirement]

**Non-Functional Requirements:**
- [ ] NFR1: [performance/security/cross-DB compat]

**Constraints:**
- Must follow: [patterns from activated skills]
- Must not break: [existing functionality]
- DB compat: [MySQL + PostgreSQL via GrammarQueryManager]

**Success Criteria:**
- [ ] SC1: [how we verify it works]
```

---

## PHASE 2: PSEUDOCODE (P in SPARC)

### 2.1 Execution Plan
```markdown
## Execution Plan

### Step 1: [Phase Name]
- INTENT: What this achieves
- INPUT: What we need (files to read)
- OUTPUT: What we produce (files to create/modify)
- PATTERN: Which skill pattern applies
- VALIDATION: How we verify

### Step 2: [Phase Name]
...
```

### 2.2 Risk Assessment
```markdown
**Risk Matrix:**
| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| [Risk] | Low/Med/High | Low/Med/High | [Strategy] |
```

---

## PHASE 3: ARCHITECTURE (A in SPARC)

### 3.1 File Impact Analysis
```markdown
## Architecture Impact

**Files to CREATE:**
- `packages/Webkul/{Package}/src/path/File.php` - [purpose]

**Files to MODIFY:**
- `packages/Webkul/{Package}/src/path/File.php` - [changes needed]

**Files to READ (Context):**
- `packages/Webkul/{Package}/src/path/File.php` - [why needed]

**Config to UPDATE:**
- `packages/Webkul/{Package}/src/Config/*.php` - [what changes]

**Migrations:**
- [If DB schema changes needed]
```

### 3.2 UnoPim Architecture Checklist
```markdown
**Architecture Compliance:**
- [ ] Model implements Contract interface
- [ ] Repository extends Webkul\Core\Eloquent\Repository
- [ ] Controller uses constructor injection
- [ ] Routes follow naming: admin.{module}.{resource}.{action}
- [ ] ACL entry added if new permission needed
- [ ] Events dispatched: {domain}.{entity}.{action}.{before|after}
- [ ] GrammarQueryManager used for raw SQL (no MySQL-specific)
- [ ] HistoryAuditable + HistoryTrait if entity needs versioning
- [ ] TranslatableModel if entity has translatable fields
- [ ] Dark mode supported (dark: Tailwind variants)
- [ ] Blade uses <x-admin::*> components
```

### 3.3 Test Strategy
```markdown
**Testing Approach:**
- Pest PHP: [what to test, which suite]
- Playwright E2E: [if UI changes, which spec]
- Run: `./vendor/bin/pest --parallel`
```

---

## PHASE 4: REFINEMENT LOOP (R in SPARC)

### 4.1 Implementation Protocol

For each step in the execution plan:

```markdown
### Executing Step [N]: [Name]

**Reading:** [files being analyzed]
**Skill Applied:** [/unopim-* pattern being followed]

[Implementation code/changes]

**Checkpoint:**
- [ ] Follows /unopim-{layer} patterns
- [ ] No breaking changes to existing code
- [ ] Cross-DB compatible (MySQL + PostgreSQL)
- [ ] Types/contracts correct
```

### 4.2 Pattern Verification
After each significant change verify against the activated skill:

```yaml
verification:
  data_layer:
    - GrammarQueryManager for raw SQL? (not MySQL-specific)
    - Contract interface on models?
    - Repository pattern for data access?
    - Product values JSON structure preserved?

  infrastructure:
    - ServiceProvider registered correctly?
    - DataGrid columns/actions follow conventions?
    - Events dispatched with before/after pairs?
    - HistoryTrait if auditable entity?

  domain:
    - Type instance pattern for products?
    - Nested set methods for categories?
    - Attribute scope resolution correct?
    - Bouncer permissions checked?

  application:
    - Controller extends correct base?
    - Route naming convention followed?
    - ACL entry exists for new permission?
    - Form Request validation rules correct?
    - Event dispatching around CRUD?

  middleware:
    - Auth guard correct (admin vs api)?
    - SecureHeaders not bypassed?
    - Locale/channel validation in place?

  client:
    - <x-admin::*> Blade components used?
    - dark: variants on all UI elements?
    - VeeValidate on all forms?
    - Icon from unopim-admin font?
```

### 4.3 Self-Correction Protocol
If error encountered:
```markdown
**Issue Detected:**
- Error: [description]
- Location: [file:line]

**Root Cause:** [analysis using skill knowledge]
**Correction:** [fix applied]
```

---

## PHASE 5: COMPLETION (C in SPARC)

### 5.1 Deliverables Summary
```markdown
## Completion Report

**Task:** $ARGUMENTS
**Status:** COMPLETED

**Changes Made:**
| File | Action | Description |
|------|--------|-------------|
| [path] | Created/Modified | [brief description] |

**Patterns Applied:**
- /unopim-{layer}: [how it was applied]

**Quality Checks:**
- [ ] Follows UnoPim architecture patterns
- [ ] Cross-DB compatible (GrammarQueryManager)
- [ ] No TypeScript/PHP errors
- [ ] Dark mode supported
- [ ] ACL permissions correct
- [ ] Events dispatched
```

### 5.2 Verification Commands
```markdown
**To Verify:**
```bash
# PHP tests
./vendor/bin/pest --parallel

# E2E tests (if UI changes)
cd tests/e2e-pw && npx playwright test

# Lint
./vendor/bin/pint --test

# Clear caches after config changes
php artisan config:clear && php artisan cache:clear
```
```

### 5.3 Follow-ups
```markdown
**Suggested Next Steps:**
1. [Related improvement or test coverage]
2. [Migration to run if schema changed]
3. [Cache to clear or config to publish]
```

---

## ADAPTIVE EXECUTION MODES

### Mode: TRIVIAL/SIMPLE
Skip phases 2-3, execute directly:
```markdown
## Quick Fix: $ARGUMENTS
**Skill:** /unopim-{domain}
[Read → Analyze → Fix → Verify]
**Done:** [Summary of change]
```

### Mode: MODERATE
Abbreviated SPARC (combine phases 2-3):
```markdown
## Task: $ARGUMENTS
### Plan & Architecture
[Combined planning with file impact]
### Implementation
[Execution with checkpoints]
### Done
[Summary]
```

### Mode: COMPLEX/EPIC
Full SPARC with parallel agents via Task tool:
```markdown
## Complex Task: $ARGUMENTS

### Phase 1: Specification [full]
### Phase 2: Pseudocode [full]
### Phase 3: Architecture [full]
### Phase 4: Implementation
**Agent Coordination (parallel via Task tool):**
- Task("Backend", "...", "coder")      → [model/repo/controller]
- Task("Frontend", "...", "coder")     → [blade/vue/tailwind]
- Task("DataGrid", "...", "coder")     → [grid implementation]
- Task("Tests", "...", "tester")       → [pest/playwright]
- Task("Review", "...", "reviewer")    → [quality check]
### Phase 5: Completion [full report]
```

---

## SKILL ACTIVATION PROTOCOL

### Progressive Loading
Only load skills when their domain is detected:

```yaml
activation_rules:
  - trigger: "product|attribute|category|family"
    load: /unopim-domain (read completely, apply type/repo/model patterns)

  - trigger: "controller|route|acl|menu|api"
    load: /unopim-app (read completely, apply controller/route/ACL patterns)

  - trigger: "vue|blade|tailwind|form|modal|icon|dark mode"
    load: /unopim-client (read completely, apply component/design system patterns)

  - trigger: "model|repository|query|grammar|json|eloquent|migration"
    load: /unopim-data (read completely, apply grammar/model/repo patterns)

  - trigger: "datagrid|event|history|concord|provider|theme"
    load: /unopim-infra (read completely, apply DataGrid/event/provider patterns)

  - trigger: "auth|bouncer|permission|middleware|passport|locale|security"
    load: /unopim-middleware (read completely, apply auth/security patterns)

  - trigger: complex task spanning multiple layers
    load: /unopim-patterns (master reference for cross-layer coordination)
```

---

## AGENT PERSONAS (Multi-Agent for COMPLEX/EPIC)

```yaml
agents:
  laravel-architect:
    type: "coder"
    role: Orchestrator for complex multi-package changes
    strengths: System design, cross-package coordination, Concord modules

  backend-engineer:
    type: "coder"
    role: PHP/Laravel specialist
    strengths: Models, repositories, controllers, services, migrations, queues

  frontend-engineer:
    type: "coder"
    role: Vue.js 3 / Blade / Tailwind specialist
    strengths: Components, forms, DataGrid UI, dark mode, responsive design

  database-architect:
    type: "coder"
    role: Schema & query specialist
    strengths: Migrations, GrammarQueryManager, JSON values, Eloquent optimization

  api-engineer:
    type: "coder"
    role: REST API specialist
    strengths: AdminApi controllers, Passport, resources, ValueSetter

  test-engineer:
    type: "tester"
    role: Pest PHP & Playwright specialist
    strengths: Unit tests, feature tests, E2E specs, fixtures

  code-reviewer:
    type: "reviewer"
    role: Quality assurance & pattern compliance
    strengths: Architecture review, security audit, pattern verification

  explorer:
    type: "Explore"
    role: Codebase reconnaissance
    strengths: Finding patterns, locating files, understanding existing code
```

### Agent Coordination Pattern
```markdown
**Swarm Activated:**
Orchestrator: @laravel-architect

Workers (parallel via Task tool):
- @backend-engineer   → [model + repo + controller + migration]
- @frontend-engineer  → [blade views + vue components + tailwind]
- @api-engineer       → [API controller + routes + resources]
- @test-engineer      → [pest tests + playwright specs]
- @code-reviewer      → [final review against /unopim-patterns]

Execution Order:
1. Explorer: Understand existing code (parallel reads)
2. Backend + Database: Model/repo/migration (parallel)
3. Application: Controllers + routes + ACL (depends on 2)
4. Frontend: Views + components (depends on 3)
5. API: API controller + routes (parallel with 4)
6. Tests: Write and run (depends on 3-5)
7. Review: Pattern compliance check (depends on all)
```

---

## UNOPIM-SPECIFIC RULES

### Product Values JSON - NEVER Manipulate Directly
```php
// WRONG: Direct JSON manipulation
$product->values['common']['sku'] = 'new-sku';

// CORRECT: Use Attribute model methods
$attribute->setProductValue($value, $productValues, $channel, $locale);
$attribute->getValueFromProductValues($values, $channel, $locale);

// CORRECT (API): Use ValueSetter facade
ValueSetter::setCommon($data['values']['common']);
ValueSetter::setLocaleSpecific($data['values']['locale_specific']);
```

### Category Tree - NEVER Manipulate _lft/_rgt
```php
// WRONG: Direct nested set manipulation
$category->_lft = 5;

// CORRECT: Use NodeTrait methods
$category->appendToNode($parent);
$category->prependToNode($parent);
Category::scoped([])->defaultOrder()->get()->toTree();
```

### Database Queries - ALWAYS Cross-DB Compatible
```php
// WRONG: MySQL-specific
DB::raw("JSON_EXTRACT(values, '$.common.sku')");

// CORRECT: GrammarQueryManager
$grammar = GrammarQueryManager::getGrammar();
$grammar->jsonExtract('values', 'common', 'sku');
```

### Events - ALWAYS Before/After Pairs
```php
// CORRECT pattern for any CRUD operation:
Event::dispatch('catalog.product.create.before');
$product = $this->productRepository->create($data);
Event::dispatch('catalog.product.create.after', $product);
```

---

## COMMAND VARIANTS

| Command | Purpose |
|---------|---------|
| `/go $TASK` | Full orchestration (adaptive complexity) |
| `/go plan $TASK` | Specification + Architecture only (no code) |
| `/go analyze $TASK` | Deep analysis with risk assessment |
| `/go quick $TASK` | Force TRIVIAL mode (immediate execution) |
| `/go epic $TASK` | Force full SPARC with swarm coordination |

---

## CRITICAL RULES

1. **ALWAYS** load relevant `/unopim-*` skill before implementing
2. **NEVER** skip domain classification
3. **ALWAYS** use GrammarQueryManager for raw SQL (MySQL + PostgreSQL + SQLite)
4. **ALWAYS** implement Contract interfaces on new models
5. **ALWAYS** use Repository pattern for data access
6. **ALWAYS** dispatch before/after events around CRUD
7. **ALWAYS** use `<x-admin::*>` Blade components (never raw HTML for standard UI)
8. **ALWAYS** support dark mode with `dark:` Tailwind variants
9. **NEVER** manipulate product values JSON directly (use Attribute methods)
10. **NEVER** manipulate category `_lft`/`_rgt` directly (use NodeTrait)
11. **NEVER** write MySQL-specific queries without grammar abstraction
12. **NEVER** bypass Bouncer/ScopeMiddleware authentication
13. **NEVER** save files to the project root (use package directories)
14. **SCALE** planning depth to task complexity
15. **CHECKPOINT** after each significant change against skill patterns

---

## INITIALIZATION COMPLETE

Ready to receive task via `/go $ARGUMENTS`
