/**
 * Media-upload payload generator.
 *
 * The endpoint is `POST /api/v1/rest/media-files/product`
 *               (`POST /api/v1/rest/media-files/category` for categories) and
 * the multipart body must include:
 *   - file:           the binary
 *   - sku / code:     identifier of the product / category
 *   - attribute /
 *     category_field: the target field code
 *
 * Playwright's `multipart` option accepts either a Buffer or a fs ReadStream-
 * like object — we use Buffer + name + mimeType for portability.
 */
'use strict';

const fs = require('fs');
const path = require('path');
const { MEDIA_FIXTURES } = require('../utils/api');

function readAsMultipartFile(filePath, mimeType = 'image/jpeg') {
  const buffer = fs.readFileSync(filePath);
  return { name: path.basename(filePath), mimeType, buffer };
}

function buildProductUpload({ sku, attribute = 'image', filePath = MEDIA_FIXTURES.jpegSmall } = {}) {
  return {
    sku,
    attribute,
    file: readAsMultipartFile(filePath),
  };
}

function buildCategoryUpload({ code, categoryField = 'file', filePath = MEDIA_FIXTURES.jpegSmall } = {}) {
  return {
    code,
    category_field: categoryField,
    file: readAsMultipartFile(filePath),
  };
}

module.exports = { buildProductUpload, buildCategoryUpload, readAsMultipartFile };
