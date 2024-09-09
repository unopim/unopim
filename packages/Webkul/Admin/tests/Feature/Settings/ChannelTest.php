<?php

use Webkul\Category\Models\Category;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Currency;
use Webkul\Core\Models\Locale;

use function Pest\Laravel\get;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

it('should returns the Channel index page', function () {
    $this->loginAsAdmin();

    $response = get(route('admin.settings.channels.index'));

    $response->assertStatus(200)
        ->assertSeeText(trans('admin::app.settings.channels.index.title'));
});

it('should returns the Channel edit page', function () {
    $this->loginAsAdmin();

    $demoChannel = Channel::factory()->create();

    $response = get(route('admin.settings.channels.edit', ['id' => $demoChannel->id]));

    $response->assertStatus(200)
        ->assertSeeText(trans('admin::app.settings.channels.edit.title'));
});

it('should return the channel datagrid', function () {
    $this->loginAsAdmin();
    Channel::factory()->create();

    $response = $this->withHeaders([
        'X-Requested-With' => 'XMLHttpRequest', ])->json('GET', route('admin.settings.channels.index'));

    $data = $response->json();

    $this->assertArrayHasKey('records', $data);
    $this->assertArrayHasKey('columns', $data);
    $this->assertNotEmpty($data['records']);

    $this->assertDatabaseHas($this->getFullTableName(Channel::class), [
        'id'   => $data['records'][0]['id'],
        'code' => $data['records'][0]['code'],
    ]);
});

it('should returns the Channel create page', function () {
    $this->loginAsAdmin();

    $response = get(route('admin.settings.channels.create'));
    $response->assertStatus(200)
        ->assertSeeTextInOrder([
            trans('admin::app.settings.channels.index.title'),
        ]);
});

it('should create the Channel', function () {
    $this->loginAsAdmin();
    $demoChannel = Channel::factory()->create();

    $response = postJson(route('admin.settings.channels.store'), [
        'code'             => 'TestChannel',
        'root_category_id' => $demoChannel->root_category_id,
        'locales'          => implode(',', $demoChannel->locales->pluck('id')->toArray()),
        'currencies'       => implode(',', $demoChannel->currencies->pluck('id')->toArray()),
    ]);

    $this->assertDatabaseHas($this->getFullTableName(Channel::class), [
        'code' => 'TestChannel',
    ]);
});

it('should update the Channel', function () {
    $this->loginAsAdmin();

    $category = Category::factory()->create(['parent_id' => null]);
    $locale = Locale::factory()->create();
    $currency = Currency::factory()->create();

    $demoChannel = Channel::factory()->create([
        'root_category_id' => $category->id,
    ]);

    $response = putJson(route('admin.settings.channels.update', ['id' => $demoChannel->id]), [
        'id'               => $demoChannel->id,
        'code'             => $demoChannel->code,
        'root_category_id' => $demoChannel->root_category_id,
        'locales'          => $locale->id,
        'currencies'       => $currency->id,
    ]);

    $response->assertStatus(302);

    $this->assertDatabaseHas('channel_currencies', [
        'channel_id'  => $demoChannel->id,
        'currency_id' => $currency->id,
    ]);

    $this->assertDatabaseHas('channel_locales', [
        'channel_id' => $demoChannel->id,
        'locale_id'  => $locale->id,
    ]);
});

it('should give validation message for the Channel create', function () {
    $this->loginAsAdmin();

    postJson(route('admin.settings.channels.store'), [
        'code'             => '',
        'root_category_id' => '',
        'locales'          => '',
        'currencies'       => '',
    ])
        ->assertJsonValidationErrorFor('code')
        ->assertJsonValidationErrorFor('root_category_id')
        ->assertJsonValidationErrorFor('locales')
        ->assertJsonValidationErrorFor('currencies');

});

it('should give validation message for the Channel update', function () {
    $this->loginAsAdmin();

    $demoChannel = Channel::factory()->create();

    putJson(route('admin.settings.channels.update', ['id' => $demoChannel->id]), [
        'id'               => $demoChannel->id,
        'code'             => $demoChannel->code,
        'root_category_id' => '',
        'locales'          => '',
        'currencies'       => '',
    ])
        ->assertJsonValidationErrorFor('root_category_id')
        ->assertJsonValidationErrorFor('locales')
        ->assertJsonValidationErrorFor('currencies');
});

it('should update translations', function () {
    $this->loginAsAdmin();

    $demoChannel = Channel::factory()->create();

    $locales = Locale::inRandomOrder()->limit(3)->where('status', 1)->get()->pluck('code')->toArray();

    $data = [];
    foreach ($locales as $locale) {
        $data[$locale] = ['name' => $locale.fake()->word()];
    }

    $data['id'] = $demoChannel->id;
    $data['code'] = $demoChannel->code;
    $data['root_category_id'] = $demoChannel->root_category_id;
    $data['locales'] = implode(',', $demoChannel->locales->pluck('id')->toArray());
    $data['currencies'] = implode(',', $demoChannel->currencies->pluck('id')->toArray());

    $response = putJson(route('admin.settings.channels.update', ['id' => $demoChannel->id]), $data);

    $response->assertSessionHas('success');
});

it('should give validation error when code is not unique', function () {
    $this->loginAsAdmin();

    $demoChannel = Channel::factory()->create();

    postJson(route('admin.settings.channels.store', ['id' => $demoChannel->id]), [
        'code'             => $demoChannel->code,
        'root_category_id' => $demoChannel->root_category_id,
        'locales'          => implode(',', $demoChannel->locales->pluck('id')->toArray()),
        'currencies'       => implode(',', $demoChannel->currencies->pluck('id')->toArray()),
    ])
        ->assertJsonValidationErrorFor('code');
});

it('should not update the code of channel', function () {
    $this->loginAsAdmin();

    $demoChannel = Channel::factory()->create();

    putJson(route('admin.settings.channels.update', ['id' => $demoChannel->id]), [
        'id'               => $demoChannel->id,
        'code'             => 'TestChannel',
        'root_category_id' => $demoChannel->root_category_id,
        'locales'          => implode(',', $demoChannel->locales->pluck('id')->toArray()),
        'currencies'       => implode(',', $demoChannel->currencies->pluck('id')->toArray()),
    ]);

    $this->assertDatabaseHas($this->getFullTableName(Channel::class), [
        'id'   => $demoChannel->id,
        'code' => $demoChannel->code,
    ]);
});

it('should delete the channel successfully', function () {
    $this->loginAsAdmin();

    $demoChannel = Channel::factory()->create();

    $this->delete(route('admin.settings.channels.delete', ['id' => $demoChannel->id]));

    $this->assertDatabaseMissing($this->getFullTableName(Channel::class), [
        'id' => $demoChannel->id,
    ]);
});

it('should not delete the default channel', function () {
    $this->loginAsAdmin();

    $this->delete(route('admin.settings.channels.delete', ['id' => 1]));

    $this->assertDatabaseHas($this->getFullTableName(Channel::class), [
        'code' => 'default',
    ]);
});

it('should not delete the last channel', function () {
    $this->loginAsAdmin();

    $this->delete(route('admin.settings.channels.delete', ['id' => 1]));

    $this->assertDatabaseHas($this->getFullTableName(Channel::class), [
        'code' => 'default',
    ]);
});
