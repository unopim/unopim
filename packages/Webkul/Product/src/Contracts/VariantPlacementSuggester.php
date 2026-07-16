<?php

namespace Webkul\Product\Contracts;

interface VariantPlacementSuggester
{
    /**
     * Suggest a level (common|sub_parent|variant) for each non-axis attribute.
     *
     * @param  array<int, array{code: string, type: string, is_unique: bool}>  $attributes
     * @param  array<int, string>  $axisCodes
     * @return array<string, string>
     */
    public function suggest(array $attributes, int $levels, array $axisCodes = []): array;
}
