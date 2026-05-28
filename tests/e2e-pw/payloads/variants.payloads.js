/**
 * Variant payloads — thin alias around `products.buildVariant` for tests that
 * import variants directly without going through the products module.
 */
'use strict';

const products = require('./products.payloads');

module.exports = { build: products.buildVariant };
