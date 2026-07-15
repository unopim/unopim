<?php

use Webkul\Category\Models\Category;
use Webkul\Core\Models\Channel;

use function Pest\Laravel\get;

/*
 * Regression guard for ChannelController now passing `$rootCategories` to the
 * create/edit views. The root-category `<select>` builds its options from
 * `$rootCategories->toArray()`; without the variable the view would throw a 500.
 *
 * ChannelTest already asserts the create/edit pages return 200, so this file does
 * NOT duplicate that. It adds the delta: the root-category option payload is
 * actually present on both pages.
 */

it('renders the channel create page with root category options present', function () {
    $this->loginAsAdmin();

    $root = Category::query()->whereNull('parent_id')->firstOrFail();

    $response = get(route('admin.settings.channels.create'));

    $response->assertOk();
    // The <select> is a component whose :options attribute embeds the JSON payload.
    $response->assertSee('name="root_category_id"', false);
    $response->assertSee($root->code, false);
});

it('renders the channel edit page with root category options present', function () {
    $this->loginAsAdmin();

    $channel = Channel::factory()->create();
    $root = Category::query()->whereNull('parent_id')->firstOrFail();

    $response = get(route('admin.settings.channels.edit', ['id' => $channel->id]));

    $response->assertOk();
    $response->assertSee('name="root_category_id"', false);
    $response->assertSee($root->code, false);
});
