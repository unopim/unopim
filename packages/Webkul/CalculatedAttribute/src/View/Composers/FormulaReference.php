<?php

namespace Webkul\CalculatedAttribute\View\Composers;

use Illuminate\View\View;
use Webkul\Attribute\Models\Attribute;
use Webkul\CalculatedAttribute\Expression\FormulaFunctions;
use Webkul\CalculatedAttribute\Services\FormulaValidator;

/**
 * Supplies the formula editor's help panel: what can be called, what can be
 * read, and the attribute type the panel belongs to.
 *
 * The function list has to come from the provider rather than from a literal in
 * the template, or the help would drift away from what the sandbox actually
 * allows the first time a function is added.
 */
class FormulaReference
{
    public function __construct(
        private readonly FormulaFunctions $functions,
    ) {}

    public function compose(View $view): void
    {
        $names = $this->functions->names();

        sort($names);

        $view->with([
            'formulaFunctions' => $names,
            'formulaVariables' => FormulaValidator::SYSTEM_VARIABLES,
            'calculatedType'   => Attribute::CALCULATED_FIELD_TYPE,
        ]);
    }
}
