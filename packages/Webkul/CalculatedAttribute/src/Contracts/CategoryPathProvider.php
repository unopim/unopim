<?php

namespace Webkul\CalculatedAttribute\Contracts;

/**
 * Answers category path questions for the formula functions.
 *
 * Kept as a contract so the expression layer never talks to Eloquent directly,
 * and so tests can supply a fixed tree without touching the database.
 */
interface CategoryPathProvider
{
    /**
     * The root-to-leaf path of the deepest category among the given codes,
     * excluding the technical root node.
     *
     * Passing a locale returns localised category names; passing null returns
     * category codes.
     *
     * @param  array<int, string>  $codes  category codes assigned to a product
     * @return array<int, string>
     */
    public function deepestPath(array $codes, ?string $locale = null): array;

    /**
     * The localised names of the given category codes, in the order given.
     *
     * @param  array<int, string>  $codes
     * @return array<int, string>
     */
    public function labels(array $codes, ?string $locale = null): array;
}
