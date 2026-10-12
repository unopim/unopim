<?php

declare(strict_types=1);

namespace Webkul\AdminApi\Http\Requests\Catalog;

use Webkul\AdminApi\Http\Requests\ApiFormRequest;

/**
 * Shape rules shared by every variant structure write.
 *
 * Only the payload's own form belongs here; VariantStructureWriter validates it
 * against the family. The verbs differ in what they require, so each subclass
 * states its own rules.
 */
abstract class VariantStructureRequest extends ApiFormRequest
{
    /**
     * Reject keys outside the allowed set, so that a misspelt level is an error
     * rather than a silently discarded part of the payload.
     *
     * @param  array<int, string>  $allowed
     */
    protected function onlyKeys(array $allowed): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($allowed): void {
            if (! is_array($value)) {
                return;
            }

            $unknown = array_diff(array_keys($value), $allowed);

            if ($unknown !== []) {
                $fail(trans('validation.in', ['attribute' => implode(', ', $unknown)]));
            }
        };
    }
}
