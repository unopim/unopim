export type TestLayer =
  | 'smoke'
  | 'sanity'
  | 'regression'
  | 'e2e'
  | 'positive'
  | 'negative'
  | 'boundary'
  | 'equivalence'
  | 'exploratory'
  | 'security'
  | 'accessibility'
  | 'responsive'
  | 'browser'
  | 'api'
  | 'database'
  | 'performance'
  | 'session'
  | 'authorization'
  | 'authentication'
  | 'file-upload'
  | 'image'
  | 'csv-import'
  | 'excel-import'
  | 'export'
  | 'pagination'
  | 'search'
  | 'filters'
  | 'sorting'
  | 'bulk-actions'
  | 'keyboard'
  | 'error-handling'
  | 'toast'
  | 'modal'
  | 'network'
  | 'retry'
  | 'session-timeout'
  | 'concurrent';

export interface ModuleDefinition {
  key: string;
  name: string;
  area: 'auth' | 'dashboard' | 'catalog' | 'settings' | 'data-transfer' | 'configuration' | 'api' | 'ai' | 'system';
  path?: string;
  table?: string;
  apiPaths?: string[];
  mandatoryFields: string[];
  optionalFields: string[];
  dependencies: string[];
  businessRules: string[];
  layers: TestLayer[];
}

const commonGridLayers: TestLayer[] = [
  'smoke', 'sanity', 'regression', 'positive', 'negative', 'boundary', 'equivalence',
  'security', 'accessibility', 'responsive', 'browser', 'database', 'session',
  'authorization', 'pagination', 'search', 'filters', 'sorting', 'bulk-actions',
  'keyboard', 'error-handling', 'toast', 'modal', 'network', 'retry', 'concurrent'
];

