<?php

namespace Webkul\ProductPassport\Listeners;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Webkul\Publication\Rules\Gs1CheckDigit;

/**
 * The `dpp_gtin` attribute a merchant fills on the product-edit page never
 * reaches `PublishPassportRequest`'s `gtin` rule (no UI posts that field), so
 * an invalid GTIN would otherwise flow into every published passport and GS1
 * link unchecked. This validates it on save, at the point the merchant can
 * still correct it.
 *
 * Registered on `catalog.product.update.before` only: product creation posts
 * just type/family/sku — every attribute value, `dpp_gtin` included, is
 * submitted through the edit page's update — so the create path carries none.
 */
class ValidateProductGtin
{
    /**
     * @throws ValidationException
     */
    public function handle(): void
    {
        $gtins = [];

        $values = (array) request()->input('values', []);

        array_walk_recursive(
            $values,
            function (mixed $value, int|string $key) use (&$gtins): void {
                if ($key === 'dpp_gtin' && $value !== null && $value !== '') {
                    $gtins[] = $value;
                }
            },
        );

        foreach ($gtins as $gtin) {
            $validator = Validator::make(
                ['dpp_gtin' => $gtin],
                ['dpp_gtin' => [new Gs1CheckDigit]],
                [],
                ['dpp_gtin' => trans('passport::app.attributes.dpp_gtin')],
            );

            if ($validator->fails()) {
                throw ValidationException::withMessages([
                    'values.common.dpp_gtin' => $validator->errors()->first('dpp_gtin'),
                ]);
            }
        }
    }
}
