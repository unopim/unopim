<?php

namespace Webkul\ProductPassport\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Maps the frozen DPP payload array to a schema.org Product JSON-LD graph.
 * schema.org is the pragmatic carrier vocabulary while the ESPR/CIRPASS JSON
 * schemas are still settling; the mapping is centralised here so swapping the
 * context/type vocabulary later touches one file.
 *
 * @property array<string, mixed> $resource
 */
class PassportJsonLdResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $payload = $this->resource;

        return array_filter([
            '@context'           => 'https://schema.org',
            '@type'              => 'Product',
            'gtin'               => $payload['identifier']['gtin'] ?? null,
            'model'              => $payload['identifier']['model'] ?? null,
            'productID'          => $payload['meta']['uuid'] ?? null,
            'url'                => $payload['meta']['url'] ?? null,
            'manufacturer'       => array_filter([
                '@type' => 'Organization',
                'name'  => $payload['operator']['name'] ?? null,
            ], fn ($v): bool => $v !== null && $v !== ''),
            'additionalProperty' => array_map(
                fn (array $field): array => [
                    '@type' => 'PropertyValue',
                    'name'  => $field['label'],
                    'value' => $field['value'],
                ],
                $payload['sections'][0]['fields'] ?? [],
            ),
        ], fn ($v): bool => $v !== null && $v !== []);
    }
}
