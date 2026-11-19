<?php

use Illuminate\Support\Str;
use Webkul\Attribute\Models\Attribute;
use Webkul\CalculatedAttribute\Services\AttributeRegistry;
use Webkul\DataTransfer\Contracts\JobTrack as JobTrackContract;
use Webkul\DataTransfer\Helpers\Error;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\Attribute\Importer as AttributeImporter;

/**
 * Deleting an attribute a formula reads empties every column derived from it,
 * silently, wherever that column is used. Each delete path is asserted
 * separately because each one guards differently.
 *
 * @return array{0: Attribute, 1: Attribute}
 */
function calcDependencyPair(): array
{
    $source = Attribute::factory()->create([
        'code'              => 'src_'.Str::random(6),
        'type'              => 'text',
        'value_per_locale'  => 0,
        'value_per_channel' => 0,
    ]);

    $derived = Attribute::factory()->create([
        'code'              => 'calc_'.Str::random(6),
        'type'              => 'calculated',
        'formula'           => "concat('-', {$source->code}, sku)",
        'value_per_locale'  => 0,
        'value_per_channel' => 0,
    ]);

    app(AttributeRegistry::class)->flush();

    return [$source, $derived];
}

it('refuses to delete an attribute a formula reads, in the admin', function (): void {
    $this->loginAsAdmin();

    [$source, $derived] = calcDependencyPair();

    $response = $this->delete(route('admin.catalog.attributes.delete', $source->id))
        ->assertStatus(400);

    expect($response->json('message'))->toContain($derived->code);

    $this->assertDatabaseHas('attributes', ['id' => $source->id]);
});

it('refuses to delete an attribute a formula reads, through the api', function (): void {
    $headers = $this->getAuthenticationHeaders();

    [$source, $derived] = calcDependencyPair();

    $response = $this->withHeaders($headers)
        ->json('DELETE', route('admin.api.attributes.delete', $source->code))
        ->assertStatus(422);

    expect($response->json('message'))->toContain($derived->code);

    $this->assertDatabaseHas('attributes', ['id' => $source->id]);
});

it('skips an attribute a formula reads during a mass delete', function (): void {
    $this->loginAsAdmin();

    [$source] = calcDependencyPair();

    $unrelated = Attribute::factory()->create([
        'code' => 'free_'.Str::random(6),
        'type' => 'text',
    ]);

    $this->post(route('admin.catalog.attributes.mass_delete'), [
        'indices' => [$source->id, $unrelated->id],
    ]);

    $this->assertDatabaseHas('attributes', ['id' => $source->id]);
    $this->assertDatabaseMissing('attributes', ['id' => $unrelated->id]);
});

it('refuses to delete an attribute a formula reads, through an attribute import', function (): void {
    [$source, $derived] = calcDependencyPair();

    $import = Mockery::mock(JobTrackContract::class)->makePartial();
    $import->action = Import::ACTION_DELETE;

    $errors = app(Error::class);

    $importer = app(AttributeImporter::class);
    $importer->setErrorHelper($errors);
    $importer->setImport($import);

    (fn () => $this->attributeStorage->load([$source->code]))->call($importer);

    expect($importer->validateRow(['code' => $source->code], 1))->toBeFalse()
        ->and(json_encode($errors->getAllErrors()))->toContain($derived->code);

    $this->assertDatabaseHas('attributes', ['id' => $source->id]);
});

it('allows deleting an attribute no formula reads', function (): void {
    $this->loginAsAdmin();

    calcDependencyPair();

    $unrelated = Attribute::factory()->create([
        'code' => 'free_'.Str::random(6),
        'type' => 'text',
    ]);

    $this->delete(route('admin.catalog.attributes.delete', $unrelated->id))->assertOk();

    $this->assertDatabaseMissing('attributes', ['id' => $unrelated->id]);
});

it('does not count a code that only appears inside a string literal', function (): void {
    $this->loginAsAdmin();

    $source = Attribute::factory()->create([
        'code' => 'quoted_'.Str::random(6),
        'type' => 'text',
    ]);

    Attribute::factory()->create([
        'code'    => 'calc_'.Str::random(6),
        'type'    => 'calculated',
        'formula' => "concat('-', '{$source->code}', sku)",
    ]);

    app(AttributeRegistry::class)->flush();

    $this->delete(route('admin.catalog.attributes.delete', $source->id))->assertOk();

    $this->assertDatabaseMissing('attributes', ['id' => $source->id]);
});
