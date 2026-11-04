<?php

use Webkul\Product\Models\Product;

function statusAuditsFor(Product $product): array
{
    return $product->audits()
        ->where('event', 'updated')
        ->get()
        ->filter(fn ($audit): bool => array_key_exists('status', $audit->new_values ?? []))
        ->values()
        ->all();
}

function enabledProductForStatusHistory(): Product
{
    $product = seedRequiredProductValues(Product::factory()->simple()->create(['status' => 1]));

    return $product->fresh();
}

describe('product status history', function (): void {
    beforeEach(function (): void {
        $this->loginAsAdmin();
    });

    it('does not record a status change when the product is saved with an unchanged status', function (): void {
        $product = enabledProductForStatusHistory();

        $this->put(route('admin.catalog.products.update', $product->id), [
            'sku'    => $product->sku,
            'status' => '1',
            'values' => $product->values,
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        expect(statusAuditsFor($product))->toBeEmpty();
    });

    it('records a status change when the product is disabled', function (): void {
        $product = enabledProductForStatusHistory();

        $this->put(route('admin.catalog.products.update', $product->id), [
            'sku'    => $product->sku,
            'status' => '0',
            'values' => $product->values,
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $audits = statusAuditsFor($product);

        expect($audits)->toHaveCount(1)
            ->and((bool) $audits[0]->old_values['status'])->toBeTrue()
            ->and((bool) $audits[0]->new_values['status'])->toBeFalse()
            ->and($product->fresh()->status)->toBeFalse();
    });

    it('treats an integer status matching the stored value as unchanged', function (): void {
        $product = enabledProductForStatusHistory();

        $product->status = 1;

        expect($product->isDirty('status'))->toBeFalse();
    });
});
