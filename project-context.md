---
project_name: 'unopim'
user_name: 'Abdulrahmangamal'
date: '2026-02-08'
sections_completed: ['technology_stack', 'architecture', 'conventions']
existing_patterns_found: 5
---

# Project Context for AI Agents

_This file contains critical rules and patterns that AI agents must follow when implementing code in this project. Focus on unobvious details that agents might otherwise miss._

---

## Technology Stack & Versions

- **Backend Framework**: Laravel 10.x
- **Language**: PHP ^8.2
- **Search Engine**: Elasticsearch ^8.17
- **Database**: MySQL (suggested by `ext-pdo_mysql`)
- **Frontend Tooling**: Vite ^4.0, TailwindCSS (likely, check `tailwind.config.js` if exists)
- **Key Packages**:
  - `konekt/concord` (Modular Architecture)
  - `astrotomic/laravel-translatable` (Localization)
  - `maatwebsite/excel` (Import/Export)
  - `openai-php/laravel` (AI Integration)

## Critical Implementation Rules

### Architecture Rules

- **Modular Design**: The application follows a modular architecture using `konekt/concord`. Features are organized into proper packages under `packages/Webkul/` (e.g., `Attribute`, `Product`, `Category`).
- **Service Layer**: Business logic should be encapsulated in Services or Repositories (`prettus/l5-repository`), not bloated Controllers.
- **DTOs**: Use Data Transfer Objects for complex data passing between layers.

### Code Style & Conventions

- **Strict Typing**: PHP 8.2 features (typed properties, return types) must be used.
- **Localization**: All user-facing strings must be translatable using `__('package::file.key')` pattern.
- **API Standards**: Follow JSON Reset API standards for `Webkul\AdminApi`.

### Development Workflow

- **Dependency Management**:
  - PHP: `composer`
  - JS: `npm` / `yarn`
- **Build Process**: Run `npm run dev` for assets compilation.
