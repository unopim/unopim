<?php

namespace Webkul\ProductPassport\Services;

use RuntimeException;
use Webkul\Product\Models\Product;
use Webkul\Publication\Contracts\PayloadBuilder;
use Webkul\Publication\DataTransferObjects\PublicationContext;

/**
 * Placeholder for Task 10, which builds the real DPP payload (identity,
 * material composition, operator info, certificates) from the product's
 * `dpp` attribute group. Registered now so the `dpp` publication type has a
 * resolvable builder class; not wired to any publishing flow in this task.
 */
class PassportPayloadBuilder implements PayloadBuilder
{
    /**
     * @return array{meta?: array<string, mixed>, ...}
     */
    public function build(Product $product, PublicationContext $context): array
    {
        throw new RuntimeException('PassportPayloadBuilder::build() is not implemented until Task 10.');
    }
}
