# UnoPim - Project Overview

## Executive Summary

UnoPim is an open-source **Product Information Management (PIM)** system built on the Laravel 10.x framework. It provides businesses with a centralized repository to organize, manage, and enrich product information across multiple channels, locales, and currencies. The system supports AI-powered content generation, comprehensive data import/export, and a full RESTful API for third-party integration.

**Version:** 1.0.x
**License:** MIT
**Repository:** [github.com/unopim/unopim](https://github.com/unopim/unopim)

---

## Project Identity

| Field | Value |
|-------|-------|
| **Project Name** | UnoPim |
| **Type** | Product Information Management (PIM) |
| **Repository Type** | Monolith (modular package architecture) |
| **Primary Language** | PHP 8.2+ |
| **Framework** | Laravel 10.x |
| **Frontend** | Vue.js 3 + Tailwind CSS + Blade Templates |
| **Database** | MySQL 8.0+ / PostgreSQL 14+ |
| **Search** | Elasticsearch 8.x (optional) |
| **Cache/Queue** | Redis (optional), Database fallback |
| **API** | RESTful (OAuth2 via Laravel Passport) |
| **Build Tool** | Vite 4.x |
| **Testing** | Pest PHP + PHPUnit + Playwright |

---

## Technology Stack Summary

| Category | Technology | Version | Purpose |
|----------|-----------|---------|---------|
| **Backend Framework** | Laravel | ^10.0 | Core application framework |
| **Language** | PHP | ^8.2 | Server-side language |
| **Frontend Framework** | Vue.js | 3.x (ESM) | Reactive UI components |
| **CSS Framework** | Tailwind CSS | 3.x | Utility-first styling |
| **Template Engine** | Blade | (Laravel) | Server-side rendering |
| **Database (Primary)** | MySQL | 8.0.32+ | Relational data storage |
| **Database (Alt)** | PostgreSQL | 14+ | Alternative RDBMS |
| **Search Engine** | Elasticsearch | 8.17+ | Full-text product search |
| **Queue** | Redis/Database | - | Background job processing |
| **Cache** | File/Redis | - | Application caching |
| **API Auth** | Laravel Passport | ^12.2 | OAuth2 API authentication |
| **Session Auth** | Laravel Sanctum | ^3.2 | Session-based admin auth |
| **AI Integration** | OpenAI API | ^0.7.8 | Content generation (Magic AI) |
| **Build Tool** | Vite | ^4.0 | Frontend asset bundling |
| **Form Validation** | VeeValidate | 3.x | Client-side validation |
| **PDF Generation** | DomPDF | ^2.0 | PDF export capability |
| **Excel/CSV** | Maatwebsite Excel + OpenSpout | ^3.1 / ^4.28 | Data import/export |
| **Audit Trail** | Laravel Auditing | ^13.6 | Change tracking |
| **Nested Sets** | kalnoy/nestedset | ^6.0 | Category tree structure |
| **Modular Architecture** | Konekt Concord | ^1.2 | Package module system |
| **Testing** | Pest PHP | ^2.6 | PHP test framework |
| **E2E Testing** | Playwright | Latest | Browser automation tests |
| **Code Style** | Laravel Pint | ^1.22 | PHP code formatting |
| **Docker** | Docker Compose | - | Container orchestration |

---

## Architecture Classification

- **Architecture Pattern:** Modular Monolith (Service-Oriented Package Architecture)
- **Design Pattern:** Repository Pattern + Service Layer
- **Frontend Pattern:** Server-rendered Blade with Vue.js Islands
- **Data Storage:** EAV-like JSON storage for product attributes
- **Category Structure:** Nested Set Model (Materialized Path)
- **API Style:** RESTful with versioned endpoints (v1)
- **Authentication:** Dual-guard (Session for Web, OAuth2 for API)
- **Authorization:** Role-Based Access Control (RBAC) with 80+ granular permissions

---

## Core Features

### Product Management
- Simple and Configurable product types
- Flexible attribute system (12 attribute types)
- Attribute families and groups for organized product data
- Product associations (related, up-sell, cross-sell)
- Product bulk editing
- Product completeness scoring per channel/locale

### Catalog Organization
- Hierarchical category trees (nested set)
- Custom category fields
- Multi-channel product distribution
- Multi-locale product content

### Data Transfer
- CSV/XLSX import and export
- Batch processing with job tracking
- Quick export functionality
- Error reporting and validation

### AI-Powered Features (Magic AI)
- Automated product content generation
- Multi-locale content translation
- Custom AI prompts
- System prompt management

### Integration
- RESTful API (v1) with OAuth2 authentication
- Webhook support for event-driven integrations
- Elasticsearch for scalable search

### Administration
- Role-based access control (RBAC)
- User management with permission granularity
- Multi-locale admin interface (33 languages)
- Dark/Light theme support
- Version control and history tracking
- Dashboard with volume monitoring

---

## Modular Package Architecture

UnoPim is built on **19 internal Webkul packages**, each providing a distinct domain:

| Package | Purpose |
|---------|---------|
| **Admin** | Admin panel UI, controllers, routes, views |
| **AdminApi** | REST API controllers, OAuth2, API key management |
| **Attribute** | Attribute system (types, options, families, groups) |
| **Category** | Category tree, fields, options |
| **Completeness** | Product completeness scoring |
| **Core** | Shared utilities, models, middleware, helpers |
| **DataGrid** | Reusable data grid component |
| **DataTransfer** | Import/export engine, job processing |
| **DebugBar** | Development debugging tools |
| **ElasticSearch** | Elasticsearch integration and indexing |
| **FPC** | Full Page Cache |
| **HistoryControl** | Version control and change tracking |
| **Installer** | GUI installation wizard |
| **Inventory** | Inventory management |
| **MagicAI** | AI content generation (OpenAI integration) |
| **Notification** | In-app and email notifications |
| **Product** | Product models, types, services |
| **Theme** | Theme management and view rendering |
| **User** | Authentication, roles, permissions |
| **Webhook** | Webhook event dispatching |

---

## Scalability

UnoPim is designed to handle enterprise-scale product catalogs:
- Tested with **10+ million products**
- Elasticsearch integration for performant search
- Queue-based processing for heavy operations
- Response caching for API performance
- Laravel Octane support for high-concurrency

---

## Supported Locales (33)

ar_AE, ca_ES, da_DK, de_DE, en_AU, en_GB, en_NZ, en_US, es_ES, es_VE, fi_FI, fr_FR, hi_IN, hr_HR, id_ID, it_IT, ja_JP, ko_KR, mn_MN, nl_NL, no_NO, pl_PL, pt_BR, pt_PT, ro_RO, ru_RU, sv_SE, tl_PH, tr_TR, uk_UA, vi_VN, zh_CN, zh_TW

---

## Related Documentation

- [Architecture](./architecture.md) - System architecture and design patterns
- [Source Tree Analysis](./source-tree-analysis.md) - Directory structure and organization
- [Data Models](./data-models.md) - Database schema and relationships
- [API Contracts](./api-contracts.md) - REST API endpoint documentation
- [Component Inventory](./component-inventory.md) - UI component library
- [Development Guide](./development-guide.md) - Setup and development workflow
- [Deployment Guide](./deployment-guide.md) - Deployment and infrastructure
