/**
 * Attribute payload generator.
 *
 * Attribute `type` decides which validation / option semantics apply.
 * `value_per_locale` + `value_per_channel` decide where in a product's
 * `values.*` map the attribute's value will live (see products.payloads.js).
 */
'use strict';

const { uniqueCode, SEED } = require('../utils/api');

const TYPES = [
  'text', 'textarea', 'price', 'boolean',
  'select', 'multiselect', 'datetime', 'date',
  'file', 'image', 'gallery', 'asset',
];

function build(overrides = {}) {
  const code = overrides.code || uniqueCode('attr');
  return {
    code,
    type: overrides.type || 'text',
    validation: overrides.validation || null,
    regex_pattern: overrides.regex_pattern || null,
    position: overrides.position ?? 0,
    is_required: overrides.is_required ?? false,
    is_unique: overrides.is_unique ?? false,
    value_per_locale: overrides.value_per_locale ?? false,
    value_per_channel: overrides.value_per_channel ?? false,
    enable_wysiwyg: overrides.enable_wysiwyg ?? false,
    is_filterable: overrides.is_filterable ?? false,
    is_visible_on_front: overrides.is_visible_on_front ?? false,
    labels: overrides.labels || { [SEED.defaultLocale]: `Attribute ${code}` },
  };
}

function buildText(overrides) { return build({ type: 'text', ...overrides }); }
function buildSelect(overrides) { return build({ type: 'select', ...overrides }); }
function buildMultiselect(overrides) { return build({ type: 'multiselect', ...overrides }); }
function buildBoolean(overrides) { return build({ type: 'boolean', ...overrides }); }
function buildPrice(overrides) { return build({ type: 'price', ...overrides }); }
function buildImage(overrides) { return build({ type: 'image', ...overrides }); }
function buildLocaleScoped(overrides) { return build({ value_per_locale: true, ...overrides }); }
function buildChannelLocaleScoped(overrides) {
  return build({ value_per_locale: true, value_per_channel: true, ...overrides });
}

function buildMissingType(overrides) {
  const p = build(overrides);
  delete p.type;
  return p;
}

/**
 * UnoPim enforces these fields as immutable on PUT:
 *   code, type, value_per_locale, value_per_channel, is_unique
 * Sending any of them returns 422 "The following fields cannot be modified".
 * `buildUpdate` strips the full set so PUT tests can mutate the rest freely.
 */
function buildUpdate(overrides = {}) {
  const p = build(overrides);
  for (const k of ['code', 'type', 'value_per_locale', 'value_per_channel', 'is_unique']) {
    delete p[k];
  }
  return p;
}

module.exports = {
  TYPES,
  build,
  buildText,
  buildSelect,
  buildMultiselect,
  buildBoolean,
  buildPrice,
  buildImage,
  buildLocaleScoped,
  buildChannelLocaleScoped,
  buildMissingType,
  buildUpdate,
};
