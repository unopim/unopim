<?php

namespace Webkul\CalculatedAttribute\Observers;

use Illuminate\Database\Eloquent\Model;
use Webkul\Attribute\Models\Attribute;

/**
 * Decides what actually lands in the `formula` column.
 *
 * On the model rather than in a controller, because three write paths reach it
 * — the admin form, the REST create and the REST update — and a formula left
 * behind by a type change would keep being evaluated for an attribute that no
 * longer has one.
 *
 * An attribute of some other type is left alone entirely when it carries no
 * `formula` at all, so a save made before this package's migration has run does
 * not try to write a column that is not there yet.
 */
class AttributeFormula
{
    public function saving(Model $attribute): void
    {
        $isCalculated = $attribute->type === Attribute::CALCULATED_FIELD_TYPE;

        if (! $isCalculated && ! array_key_exists('formula', $attribute->getAttributes())) {
            return;
        }

        if (! $isCalculated) {
            $attribute->formula = null;

            return;
        }

        $formula = trim((string) $attribute->formula);

        $attribute->formula = $formula === '' ? null : $formula;
    }
}
