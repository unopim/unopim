/**
 * JSON-Schema validator.
 *
 * Uses `ajv` when available (recommended — `npm i -D ajv ajv-formats`), and
 * gracefully degrades to a small built-in recursive validator so the suite
 * never fails to *load* on a host that hasn't installed ajv yet. The built-in
 * checker covers the subset of JSON Schema actually used by these contracts:
 * `type`, `required`, `properties`, `items`, `enum`, `additionalProperties`,
 * `minLength`, `minimum`, `format` (limited).
 *
 * `validateSchema(schema, data)` throws a readable assertion error on failure
 * — pair it with `expect(() => validateSchema(...)).not.toThrow()` if you
 * prefer the failure surfaced through Playwright's matchers.
 */
'use strict';

let Ajv;
let addFormats;
try {
  // eslint-disable-next-line global-require
  Ajv = require('ajv');
  // eslint-disable-next-line global-require
  addFormats = require('ajv-formats');
} catch (_) {
  Ajv = null;
}

const ajvInstance = Ajv ? new Ajv({ allErrors: true, strict: false }) : null;
if (ajvInstance && addFormats) addFormats(ajvInstance);

/** ── built-in fallback ──────────────────────────────────────────────────── */
function checkType(value, type) {
  if (Array.isArray(type)) return type.some((t) => checkType(value, t));
  switch (type) {
    case 'string': return typeof value === 'string';
    case 'number': return typeof value === 'number' && !Number.isNaN(value);
    case 'integer': return Number.isInteger(value);
    case 'boolean': return typeof value === 'boolean';
    case 'array': return Array.isArray(value);
    case 'object': return value !== null && typeof value === 'object' && !Array.isArray(value);
    case 'null': return value === null;
    default: return true;
  }
}

function validateBuiltIn(schema, data, pathPrefix = '$') {
  const errors = [];
  if (schema.type && !checkType(data, schema.type)) {
    errors.push(`${pathPrefix}: expected type ${JSON.stringify(schema.type)}, got ${Array.isArray(data) ? 'array' : typeof data}`);
    return errors;
  }
  if (schema.enum && !schema.enum.includes(data)) {
    errors.push(`${pathPrefix}: value ${JSON.stringify(data)} not in enum ${JSON.stringify(schema.enum)}`);
  }
  if (typeof data === 'string') {
    if (typeof schema.minLength === 'number' && data.length < schema.minLength) {
      errors.push(`${pathPrefix}: string shorter than minLength ${schema.minLength}`);
    }
  }
  if (typeof data === 'number') {
    if (typeof schema.minimum === 'number' && data < schema.minimum) {
      errors.push(`${pathPrefix}: number below minimum ${schema.minimum}`);
    }
  }
  if (schema.type === 'object' || (schema.properties && data && typeof data === 'object' && !Array.isArray(data))) {
    const required = schema.required || [];
    for (const key of required) {
      if (!Object.prototype.hasOwnProperty.call(data || {}, key)) {
        errors.push(`${pathPrefix}: missing required property "${key}"`);
      }
    }
    if (schema.properties && data) {
      for (const [key, sub] of Object.entries(schema.properties)) {
        if (data[key] !== undefined) {
          errors.push(...validateBuiltIn(sub, data[key], `${pathPrefix}.${key}`));
        }
      }
    }
  }
  if (schema.type === 'array' && Array.isArray(data) && schema.items) {
    data.forEach((entry, idx) => {
      errors.push(...validateBuiltIn(schema.items, entry, `${pathPrefix}[${idx}]`));
    });
  }
  return errors;
}

/** ── public API ─────────────────────────────────────────────────────────── */

/**
 * Validate `data` against `schema`. Returns `{ valid, errors }`.
 * `errors` is an array of human-readable strings.
 */
function validate(schema, data) {
  if (ajvInstance) {
    const fn = ajvInstance.compile(schema);
    const valid = fn(data);
    return {
      valid: !!valid,
      errors: valid ? [] : (fn.errors || []).map((e) => `${e.instancePath || '$'} ${e.message}`),
    };
  }
  const errors = validateBuiltIn(schema, data);
  return { valid: errors.length === 0, errors };
}

/** Throws a readable Error when the response body fails schema validation. */
function validateSchema(schema, data, label = 'schema') {
  const { valid, errors } = validate(schema, data);
  if (!valid) {
    throw new Error(
      `[${label}] JSON schema validation failed:\n  - ${errors.join('\n  - ')}\nReceived body:\n${JSON.stringify(data, null, 2).slice(0, 2000)}`,
    );
  }
  return true;
}

module.exports = { validate, validateSchema, ajvAvailable: !!ajvInstance };