export const modules: ModuleDefinition[] = [
  {
    key: 'authentication',
    name: 'Authentication',
    area: 'auth',
    path: '/admin/login',
    table: 'admins',
    mandatoryFields: ['email', 'password'],
    optionalFields: ['microsoft_sso'],
    dependencies: ['admin guard', 'admin-login throttle', 'roles'],
    businessRules: ['Inactive admins must not authenticate.', 'Failed login is throttled.', 'Session destroy uses DELETE /admin/logout.'],
    layers: ['smoke', 'sanity', 'regression', 'negative', 'security', 'authentication', 'session', 'session-timeout', 'accessibility', 'keyboard']
  },
  {
    key: 'dashboard',
    name: 'Dashboard',
    area: 'dashboard',
    path: '/admin/dashboard',
    mandatoryFields: [],
    optionalFields: ['channel', 'locale', 'date filters'],
    dependencies: ['catalog statistics', 'completeness dashboard data'],
    businessRules: ['Dashboard stats are available only to authenticated admins.', 'Completeness stats are served from the Completeness package.'],
    layers: ['smoke', 'sanity', 'regression', 'accessibility', 'responsive', 'browser', 'api', 'performance', 'session', 'authorization']
  },
  {
    key: 'products',
    name: 'Products',
    area: 'catalog',
    path: '/admin/catalog/products',
    table: 'products',
    apiPaths: ['/api/v1/rest/products', '/api/v1/rest/configurable-products'],
    mandatoryFields: ['sku', 'type', 'attribute_family_id'],
    optionalFields: ['status', 'values', 'categories', 'associations', 'images', 'videos'],
    dependencies: ['attribute families', 'attributes', 'categories', 'channels', 'locales'],
    businessRules: ['SKU is required, unique, and follows the Sku rule.', 'Status is boolean.', 'Edit route validates channel and locale.', 'Configurable products require unique variant combinations.'],
    layers: [...commonGridLayers, 'api', 'file-upload', 'image', 'export', 'performance']
  },
  {
    key: 'categories',
    name: 'Categories',
    area: 'catalog',
    path: '/admin/catalog/categories',
    table: 'categories',
    apiPaths: ['/api/v1/rest/categories'],
    mandatoryFields: ['code'],
    optionalFields: ['parent_id', 'additional_data', 'locale labels', 'media'],
    dependencies: ['category fields', 'locales'],
    businessRules: ['Code is required on create, unique, and follows the Code rule.', 'Code is unique on update.', 'Tree, children-tree, and search endpoints must preserve hierarchy.'],
    layers: [...commonGridLayers, 'api', 'file-upload', 'image']
  },
  {
    key: 'category-fields',
    name: 'Category Fields',
    area: 'catalog',
    path: '/admin/catalog/category-fields',
    table: 'category_fields',
    apiPaths: ['/api/v1/rest/category-fields'],
    mandatoryFields: ['code', 'type'],
    optionalFields: ['validations', 'options', 'translations'],
    dependencies: ['category field type config', 'locales'],
    businessRules: ['Field options are available for option-based field types.', 'Mass update and mass delete are ACL-protected.'],
    layers: [...commonGridLayers, 'api']
  },
  {
    key: 'attributes',
    name: 'Attributes',
    area: 'catalog',
    path: '/admin/catalog/attributes',
    table: 'attributes',
    apiPaths: ['/api/v1/rest/attributes'],
    mandatoryFields: ['code', 'type'],
    optionalFields: ['swatch_type', 'value_per_locale', 'value_per_channel', 'is_unique', 'options', 'ai_translate'],
    dependencies: ['attribute type config', 'locales', 'Magic AI for translatable text'],
    businessRules: ['Code is required, unique, not type/attribute_family_id, and follows Code.', 'Select and multiselect require a valid swatch_type.', 'Non-select types prohibit swatch_type.', 'Attributes used as configurable super attributes cannot be deleted.'],
    layers: [...commonGridLayers, 'api', 'image']
  },
  {
    key: 'attribute-groups',
    name: 'Attribute Groups',
    area: 'catalog',
    path: '/admin/catalog/attributegroups',
    table: 'attribute_groups',
    apiPaths: ['/api/v1/rest/attribute-groups'],
    mandatoryFields: ['code', 'name'],
    optionalFields: ['translations'],
    dependencies: ['locales'],
    businessRules: ['Group code is unique.', 'Groups can be mapped to attribute families.'],
    layers: [...commonGridLayers, 'api']
  },
  {
    key: 'attribute-families',
    name: 'Attribute Families',
    area: 'catalog',
    path: '/admin/catalog/families',
    table: 'attribute_families',
    apiPaths: ['/api/v1/rest/families'],
    mandatoryFields: ['code', 'name', 'groups'],
    optionalFields: ['family group mapping', 'completeness settings'],
    dependencies: ['attributes', 'attribute groups', 'Completeness package'],
    businessRules: ['Families can be copied.', 'Family completeness can be edited and mass updated.', 'Families referenced by products have delete restrictions.'],
    layers: [...commonGridLayers, 'api']
  },
  {
    key: 'locales',
    name: 'Locales',
    area: 'settings',
    path: '/admin/settings/locales',
    table: 'locales',
    apiPaths: ['/api/v1/rest/locales'],
    mandatoryFields: ['code', 'name', 'direction'],
    optionalFields: ['status'],
    dependencies: ['channels', 'translations'],
    businessRules: ['Locale code is unique.', 'Mass update and mass delete are supported.', 'API is read-only.'],
    layers: [...commonGridLayers, 'api']
  },
  {
    key: 'currencies',
    name: 'Currencies',
    area: 'settings',
    path: '/admin/settings/currencies',
    table: 'currencies',
    apiPaths: ['/api/v1/rest/currencies'],
    mandatoryFields: ['code'],
    optionalFields: ['symbol', 'decimal', 'status'],
    dependencies: ['channels'],
    businessRules: ['Currency code is unique.', 'Mass update and mass delete are supported.', 'API is read-only.'],
    layers: [...commonGridLayers, 'api']
  },
  {
    key: 'channels',
    name: 'Channels',
    area: 'settings',
    path: '/admin/settings/channels',
    table: 'channels',
    apiPaths: ['/api/v1/rest/channels'],
    mandatoryFields: ['code', 'root_category_id', 'locales', 'currencies'],
    optionalFields: ['locale translated names'],
    dependencies: ['root category', 'active locales', 'currencies'],
    businessRules: ['Code is required, unique, and follows Code.', 'At least one locale and currency are required.', 'Last/default channel cannot be deleted.'],
    layers: [...commonGridLayers, 'api']
  },
  {
    key: 'users',
    name: 'Users',
    area: 'settings',
    path: '/admin/settings/users',
    table: 'admins',
    mandatoryFields: ['name', 'email', 'ui_locale_id', 'role_id', 'timezone'],
    optionalFields: ['password', 'password_confirmation', 'status', 'image'],
    dependencies: ['roles', 'locales'],
    businessRules: ['Name allows alpha numeric spaces.', 'Email is required, valid, and unique.', 'Password min length is 6 and confirmation must match.', 'Avatar accepts jpeg, png, jpg, svg, gif with MIME-extension match.'],
    layers: [...commonGridLayers, 'file-upload', 'image', 'authentication', 'authorization']
  },
  {
    key: 'roles',
    name: 'Roles and Permissions',
    area: 'settings',
    path: '/admin/settings/roles',
    table: 'roles',
    mandatoryFields: ['name', 'permission_type'],
    optionalFields: ['description', 'permissions'],
    dependencies: ['ACL config', 'Bouncer middleware'],
    businessRules: ['ACL keys gate route access.', 'Custom roles store permission arrays.', 'Unauthorized admin routes return 403.'],
    layers: [...commonGridLayers, 'authorization', 'security']
  },
  {
    key: 'data-transfer-imports',
    name: 'Imports',
    area: 'data-transfer',
    path: '/admin/settings/data-transfer/imports',
    table: 'job_instances',
    mandatoryFields: ['code', 'entity_type'],
    optionalFields: ['file', 'action', 'validation_strategy', 'allowed_errors', 'field_separator', 'images_directory_path', 'filters'],
    dependencies: ['importers config', 'private storage', 'queue jobs'],
    businessRules: ['Code is unique on create.', 'Entity type must be configured importer.', 'Image ZIP upload accepts zip up to 100 MB and extracts only verified image files.', 'Start/link/index require processed rows and a valid job.'],
    layers: [...commonGridLayers, 'csv-import', 'excel-import', 'file-upload', 'image', 'retry', 'performance']
  },
  {
    key: 'data-transfer-exports',
    name: 'Exports',
    area: 'data-transfer',
    path: '/admin/settings/data-transfer/exports',
    table: 'job_instances',
    mandatoryFields: ['code', 'entity_type'],
    optionalFields: ['filters', 'format', 'field_separator'],
    dependencies: ['exporters config', 'private storage', 'queue jobs'],
    businessRules: ['Export jobs can validate, start, download samples, download output, and download error reports.', 'Filters include channels, locales, currencies, attributes, families, and categories.'],
    layers: [...commonGridLayers, 'export', 'csv-import', 'excel-import', 'retry', 'performance']
  },
  {
    key: 'job-tracker',
    name: 'Job Tracker',
    area: 'data-transfer',
    path: '/admin/settings/data-transfer/tracker',
    table: 'job_track',
    mandatoryFields: [],
    optionalFields: ['batch_id', 'state'],
    dependencies: ['imports', 'exports', 'job batches'],
    businessRules: ['Tracker supports viewing batch state and downloading source, archive, and log files.', 'Pause, resume, and cancel return localized success messages.'],
    layers: [...commonGridLayers, 'retry', 'performance']
  },
  {
    key: 'configuration',
    name: 'Configuration',
    area: 'configuration',
    path: '/admin/configuration',
    table: 'core_config',
    mandatoryFields: [],
    optionalFields: ['integration fields', 'Magic AI settings', 'webhook settings', 'download paths'],
    dependencies: ['system config', 'core config validators'],
    businessRules: ['Search is ACL-protected.', 'Downloads route through configured path.', 'Configuration validators run before storing values.'],
    layers: [...commonGridLayers, 'file-upload', 'security']
  },
  {
    key: 'api-keys',
    name: 'API Integrations',
    area: 'configuration',
    path: '/admin/integrations/api-keys',
    table: 'api_keys',
    mandatoryFields: ['name', 'permission_type'],
    optionalFields: ['permissions', 'revoked'],
    dependencies: ['Passport OAuth clients', 'admins', 'API ACL'],
    businessRules: ['API credentials are generated and regenerated from integration routes.', 'Revoked keys must not authenticate API calls.'],
    layers: [...commonGridLayers, 'api', 'security', 'authorization']
  },
  {
    key: 'notifications',
    name: 'Notifications',
    area: 'system',
    path: '/admin/notifications',
    table: 'notifications',
    mandatoryFields: [],
    optionalFields: ['route', 'route_params', 'title', 'read'],
    dependencies: ['user_notifications'],
    businessRules: ['Admins can fetch notifications, mark viewed, and mark all read.', 'Notification read state is user-specific.'],
    layers: ['smoke', 'sanity', 'regression', 'api', 'database', 'session', 'authorization', 'toast', 'network']
  },
  {
    key: 'magic-ai',
    name: 'Magic AI',
    area: 'ai',
    path: '/admin/magic-ai/prompt',
    table: 'magic_ai_prompts',
    mandatoryFields: ['title', 'prompt'],
    optionalFields: ['tone', 'type', 'purpose', 'status', 'platform'],
    dependencies: ['magic_ai_platforms', 'magic_ai_system_prompts', 'configuration'],
    businessRules: ['Content/image generation require valid credentials.', 'Prompt CRUD and platform CRUD are ACL-protected.', 'Translation endpoints only persist translatable data.'],
    layers: [...commonGridLayers, 'api', 'network', 'retry']
  },
  {
    key: 'ai-agent',
    name: 'AI Agent',
    area: 'ai',
    path: '/admin/ai-agent/agents',
    table: 'ai_agent_agents',
    mandatoryFields: ['name', 'credentialId'],
    optionalFields: ['instructions', 'tools', 'status', 'maxTokens'],
    dependencies: ['ai_agent_credentials', 'magic ai configuration'],
    businessRules: ['Chat and dashboard routes are throttled.', 'Agent execution, approvals, conversations, rollback, and notifications are authenticated admin routes.'],
    layers: [...commonGridLayers, 'api', 'network', 'retry', 'performance']
  },
  {
    key: 'webhooks',
    name: 'Webhooks',
    area: 'configuration',
    path: '/admin/webhook/settings',
    table: 'webhook_settings',
    mandatoryFields: ['field'],
    optionalFields: ['value', 'logs'],
    dependencies: ['SafeWebhookUrl validator', 'webhook_logs'],
    businessRules: ['Webhook settings store field/value pairs.', 'Logs can be viewed, deleted, and mass deleted.', 'Unsafe callback URLs must be rejected.'],
    layers: [...commonGridLayers, 'api', 'security', 'network']
  },
  {
    key: 'history',
    name: 'History and Audit',
    area: 'system',
    path: '/admin/history/view/{entity}/{id}',
    table: 'audits',
    mandatoryFields: ['entity', 'id'],
    optionalFields: ['historyId', 'versionId'],
    dependencies: ['HistoryControl package', 'auditing triggers'],
    businessRules: ['Version views, restore, and delete are ACL-protected.', 'History is tied to auditable entities.'],
    layers: ['smoke', 'regression', 'database', 'authorization', 'modal', 'error-handling']
  },
  {
    key: 'installer',
    name: 'Installer',
    area: 'system',
    path: '/install',
    mandatoryFields: ['env-file setup', 'admin config'],
    optionalFields: ['sample data'],
    dependencies: ['server requirements', 'migrations', 'seeders'],
    businessRules: ['Installer APIs run under installer locale/session middleware.', 'Installed instances must not be takeoverable.'],
    layers: ['smoke', 'security', 'negative', 'error-handling', 'network']
  }
];

export const moduleByKey = (key: string): ModuleDefinition => {
  const found = modules.find((module) => module.key === key);
  if (!found) {
    throw new Error(`Unknown module: ${key}`);
  }
  return found;
};
