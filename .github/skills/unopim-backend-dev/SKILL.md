---
name: unopim-backend-dev
description: "Backend PHP development for UnoPim. Activates when writing PHP code, creating classes, models, repositories, events, listeners, or tests; or when the user mentions model, repository, controller, service, event, listener, observer, Concord, proxy, or needs to write backend code. MUST be invoked before writing any PHP tests."
license: MIT
metadata:
  author: unopim
---

# UnoPim Backend Development

This skill provides guidance for developing UnoPim backend PHP code according to project standards and conventions.

## When to Use This Skill

**ALWAYS invoke this skill before:**

- Writing new PHP tests (`*Test.php` files)
- Creating new PHP classes, models, or repositories
- Modifying existing backend PHP code
- Adding events, listeners, or observers
- Working with Eloquent models and Concord proxies

## Instructions

Follow UnoPim project conventions when adding or modifying backend PHP code:

1. **Coding style**: See [coding-conventions.md](coding-conventions.md) for Laravel/PSR standards
2. **Architecture patterns**: See [architecture.md](architecture.md) for Concord, Repository, Strategy patterns
3. **Models and repositories**: See [models-repositories.md](models-repositories.md) for Eloquent and Concord proxy patterns
4. **Events and listeners**: See [events-listeners.md](events-listeners.md) for event-driven patterns
5. **Writing tests**: See [testing.md](testing.md) for Pest testing conventions

## Key Principles

- Follow Laravel coding standards with `pint` (Laravel preset)
- Use the Repository pattern — all repos extend `Webkul\Core\Eloquent\Repository`
- Use Concord proxy models for model extensibility
- Place new code under the appropriate `packages/Webkul/{Package}/src/` directory
- Use PSR-4 autoloading: `Webkul\{Package}\` namespace
- Events follow `{domain}.{entity}.{action}.{before|after}` naming
- Product/category values use structured JSON with scoping (`common`, `locale_specific`, `channel_specific`, `channel_locale_specific`)
- Run `./vendor/bin/pint` before committing
- Run `./vendor/bin/pest` to verify tests pass
