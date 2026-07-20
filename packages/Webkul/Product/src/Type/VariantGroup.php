<?php

namespace Webkul\Product\Type;

/**
 * Internal middle node in a 2-level variant tree
 * (configurable → variant_group → simple). Never created directly by a user;
 * materialized when adding a sub-parent group to a configurable product.
 */
class VariantGroup extends AbstractType {}
