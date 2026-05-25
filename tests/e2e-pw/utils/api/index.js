/**
 * Barrel re-export so every spec file can do:
 *   const api = require('../../utils/api');
 *
 * and reach `api.get`, `api.authenticate`, `api.RESPONSE_TIME`, etc. in one
 * place. We also re-export the legacy paths (`uniqueCode`, `authenticate`) the
 * pre-existing `tests/10-api/helpers/api-helpers.js` exposed, so older specs
 * can be migrated incrementally.
 */
'use strict';

const config = require('./config');
const auth = require('./auth-helper');
const req = require('./request-wrapper');
const schema = require('./schema-validator');
const validator = require('./response-validator');

/** Unique, parallel-safe ID for test data. */
function uniqueCode(prefix = 'test') {
  const { randomBytes } = require('crypto');
  return `${prefix}_${Date.now().toString(36)}_${randomBytes(3).toString('hex')}`;
}

module.exports = {
  ...config,
  ...auth,
  ...req,
  ...schema,
  ...validator,
  uniqueCode,
};
