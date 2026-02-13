# UnoPim - Project Documentation Index

> Generated: 2026-02-08 | Scan Level: Exhaustive | Mode: Initial Scan

---

## Project Overview

- **Type:** Monolith (modular package architecture)
- **Primary Language:** PHP 8.2+ (Laravel 10.x)
- **Frontend:** Vue.js 3 + Tailwind CSS + Blade
- **Database:** MySQL 8.0+ / PostgreSQL 14+
- **Architecture:** Modular monolith with 19 Webkul packages
- **API:** RESTful v1 (OAuth2 via Laravel Passport)

---

## Quick Reference

| Attribute | Value |
|-----------|-------|
| **Tech Stack** | Laravel 10 + Vue.js 3 + Tailwind CSS |
| **Entry Point** | `public/index.php` (web), `artisan` (CLI) |
| **Architecture Pattern** | Modular Monolith, Repository Pattern |
| **Package System** | Konekt Concord (19 packages) |
| **Testing** | Pest PHP (9 suites) + Playwright E2E (23 specs) |
| **CI/CD** | 3 GitHub Actions workflows |
| **Docker** | 4-service Docker Compose |
| **Locales** | 33 languages |
| **API Endpoints** | 40+ REST endpoints |
| **Admin Permissions** | 80+ granular ACL permissions |

---

## Generated Documentation

### Core Documents
- [Project Overview](./project-overview.md) - Executive summary, tech stack, features, package inventory
- [Architecture](./architecture.md) - System architecture, layers, data design, auth, queue, testing strategy
- [Source Tree Analysis](./source-tree-analysis.md) - Complete directory structure with annotations
- [Data Models](./data-models.md) - Database schema, all tables, relationships, JSON structures
- [API Contracts](./api-contracts.md) - REST API endpoints, authentication, admin routes
- [Component Inventory](./component-inventory.md) - 95+ UI components, Vue plugins, styling system
- [Development Guide](./development-guide.md) - Setup, commands, testing, package development, contribution
- [Deployment Guide](./deployment-guide.md) - Docker, CI/CD, queue, Elasticsearch, performance, security

### Patterns & Skills Reference (by Architectural Layer)

- [DATA/EXTERNAL Layer](./patterns-data-external.md) - Database grammars, Eloquent models, Repository pattern, TranslatableModel, Elasticsearch, MagicAI/OpenAI, Mail, Queue, Cache, Filesystem, product values JSON structure
- [INFRASTRUCTURE Layer](./patterns-infrastructure.md) - Konekt Concord modules, ServiceProviders, DataGrid abstract class, Event system, HistoryControl/Auditing, FPC cache invalidation, Theme/Vite build, Notification/Webhook infrastructure
- [DOMAIN Layer](./patterns-domain.md) - Product types (Simple/Configurable), Attribute system (12 types), Category nested set, User/Role RBAC, DataTransfer import/export, MagicAI orchestrator, cross-domain event dispatching
- [APPLICATION Layer](./patterns-application.md) - Admin controllers, API controllers, route patterns, Form Requests, ACL permission tree, Menu config, DataGrid implementations, event naming conventions, request/response flows
- [MIDDLEWARE Layer](./patterns-middleware.md) - HTTP Kernel stack, SecureHeaders, Bouncer auth, ScopeMiddleware API auth, Locale/Channel validation, Passport OAuth2, CORS, rate limiting, session config, middleware execution flows
- [CLIENT Layer & Design System](./patterns-client-designsystem.md) - Vue.js 3 app architecture, Blade component library, Tailwind theme (Violet/Cherry), button/label classes, icon font, VeeValidate, DataGrid filters, dark mode, page templates, JS plugins

---

## Existing Project Documentation

- [README.md](../README.md) - Project introduction, installation, Docker setup
- [Features.md](../Features.md) - Feature list
- [Changelog.md](../Changelog.md) - Version history (v0.1.0 through v1.0.0)
- [SECURITY.md](../SECURITY.md) - Security policy, vulnerability reporting
- [CODE_OF_CONDUCT.md](../CODE_OF_CONDUCT.md) - Community guidelines
- [UPGRADE.md](../UPGRADE.md) - Manual upgrade instructions
- [UPGRADE-0.1.x-0.2.0.md](../UPGRADE-0.1.x-0.2.0.md) - Version-specific upgrade
- [UPGRADE-0.2.x-0.3.0.md](../UPGRADE-0.2.x-0.3.0.md) - Version-specific upgrade
- [UPGRADE-0.3.x-1.0.0.md](../UPGRADE-0.3.x-1.0.0.md) - Version-specific upgrade
- [CHANGE_IMPACT_CLASSIFICATION.md](../CHANGE_IMPACT_CLASSIFICATION.md) - Change impact classification
- [ElasticSearch Guide](../packages/Webkul/ElasticSearch/Guide.md) - Elasticsearch integration guide

---

## Getting Started

### For AI-Assisted Development
1. Start with this **index.md** as your primary entry point
2. Read [Project Overview](./project-overview.md) for high-level understanding
3. Read [Architecture](./architecture.md) for system design and patterns
4. Read [Source Tree Analysis](./source-tree-analysis.md) to locate code
5. Read [Data Models](./data-models.md) for database schema understanding
6. Reference [API Contracts](./api-contracts.md) for endpoint work
7. Reference [Component Inventory](./component-inventory.md) for UI development
8. Browse [Patterns & Skills Reference](#patterns--skills-reference-by-architectural-layer) for exact implementation patterns per layer
9. Follow [Development Guide](./development-guide.md) for setup and commands

### For New Developers
1. Follow [Development Guide](./development-guide.md) for local setup
2. Read [Architecture](./architecture.md) to understand the system
3. Review [Source Tree Analysis](./source-tree-analysis.md) for navigation
4. Check existing [README.md](../README.md) for quick start

### For Feature Planning (Brownfield PRD)
1. Use this index as input to the PRD workflow
2. [Architecture](./architecture.md) provides constraints and patterns
3. [Data Models](./data-models.md) shows existing schema for extension
4. [API Contracts](./api-contracts.md) documents existing endpoints for expansion
5. [Component Inventory](./component-inventory.md) identifies reusable UI components

---

## Key Architectural Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Framework | Laravel 10 | PHP ecosystem, rich package library, Eloquent ORM |
| Frontend | Vue.js 3 Islands + Blade | Server-rendered with selective interactivity |
| Module System | Konekt Concord | Clean package separation within monolith |
| Product Data | JSON column (values) | Flexible attribute storage without EAV join overhead |
| Categories | Nested Set Model | Efficient tree operations (kalnoy/nestedset) |
| Authentication | Dual-guard (Session + OAuth2) | Web admin + API integration |
| Authorization | RBAC with 80+ permissions | Granular access control per feature |
| Search | Elasticsearch (optional) | Scalable full-text search for large catalogs |
| Queue | Redis/Database | Background processing for imports/exports |
| AI | OpenAI API | Content generation and translation |

---

## Code Conventions Quick Reference

| Area | Convention |
|------|-----------|
| PHP Style | Laravel Pint (PSR-12 based) |
| Data Access | Repository Pattern |
| Business Logic | Service classes |
| Dependency Injection | Contracts (interfaces) |
| Shared Behavior | Traits (TranslatableModel, HistoryTrait) |
| Routes | Named routes: `admin.{module}.{resource}.{action}` |
| API Routes | `admin.api.{resource}.{action}` |
| ACL Keys | `{module}.{resource}.{action}` |
| Blade Components | `<x-admin::component-name>` |
| Vue Components | `<v-component-name>` registered globally |
| Translations | `@lang('admin::app.{path}')` |
