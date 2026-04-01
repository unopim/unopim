---
name: unopim-code-review
description: "Code review for UnoPim. Activates when reviewing code changes, checking standards compliance, flagging violations, or performing PR reviews; or when the user mentions review, standards, conventions, best practices, violations, or code quality."
license: MIT
metadata:
  author: unopim
---

# UnoPim Code Review

Review code changes against UnoPim coding standards and conventions.

## Critical Violations to Flag

### Backend PHP Code

Consult the `unopim-backend-dev` skill for detailed standards. Flag these violations:

**Architecture & Structure:**

- Missing Concord proxy model for new Eloquent models
- Repository not extending `Webkul\Core\Eloquent\Repository`
- Code placed outside the correct `packages/Webkul/{Package}/src/` directory
- Missing interface/contract for new models
- Direct `new Model()` instantiation instead of using repository pattern

**Naming & Conventions:**

- Class names not in PascalCase
- Methods/variables not in camelCase
- Event names not following `{domain}.{entity}.{action}.{before|after}` pattern
- Route names not following dot-separated convention
- Missing namespace declaration

**Documentation:**

- Missing docblocks on public/protected methods
- Missing `@param`/`@return` annotations
- Verbose docblocks (keep concise, one line ideal)

**Data Integrity:**

- Missing validation before data modification
- Using `$guarded = []` on models
- Missing CSRF protection on POST/PUT/DELETE routes
- Unsanitized user input in queries
- Missing ACL checks on admin routes

**Testing:**

- No tests for new functionality
- Tests missing `beforeEach` login setup
- Missing database assertions (`assertDatabaseHas`/`assertDatabaseMissing`)
- Test not following Pest `it()`/`describe()` pattern

**Service Provider:**

- Missing route loading in `boot()`
- Missing config merges in `register()` (menu, acl, importers, exporters)
- Missing migration loading
- Missing translation/view namespace registration

### Frontend Code

- Missing `<x-admin::` component usage (using raw HTML instead)
- Vue components not following project patterns
- Missing translations (`@lang()` / `trans()`)
- Hardcoded strings in UI

---

## Review Approach

1. **Scan for critical violations** listed above
2. **Cite specific skill files** when flagging issues
3. **Provide correct examples** from the skill documentation
4. **Group related issues** for clarity
5. **Be constructive** — explain why the standard exists

## Output Format

For each violation found:

```text
[Issue Type]: [Specific problem]
Location: [File path and line number]
Standard: [Link to relevant skill file]
Fix: [Brief explanation or example]
```
