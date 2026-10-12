/**
 * Schema barrel. Each export is a plain JSON Schema (draft-07-compatible
 * subset) suitable for `validateSchema(schema, body)`.
 *
 * Schemas are intentionally a permissive *contract* — they assert the keys
 * UnoPim guarantees, not every key it currently happens to return. Tightening
 * via `additionalProperties: false` would break under harmless field additions
 * on minor releases and is therefore avoided.
 */
'use strict';

module.exports = {
  oauthToken: require('./oauth-token.schema.json'),
  oauthError: require('./oauth-error.schema.json'),
  paginatedEnvelope: require('./paginated-envelope.schema.json'),
  successEnvelope: require('./success-envelope.schema.json'),
  errorEnvelope: require('./error-envelope.schema.json'),
  locale: require('./locale.schema.json'),
  currency: require('./currency.schema.json'),
  channel: require('./channel.schema.json'),
  category: require('./category.schema.json'),
  categoryField: require('./category-field.schema.json'),
  categoryFieldOption: require('./category-field-option.schema.json'),
  attribute: require('./attribute.schema.json'),
  attributeOption: require('./attribute-option.schema.json'),
  attributeGroup: require('./attribute-group.schema.json'),
  attributeFamily: require('./attribute-family.schema.json'),
  product: require('./product.schema.json'),
  configurableProduct: require('./configurable-product.schema.json'),
  mediaUpload: require('./media-upload.schema.json'),
};
