# Functional Specification

The executable module registry is `constants/modules.ts`. It captures discovered pages, APIs, tables, mandatory fields, optional fields, dependencies, and business rules. This document summarizes the source-backed behavior to guide manual and automated QA.

## Catalog

Products require unique SKU, type, status boolean, and an attribute family. Product edit validates selected channel and locale. Product APIs cover simple products, configurable products, the legacy misspelled configurable endpoint, media upload, partial update, delete, and read/list. Tests must cover SKU uniqueness, variant uniqueness, image/video/file media, categories, associations, bulk update, mass delete, filters, search, pagination, and API/database parity.

Categories require a unique code on create and retain code uniqueness on update. Category hierarchy is exposed through tree, children-tree, and search routes. Tests must cover parent/child creation, locale dynamic fields, image media, delete restrictions, mass delete, API partial update, and tree integrity.

Category fields and attributes are configurable metadata. Attributes require unique supported codes and types. Select and multiselect require valid swatch types, while other types prohibit swatch types. Attributes used as super attributes cannot be deleted. Attribute groups and families support CRUD, copy, family/group mappings, and completeness settings.

## Settings

Channels require code, root category, locales, and currencies. Default/last channel deletion is blocked. Locales and currencies support grid CRUD and mass actions. Users require name, email, UI locale, role, and timezone; password is optional but must be at least six characters and match confirmation when provided; avatar images must match extension and MIME. Roles drive ACL permissions.

## Data Transfer

Imports and exports use `job_instances` plus `job_track` and batch tables. Imports require unique code on create and a configured entity type. Import image ZIP upload accepts only zip files up to 100 MB and extracts only verified image entries, rejecting zip-slip paths, non-image extensions, spoofed MIME, and oversized entries. Jobs support validate, start, link, index, stats, pause, resume, cancel, sample download, file download, and error report download.

## Configuration And Integrations

Configuration values are stored through core config validators. API integrations create and manage API keys/OAuth clients with permission types and route ACL. Webhook settings store field/value pairs and logs support view/delete/mass delete. Unsafe webhook URLs must be rejected by the validator.

## AI, History, Notifications, Installer

Magic AI supports prompt CRUD, platform CRUD, credential validation, content/image generation, model fetch, default prompt, and translation workflows. AI Agent supports credentials, agents, generation, execution, chat, conversations, dashboard analytics, rollback, and notifications, with throttled chat/dashboard routes. History exposes audit view, version view, restore, and delete. Notifications expose fetch, viewed, and read-all flows. Installer APIs prepare env, migration, seeding, admin setup, sample data, and must not allow takeover after installation.
