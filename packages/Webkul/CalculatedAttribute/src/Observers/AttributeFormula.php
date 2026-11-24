<?php

namespace Webkul\CalculatedAttribute\Observers;

use Illuminate\Database\Eloquent\Model;
use Webkul\Attribute\Models\Attribute;

/**
 * Decides what actually lands in the `formula` column.
 *
 * The column is added by this package's migration, so it is deliberately not
 * fillable on the core model: a core install without the package, or with it
 * not yet migrated, must not try to write it. The submitted formula is taken
 * from the request here instead and assigned directly, which every write path
 * reaching the model shares — the admin form, the REST create and the REST
 * update. It has already been validated by the time the model saves.
 *
 * A formula left behind by a type change would keep being evaluated for an
 * attribute that no longer has one, so it is cleared. An attribute of some
 * other type that never had the column loaded is left alone entirely.
 */
class AttributeFormula
{
    public function saving(Model $attribute): void
    {
        $isCalculated = $attribute->type === Attribute::CALCULATED_FIELD_TYPE;

        if (! $isCalculated) {
            if (array_key_exists('formula', $attribute->getAttributes())) {
                $attribute->formula = null;
            }

            return;
        }

        $submitted = request()->exists('formula')
            ? request()->input('formula')
            : $attribute->getAttributes()['formula'] ?? null;

        $formula = trim(is_string($submitted) ? $submitted : '');

        $attribute->formula = $formula === '' ? null : $formula;
    }
}
