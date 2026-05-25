/**
 * Payload barrel.
 *
 * Each module exports `build*` factories so tests stay declarative:
 *   const payload = payloads.categories.build({ parent: 'root' });
 *
 * All factories accept a partial-override object so a spec only states the
 * fields it cares about (the rest fall back to valid defaults).
 */
'use strict';

module.exports = {
  categories: require('./categories.payloads'),
  categoryFields: require('./category-fields.payloads'),
  categoryFieldOptions: require('./category-field-options.payloads'),
  attributes: require('./attributes.payloads'),
  attributeOptions: require('./attribute-options.payloads'),
  attributeGroups: require('./attribute-groups.payloads'),
  attributeFamilies: require('./attribute-families.payloads'),
  products: require('./products.payloads'),
  configurableProducts: require('./configurable-products.payloads'),
  variants: require('./variants.payloads'),
  media: require('./media.payloads'),
};
